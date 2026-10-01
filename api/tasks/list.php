<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/access.php';
$userId = currentUserId();
$search = mb_strtolower(cleanString($_GET['search'] ?? '', 100));
$status = cleanString($_GET['status'] ?? '', 20);
$tasks = array_values(array_filter(fetchTasks($userId), static function (array $task) use ($search, $status): bool {
    return ($search === '' || mb_strpos(mb_strtolower($task['title'] . ' ' . $task['description'] . ' ' . $task['project_name']), $search) !== false)
        && ($status === '' || $task['status'] === $status);
}));
jsonResponse(['success' => true, 'tasks' => $tasks]);
