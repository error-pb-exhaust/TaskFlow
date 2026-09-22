<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
requireMethod('POST');
$admin = requireAdmin();
$data = requestData();

$name = cleanString($data['name'] ?? '', 100);
$email = strtolower(cleanString($data['email'] ?? '', 190));
$role = cleanString($data['role'] ?? 'member', 20);

if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['success' => false, 'message' => 'Enter a valid name and email address.'], 422);
}
if (!in_array($role, ['admin', 'manager', 'member', 'guest'], true)) {
    jsonResponse(['success' => false, 'message' => 'Invalid role.'], 422);
}

$pdo = database();
$pdo->exec("UPDATE invitations SET status = 'expired' WHERE status = 'pending' AND expires_at <= NOW()");
$existing = $pdo->prepare("SELECT email FROM users WHERE email = ? UNION SELECT email FROM invitations WHERE email = ? AND status = 'pending'");
$existing->execute([$email, $email]);
if ($existing->fetch()) {
    jsonResponse(['success' => false, 'message' => 'This email is already registered or invited.'], 409);
}

$stmt = $pdo->prepare("INSERT INTO invitations (name, email, role, invited_by, expires_at) VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY))");
$stmt->execute([$name, $email, $role, (int) $admin['id']]);
writeAuditLog((int) $admin['id'], 'Member invited', "$name ($email) invited as $role.");

jsonResponse(['success' => true, 'message' => 'Invitation created.', 'invitation_id' => (int) $pdo->lastInsertId()], 201);
