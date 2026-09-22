<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
$userId = currentUserId();

try {
    $stmt = database()->prepare('SELECT id, message, link_view, related_task_id, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 30');
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll();
    $unread = count(array_filter($notifications, static fn(array $item): bool => !(bool) $item['is_read']));
    jsonResponse(['success' => true, 'notifications' => $notifications, 'unread' => $unread]);
} catch (PDOException $exception) {
    error_log('TaskFlow notification list error: ' . $exception->getMessage());
    jsonResponse(['success' => false, 'message' => 'Notifications could not be loaded. Run install.php once.'], 503);
}
