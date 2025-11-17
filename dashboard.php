<?php
require __DIR__ . '/auth.php';
require_login();
$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 2rem auto; padding: 1rem; }
        ul { list-style: disc; padding-left: 1.5rem; }
        .links a { margin-right: 1rem; }
    </style>
</head>
<body>
<h1>User Dashboard</h1>
<p>You are logged in as <strong><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></strong> with role <strong><?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?></strong>.</p>

<section>
    <h2>Owned Pages</h2>
    <?php if (!empty($user['owned_pages'])): ?>
        <ul>
            <?php foreach ($user['owned_pages'] as $page): ?>
                <li><?= htmlspecialchars($page, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No pages assigned.</p>
    <?php endif; ?>
</section>

<div class="links">
    <a href="/index.php">Home</a>
    <a href="/editor.php">Editor</a>
    <a href="/admin.php">Admin</a>
    <a href="/logout.php">Logout</a>
</div>
</body>
</html>
