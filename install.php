<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

// The installer creates a known demo account; it must not be reachable remotely.
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Run the installer from localhost only.');
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $server = database(true);
        $server->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        $pdo = database();
        $sql = file_get_contents(__DIR__ . '/database.sql');
        if ($sql === false) {
            throw new RuntimeException('database.sql could not be read.');
        }
        $pdo->exec($sql);

        $pdo->exec("ALTER TABLE users MODIFY role ENUM('admin','manager','member','guest') NOT NULL DEFAULT 'member'");
        $userColumns = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('status', $userColumns, true)) {
            $pdo->exec("ALTER TABLE users ADD status ENUM('active','disabled') NOT NULL DEFAULT 'active' AFTER role");
        }
        if (!in_array('last_login_at', $userColumns, true)) {
            $pdo->exec('ALTER TABLE users ADD last_login_at DATETIME NULL AFTER status');
        }

        $projectColumns = $pdo->query('SHOW COLUMNS FROM projects')->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('description', $projectColumns, true)) {
            $pdo->exec('ALTER TABLE projects ADD description VARCHAR(500) NULL AFTER name');
        }
        if (!in_array('status', $projectColumns, true)) {
            $pdo->exec("ALTER TABLE projects ADD status ENUM('active','complete','archived') NOT NULL DEFAULT 'active' AFTER color");
        }
        if (!in_array('deadline', $projectColumns, true)) {
            $pdo->exec('ALTER TABLE projects ADD deadline DATE NULL AFTER status');
        }
        $taskColumns = $pdo->query('SHOW COLUMNS FROM tasks')->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('completed_at', $taskColumns, true)) {
            $pdo->exec('ALTER TABLE tasks ADD completed_at DATETIME NULL AFTER updated_at');
            $pdo->exec("UPDATE tasks SET completed_at = updated_at WHERE status = 'done'");
        }

        if (!in_array('approved_by', $taskColumns, true)) $pdo->exec('ALTER TABLE tasks ADD approved_by INT UNSIGNED NULL');
        if (!in_array('visibility', $taskColumns, true)) {
            $pdo->exec("ALTER TABLE tasks ADD visibility ENUM('project','assignees') NOT NULL DEFAULT 'project'");
        }
        if (!in_array('team_id', $taskColumns, true)) $pdo->exec('ALTER TABLE tasks ADD team_id INT UNSIGNED NULL');
        $memberColumns = $pdo->query('SHOW COLUMNS FROM project_members')->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('project_role', $memberColumns, true)) $pdo->exec("ALTER TABLE project_members ADD project_role ENUM('member','manager') NOT NULL DEFAULT 'member'");

        $pdo->exec("INSERT IGNORE INTO workspace_settings (setting_key, setting_value) VALUES
          ('require_2fa', '0'),
          ('retention_days', '365'),
          ('allow_guest_access', '1')");

        $email = 'arafat.sarker@gmail.com';
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $check->execute([$email]);

        if (!$check->fetch()) {
            $insert = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
            $insert->execute(['Arafat Sarker', $email, password_hash('taskflow2026', PASSWORD_DEFAULT), 'admin']);
            $userId = (int) $pdo->lastInsertId();

            $project = $pdo->prepare('INSERT INTO projects (name, color, owner_id) VALUES (?, ?, ?)');
            $project->execute(['Website Redesign', '#0073EA', $userId]);
            $projectId = (int) $pdo->lastInsertId();

            $seed = $pdo->prepare('INSERT INTO tasks (title, project_id, status, priority, due_date, description, assignee_id, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $tasks = [
                ['Homepage wireframe', 'todo', 'high', date('Y-m-d'), 'Create responsive desktop and mobile wireframes.'],
                ['API integration', 'progress', 'medium', date('Y-m-d', strtotime('+2 days')), 'Connect the product interface with backend services.'],
                ['Design review', 'done', 'low', date('Y-m-d', strtotime('+4 days')), 'Review spacing, typography, and component consistency.'],
            ];
            foreach ($tasks as $task) {
                $seed->execute([$task[0], $projectId, $task[1], $task[2], $task[3], $task[4], $userId, $userId]);
            }
            $pdo->prepare("UPDATE tasks SET completed_at = NOW() WHERE project_id = ? AND status = 'done' AND completed_at IS NULL")->execute([$projectId]);

            $audit = $pdo->prepare('INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)');
            $audit->execute([$userId, 'Workspace installed', 'TaskFlow database and administrator account created.', $_SERVER['REMOTE_ADDR'] ?? null]);
        }

        $migrated = $pdo->query("SELECT setting_value FROM workspace_settings WHERE setting_key = 'relations_v2'")->fetchColumn();
        if (!$migrated) {
            $pdo->beginTransaction();
            $pdo->exec('INSERT IGNORE INTO task_assignees (task_id, user_id) SELECT id, assignee_id FROM tasks WHERE assignee_id IS NOT NULL');
            $pdo->exec('INSERT IGNORE INTO project_members (project_id, user_id) SELECT t.project_id, t.assignee_id FROM tasks t JOIN projects p ON p.id = t.project_id WHERE t.assignee_id IS NOT NULL AND t.assignee_id <> p.owner_id');
            $pdo->exec("INSERT INTO workspace_settings (setting_key, setting_value) VALUES ('relations_v2', '1')");
            $pdo->commit();
        }
        $message = 'Installation completed. You can now sign in.';
    } catch (Throwable $exception) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        $error = 'Installation failed: ' . $exception->getMessage();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Install TaskFlow</title>
  <style>
    body{font-family:Inter,Arial,sans-serif;background:#f5f7fb;margin:0;display:grid;place-items:center;min-height:100vh;color:#172033}
    main{width:min(560px,calc(100% - 40px));background:#fff;border:1px solid #e3e8f0;border-radius:18px;padding:32px;box-shadow:0 18px 60px rgba(23,32,51,.1)}
    h1{margin-top:0}.notice{padding:12px 14px;border-radius:10px;margin:16px 0}.ok{background:#e8fbf3;color:#087849}.error{background:#ffebef;color:#b4233d}
    button,a{display:inline-block;border:0;border-radius:9px;padding:12px 18px;background:#0073ea;color:#fff;text-decoration:none;font-weight:700;cursor:pointer}
    code{background:#eef2f7;padding:2px 6px;border-radius:5px}
  </style>
</head>
<body><main>
  <h1>TaskFlow setup</h1>
  <p>Make sure Apache and MySQL are running in XAMPP, then install the database.</p>
  <?php if ($message): ?><div class="notice ok"><?= htmlspecialchars($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="notice error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($message): ?>
    <p>Email: <code>arafat.sarker@gmail.com</code><br>Password: <code>taskflow2026</code></p>
    <a href="index.php">Open TaskFlow</a>
  <?php else: ?>
    <form method="post"><button type="submit">Install database</button></form>
  <?php endif; ?>
</main></body></html>
