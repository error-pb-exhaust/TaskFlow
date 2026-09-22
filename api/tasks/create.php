<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
requireMethod('POST');
$userId = currentUserId();
$data = requestData();

$title = cleanString($data['task_name'] ?? $data['title'] ?? '', 255);
$projectName = cleanString($data['project'] ?? 'Website Redesign', 150);
$requestedProjectId = (int) ($data['project_id'] ?? 0);
$statusMap = ['To do' => 'todo', 'In progress' => 'progress', 'In review' => 'review', 'Done' => 'done'];
$statusInput = cleanString($data['status'] ?? 'To do', 20);
$status = $statusMap[$statusInput] ?? (in_array($statusInput, array_values($statusMap), true) ? $statusInput : 'todo');
$priority = strtolower(cleanString($data['priority'] ?? 'Medium', 10));
$dueDate = cleanString($data['due_date'] ?? '', 10) ?: null;
$description = cleanString($data['description'] ?? '', 10000);
$assigneeId = (int) ($data['assignee_id'] ?? $userId);

if ($title === '') {
    jsonResponse(['success' => false, 'message' => 'Task name is required.'], 422);
}
if (!in_array($priority, ['low', 'medium', 'high'], true)) {
    $priority = 'medium';
}
if ($dueDate !== null && !DateTime::createFromFormat('Y-m-d', $dueDate)) {
    jsonResponse(['success' => false, 'message' => 'Due date is invalid.'], 422);
}

$pdo = database();
$assignee = $pdo->prepare("SELECT id, name FROM users WHERE id = ? AND status = 'active'");
$assignee->execute([$assigneeId]);
$assigneeRecord = $assignee->fetch();
if (!$assigneeRecord) {
    jsonResponse(['success' => false, 'message' => 'Select an active assignee.'], 422);
}
$projectId = 0;
if ($requestedProjectId > 0) {
    $project = $pdo->prepare("SELECT id, name FROM projects WHERE id = ? AND status <> 'archived' LIMIT 1");
    $project->execute([$requestedProjectId]);
    $projectRecord = $project->fetch();
    if ($projectRecord) {
        $projectId = (int) $projectRecord['id'];
        $projectName = $projectRecord['name'];
    } else {
        jsonResponse(['success' => false, 'message' => 'Select an active project.'], 422);
    }
} else {
    $project = $pdo->prepare('SELECT id, name FROM projects WHERE owner_id = ? AND name = ? LIMIT 1');
    $project->execute([$userId, $projectName]);
    $projectRecord = $project->fetch();
    if ($projectRecord) $projectId = (int) $projectRecord['id'];
}

if (!$projectId) {
    $newProject = $pdo->prepare('INSERT INTO projects (name, owner_id) VALUES (?, ?)');
    $newProject->execute([$projectName, $userId]);
    $projectId = $pdo->lastInsertId();
}

$stmt = $pdo->prepare('INSERT INTO tasks (title, project_id, status, priority, due_date, description, assignee_id, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->execute([$title, $projectId, $status, $priority, $dueDate, $description, $assigneeId, $userId]);
$taskId = (int) $pdo->lastInsertId();
writeAuditLog($userId, 'Task created', "$title was created in $projectName.");
if ($assigneeId !== $userId) {
    createNotification($assigneeId, "You were assigned to $title.", $taskId, 'tasks');
}

jsonResponse(['success' => true, 'message' => 'Task created successfully.', 'task_id' => $taskId], 201);
