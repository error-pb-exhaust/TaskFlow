<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
requireMethod('POST');

$data = requestData();
$name = cleanString($data['name'] ?? '', 100);
$email = strtolower(cleanString($data['email'] ?? '', 190));
$password = (string) ($data['password'] ?? '');

if (mb_strlen($name) < 2) {
    jsonResponse(['success' => false, 'message' => 'Name must contain at least 2 characters.'], 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['success' => false, 'message' => 'Enter a valid email address.'], 422);
}
if (strlen($password) < 8) {
    jsonResponse(['success' => false, 'message' => 'Password must contain at least 8 characters.'], 422);
}

try {
    $pdo = database();
    $pdo->beginTransaction();

    $invitationStmt = $pdo->prepare("SELECT id, role FROM invitations WHERE email = ? AND status = 'pending' AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
    $invitationStmt->execute([$email]);
    $invitation = $invitationStmt->fetch();
    $assignedRole = $invitation['role'] ?? 'member';

    $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
    $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $assignedRole]);
    $userId = (int) $pdo->lastInsertId();

    $project = $pdo->prepare('INSERT INTO projects (name, color, owner_id) VALUES (?, ?, ?)');
    $project->execute(['My First Project', '#0073EA', $userId]);

    if ($invitation) {
        $accept = $pdo->prepare("UPDATE invitations SET status = 'accepted' WHERE id = ?");
        $accept->execute([(int) $invitation['id']]);
    }

    writeAuditLog($userId, 'Account registered', "$name created a TaskFlow account with the $assignedRole role.");

    $pdo->commit();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;

    jsonResponse([
        'success' => true,
        'message' => 'Account created successfully.',
        'user' => ['id' => $userId, 'name' => $name, 'email' => $email, 'role' => $assignedRole],
    ], 201);
} catch (PDOException $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $duplicate = (int) ($exception->errorInfo[1] ?? 0) === 1062;
    jsonResponse(['success' => false, 'message' => $duplicate ? 'An account already uses this email.' : 'Account could not be created.'], $duplicate ? 409 : 500);
}
