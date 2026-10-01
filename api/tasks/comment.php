<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/access.php';
requireMethod('POST');
$userId = currentUserId();
requireWritableAccount($userId);
$data = requestData();
$taskId = (int) ($data['task_id'] ?? 0);
$comment = cleanString($data['comment'] ?? '', 2000);
if ($taskId < 1 || $comment === '') {
    jsonResponse(['success' => false, 'message' => 'Task and comment are required.'], 422);
}

$task = requireTask($taskId, $userId, false);

$stmt = database()->prepare('INSERT INTO task_comments (task_id, user_id, comment) VALUES (?, ?, ?)');
$stmt->execute([$taskId, $userId, $comment]);
foreach (array_unique(array_merge($task['assignee_ids'], [(int) $task['owner_id']])) as $recipient) {
    if ($recipient > 0 && $recipient !== $userId) createNotification($recipient, 'A comment was added to ' . $task['title'] . '.', $taskId, 'tasks');
}
writeAuditLog($userId, 'Task comment added', 'A comment was added to ' . $task['title'] . '.');
jsonResponse(['success' => true, 'message' => 'Comment added.'], 201);
