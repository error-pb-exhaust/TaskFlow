<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/access.php';
requireMethod('POST'); $userId = currentUserId(); $id = (int) (requestData()['id'] ?? 0);
$task = requireTask($id, $userId); requireProjectManager((int) $task['project_id'], $userId);
database()->prepare('DELETE FROM tasks WHERE id = ?')->execute([$id]);
writeAuditLog($userId, 'Task deleted', 'Task ID ' . $id . ' deleted.');
jsonResponse(['success' => true, 'message' => 'Task deleted.']);
