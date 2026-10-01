<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
requireAdmin();
$pdo = database();
$pdo->exec("UPDATE invitations SET status = 'expired' WHERE status = 'pending' AND expires_at <= NOW()");

$activeUsers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
$pendingInvites = (int) $pdo->query("SELECT COUNT(*) FROM invitations WHERE status = 'pending' AND expires_at > NOW()")->fetchColumn();
$expiringInvites = (int) $pdo->query("SELECT COUNT(*) FROM invitations WHERE status = 'pending' AND expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)")->fetchColumn();
$overdueTasks = (int) $pdo->query("SELECT COUNT(*) FROM tasks WHERE due_date < CURDATE() AND status <> 'done'")->fetchColumn();

$settings = $pdo->query('SELECT setting_key, setting_value FROM workspace_settings')->fetchAll(PDO::FETCH_KEY_PAIR);
$securityAlerts = 1; // Two-factor authentication is not implemented.

$activityStmt = $pdo->query("SELECT DATE(created_at) AS activity_date, COUNT(*) AS total FROM audit_logs WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(created_at)");
$activityRows = $activityStmt->fetchAll();
$activityMap = [];
foreach ($activityRows as $row) {
    $activityMap[$row['activity_date']] = (int) $row['total'];
}
$activity = [];
for ($offset = 6; $offset >= 0; $offset--) {
    $date = date('Y-m-d', strtotime("-$offset days"));
    $activity[] = ['date' => $date, 'day' => date('D', strtotime($date)), 'total' => $activityMap[$date] ?? 0];
}

jsonResponse([
    'success' => true,
    'metrics' => [
        'active_users' => $activeUsers,
        'pending_invites' => $pendingInvites,
        'security_alerts' => $securityAlerts,
        'system_health' => 'Online',
    ],
    'attention' => [
        'two_factor_disabled' => $activeUsers,
        'expiring_invites' => $expiringInvites,
        'overdue_tasks' => $overdueTasks,
    ],
    'activity' => $activity,
]);
