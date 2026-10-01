<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/bootstrap.php';
requireMethod('POST');
$data = requestData();
$token = (string) ($data['token'] ?? '');
if (!preg_match('/^[a-f0-9]{64}$/', $token)) jsonResponse(['success' => false, 'message' => 'This invitation link is invalid.'], 404);
$pdo = database();
$action = $data['action'] ?? 'preview';
if (!in_array($action, ['preview', 'accept'], true)) jsonResponse(['success' => false, 'message' => 'Invalid invitation action.'], 422);
$userId = $action === 'accept' ? currentUserId() : null;
try {
    if ($action === 'accept') $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT i.*, i.expires_at <= NOW() AS expired, p.name AS project_name, p.status AS project_status, p.owner_id, u.name AS manager_name, u.status AS manager_status FROM project_invitations i JOIN projects p ON p.id = i.project_id JOIN users u ON u.id = p.owner_id WHERE i.token_hash = ?" . ($action === 'accept' ? ' FOR UPDATE' : ''));
    $stmt->execute([hash('sha256', $token)]);
    $invitation = $stmt->fetch();
    if (!$invitation || $invitation['status'] !== 'pending' || (int) $invitation['expired'] === 1 || $invitation['project_status'] === 'archived' || $invitation['manager_status'] !== 'active') {
        if ($pdo->inTransaction()) $pdo->rollBack();
        jsonResponse(['success' => false, 'message' => 'This invitation has expired, was cancelled, or was already accepted. Ask the manager for a new link.'], 410);
    }
    if ($action === 'preview') jsonResponse(['success' => true, 'invitation' => ['project_name' => $invitation['project_name'], 'manager_name' => $invitation['manager_name'], 'email' => $invitation['email'], 'expires_at' => $invitation['expires_at']]]);
    $user = $pdo->prepare("SELECT email FROM users WHERE id = ? AND status = 'active' FOR UPDATE");
    $user->execute([$userId]);
    $email = $user->fetchColumn();
    if (!$email || strtolower((string) $email) !== strtolower($invitation['email'])) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'message' => 'Sign in with the email address shown on this invitation.'], 403);
    }
    $pdo->prepare('INSERT IGNORE INTO project_members (project_id, user_id) VALUES (?, ?)')->execute([(int) $invitation['project_id'], $userId]);
    $pdo->prepare("UPDATE project_invitations SET status = 'accepted', accepted_by = ?, accepted_at = NOW() WHERE id = ?")->execute([$userId, (int) $invitation['id']]);
    createNotification((int) $invitation['owner_id'], $email . ' accepted the invitation to ' . $invitation['project_name'] . '.', null, 'team');
    writeAuditLog($userId, 'Project invitation accepted', 'Joined ' . $invitation['project_name'] . '.');
    $pdo->commit();
    jsonResponse(['success' => true, 'message' => 'You have joined the project. The manager can now assign work to you.']);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('TaskFlow invite error: ' . $exception->getMessage());
    jsonResponse(['success' => false, 'message' => 'Invitation could not be processed. Please try again.'], 500);
}
