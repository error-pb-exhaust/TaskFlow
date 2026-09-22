<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
requireAdmin();

$stmt = database()->query('SELECT a.id, a.action, a.details, a.ip_address, a.created_at, u.name AS user_name, u.email AS user_email FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.created_at DESC LIMIT 100');
jsonResponse(['success' => true, 'logs' => $stmt->fetchAll()]);
