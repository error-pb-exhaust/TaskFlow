<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

function projectContext(int $projectId, int $userId, bool $lock = false): array
{
    $stmt = database()->prepare('SELECT p.*, u.name AS owner_name FROM projects p JOIN users u ON u.id = p.owner_id WHERE p.id = ?' . ($lock ? ' FOR UPDATE' : ''));
    $stmt->execute([$projectId]);
    $project = $stmt->fetch();
    $member = database()->prepare('SELECT project_role FROM project_members WHERE project_id = ? AND user_id = ?');
    $member->execute([$projectId, $userId]);
    $role = $member->fetchColumn();
    if (!$project || ((int) $project['owner_id'] !== $userId && !$role)) jsonResponse(['success' => false, 'message' => 'Project not found or access denied.'], 403);
    $account = database()->prepare("SELECT role FROM users WHERE id = ? AND status = 'active'");
    $account->execute([$userId]);
    $accountRole = $account->fetchColumn();
    if (!$accountRole) jsonResponse(['success' => false, 'message' => 'Account is not active.'], 403);
    $project['is_owner'] = (int) $project['owner_id'] === $userId;
    $project['can_manage'] = $accountRole !== 'guest' && ($project['is_owner'] || $role === 'manager');
    $project['account_role'] = $accountRole;
    return $project;
}
function requireProjectManager(int $projectId, int $userId, bool $lock = false): array
{
    $project = projectContext($projectId, $userId, $lock);
    if (!$project['can_manage']) jsonResponse(['success' => false, 'message' => 'A project manager is required for this action.'], 403);
    return $project;
}
function projectAccessSql(int $userId): string
{
    return "(p.owner_id = $userId OR EXISTS (SELECT 1 FROM project_members pm WHERE pm.project_id = p.id AND pm.user_id = $userId))";
}
function taskVisibilitySql(int $userId): string
{
    return projectAccessSql($userId) . " AND (t.visibility = 'project' OR (EXISTS (SELECT 1 FROM users viewer WHERE viewer.id = $userId AND viewer.role <> 'guest') AND (p.owner_id = $userId OR EXISTS (SELECT 1 FROM project_members pm WHERE pm.project_id = p.id AND pm.user_id = $userId AND pm.project_role = 'manager'))) OR EXISTS (SELECT 1 FROM task_assignees ta WHERE ta.task_id = t.id AND ta.user_id = $userId))";
}
function fetchTasks(int $userId, ?int $taskId = null): array
{
    $sql = 'SELECT t.*, p.name AS project_name, p.color AS project_color, p.owner_id, c.name AS creator_name, tm.name AS team_name FROM tasks t JOIN projects p ON p.id = t.project_id JOIN users c ON c.id = t.created_by LEFT JOIN teams tm ON tm.id = t.team_id WHERE ' . taskVisibilitySql($userId);
    $params = [];
    if ($taskId !== null) { $sql .= ' AND t.id = ?'; $params[] = $taskId; }
    $sql .= ' ORDER BY t.due_date IS NULL, t.due_date, t.created_at DESC';
    $stmt = database()->prepare($sql); $stmt->execute($params); $rows = $stmt->fetchAll();
    if (!$rows) return [];
    $ids = implode(',', array_map(static fn(array $row): int => (int) $row['id'], $rows));
    $assignees = database()->query("SELECT ta.task_id, u.id, u.name, u.status FROM task_assignees ta JOIN users u ON u.id = ta.user_id WHERE ta.task_id IN ($ids) ORDER BY u.name")->fetchAll();
    $byTask = [];
    foreach ($assignees as $person) $byTask[(int) $person['task_id']][] = $person;
    $contexts = [];
    foreach ($rows as &$row) {
        $pid = (int) $row['project_id'];
        if (!isset($contexts[$pid])) $contexts[$pid] = projectContext($pid, $userId);
        $row['assignees'] = $byTask[(int) $row['id']] ?? [];
        $row['assignee_ids'] = array_map(static fn(array $person): int => (int) $person['id'], $row['assignees']);
        $row['assignee_name'] = implode(', ', array_column($row['assignees'], 'name'));
        $row['can_manage'] = $contexts[$pid]['can_manage'];
        $row['can_edit'] = $contexts[$pid]['account_role'] !== 'guest' && ($row['can_manage'] || ($row['status'] !== 'done' && in_array($userId, $row['assignee_ids'], true)));
        $row['can_approve'] = $row['can_manage'] && $row['status'] === 'review';
    }
    unset($row);
    return $rows;
}
function requireTask(int $taskId, int $userId, bool $edit = false): array
{
    $tasks = fetchTasks($userId, $taskId);
    if (!$tasks || ($edit && !$tasks[0]['can_edit'])) jsonResponse(['success' => false, 'message' => 'Task not found or permission denied.'], 403);
    return $tasks[0];
}
function assignmentIds(array $data, int $projectId, ?int &$teamId): array
{
    $teamId = (int) ($data['team_id'] ?? 0) ?: null;
    if ($teamId) {
        $attached = database()->prepare('SELECT 1 FROM project_teams WHERE project_id = ? AND team_id = ?');
        $attached->execute([$projectId, $teamId]);
        if (!$attached->fetchColumn()) jsonResponse(['success' => false, 'message' => 'Add this team to the project first.'], 422);
        $stmt = database()->prepare("SELECT tm.user_id FROM team_members tm JOIN users u ON u.id = tm.user_id WHERE tm.team_id = ? AND u.status = 'active' AND u.role <> 'guest'");
        $stmt->execute([$teamId]); $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    } else {
        $values = $data['assignee_ids'] ?? [$data['assignee_id'] ?? 0];
        if (!is_array($values)) jsonResponse(['success' => false, 'message' => 'Select one or more assignees.'], 422);
        $ids = array_values(array_unique(array_map('intval', $values)));
    }
    if (!$ids || count($ids) > 100 || min($ids) < 1) jsonResponse(['success' => false, 'message' => 'Select between 1 and 100 active assignees.'], 422);
    $stmt = database()->prepare("SELECT u.id FROM users u JOIN projects p ON p.id = ? WHERE u.id = ? AND u.status = 'active' AND u.role <> 'guest' AND (u.id = p.owner_id OR EXISTS (SELECT 1 FROM project_members pm WHERE pm.project_id = p.id AND pm.user_id = u.id))");
    foreach ($ids as $id) {
        $stmt->execute([$projectId, $id]);
        if (!$stmt->fetchColumn()) jsonResponse(['success' => false, 'message' => 'Every assignee must be an active, non-guest project member. Add or sync the team to this project first.'], 422);
    }
    return $ids;
}
function storeAssignments(int $taskId, array $ids): void
{
    database()->prepare('DELETE FROM task_assignees WHERE task_id = ?')->execute([$taskId]);
    $insert = database()->prepare('INSERT INTO task_assignees (task_id, user_id) VALUES (?, ?)');
    foreach ($ids as $id) $insert->execute([$taskId, $id]);
}
function notifyProjectManagers(int $projectId, string $message, ?int $taskId, int $except): void
{
    $stmt = database()->prepare("SELECT owner_id AS id FROM projects WHERE id = ? UNION SELECT user_id AS id FROM project_members WHERE project_id = ? AND project_role = 'manager'");
    $stmt->execute([$projectId, $projectId]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) if ((int) $id !== $except) createNotification((int) $id, $message, $taskId, 'tasks');
}

