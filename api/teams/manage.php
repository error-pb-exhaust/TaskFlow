<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/access.php';
requireMethod('POST'); $userId = currentUserId(); requireWritableAccount($userId); $data = requestData();
$action = $data['action'] ?? ''; $tid = (int) ($data['team_id'] ?? 0); $pid = (int) ($data['project_id'] ?? 0); $pdo = database();
try {
    $pdo->beginTransaction();
    // Project is locked before team to match task assignment and ownership operations.
    if ($action === 'attach') requireProjectManager($pid, $userId, true);
    if ($action === 'create') {
        $name = cleanString($data['name'] ?? '', 150);
        if (mb_strlen($name) < 2) jsonResponse(['success' => false, 'message' => 'Enter a team name of at least two characters.'], 422);
        $allowed = $pdo->prepare("SELECT id FROM users WHERE id = ? AND (role IN ('manager','admin') OR EXISTS (SELECT 1 FROM projects WHERE owner_id = ?) OR EXISTS (SELECT 1 FROM project_members WHERE user_id = ? AND project_role = 'manager'))");
        $allowed->execute([$userId, $userId, $userId]);
        if (!$allowed->fetchColumn()) jsonResponse(['success' => false, 'message' => 'Choose Manager in your profile to create a reusable team.'], 403);
        $pdo->prepare('INSERT INTO teams (name, owner_id) VALUES (?, ?)')->execute([$name, $userId]); $tid = (int) $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO team_members (team_id, user_id) VALUES (?, ?)')->execute([$tid, $userId]);
    } else {
        $find = $pdo->prepare('SELECT * FROM teams WHERE id = ? FOR UPDATE'); $find->execute([$tid]); $team = $find->fetch();
        if (!$team) jsonResponse(['success' => false, 'message' => 'Team not found.'], 404);
        if ($action === 'leave') {
            if ((int) $team['owner_id'] === $userId) jsonResponse(['success' => false, 'message' => 'The team owner cannot leave their own team.'], 409);
            $pdo->prepare('DELETE FROM team_members WHERE team_id = ? AND user_id = ?')->execute([$tid, $userId]);
        } else {
            if ((int) $team['owner_id'] !== $userId) jsonResponse(['success' => false, 'message' => 'Only this reusable team’s owner can change or attach it.'], 403);
            if ($action === 'add') {
                $email = strtolower(cleanString($data['email'] ?? '', 190));
                $find = $pdo->prepare("SELECT id FROM users WHERE email = ? AND status = 'active' AND role <> 'guest'"); $find->execute([$email]); $person = (int) $find->fetchColumn();
                // Build reusable groups only from accepted project relationships.
                $colleague = $pdo->prepare("SELECT 1 FROM projects p LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ? WHERE (p.owner_id = ? OR pm.project_role = 'manager') AND (p.owner_id = ? OR EXISTS (SELECT 1 FROM project_members other WHERE other.project_id = p.id AND other.user_id = ?)) LIMIT 1");
                $colleague->execute([$userId, $userId, $person, $person]);
                if (!$person || ($person !== $userId && !$colleague->fetchColumn())) jsonResponse(['success' => false, 'message' => 'Invite this person to a project first. After they accept, add them to the reusable team.'], 422);
                $pdo->prepare('INSERT IGNORE INTO team_members (team_id, user_id) VALUES (?, ?)')->execute([$tid, $person]);
            } elseif ($action === 'remove') {
                $person = (int) ($data['user_id'] ?? 0);
                if ($person === $userId) jsonResponse(['success' => false, 'message' => 'Keep the team owner in the team.'], 422);
                $pdo->prepare('DELETE FROM team_members WHERE team_id = ? AND user_id = ?')->execute([$tid, $person]);
            } elseif ($action === 'attach') {
                $pdo->prepare('INSERT IGNORE INTO project_teams (project_id, team_id) VALUES (?, ?)')->execute([$pid, $tid]);
                $pdo->prepare("INSERT IGNORE INTO project_members (project_id, user_id) SELECT ?, tm.user_id FROM team_members tm JOIN users u ON u.id = tm.user_id JOIN projects p ON p.id = ? WHERE tm.team_id = ? AND u.status = 'active' AND u.id <> p.owner_id")->execute([$pid, $pid, $tid]);
            } else jsonResponse(['success' => false, 'message' => 'Unknown team action.'], 422);
        }
    }
    writeAuditLog($userId, 'Reusable team updated', 'Team ' . $tid . ': ' . $action . '.');
    $pdo->commit(); jsonResponse(['success' => true, 'team_id' => $tid, 'message' => 'Team updated. Existing task assignments remain unchanged; add the team to a project again to include new members.']);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack(); error_log($exception->getMessage());
    jsonResponse(['success' => false, 'message' => 'Team could not be updated.'], 500);
}
