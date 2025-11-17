<?php
require __DIR__ . '/auth.php';
ensure_session_started();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /index.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

try {
    $user = register_user($username, $password);
    $_SESSION['user'] = [
        'username' => $user['username'],
        'role' => $user['role'],
        'owned_pages' => $user['owned_pages'],
    ];
    $_SESSION['flash'] = 'Account created successfully.';
    header('Location: /dashboard.php');
    exit;
} catch (InvalidArgumentException $e) {
    $_SESSION['flash'] = $e->getMessage();
    header('Location: /index.php');
    exit;
}
