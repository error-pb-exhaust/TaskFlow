<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
requireMethod('POST');
$userId = currentUserId();
$data = requestData();
$id = (int) ($data['id'] ?? 0);

if ($id < 1) {
    jsonResponse(['success' => false, 'message' => 'A valid task ID is required.'], 422);
}

$stmt = database()->prepare('DELETE t FROM tasks t JOIN projects p ON p.id = t.project_id WHERE t.id = ? AND p.owner_id = ?');
$stmt->execute([$id, $userId]);

if (!$stmt->rowCount()) {
    jsonResponse(['success' => false, 'message' => 'Task not found or permission denied.'], 404);
}
writeAuditLog($userId, 'Task deleted', "Task ID $id was deleted.");
jsonResponse(['success' => true, 'message' => 'Task deleted.']);
