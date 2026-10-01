<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
requireMethod('POST');

$data = requestData();
$email = strtolower(cleanString($data['email'] ?? '', 190));
$password = (string) ($data['password'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    jsonResponse(['success' => false, 'message' => 'Enter a valid email and password.'], 422);
}

try {
    $stmt = database()->prepare('SELECT id, name, email, password_hash, role, status FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        jsonResponse(['success' => false, 'message' => 'Email or password is incorrect.'], 401);
    }
    if ($user['status'] !== 'active') {
        jsonResponse(['success' => false, 'message' => 'This account has been disabled by an administrator.'], 403);
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];

    $updateLogin = database()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
    $updateLogin->execute([(int) $user['id']]);
    writeAuditLog((int) $user['id'], 'User signed in', $user['email'] . ' signed in successfully.');

    unset($user['password_hash']);
    unset($user['status']);
    jsonResponse(['success' => true, 'message' => 'Signed in successfully.', 'user' => $user, 'csrf_token' => $_SESSION['csrf_token']]);
} catch (PDOException $exception) {
    jsonResponse(['success' => false, 'message' => 'Database is not installed. Open install.php first.'], 503);
}
