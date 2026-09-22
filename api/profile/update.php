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
    if ($newPassword !== '' && !password_verify($currentPassword, $user['password_hash'])) {
        jsonResponse(['success' => false, 'message' => 'Current password is incorrect.'], 422);
    }

    $duplicate = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
    $duplicate->execute([$email, $userId]);
    if ($duplicate->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Another account already uses this email.'], 409);
    }

    if ($newPassword !== '') {
        $update = $pdo->prepare('UPDATE users SET name = ?, email = ?, password_hash = ? WHERE id = ?');
        $update->execute([$name, $email, password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
    } else {
        $update = $pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
        $update->execute([$name, $email, $userId]);
    }
    writeAuditLog($userId, 'Profile updated', $newPassword !== '' ? 'Profile and password were updated.' : 'Profile name or email was updated.');
    jsonResponse(['success' => true, 'message' => 'Profile updated.', 'user' => ['id' => $userId, 'name' => $name, 'email' => $email, 'role' => $user['role']]]);
} catch (PDOException $exception) {
    error_log('TaskFlow profile update error: ' . $exception->getMessage());
    jsonResponse(['success' => false, 'message' => 'Profile could not be updated.'], 500);
}
