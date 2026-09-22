<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
requireMethod('POST');
$userId = currentUserId();
$data = requestData();
$taskId = (int) ($data['task_id'] ?? 0);
$comment = cleanString($data['comment'] ?? '', 2000);
if ($taskId < 1 || $comment === '') {
    jsonResponse(['success' => false, 'message' => 'Task and comment are required.'], 422);
}

$access = database()->prepare('SELECT t.title, t.assignee_id, t.created_by, p.owner_id FROM tasks t JOIN projects p ON p.id = t.project_id WHERE t.id = ? AND (p.owner_id = ? OR t.assignee_id = ? OR t.created_by = ?)');
$access->execute([$taskId, $userId, $userId, $userId]);
$task = $access->fetch();
if (!$task) {
    jsonResponse(['success' => false, 'message' => 'Task not found or access denied.'], 403);
}

$stmt = database()->prepare('INSERT INTO task_comments (task_id, user_id, comment) VALUES (?, ?, ?)');
$stmt->execute([$taskId, $userId, $comment]);
foreach (array_unique([(int) $task['assignee_id'], (int) $task['created_by'], (int) $task['owner_id']]) as $recipient) {
    if ($recipient > 0 && $recipient !== $userId) createNotification($recipient, 'A comment was added to ' . $task['title'] . '.', $taskId, 'tasks');
}
writeAuditLog($userId, 'Task comment added', 'A comment was added to ' . $task['title'] . '.');
jsonResponse(['success' => true, 'message' => 'Comment added.'], 201);
