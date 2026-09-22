<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
requireMethod('POST');
$userId = currentUserId();
$data = requestData();
$projectId = (int) ($data['project_id'] ?? 0);
$status = cleanString($data['status'] ?? '', 20);

if ($projectId < 1 || !in_array($status, ['active', 'complete', 'archived'], true)) {
    jsonResponse(['success' => false, 'message' => 'Invalid project or status.'], 422);
}

$pdo = database();
$userStmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$userStmt->execute([$userId]);
$role = $userStmt->fetchColumn();
$project = $pdo->prepare('SELECT name, owner_id FROM projects WHERE id = ?');
$project->execute([$projectId]);
$record = $project->fetch();
if (!$record || ((int) $record['owner_id'] !== $userId && $role !== 'admin')) {
    jsonResponse(['success' => false, 'message' => 'Project not found or permission denied.'], 403);
}

$update = $pdo->prepare('UPDATE projects SET status = ? WHERE id = ?');
$update->execute([$status, $projectId]);
writeAuditLog($userId, 'Project status changed', $record['name'] . " changed to $status.");
jsonResponse(['success' => true, 'message' => 'Project status updated.']);
