<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/access.php';
requireMethod('POST');
$userId = currentUserId();
requireWritableAccount($userId);
$data = requestData();
$taskId = (int) ($data['task_id'] ?? 0);
$action = cleanString($data['action'] ?? 'create', 20);

$task = requireTask($taskId, $userId, true);

if ($action === 'toggle') {
    $itemId = (int) ($data['item_id'] ?? 0);
    $completed = !empty($data['is_completed']) ? 1 : 0;
    $stmt = database()->prepare('UPDATE checklist_items SET is_completed = ? WHERE id = ? AND task_id = ?');
    $stmt->execute([$completed, $itemId, $taskId]);
    jsonResponse(['success' => true, 'message' => 'Checklist updated.']);
}

$text = cleanString($data['item_text'] ?? '', 255);
if ($text === '') jsonResponse(['success' => false, 'message' => 'Checklist text is required.'], 422);
$stmt = database()->prepare('INSERT INTO checklist_items (task_id, item_text, created_by) VALUES (?, ?, ?)');
$stmt->execute([$taskId, $text, $userId]);
writeAuditLog($userId, 'Checklist item added', 'A checklist item was added to ' . $task['title'] . '.');
jsonResponse(['success' => true, 'message' => 'Checklist item added.'], 201);
