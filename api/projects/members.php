<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/access.php';
require_once __DIR__ . '/../../config/invitation_mail.php';
$userId = currentUserId();
$data = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' ? requestData() : $_GET;
$projectId = (int) ($data['project_id'] ?? 0);
$pdo = database();
$record = requireProjectManager($projectId, $userId);
if ($record['status'] === 'archived') jsonResponse(['success' => false, 'message' => 'Restore the project before inviting people.'], 409);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    $stmt = $pdo->prepare("SELECT u.id, u.name, u.email, u.role FROM project_members pm JOIN users u ON u.id = pm.user_id WHERE pm.project_id = ? AND u.status = 'active' ORDER BY u.name");
    $stmt->execute([$projectId]);
    $members = $stmt->fetchAll();
    $invites = $pdo->prepare("SELECT id, email, delivery_status, expires_at, expires_at <= NOW() AS expired FROM project_invitations WHERE project_id = ? AND status = 'pending' ORDER BY created_at DESC");
    $invites->execute([$projectId]);
    jsonResponse(['success' => true, 'members' => $members, 'invitations' => $invites->fetchAll()]);
}

requireMethod('POST');
$action = cleanString($data['action'] ?? 'add', 10);
if ($action === 'add' || $action === 'resend') {
    $email = strtolower(cleanString($data['email'] ?? '', 190));
    if ($action === 'resend') {
        $findInvite = $pdo->prepare("SELECT email FROM project_invitations WHERE id = ? AND project_id = ? AND status = 'pending'");
        $findInvite->execute([(int) ($data['invitation_id'] ?? 0), $projectId]);
        $email = (string) $findInvite->fetchColumn();
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonResponse(['success' => false, 'message' => 'Enter a valid teammate email.'], 422);
    $find = $pdo->prepare("SELECT id, name, status FROM users WHERE email = ? LIMIT 1");
    $find->execute([$email]);
    $member = $find->fetch();
    if ($member && $member['status'] !== 'active') jsonResponse(['success' => false, 'message' => 'This account is disabled. Ask the administrator to reactivate it first.'], 422);
    if ($member && ((int) $member['id'] === $userId || (int) $member['id'] === (int) $record['owner_id'])) jsonResponse(['success' => false, 'message' => 'This person is already on this project.'], 422);
    if ($member) {
        $existing = $pdo->prepare('SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ?');
        $existing->execute([$projectId, (int) $member['id']]);
        if ($existing->fetchColumn()) jsonResponse(['success' => false, 'message' => 'This person is already on the project team.'], 409);
    }
    $recent = $pdo->prepare("SELECT 1 FROM project_invitations WHERE project_id = ? AND email = ? AND status = 'pending' AND updated_at > DATE_SUB(NOW(), INTERVAL 60 SECOND)");
    $recent->execute([$projectId, $email]);
    if ($recent->fetchColumn()) jsonResponse(['success' => false, 'message' => 'Please wait one minute before sending another invitation to this person.'], 429);
    $token = bin2hex(random_bytes(32));
    $url = invitationUrl($token);
    $insert = $pdo->prepare("INSERT INTO project_invitations (project_id, email, token_hash, invited_by, expires_at) VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY)) ON DUPLICATE KEY UPDATE token_hash = VALUES(token_hash), invited_by = VALUES(invited_by), expires_at = VALUES(expires_at), status = 'pending', delivery_status = 'not_configured', accepted_by = NULL, accepted_at = NULL, updated_at = NOW()");
    $insert->execute([$projectId, $email, hash('sha256', $token), $userId]);
    $delivery = sendProjectInvitation($email, $record['name'], $url);
    $pdo->prepare('UPDATE project_invitations SET delivery_status = ? WHERE project_id = ? AND email = ? AND token_hash = ?')->execute([$delivery, $projectId, $email, hash('sha256', $token)]);
    writeAuditLog($userId, 'Project invitation created', $email . ' invited to ' . $record['name'] . '.');
    jsonResponse(['success' => true, 'message' => $delivery === 'sent' ? 'Invitation accepted by the email provider. Ask the recipient to check their inbox or spam folder.' : ($delivery === 'failed' ? 'Invitation created, but email delivery failed. Copy the link or check SMTP settings.' : 'Invitation created. Configure SMTP to send email, or copy and share this link.'), 'invitation_url' => $url, 'delivery_status' => $delivery]);
}
if ($action === 'cancel') {
    $cancel = $pdo->prepare("UPDATE project_invitations SET status = 'cancelled' WHERE id = ? AND project_id = ? AND status = 'pending'");
    $cancel->execute([(int) ($data['invitation_id'] ?? 0), $projectId]);
    if (!$cancel->rowCount()) jsonResponse(['success' => false, 'message' => 'Pending invitation not found.'], 404);
    jsonResponse(['success' => true, 'message' => 'Invitation cancelled.']);
}
if ($action === 'remove') {
    $pdo->beginTransaction();
    $record = requireProjectManager($projectId, $userId, true);
    removeProjectMembership($record, (int) ($data['user_id'] ?? 0), $userId);
    $pdo->commit();
    jsonResponse(['success' => true, 'message' => 'Teammate removed.']);
}
jsonResponse(['success' => false, 'message' => 'Unknown team action.'], 422);
