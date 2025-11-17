<?php
require __DIR__ . '/auth.php';
ensure_session_started();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$user = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebGen Auth</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 2rem auto; padding: 1rem; }
        form { border: 1px solid #ccc; padding: 1rem; margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; }
        input[type="text"], input[type="password"] { width: 100%; padding: 0.5rem; margin-bottom: 1rem; }
        .message { padding: 0.75rem 1rem; background: #f1f8ff; border: 1px solid #b6d6ff; margin-bottom: 1rem; }
        .links a { margin-right: 1rem; }
    </style>
</head>
<body>
<h1>Welcome to WebGen</h1>
<?php if ($flash): ?>
    <div class="message"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($user): ?>
    <p>Hello, <strong><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></strong> (<?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?>)</p>
    <div class="links">
        <a href="/dashboard.php">Dashboard</a>
        <a href="/editor.php">Editor</a>
        <a href="/admin.php">Admin</a>
        <a href="/logout.php">Logout</a>
    </div>
<?php else: ?>
    <form action="/login.php" method="post">
        <h2>Login</h2>
        <label for="login-username">Username</label>
        <input type="text" id="login-username" name="username" required>

        <label for="login-password">Password</label>
        <input type="password" id="login-password" name="password" required>

        <button type="submit">Login</button>
    </form>

    <form action="/register.php" method="post">
        <h2>Register</h2>
        <label for="reg-username">Username</label>
        <input type="text" id="reg-username" name="username" required>

        <label for="reg-password">Password (min 8 characters)</label>
        <input type="password" id="reg-password" name="password" minlength="8" required>

        <button type="submit">Create Account</button>
    </form>
<?php endif; ?>
</body>
</html>
