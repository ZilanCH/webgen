<?php
require __DIR__ . '/auth.php';
require_role(['Admin']);
ensure_session_started();

$user = $_SESSION['user'];
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        switch ($action) {
            case 'create_user':
                $username = trim($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                $role = $_POST['role'] ?? 'User';
                register_user($username, $password, [], $role);
                $flash = "User {$username} created.";
                break;
            case 'update_role':
                $target = trim($_POST['username'] ?? '');
                $role = $_POST['role'] ?? 'User';
                if (set_user_role($target, $role)) {
                    $flash = "Role for {$target} updated to {$role}.";
                } else {
                    $flash = "User {$target} not found.";
                }
                break;
            case 'reset_password':
                $target = trim($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                reset_user_password($target, $password);
                $flash = "Password for {$target} reset.";
                break;
            case 'delete_user':
                $target = trim($_POST['username'] ?? '');
                if (strcasecmp($target, $user['username']) === 0) {
                    $flash = 'You cannot delete your own admin account.';
                } elseif (delete_user_record($target)) {
                    $flash = "User {$target} deleted.";
                } else {
                    $flash = "User {$target} not found.";
                }
                break;
            case 'delete_site':
                $slug = trim($_POST['slug'] ?? '');
                if (delete_site($slug)) {
                    $flash = "Site {$slug} removed.";
                } else {
                    $flash = 'Unable to delete requested site.';
                }
                break;
        }
    } catch (Throwable $e) {
        $flash = $e->getMessage();
    }
}

$users = load_users();
$sites = list_generated_sites();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Area</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Inter', system-ui, sans-serif; margin: 0; background: #0b1224; color: #e2e8f0; }
        header { padding: 24px; background: linear-gradient(135deg, rgba(0,188,212,0.25), rgba(139,92,246,0.25)); border-bottom: 1px solid #1e293b; }
        h1 { margin: 0; }
        .container { max-width: 1100px; margin: 0 auto; padding: 20px; display: grid; gap: 16px; grid-template-columns: 2fr 1fr; align-items: start; }
        .card { background: #0f172a; border: 1px solid #1f2937; border-radius: 12px; padding: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .card h2 { margin-top: 0; color: #22d3ee; }
        label { display: block; margin-top: 10px; font-weight: 600; }
        input, select { width: 100%; padding: 10px; border-radius: 10px; border: 1px solid #1f2937; background: #111827; color: #e2e8f0; margin-top: 6px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px; }
        .pill { display: inline-flex; align-items: center; gap: 6px; padding: 8px 12px; border-radius: 10px; background: rgba(255,255,255,0.08); text-decoration: none; color: #e2e8f0; border: 1px solid #1f2937; }
        .button { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 14px; border-radius: 10px; border: none; cursor: pointer; font-weight: 700; text-decoration: none; }
        .primary { background: linear-gradient(135deg, #22d3ee, #8b5cf6); color: #050815; box-shadow: 0 10px 30px rgba(34,211,238,0.35); width: 100%; }
        .secondary { background: #1e293b; color: #e2e8f0; border: 1px solid #334155; }
        .danger { background: #7f1d1d; color: #fee2e2; border: 1px solid #b91c1c; }
        .tag { display: inline-block; padding: 4px 8px; border-radius: 999px; background: rgba(34,211,238,0.14); border: 1px solid #22d3ee; color: #e0f2fe; font-size: 12px; }
        .row { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        .message { padding: 12px; border-radius: 10px; background: rgba(34,211,238,0.15); border: 1px solid #22d3ee; color: #e2e8f0; margin: 10px 0; }
        .small-note { color: #94a3b8; font-size: 13px; margin: 6px 0 0; }
        ul { padding-left: 18px; }
    </style>
</head>
<body>
<header>
    <div class="row">
        <h1>Admin Control Center</h1>
        <span class="tag">Signed in as <?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="row" style="margin-top: 8px;">
        <a class="pill" href="/dashboard.php">📂 Dashboard</a>
        <a class="pill" href="/editor.php">🛠️ Editor</a>
        <a class="pill" href="/index.php">🏠 Generator</a>
        <a class="pill" href="/logout.php">🚪 Logout</a>
    </div>
    <?php if ($flash): ?><div class="message"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
</header>

<div class="container">
    <div class="card">
        <h2>User Management</h2>
        <form method="post" class="grid">
            <input type="hidden" name="action" value="create_user">
            <label>Username<input type="text" name="username" required></label>
            <label>Password<input type="password" name="password" minlength="8" required></label>
            <label>Role
                <select name="role">
                    <option value="User">User</option>
                    <option value="Admin">Admin</option>
                </select>
            </label>
            <button type="submit" class="button primary">➕ Create user</button>
        </form>

        <h3>Existing users</h3>
        <div class="grid">
            <?php foreach ($users as $u): ?>
                <div class="card" style="border-color:#1f2937;">
                    <div class="row" style="justify-content: space-between;">
                        <strong><?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span class="tag"><?= htmlspecialchars($u['role'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <?php if (!empty($u['owned_pages'])): ?>
                        <p class="small-note">Sites: <?= htmlspecialchars(implode(', ', $u['owned_pages']), ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                    <form method="post" class="row">
                        <input type="hidden" name="username" value="<?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?>">
                        <select name="role" class="secondary">
                            <option value="User" <?= ($u['role'] ?? 'User') === 'User' ? 'selected' : '' ?>>User</option>
                            <option value="Admin" <?= ($u['role'] ?? 'User') === 'Admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                        <button class="button secondary" type="submit" name="action" value="update_role">💼 Update role</button>
                    </form>
                    <form method="post" class="row" style="margin-top:8px;">
                        <input type="hidden" name="username" value="<?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="password" name="password" placeholder="New password" minlength="8" required>
                        <button class="button secondary" type="submit" name="action" value="reset_password">🔒 Reset password</button>
                    </form>
                    <form method="post" class="row" style="margin-top:8px;">
                        <input type="hidden" name="username" value="<?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?>">
                        <button class="button danger" type="submit" name="action" value="delete_user">🗑️ Delete user</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <h2>Generated Sites</h2>
        <?php if (empty($sites)): ?>
            <p>No generated pages yet.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($sites as $slug): ?>
                    <li class="row" style="justify-content: space-between;">
                        <a class="pill" href="/<?= htmlspecialchars($slug) ?>/" target="_blank" rel="noopener">🌐 <?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?></a>
                        <form method="post" onsubmit="return confirm('Delete site <?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?> ?');">
                            <input type="hidden" name="slug" value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>">
                            <button class="button danger" type="submit" name="action" value="delete_site">🗑️ Delete</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
