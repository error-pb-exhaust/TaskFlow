<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
requireMethod('POST');
$userId = currentUserId();
$data = requestData();

$id = (int) ($data['id'] ?? 0);
$title = cleanString($data['title'] ?? '', 255);
$status = cleanString($data['status'] ?? '', 20);
$priority = cleanString($data['priority'] ?? '', 10);
$dueDate = cleanString($data['due_date'] ?? '', 10) ?: null;
$description = cleanString($data['description'] ?? '', 10000);

if ($id < 1 || $title === '') {
    jsonResponse(['success' => false, 'message' => 'Task ID and title are required.'], 422);
}
if (!in_array($status, ['backlog', 'todo', 'progress', 'review', 'done'], true)) {
    jsonResponse(['success' => false, 'message' => 'Invalid task status.'], 422);
}
if (!in_array($priority, ['low', 'medium', 'high'], true)) {
    jsonResponse(['success' => false, 'message' => 'Invalid task priority.'], 422);
}

$pdo = database();
$currentStmt = $pdo->prepare('SELECT t.assignee_id, p.owner_id FROM tasks t JOIN projects p ON p.id = t.project_id WHERE t.id = ? AND (p.owner_id = ? OR t.assignee_id = ? OR t.created_by = ?)');
$currentStmt->execute([$id, $userId, $userId, $userId]);
$current = $currentStmt->fetch();
if (!$current) {
    jsonResponse(['success' => false, 'message' => 'Task not found or permission denied.'], 403);
}

$assigneeId = array_key_exists('assignee_id', $data) ? (int) $data['assignee_id'] : (int) $current['assignee_id'];
if ($assigneeId < 1) $assigneeId = (int) $current['assignee_id'];
if ($assigneeId !== (int) $current['assignee_id'] && (int) $current['owner_id'] !== $userId) {
    $roleStmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
    $roleStmt->execute([$userId]);
    if ($roleStmt->fetchColumn() !== 'admin') {
        jsonResponse(['success' => false, 'message' => 'Only the project owner or an administrator can reassign this task.'], 403);
    }
}
$assigneeStmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND status = 'active'");
$assigneeStmt->execute([$assigneeId]);
if (!$assigneeStmt->fetchColumn()) {
    jsonResponse(['success' => false, 'message' => 'Select an active assignee.'], 422);
}

$stmt = $pdo->prepare('UPDATE tasks SET title = ?, status = ?, priority = ?, due_date = ?, description = ?, assignee_id = ? WHERE id = ?');
$stmt->execute([$title, $status, $priority, $dueDate, $description, $assigneeId, $id]);

if ($stmt->rowCount()) {
    writeAuditLog($userId, 'Task updated', "$title was updated to $status status.");
    if ($assigneeId !== (int) $current['assignee_id'] && $assigneeId !== $userId) {
        createNotification($assigneeId, "You were assigned to $title.", $id, 'tasks');
    }
}

jsonResponse(['success' => true, 'message' => $stmt->rowCount() ? 'Task updated.' : 'No task was changed.']);
