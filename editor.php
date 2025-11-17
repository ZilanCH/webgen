<?php
require __DIR__ . '/auth.php';
require_role(['User', 'Admin']);
$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Editor Area</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 2rem auto; padding: 1rem; }
        .links a { margin-right: 1rem; }
    </style>
</head>
<body>
<h1>Editor Workspace</h1>
<p>Only logged-in editors or administrators can access this area.</p>
<p>Signed in as <strong><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></strong> with role <strong><?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?></strong>.</p>

<div class="links">
    <a href="/dashboard.php">Dashboard</a>
    <a href="/admin.php">Admin</a>
    <a href="/logout.php">Logout</a>
</div>
</body>
</html>
