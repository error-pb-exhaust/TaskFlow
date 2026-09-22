<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
requireMethod('POST');

if (!empty($_SESSION['user_id'])) {
    writeAuditLog((int) $_SESSION['user_id'], 'User signed out', 'The user ended the current session.');
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

jsonResponse(['success' => true, 'message' => 'Signed out.']);
