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
jsonResponse(['success' => false, 'message' => 'These settings are not enforced yet. Saving is disabled until their controls are implemented.'], 501);
