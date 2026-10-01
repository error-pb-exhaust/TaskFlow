<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/access.php';
requireMethod('POST'); $userId = currentUserId(); $data = requestData();
$pid = (int) ($data['project_id'] ?? 0); $action = $data['action'] ?? ''; $target = (int) ($data['user_id'] ?? 0); $pdo = database();
try {
    $pdo->beginTransaction(); $project = projectContext($pid, $userId, true);
    if ($action === 'leave') {
        removeProjectMembership($project, $userId, $userId);
        notifyProjectManagers($pid, 'A member left ' . $project['name'] . '.', null, $userId);
    } elseif ($action === 'remove') {
        requireProjectManager($pid, $userId); removeProjectMembership($project, $target, $userId);
    } elseif (in_array($action, ['role','transfer'], true)) {
        if (!$project['is_owner'] || !$project['can_manage']) jsonResponse(['success' => false, 'message' => 'Only the project owner can change managers or transfer ownership.'], 403);
        if ($target === $userId) jsonResponse(['success' => false, 'message' => 'Choose another project member.'], 422);
        $person = $pdo->prepare("SELECT u.id FROM project_members pm JOIN users u ON u.id = pm.user_id WHERE pm.project_id = ? AND pm.user_id = ? AND u.status = 'active' AND u.role <> 'guest'");
        $person->execute([$pid, $target]);
        if (!$person->fetchColumn()) jsonResponse(['success' => false, 'message' => 'Choose an active non-guest project member.'], 422);
        if ($action === 'role') {
            $role = $data['project_role'] ?? '';
            if (!in_array($role, ['manager','member'], true)) jsonResponse(['success' => false, 'message' => 'Invalid project role.'], 422);
            $pdo->prepare('UPDATE project_members SET project_role = ? WHERE project_id = ? AND user_id = ?')->execute([$role, $pid, $target]);
            createNotification($target, 'Your role in ' . $project['name'] . ' is now ' . $role . '.', null, 'team');
        } else {
            $pdo->prepare("INSERT INTO project_members (project_id, user_id, project_role) VALUES (?, ?, 'manager') ON DUPLICATE KEY UPDATE project_role = 'manager'")->execute([$pid, $userId]);
            $pdo->prepare('DELETE FROM project_members WHERE project_id = ? AND user_id = ?')->execute([$pid, $target]);
            $pdo->prepare('UPDATE projects SET owner_id = ? WHERE id = ?')->execute([$target, $pid]);
            createNotification($target, 'You now own ' . $project['name'] . '.', null, 'projects');
        }
        writeAuditLog($userId, 'Project management changed', $project['name'] . ': ' . $action . ' to member ' . $target . '.');
    } else jsonResponse(['success' => false, 'message' => 'Unknown project action.'], 422);
    $pdo->commit(); jsonResponse(['success' => true, 'message' => 'Project relationship updated.']);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack(); error_log($exception->getMessage());
    jsonResponse(['success' => false, 'message' => 'Project relationship could not be updated.'], 500);
}