function removeProjectMembership(array $project, int $targetId, int $actorId): void
{
    if ($targetId === (int) $project['owner_id']) jsonResponse(['success' => false, 'message' => 'Transfer project ownership before leaving.'], 409);
    $stmt = database()->prepare('SELECT project_role FROM project_members WHERE project_id = ? AND user_id = ?');
    $stmt->execute([(int) $project['id'], $targetId]); $role = $stmt->fetchColumn();
    if (!$role) jsonResponse(['success' => false, 'message' => 'This person is not a project member.'], 404);
    if ($targetId !== $actorId && (!$project['can_manage'] || ($role === 'manager' && !$project['is_owner']))) jsonResponse(['success' => false, 'message' => 'Only the owner can remove another project manager.'], 403);
    $open = database()->prepare("SELECT COUNT(*) FROM task_assignees ta JOIN tasks t ON t.id = ta.task_id WHERE t.project_id = ? AND ta.user_id = ? AND t.status <> 'done'");
    $open->execute([(int) $project['id'], $targetId]);
    if ((int) $open->fetchColumn() > 0) jsonResponse(['success' => false, 'message' => 'Reassign or finish this person’s open tasks before leaving or removing them.'], 409);
    database()->prepare('DELETE FROM project_members WHERE project_id = ? AND user_id = ?')->execute([(int) $project['id'], $targetId]);
    database()->prepare("UPDATE project_invitations SET status = 'cancelled' WHERE project_id = ? AND email = (SELECT email FROM users WHERE id = ?) AND status = 'pending'")->execute([(int) $project['id'], $targetId]);
    writeAuditLog($actorId, 'Project member left', "Member $targetId left " . $project['name'] . '.');
}
