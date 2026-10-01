<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/access.php';
requireMethod('POST');
$userId = currentUserId(); requireWritableAccount($userId);
$data = requestData();
$title = cleanString($data['task_name'] ?? $data['title'] ?? '', 255);
$projectId = (int) ($data['project_id'] ?? 0);
$statusMap = ['To do' => 'todo', 'In progress' => 'progress', 'In review' => 'review', 'Backlog' => 'backlog'];
$input = cleanString($data['status'] ?? 'todo', 20); $status = $statusMap[$input] ?? $input;
$priority = strtolower(cleanString($data['priority'] ?? 'medium', 10));
$date = cleanString($data['due_date'] ?? '', 10) ?: null;
$visibility = cleanString($data['visibility'] ?? 'project', 20);
if ($title === '' || !in_array($status, ['todo','progress','review','backlog'], true) || !in_array($priority, ['low','medium','high'], true) || !in_array($visibility, ['project','assignees'], true)) jsonResponse(['success' => false, 'message' => 'Enter a title, valid status, priority and visibility. New tasks cannot start as approved.'], 422);
if ($date && (!($parsed = DateTime::createFromFormat('!Y-m-d', $date)) || $parsed->format('Y-m-d') !== $date)) jsonResponse(['success' => false, 'message' => 'Invalid due date.'], 422);
$pdo = database();
try {
    $pdo->beginTransaction();
    $project = requireProjectManager($projectId, $userId, true);
    if ($project['status'] !== 'active') jsonResponse(['success' => false, 'message' => 'Choose an active project.'], 422);
    $teamId = null; $ids = assignmentIds($data, $projectId, $teamId);
    $stmt = $pdo->prepare('INSERT INTO tasks (title, project_id, status, priority, due_date, description, assignee_id, created_by, visibility, team_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$title, $projectId, $status, $priority, $date, cleanString($data['description'] ?? '', 10000), $ids[0], $userId, $visibility, $teamId]);
    $id = (int) $pdo->lastInsertId(); storeAssignments($id, $ids);
    foreach ($ids as $person) if ($person !== $userId) createNotification($person, 'You were assigned to ' . $title . '.', $id);
    writeAuditLog($userId, 'Task created', $title . ' created in ' . $project['name'] . '.');
    $pdo->commit();
    jsonResponse(['success' => true, 'task_id' => $id, 'message' => 'Task created and assigned.'], 201);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack(); error_log($exception->getMessage());
    jsonResponse(['success' => false, 'message' => 'Task could not be created.'], 500);
}
