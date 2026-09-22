<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
requireMethod('POST');
$userId = currentUserId();
$data = requestData();

$name = cleanString($data['name'] ?? '', 150);
$description = cleanString($data['description'] ?? '', 500);
$color = cleanString($data['color'] ?? '#0073EA', 20);
$deadline = cleanString($data['deadline'] ?? '', 10) ?: null;

if (mb_strlen($name) < 2) {
    jsonResponse(['success' => false, 'message' => 'Project name must contain at least 2 characters.'], 422);
}
if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
    $color = '#0073EA';
}
if ($deadline !== null && !DateTime::createFromFormat('Y-m-d', $deadline)) {
    jsonResponse(['success' => false, 'message' => 'Project deadline is invalid.'], 422);
}

$pdo = database();
$duplicate = $pdo->prepare("SELECT id FROM projects WHERE name = ? AND status <> 'archived' LIMIT 1");
$duplicate->execute([$name]);
if ($duplicate->fetch()) {
    jsonResponse(['success' => false, 'message' => 'An active project already uses this name.'], 409);
}

$stmt = $pdo->prepare('INSERT INTO projects (name, description, color, deadline, owner_id) VALUES (?, ?, ?, ?, ?)');
$stmt->execute([$name, $description, $color, $deadline, $userId]);
$projectId = (int) $pdo->lastInsertId();
writeAuditLog($userId, 'Project created', "$name was created.");

jsonResponse(['success' => true, 'message' => 'Project created.', 'project_id' => $projectId], 201);
