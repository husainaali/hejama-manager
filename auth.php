<?php
// auth.php — Include at the top of any protected PHP page.
// Usage: require_once 'auth.php'; OR require_once __DIR__ . '/../auth.php';
// Then call: requireRole(['super_admin','reception']) etc.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getCurrentUser(): ?array {
    if (isset($_SESSION['user_id'])) {
        return [
            'id'        => $_SESSION['user_id'],
            'username'  => $_SESSION['username'],
            'full_name' => $_SESSION['full_name'],
            'role'      => $_SESSION['role'],
            'specialist_id' => $_SESSION['specialist_id'] ?? null,
        ];
    }
    return null;
}

function requireAuth(): array {
    $user = getCurrentUser();
    if (!$user) {
        header('Location: login.html');
        exit;
    }
    return $user;
}

function requireRole(array $roles): array {
    $user = requireAuth();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
    return $user;
}
?>
