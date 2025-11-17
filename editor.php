<?php
require __DIR__ . '/auth.php';
require_role(['User', 'Admin']);
ensure_session_started();

$user = $_SESSION['user'];
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'delete_site') {
        $slug = trim($_POST['slug'] ?? '');
        $canDelete = $user['role'] === 'Admin' || in_array($slug, $user['owned_pages'] ?? [], true);
        if ($canDelete && delete_site($slug)) {
            $flash = "Site {$slug} removed.";
        } else {
            $flash = 'You cannot delete this site or it does not exist.';
        }
    }
    $user = $_SESSION['user'];
}

$ownedPages = $user['owned_pages'] ?? [];
$allSites = list_generated_sites();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Editor Workspace</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Inter', system-ui, sans-serif; margin: 0; background: #070d1c; color: #e2e8f0; }
        header { padding: 24px; background: linear-gradient(135deg, rgba(0,188,212,0.3), rgba(139,92,246,0.3)); border-bottom: 1px solid #1f2937; }
        h1 { margin: 0; }
        .nav { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 8px; }
        .pill { display: inline-flex; align-items: center; gap: 6px; padding: 10px 12px; border-radius: 10px; background: rgba(255,255,255,0.08); text-decoration: none; color: #e2e8f0; border: 1px solid #1f2937; }
        .container { max-width: 1100px; margin: 0 auto; padding: 20px; display: grid; gap: 16px; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); }
        .card { background: #0f172a; border: 1px solid #1f2937; border-radius: 12px; padding: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .card h2 { margin-top: 0; color: #22d3ee; }
        .row { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; justify-content: space-between; }
        .button { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 14px; border-radius: 10px; border: none; cursor: pointer; font-weight: 700; text-decoration: none; }
        .primary { background: linear-gradient(135deg, #22d3ee, #8b5cf6); color: #050815; box-shadow: 0 10px 30px rgba(34,211,238,0.35); }
        .secondary { background: #1e293b; color: #e2e8f0; border: 1px solid #334155; }
        .danger { background: #7f1d1d; color: #fee2e2; border: 1px solid #b91c1c; }
        ul { padding-left: 18px; }
        .message { padding: 12px; border-radius: 10px; background: rgba(34,211,238,0.15); border: 1px solid #22d3ee; color: #e2e8f0; margin: 10px 0; }
        .small-note { color: #94a3b8; font-size: 13px; }
        .tag { display: inline-block; padding: 4px 8px; border-radius: 999px; background: rgba(34,211,238,0.14); border: 1px solid #22d3ee; color: #e0f2fe; font-size: 12px; }
    </style>
</head>
<body>
<header>
    <div class="row" style="justify-content: space-between;">
        <div>
            <h1>Editor Workspace</h1>
            <p class="small-note">Signed in as <strong><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></strong> · <?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <span class="tag">Editable sites: <?= count($ownedPages) ?></span>
    </div>
    <div class="nav">
        <a class="pill" href="/index.php">🏠 Generator</a>
        <a class="pill" href="/dashboard.php">📂 Dashboard</a>
        <?php if ($user['role'] === 'Admin'): ?><a class="pill" href="/admin.php">🛡️ Admin</a><?php endif; ?>
        <a class="pill" href="/logout.php">🚪 Logout</a>
    </div>
    <?php if ($flash): ?><div class="message"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
</header>

<div class="container">
    <div class="card">
        <h2>Owned pages</h2>
        <p class="small-note">Access or clean up the sites you created.</p>
        <?php if (empty($ownedPages)): ?>
            <p class="small-note">No pages yet.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($ownedPages as $slug): ?>
                    <li class="row">
                        <a class="pill" href="/<?= htmlspecialchars($slug) ?>/" target="_blank" rel="noopener">🌐 <?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?></a>
                        <form method="post" onsubmit="return confirm('Delete site <?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?> ?');">
                            <input type="hidden" name="slug" value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>">
                            <button class="button danger" type="submit" name="action" value="delete_site">🗑️ Delete</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <a class="button primary" href="/index.php">✨ Create new page</a>
    </div>

    <div class="card">
        <h2>All sites</h2>
        <p class="small-note">Browse the generated pages on this instance.</p>
        <?php if (empty($allSites)): ?>
            <p class="small-note">No generated pages found.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($allSites as $slug): ?>
                    <li class="row">
                        <a class="pill" href="/<?= htmlspecialchars($slug) ?>/" target="_blank" rel="noopener">🌐 <?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?></a>
                        <?php if ($user['role'] === 'Admin'): ?>
                            <form method="post" onsubmit="return confirm('Delete site <?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?> ?');">
                                <input type="hidden" name="slug" value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>">
                                <button class="button danger" type="submit" name="action" value="delete_site">🗑️ Delete</button>
                            </form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
