<?php
require __DIR__ . '/auth.php';
require_role(['Admin']);
$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Area</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 2rem auto; padding: 1rem; }
        .links a { margin-right: 1rem; }
    </style>
</head>
<body>
<h1>Admin Tools</h1>
<p>Welcome, <?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?>. You have administrator access.</p>
<p>Use this area to manage editor-only features or review owned pages:</p>
<ul>
    <?php foreach ($user['owned_pages'] as $page): ?>
        <li><?= htmlspecialchars($page, ENT_QUOTES, 'UTF-8') ?></li>
    <?php endforeach; ?>
</ul>
<div class="links">
    <a href="/dashboard.php">Dashboard</a>
    <a href="/editor.php">Editor</a>
    <a href="/logout.php">Logout</a>
</div>
</body>
</html>
