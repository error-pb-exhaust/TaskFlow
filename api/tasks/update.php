<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/access.php';
requireMethod('POST'); $userId = currentUserId(); requireWritableAccount($userId); $data = requestData();
$id = (int) ($data['id'] ?? 0); $pdo = database();
try {
    $pdo->beginTransaction();
    $find = $pdo->prepare('SELECT project_id FROM tasks WHERE id = ?'); $find->execute([$id]);
    $projectId = (int) $find->fetchColumn();
    $project = projectContext($projectId, $userId, true);
    $current = requireTask($id, $userId, true);
    $manager = $project['can_manage'];
    if (!$manager && $current['status'] === 'done') jsonResponse(['success' => false, 'message' => 'This task was approved. A manager must reopen it.'], 409);
    $status = cleanString($data['status'] ?? $current['status'], 20);
    if (!in_array($status, ['backlog','todo','progress','review','done'], true)) jsonResponse(['success' => false, 'message' => 'Invalid status.'], 422);
    if (!$manager && $status === 'done') $status = 'review';
    if ($manager && $status === 'done' && !in_array($current['status'], ['review','done'], true)) jsonResponse(['success' => false, 'message' => 'Move the task to In review before approving it.'], 422);
    $assignmentChange = array_key_exists('assignee_ids', $data) || array_key_exists('assignee_id', $data) || array_key_exists('team_id', $data);
    if (!$manager && ($assignmentChange || array_key_exists('visibility', $data))) jsonResponse(['success' => false, 'message' => 'Only a project manager can change assignment or visibility.'], 403);
    $title = $manager ? cleanString($data['title'] ?? $current['title'], 255) : $current['title'];
    $priority = $manager ? cleanString($data['priority'] ?? $current['priority'], 10) : $current['priority'];
    $date = $manager ? (cleanString($data['due_date'] ?? $current['due_date'] ?? '', 10) ?: null) : $current['due_date'];
    $description = $manager ? cleanString($data['description'] ?? $current['description'] ?? '', 10000) : $current['description'];
    $visibility = $manager ? cleanString($data['visibility'] ?? $current['visibility'], 20) : $current['visibility'];
    if ($title === '' || !in_array($priority, ['low','medium','high'], true) || !in_array($visibility, ['project','assignees'], true)) jsonResponse(['success' => false, 'message' => 'Invalid task details.'], 422);
    if ($date && (!($parsed = DateTime::createFromFormat('!Y-m-d', $date)) || $parsed->format('Y-m-d') !== $date)) jsonResponse(['success' => false, 'message' => 'Invalid due date.'], 422);
    $teamId = $current['team_id'] ? (int) $current['team_id'] : null;
    $ids = $current['assignee_ids'];
    if ($assignmentChange) $ids = assignmentIds($data, $projectId, $teamId);
    $completed = $status === 'done' ? ($current['completed_at'] ?: date('Y-m-d H:i:s')) : null;
    $approver = $status === 'done' ? ($current['approved_by'] ?: $userId) : null;
    $stmt = $pdo->prepare('UPDATE tasks SET title = ?, status = ?, priority = ?, due_date = ?, description = ?, assignee_id = ?, team_id = ?, visibility = ?, completed_at = ?, approved_by = ? WHERE id = ?');
    $stmt->execute([$title, $status, $priority, $date, $description, $ids[0] ?? null, $teamId, $visibility, $completed, $approver, $id]);
    if ($assignmentChange) {
        storeAssignments($id, $ids);
        foreach (array_diff($ids, $current['assignee_ids']) as $person) if ($person !== $userId) createNotification($person, 'You were assigned to ' . $title . '.', $id);
    }
    if ($status !== $current['status']) {
        if ($status === 'review') notifyProjectManagers($projectId, $title . ' is ready for your review.', $id, $userId);
        foreach ($ids as $person) if ($person !== $userId) createNotification($person, $title . ': ' . ($status === 'done' ? 'approved by a manager' : $status) . '.', $id);
    }
    writeAuditLog($userId, $status === 'done' ? 'Task approved' : 'Task updated', $title . ' status: ' . $status . '.');
    $pdo->commit();
    jsonResponse(['success' => true, 'status' => $status, 'message' => $status === 'review' && !$manager ? 'Submitted for manager approval.' : 'Task updated.']);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack(); error_log($exception->getMessage());
    jsonResponse(['success' => false, 'message' => 'Task update failed.'], 500);
}
