<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
requireMethod('POST');
$admin = requireAdmin();
$data = requestData();

$userId = (int) ($data['user_id'] ?? 0);
$role = cleanString($data['role'] ?? '', 20);
$status = cleanString($data['status'] ?? '', 20);

if ($userId < 1 || !in_array($role, ['admin', 'manager', 'member', 'guest'], true) || !in_array($status, ['active', 'disabled'], true)) {
    jsonResponse(['success' => false, 'message' => 'Invalid user, role, or status.'], 422);
}
if ($userId === (int) $admin['id'] && ($role !== 'admin' || $status !== 'active')) {
    jsonResponse(['success' => false, 'message' => 'You cannot remove your own administrator access.'], 422);
}

$pdo = database();
$oldStmt = $pdo->prepare('SELECT name, role, status FROM users WHERE id = ?');
$oldStmt->execute([$userId]);
$old = $oldStmt->fetch();
if (!$old) {
    jsonResponse(['success' => false, 'message' => 'User not found.'], 404);
}

$update = $pdo->prepare('UPDATE users SET role = ?, status = ? WHERE id = ?');
$update->execute([$role, $status, $userId]);
writeAuditLog((int) $admin['id'], 'Member access updated', $old['name'] . ": {$old['role']}/{$old['status']} changed to $role/$status.");

jsonResponse(['success' => true, 'message' => 'Member access updated.']);
