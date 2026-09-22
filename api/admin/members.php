<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
requireAdmin();
$pdo = database();
$pdo->exec("UPDATE invitations SET status = 'expired' WHERE status = 'pending' AND expires_at <= NOW()");

$users = $pdo->query('SELECT id, name, email, role, status, last_login_at, created_at FROM users ORDER BY FIELD(role, "admin", "manager", "member", "guest"), name')->fetchAll();
$invitations = $pdo->query("SELECT i.id, i.name, i.email, i.role, i.status, i.expires_at, i.created_at, u.name AS invited_by_name FROM invitations i JOIN users u ON u.id = i.invited_by WHERE i.status = 'pending' ORDER BY i.created_at DESC")->fetchAll();

jsonResponse(['success' => true, 'users' => $users, 'invitations' => $invitations]);
