<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
$userId = currentUserId();
$taskId = (int) ($_GET['id'] ?? 0);
if ($taskId < 1) {
    jsonResponse(['success' => false, 'message' => 'Task ID is required.'], 422);
}

$taskStmt = database()->prepare('SELECT t.id, t.title, t.description, t.status, t.priority, t.due_date, t.created_at, t.updated_at, t.assignee_id, p.id AS project_id, p.name AS project_name, p.owner_id, a.name AS assignee_name, c.name AS creator_name FROM tasks t JOIN projects p ON p.id = t.project_id LEFT JOIN users a ON a.id = t.assignee_id JOIN users c ON c.id = t.created_by WHERE t.id = ? AND (p.owner_id = ? OR t.assignee_id = ? OR t.created_by = ?) LIMIT 1');
$taskStmt->execute([$taskId, $userId, $userId, $userId]);
$task = $taskStmt->fetch();
if (!$task) {
    jsonResponse(['success' => false, 'message' => 'Task not found or access denied.'], 404);
}

$comments = database()->prepare('SELECT c.id, c.comment, c.created_at, u.id AS user_id, u.name AS user_name FROM task_comments c JOIN users u ON u.id = c.user_id WHERE c.task_id = ? ORDER BY c.created_at ASC');
$comments->execute([$taskId]);
$checklist = database()->prepare('SELECT id, item_text, is_completed, created_at FROM checklist_items WHERE task_id = ? ORDER BY created_at, id');
$checklist->execute([$taskId]);

jsonResponse(['success' => true, 'task' => $task, 'comments' => $comments->fetchAll(), 'checklist' => $checklist->fetchAll()]);
