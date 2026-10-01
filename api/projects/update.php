<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/access.php';
requireMethod('POST');
$userId = currentUserId();
$data = requestData();
$projectId = (int) ($data['project_id'] ?? 0);
$status = cleanString($data['status'] ?? '', 20);

if ($projectId < 1 || !in_array($status, ['active', 'complete', 'archived'], true)) {
    jsonResponse(['success' => false, 'message' => 'Invalid project or status.'], 422);
}

$pdo = database();
$record = requireProjectManager($projectId, $userId);

$update = $pdo->prepare('UPDATE projects SET status = ? WHERE id = ?');
$update->execute([$status, $projectId]);
writeAuditLog($userId, 'Project status changed', $record['name'] . " changed to $status.");
jsonResponse(['success' => true, 'message' => 'Project status updated.']);
