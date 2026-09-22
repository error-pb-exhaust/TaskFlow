<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
requireMethod('POST');
$userId = currentUserId();
$data = requestData();
$id = (int) ($data['id'] ?? 0);

if ($id > 0) {
    $stmt = database()->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $userId]);
} else {
    $stmt = database()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
    $stmt->execute([$userId]);
}
jsonResponse(['success' => true, 'message' => 'Notifications marked as read.']);
