<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

if (empty($_SESSION['user_id'])) {
    jsonResponse(['success' => true, 'authenticated' => false]);
}

try {
    $stmt = database()->prepare("SELECT id, name, email, role FROM users WHERE id = ? AND status = 'active'");
    $stmt->execute([(int) $_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user) {
        $_SESSION = [];
        jsonResponse(['success' => true, 'authenticated' => false]);
    }
    jsonResponse(['success' => true, 'authenticated' => true, 'user' => $user]);
} catch (PDOException $exception) {
    jsonResponse(['success' => false, 'message' => 'Database is not installed. Open install.php first.'], 503);
}
