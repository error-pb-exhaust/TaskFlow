<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
requireMethod('POST');
$userId = currentUserId();
$data = requestData();

$name = cleanString($data['name'] ?? '', 100);
$email = strtolower(cleanString($data['email'] ?? '', 190));
$currentPassword = (string) ($data['current_password'] ?? '');
$newPassword = (string) ($data['new_password'] ?? '');
$accountType = cleanString($data['account_type'] ?? '', 20);

if (mb_strlen($name) < 2) {
    jsonResponse(['success' => false, 'message' => 'Name must contain at least 2 characters.'], 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['success' => false, 'message' => 'Enter a valid email address.'], 422);
}
if ($newPassword !== '' && strlen($newPassword) < 8) {
    jsonResponse(['success' => false, 'message' => 'The new password must contain at least 8 characters.'], 422);
}

try {
    $pdo = database();
    $stmt = $pdo->prepare('SELECT password_hash, role FROM users WHERE id = ? AND status = \'active\'');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'Account not found.'], 404);
    }
    if ($accountType !== '' && !in_array($accountType, ['manager', 'member'], true)) {
        jsonResponse(['success' => false, 'message' => 'Choose Manager or Team Member.'], 422);
    }
    $role = in_array($user['role'], ['admin', 'guest'], true) ? $user['role'] : ($accountType ?: $user['role']);
    if ($user['role'] === 'manager' && $role === 'member') {
        $owned = $pdo->prepare('SELECT COUNT(*) FROM projects WHERE owner_id = ?');
        $owned->execute([$userId]);
        if ((int) $owned->fetchColumn() > 0) {
            jsonResponse(['success' => false, 'message' => 'You own projects. Keep the Manager role to manage them.'], 409);
        }
    }
    if ($newPassword !== '' && !password_verify($currentPassword, $user['password_hash'])) {
        jsonResponse(['success' => false, 'message' => 'Current password is incorrect.'], 422);
    }

    $duplicate = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
    $duplicate->execute([$email, $userId]);
    if ($duplicate->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Another account already uses this email.'], 409);
    }

    if ($newPassword !== '') {
        $update = $pdo->prepare('UPDATE users SET name = ?, email = ?, role = ?, password_hash = ? WHERE id = ?');
        $update->execute([$name, $email, $role, password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
    } else {
        $update = $pdo->prepare('UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?');
        $update->execute([$name, $email, $role, $userId]);
    }
    writeAuditLog($userId, 'Profile updated', $newPassword !== '' ? 'Profile and password were updated.' : 'Profile name or email was updated.');
    jsonResponse(['success' => true, 'message' => 'Profile updated.', 'user' => ['id' => $userId, 'name' => $name, 'email' => $email, 'role' => $role]]);
} catch (PDOException $exception) {
    error_log('TaskFlow profile update error: ' . $exception->getMessage());
    jsonResponse(['success' => false, 'message' => 'Profile could not be updated.'], 500);
}
