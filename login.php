<?php
require __DIR__ . '/auth.php';
ensure_session_started();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /index.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

$user = authenticate_user($username, $password);
if ($user === null) {
    $_SESSION['flash'] = 'Invalid username or password.';
    header('Location: /index.php');
    exit;
}

$_SESSION['user'] = $user;
$_SESSION['flash'] = 'Welcome back, ' . htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') . '!';
header('Location: /dashboard.php');
exit;
