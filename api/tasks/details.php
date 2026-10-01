<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/access.php';
$userId = currentUserId();
$taskId = (int) ($_GET['id'] ?? 0);
if ($taskId < 1) {
    jsonResponse(['success' => false, 'message' => 'Task ID is required.'], 422);
}

$task = requireTask($taskId, $userId);

$comments = database()->prepare('SELECT c.id, c.comment, c.created_at, u.id AS user_id, u.name AS user_name FROM task_comments c JOIN users u ON u.id = c.user_id WHERE c.task_id = ? ORDER BY c.created_at ASC');
$comments->execute([$taskId]);
$checklist = database()->prepare('SELECT id, item_text, is_completed, created_at FROM checklist_items WHERE task_id = ? ORDER BY created_at, id');
$checklist->execute([$taskId]);

jsonResponse(['success' => true, 'task' => $task, 'comments' => $comments->fetchAll(), 'checklist' => $checklist->fetchAll()]);
