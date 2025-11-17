<?php
require __DIR__ . '/auth.php';
ensure_session_started();

$message = 'You have been logged out.';
$_SESSION = [];
session_destroy();

ensure_session_started();
$_SESSION['flash'] = $message;

header('Location: /index.php');
exit;
