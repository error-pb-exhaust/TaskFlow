<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/database.php';

function jsonResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function requestData(): array
{
    $type = $_SERVER['CONTENT_TYPE'] ?? '';
    if (str_contains($type, 'application/json')) {
        $data = json_decode(file_get_contents('php://input') ?: '{}', true);
        return is_array($data) ? $data : [];
    }
    return $_POST;
}

function requireMethod(string $method): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== strtoupper($method)) {
        jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
    }
    if (strtoupper($method) === 'POST' && !hash_equals($_SESSION['csrf_token'], $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
        jsonResponse(['success' => false, 'message' => 'Session verification failed. Refresh the page and try again.'], 403);
    }
}

function currentUserId(): int
{
    if (empty($_SESSION['user_id'])) {
        jsonResponse(['success' => false, 'message' => 'Please sign in first.'], 401);
    }
    $stmt = database()->prepare("SELECT id FROM users WHERE id = ? AND status = 'active' LIMIT 1");
    $stmt->execute([(int) $_SESSION['user_id']]);
    if (!$stmt->fetchColumn()) {
        $_SESSION = [];
        jsonResponse(['success' => false, 'message' => 'Your account is no longer active. Please sign in again.'], 401);
    }
    return (int) $_SESSION['user_id'];
}

function requireAdmin(): array
{
    $userId = currentUserId();
    $stmt = database()->prepare('SELECT id, name, email, role, status FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || $user['status'] !== 'active' || $user['role'] !== 'admin') {
        jsonResponse(['success' => false, 'message' => 'Administrator access is required.'], 403);
    }
    return $user;
}

function requireWritableAccount(int $userId): void
{
    $stmt = database()->prepare('SELECT role FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    if ($stmt->fetchColumn() === 'guest') {
        jsonResponse(['success' => false, 'message' => 'Guest accounts have read-only access.'], 403);
    }
}

function writeAuditLog(int $userId, string $action, string $details): void
{
    $stmt = database()->prepare('INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)');
    $stmt->execute([$userId, cleanString($action, 100), cleanString($details, 500), $_SERVER['REMOTE_ADDR'] ?? null]);
}

function createNotification(int $userId, string $message, ?int $taskId = null, string $view = 'tasks'): void
{
    $stmt = database()->prepare('INSERT INTO notifications (user_id, message, link_view, related_task_id) VALUES (?, ?, ?, ?)');
    $stmt->execute([$userId, cleanString($message, 500), cleanString($view, 50), $taskId]);
}

function cleanString(mixed $value, int $maxLength = 255): string
{
    return mb_substr(trim((string) $value), 0, $maxLength);
}
