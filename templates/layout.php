<?php
$pageTitle = $title ?? 'Webgen';
$user = $user ?? null;
$footerLinks = sorted_footer_links($data ?? []);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo sanitize($pageTitle); ?> | Webgen</title>
    <link rel="stylesheet" href="static/styles.css" />
</head>
<body>
    <header class="topbar">
        <div class="brand">Webgen</div>
        <nav class="nav-links">
            <?php if ($user): ?>
                <a href="?route=pages">Meine Seiten</a>
                <a href="?route=page_new">Neue Seite</a>
                <?php if ($user['role'] === 'admin'): ?>
                    <a href="?route=admin_dashboard">Admin</a>
                <?php endif; ?>
                <a href="?route=logout">Logout (<?php echo sanitize($user['email']); ?>)</a>
            <?php else: ?>
                <a href="?route=login">Login</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="content">
        <?php include __DIR__ . '/' . $template_file; ?>
    </main>

    <footer class="footer">
        <div class="footer-text"><?php echo sanitize(footer_text($data ?? [], $page['title'] ?? ($title ?? 'Webgen'))); ?></div>
        <?php if ($footerLinks): ?>
            <ul class="footer-links">
                <?php foreach ($footerLinks as $link): ?>
                    <li><a href="<?php echo sanitize($link['url']); ?>" target="_blank" rel="noopener"><?php echo sanitize($link['label']); ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </footer>
</body>
</html>
