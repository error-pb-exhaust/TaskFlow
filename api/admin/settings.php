<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
$admin = requireAdmin();
$pdo = database();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    $settings = $pdo->query('SELECT setting_key, setting_value, updated_at FROM workspace_settings')->fetchAll();
    $result = [];
    foreach ($settings as $setting) {
        $result[$setting['setting_key']] = $setting['setting_value'];
    }
    jsonResponse(['success' => true, 'settings' => $result]);
}

requireMethod('POST');
$data = requestData();
$require2fa = !empty($data['require_2fa']) ? '1' : '0';
$guestAccess = !empty($data['allow_guest_access']) ? '1' : '0';
$retentionDays = max(30, min(3650, (int) ($data['retention_days'] ?? 365)));

$stmt = $pdo->prepare('INSERT INTO workspace_settings (setting_key, setting_value, updated_by) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)');
$stmt->execute(['require_2fa', $require2fa, (int) $admin['id']]);
$stmt->execute(['allow_guest_access', $guestAccess, (int) $admin['id']]);
$stmt->execute(['retention_days', (string) $retentionDays, (int) $admin['id']]);
writeAuditLog((int) $admin['id'], 'Security settings changed', "2FA=$require2fa, guest access=$guestAccess, retention=$retentionDays days.");

jsonResponse(['success' => true, 'message' => 'Workspace settings saved.']);
