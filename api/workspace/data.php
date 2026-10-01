<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/access.php';
$userId = currentUserId();
try {
    $pdo = database();
    $tasks = fetchTasks($userId);
    $rows = $pdo->query('SELECT p.id FROM projects p WHERE ' . projectAccessSql($userId) . ' ORDER BY p.created_at DESC')->fetchAll();
    $projects = []; $memberships = []; $personIds = [$userId]; $emailVisible = [$userId];
    foreach ($rows as $row) {
        $p = projectContext((int) $row['id'], $userId);
        unset($p['account_role']);
        $pt = array_values(array_filter($tasks, static fn(array $t): bool => (int) $t['project_id'] === (int) $p['id']));
        $p['task_count'] = count($pt);
        $p['completed_count'] = count(array_filter($pt, static fn(array $t): bool => $t['status'] === 'done'));
        $p['overdue_count'] = count(array_filter($pt, static fn(array $t): bool => $t['due_date'] && $t['due_date'] < date('Y-m-d') && $t['status'] !== 'done'));
        $stmt = $pdo->prepare('SELECT project_id, user_id, project_role FROM project_members WHERE project_id = ?');
        $stmt->execute([(int) $p['id']]);
        $links = $stmt->fetchAll();
        $links[] = ['project_id' => (int) $p['id'], 'user_id' => (int) $p['owner_id'], 'project_role' => 'owner'];
        foreach ($links as $link) { $personIds[] = (int) $link['user_id']; if ($p['can_manage']) $emailVisible[] = (int) $link['user_id']; }
        $memberships = array_merge($memberships, $links);
        $projects[] = $p;
    }
    $teamsStmt = $pdo->prepare('SELECT DISTINCT tm.* FROM teams tm LEFT JOIN team_members m ON m.team_id = tm.id WHERE tm.owner_id = ? OR m.user_id = ? ORDER BY tm.name');
    $teamsStmt->execute([$userId, $userId]); $teams = $teamsStmt->fetchAll();
    // Managers also need attached teams for assigning project work.
    $teamIds = array_map(static fn(array $t): int => (int) $t['id'], $teams);
    $projectTeams = [];
    foreach ($projects as $project) {
        $stmt = $pdo->prepare('SELECT pt.project_id, pt.team_id, tm.name, tm.owner_id, tm.created_at FROM project_teams pt JOIN teams tm ON tm.id = pt.team_id WHERE pt.project_id = ?');
        $stmt->execute([(int) $project['id']]);
        foreach ($stmt->fetchAll() as $link) {
            $projectTeams[] = ['project_id' => (int) $link['project_id'], 'team_id' => (int) $link['team_id']];
            if ($project['can_manage'] && !in_array((int) $link['team_id'], $teamIds, true)) {
                $teams[] = ['id' => (int) $link['team_id'], 'name' => $link['name'], 'owner_id' => (int) $link['owner_id'], 'created_at' => $link['created_at']];
                $teamIds[] = (int) $link['team_id'];
            }
        }
    }
    $teamMembers = [];
    foreach ($teams as $team) {
        $stmt = $pdo->prepare('SELECT team_id, user_id FROM team_members WHERE team_id = ?'); $stmt->execute([(int) $team['id']]);
        foreach ($stmt->fetchAll() as $link) { $teamMembers[] = $link; $personIds[] = (int) $link['user_id']; if ((int) $team['owner_id'] === $userId) $emailVisible[] = (int) $link['user_id']; }
    }
    $ids = implode(',', array_unique($personIds));
    $members = $pdo->query("SELECT id, name, email, role, status, last_login_at FROM users WHERE id IN ($ids) ORDER BY name")->fetchAll();
    foreach ($members as &$member) {
        if (!in_array((int) $member['id'], $emailVisible, true)) $member['email'] = '';
        $assigned = array_filter($tasks, static fn(array $t): bool => $t['status'] !== 'done' && in_array((int) $member['id'], $t['assignee_ids'], true));
        $member['open_tasks'] = count($assigned);
        $member['due_this_week'] = count(array_filter($assigned, static fn(array $t): bool => $t['due_date'] && $t['due_date'] >= date('Y-m-d') && $t['due_date'] <= date('Y-m-d', strtotime('+7 days'))));
    }
    unset($member);
    $activityStmt = $pdo->prepare('SELECT a.id, a.action, a.details, a.created_at, u.name AS user_name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id WHERE a.user_id = ? ORDER BY a.created_at DESC LIMIT 50');
    $activityStmt->execute([$userId]);
    jsonResponse(['success' => true, 'tasks' => $tasks, 'projects' => $projects, 'members' => $members, 'memberships' => $memberships, 'teams' => $teams, 'team_members' => $teamMembers, 'project_teams' => $projectTeams, 'activity' => $activityStmt->fetchAll()]);
} catch (Throwable $exception) {
    error_log('TaskFlow workspace: ' . $exception->getMessage());
    jsonResponse(['success' => false, 'message' => 'Workspace could not load. Run install.php to update the database.'], 503);
}
