<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
$userId = currentUserId();

$search = cleanString($_GET['search'] ?? '', 100);
$status = cleanString($_GET['status'] ?? '', 20);
$allowedStatuses = ['backlog', 'todo', 'progress', 'review', 'done'];

$sql = 'SELECT t.id, t.title, t.status, t.priority, t.due_date, t.description, t.assignee_id, t.created_at, t.updated_at,
               p.id AS project_id, p.name AS project_name,
               u.name AS assignee_name
        FROM tasks t
        JOIN projects p ON p.id = t.project_id
        LEFT JOIN users u ON u.id = t.assignee_id
        WHERE (p.owner_id = ? OR t.assignee_id = ?)';
$params = [$userId, $userId];

if ($search !== '') {
    $like = '%' . $search . '%';
    $sql .= ' AND (t.title LIKE ? OR t.description LIKE ? OR p.name LIKE ?)';
    array_push($params, $like, $like, $like);
}
if (in_array($status, $allowedStatuses, true)) {
    $sql .= ' AND t.status = ?';
    $params[] = $status;
}
$sql .= ' ORDER BY FIELD(t.status, "progress", "review", "todo", "backlog", "done"), t.due_date IS NULL, t.due_date, t.created_at DESC';

try {
    $stmt = database()->prepare($sql);
    $stmt->execute($params);
    jsonResponse(['success' => true, 'tasks' => $stmt->fetchAll()]);
} catch (PDOException $exception) {
    error_log('TaskFlow task list error: ' . $exception->getMessage());
    jsonResponse(['success' => false, 'message' => 'Tasks could not be loaded. Run install.php to update the database.'], 503);
}
