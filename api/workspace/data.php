<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
$userId = currentUserId();

try {
    $pdo = database();

    $taskStmt = $pdo->prepare('SELECT t.id, t.title, t.status, t.priority, t.due_date, t.description, t.assignee_id, t.created_at, t.updated_at, p.id AS project_id, p.name AS project_name, p.color AS project_color, u.name AS assignee_name FROM tasks t JOIN projects p ON p.id = t.project_id LEFT JOIN users u ON u.id = t.assignee_id WHERE p.owner_id = ? OR t.assignee_id = ? ORDER BY t.due_date IS NULL, t.due_date, t.created_at DESC');
    $taskStmt->execute([$userId, $userId]);
    $tasks = $taskStmt->fetchAll();

    $projects = $pdo->query("SELECT p.id, p.name, p.description, p.color, p.status, p.deadline, p.owner_id, p.created_at, u.name AS owner_name, COUNT(t.id) AS task_count, COALESCE(SUM(CASE WHEN t.status = 'done' THEN 1 ELSE 0 END), 0) AS completed_count, COALESCE(SUM(CASE WHEN t.due_date < CURDATE() AND t.status <> 'done' THEN 1 ELSE 0 END), 0) AS overdue_count FROM projects p JOIN users u ON u.id = p.owner_id LEFT JOIN tasks t ON t.project_id = p.id GROUP BY p.id, p.name, p.description, p.color, p.status, p.deadline, p.owner_id, p.created_at, u.name ORDER BY FIELD(p.status, 'active', 'complete', 'archived'), p.created_at DESC")->fetchAll();

    $members = $pdo->query("SELECT u.id, u.name, u.email, u.role, u.status, u.last_login_at, COUNT(t.id) AS open_tasks, COALESCE(SUM(CASE WHEN t.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND t.status <> 'done' THEN 1 ELSE 0 END), 0) AS due_this_week FROM users u LEFT JOIN tasks t ON t.assignee_id = u.id AND t.status <> 'done' GROUP BY u.id, u.name, u.email, u.role, u.status, u.last_login_at ORDER BY u.name")->fetchAll();

    $activity = $pdo->query('SELECT a.id, a.action, a.details, a.created_at, u.name AS user_name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.created_at DESC LIMIT 50')->fetchAll();

    jsonResponse([
        'success' => true,
        'tasks' => $tasks,
        'projects' => $projects,
        'members' => $members,
        'activity' => $activity,
    ]);
} catch (PDOException $exception) {
    error_log('TaskFlow workspace data error: ' . $exception->getMessage());
    jsonResponse([
        'success' => false,
        'message' => 'Workspace data could not be loaded. Run install.php to update the database schema.',
    ], 503);
}
