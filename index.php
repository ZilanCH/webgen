<?php
require __DIR__ . '/auth.php';
ensure_session_started();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$user = $_SESSION['user'] ?? null;

if (!$user && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['flash'] = 'Bitte zuerst einloggen, um Seiten zu erstellen oder zu sehen.';
    header('Location: /index.php');
    exit;
}

$templates = [
    'portfolio' => [
        'label' => 'Portfolio',
        'description' => 'Showcase your projects and background.',
        'fields' => [
            ['name' => 'portfolio_about', 'label' => 'About blurb', 'type' => 'textarea', 'placeholder' => 'Short introduction.'],
            ['name' => 'portfolio_projects', 'label' => 'Projects (one per line as Title | Description | Link)', 'type' => 'textarea', 'placeholder' => 'Project One | What it does | https://example.com'],
        ],
    ],
    'contact' => [
        'label' => 'Contact',
        'description' => 'Contact details and quick call-to-actions.',
        'fields' => [
            ['name' => 'contact_email', 'label' => 'Contact email', 'type' => 'text'],
            ['name' => 'contact_phone', 'label' => 'Phone number', 'type' => 'text'],
            ['name' => 'contact_address', 'label' => 'Address', 'type' => 'textarea'],
            ['name' => 'contact_message', 'label' => 'Headline message', 'type' => 'text'],
        ],
    ],
    'imprint_privacy' => [
        'label' => 'Imprint / Privacy',
        'description' => 'Display legal imprint and privacy notice.',
        'fields' => [
            ['name' => 'imprint_body', 'label' => 'Imprint content', 'type' => 'textarea'],
            ['name' => 'privacy_body', 'label' => 'Privacy policy', 'type' => 'textarea'],
        ],
    ],
    'product' => [
        'label' => 'Product',
        'description' => 'Highlight a product with features and pricing.',
        'fields' => [
            ['name' => 'product_name', 'label' => 'Product name', 'type' => 'text'],
            ['name' => 'product_description', 'label' => 'Description', 'type' => 'textarea'],
            ['name' => 'product_features', 'label' => 'Features (one per line)', 'type' => 'textarea'],
            ['name' => 'product_price', 'label' => 'Price display', 'type' => 'text'],
        ],
    ],
    'pricing' => [
        'label' => 'Pricing',
        'description' => 'List multiple pricing plans.',
        'fields' => [
            ['name' => 'pricing_plans', 'label' => 'Plans (one per line as Name | Price | Features separated by ; )', 'type' => 'textarea'],
            ['name' => 'pricing_cta', 'label' => 'Shared call-to-action', 'type' => 'text'],
        ],
    ],
    'about' => [
        'label' => 'About',
        'description' => 'Simple about page with highlights.',
        'fields' => [
            ['name' => 'about_story', 'label' => 'Story', 'type' => 'textarea'],
            ['name' => 'about_highlights', 'label' => 'Highlights (one per line)', 'type' => 'textarea'],
        ],
    ],
];

function sanitize_text(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function sanitize_slug(string $slug): string
{
    $slug = strtolower(trim($slug));
    $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
    return trim($slug, '-') ?: 'site';
}

function gather_buttons(): array
{
    $buttons = [];
    $labels = $_POST['button_label'] ?? [];
    $urls = $_POST['button_url'] ?? [];
    $colors = $_POST['button_color'] ?? [];
    foreach ($labels as $index => $label) {
        $label = sanitize_text($label);
        $url = sanitize_text($urls[$index] ?? '');
        if ($label === '' && $url === '') {
            continue;
        }
        $buttons[] = [
            'label' => $label,
            'url' => $url,
            'color' => sanitize_text($colors[$index] ?? ''),
        ];
    }
    return $buttons;
}

function gather_social(): array
{
    $social = [];
    $email = sanitize_text($_POST['social_email'] ?? '');
    $discord = sanitize_text($_POST['social_discord'] ?? '');
    if ($email !== '') {
        $social[] = ['label' => 'Email', 'url' => 'mailto:' . $email];
    }
    if ($discord !== '') {
        $social[] = ['label' => 'Discord', 'url' => $discord];
    }
    return $social;
}

function parse_lines(string $input): array
{
    $lines = preg_split('/\r?\n/', trim($input));
    return array_values(array_filter(array_map('trim', $lines), fn($line) => $line !== ''));
}

function build_template_content(string $template, array $data): string
{
    switch ($template) {
        case 'portfolio':
            $projects = [];
            foreach (parse_lines($data['portfolio_projects'] ?? '') as $line) {
                [$title, $desc, $link] = array_pad(array_map('trim', explode('|', $line)), 3, '');
                $projects[] = ['title' => sanitize_text($title), 'desc' => sanitize_text($desc), 'link' => sanitize_text($link)];
            }
            $about = sanitize_text($data['portfolio_about'] ?? '');
            $projectMarkup = '';
            foreach ($projects as $project) {
                $linkPart = $project['link'] ? '<a href="' . $project['link'] . '" target="_blank" rel="noopener">Visit</a>' : '';
                $projectMarkup .= "<div class='card'><h3>{$project['title']}</h3><p>{$project['desc']}</p>{$linkPart}</div>";
            }
            return "<section><h2>About</h2><p>{$about}</p></section><section><h2>Projects</h2><div class='grid'>{$projectMarkup}</div></section>";
        case 'contact':
            $email = sanitize_text($data['contact_email'] ?? '');
            $phone = sanitize_text($data['contact_phone'] ?? '');
            $address = sanitize_text($data['contact_address'] ?? '');
            $headline = sanitize_text($data['contact_message'] ?? '');
            $contactLines = '';
            if ($email) {
                $contactLines .= "<p><strong>Email:</strong> <a href='mailto:{$email}'>{$email}</a></p>";
            }
            if ($phone) {
                $contactLines .= "<p><strong>Phone:</strong> {$phone}</p>";
            }
            if ($address) {
                $contactLines .= "<p><strong>Address:</strong> {$address}</p>";
            }
            return "<section><h2>{$headline}</h2>{$contactLines}<p>We will get back to you quickly.</p></section>";
        case 'imprint_privacy':
            $imprint = nl2br(sanitize_text($data['imprint_body'] ?? ''));
            $privacy = nl2br(sanitize_text($data['privacy_body'] ?? ''));
            return "<section><h2>Imprint</h2><p>{$imprint}</p></section><section><h2>Privacy Policy</h2><p>{$privacy}</p></section>";
        case 'product':
            $name = sanitize_text($data['product_name'] ?? '');
            $description = sanitize_text($data['product_description'] ?? '');
            $features = parse_lines($data['product_features'] ?? '');
            $price = sanitize_text($data['product_price'] ?? '');
            $featureList = '';
            foreach ($features as $feature) {
                $featureList .= '<li>' . sanitize_text($feature) . '</li>';
            }
            return "<section class='hero'><h2>{$name}</h2><p>{$description}</p><p class='price'>{$price}</p></section><section><h3>Features</h3><ul>{$featureList}</ul></section>";
        case 'pricing':
            $plans = [];
            foreach (parse_lines($data['pricing_plans'] ?? '') as $line) {
                [$plan, $price, $features] = array_pad(array_map('trim', explode('|', $line)), 3, '');
                $plans[] = [
                    'plan' => sanitize_text($plan),
                    'price' => sanitize_text($price),
                    'features' => array_filter(array_map('trim', explode(';', $features)))
                ];
            }
            $cta = sanitize_text($data['pricing_cta'] ?? '');
            $planMarkup = '';
            foreach ($plans as $plan) {
                $featureItems = '';
                foreach ($plan['features'] as $f) {
                    $featureItems .= '<li>' . sanitize_text($f) . '</li>';
                }
                $planMarkup .= "<div class='card'><h3>{$plan['plan']}</h3><p class='price'>{$plan['price']}</p><ul>{$featureItems}</ul></div>";
            }
            return "<section><h2>Plans</h2><div class='grid'>{$planMarkup}</div><p class='cta'>{$cta}</p></section>";
        case 'about':
        default:
            $story = sanitize_text($data['about_story'] ?? '');
            $highlights = parse_lines($data['about_highlights'] ?? '');
            $highlightList = '';
            foreach ($highlights as $hl) {
                $highlightList .= '<li>' . sanitize_text($hl) . '</li>';
            }
            return "<section><h2>About</h2><p>{$story}</p><ul>{$highlightList}</ul></section>";
    }
}

function build_site_html(array $data, string $template, array $buttons, array $social, string $logoPath, string $favicon): string
{
    $primary = sanitize_text($data['primary_color'] ?? '#00bcd4');
    $secondary = sanitize_text($data['secondary_color'] ?? '#8b5cf6');
    $name = sanitize_text($data['name'] ?? 'Zilan Webgen');
    $title = sanitize_text($data['title'] ?? 'Title');
    $subtitle = sanitize_text($data['subtitle'] ?? 'Subtitle');

    $buttonMarkup = '';
    foreach ($buttons as $button) {
        $color = $button['color'] ?: $primary;
        $buttonMarkup .= "<a class='btn' style='background: {$color}' href='{$button['url']}' target='_blank' rel='noopener'>{$button['label']}</a>";
    }

    $socialMarkup = '';
    foreach ($social as $item) {
        $socialMarkup .= "<a href='{$item['url']}' target='_blank' rel='noopener'>{$item['label']}</a>";
    }

    $logoImg = $logoPath ? "<img class='logo' src='{$logoPath}' alt='Logo'>" : '';
    $faviconLink = $favicon ? "<link rel='icon' href='{$favicon}'>" : '';

    $templateContent = build_template_content($template, $data);

    return <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    {$faviconLink}
    <style>
        :root { --primary: {$primary}; --secondary: {$secondary}; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #0b1020; color: #e2e8f0; }
        header { background: radial-gradient(circle at 20% 20%, rgba(0,188,212,0.18), transparent 35%), radial-gradient(circle at 80% 0%, rgba(139,92,246,0.18), transparent 35%), #0b1020; color: #fff; padding: 32px 24px; text-align: center; }
        header h1 { margin: 8px 0; }
        header p { margin: 0; opacity: 0.9; }
        main { padding: 24px; max-width: 900px; margin: 0 auto; }
        section { background: #0f172a; border-radius: 12px; padding: 20px; margin-bottom: 16px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.35); border: 1px solid #1e293b; }
        h2 { margin-top: 0; color: var(--primary); }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; }
        .card { background: #0b1224; border: 1px solid #1e293b; border-radius: 10px; padding: 16px; }
        .btn { display: inline-block; margin: 6px 6px 0 0; padding: 10px 16px; border-radius: 8px; color: #fff; text-decoration: none; font-weight: 700; box-shadow: 0 10px 20px rgba(0,0,0,0.2); }
        .social { margin-top: 10px; display: flex; gap: 12px; flex-wrap: wrap; justify-content: center; }
        .logo { max-height: 60px; margin-bottom: 8px; }
        .price { font-size: 22px; color: var(--secondary); font-weight: bold; }
        ul { padding-left: 20px; }
        .cta { font-weight: bold; color: var(--primary); text-align: center; }
        .hero { text-align: center; }
        .actions { margin-top: 10px; display: flex; justify-content: center; gap: 10px; flex-wrap: wrap; }
        .badge { display: inline-block; padding: 6px 10px; border-radius: 999px; background: rgba(255,255,255,0.08); color: #e2e8f0; font-size: 12px; text-transform: uppercase; letter-spacing: 0.08em; }
        .social a { color: #e2e8f0; padding: 6px 10px; border-radius: 8px; background: rgba(255,255,255,0.06); text-decoration: none; }
    </style>
</head>
<body>
    <header>
        {$logoImg}
        <p class="eyebrow">{$name}</p>
        <h1>{$title}</h1>
        <p>{$subtitle}</p>
        <div class="actions">{$buttonMarkup}</div>
        <div class="social">{$socialMarkup}</div>
    </header>
    <main>
        {$templateContent}
    </main>
</body>
</html>
HTML;
}

function handle_logo_upload(string $slug): string
{
    if (!isset($_FILES['logo_file']) || !is_uploaded_file($_FILES['logo_file']['tmp_name'])) {
        return '';
    }
    $targetDir = __DIR__ . '/' . $slug . '/assets';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    $original = basename($_FILES['logo_file']['name']);
    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $original);
    $targetPath = $targetDir . '/' . $safeName;
    if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $targetPath)) {
        return './assets/' . $safeName;
    }
    return '';
}

function build_logo_for_preview(): string
{
    if (!isset($_FILES['logo_file']) || !is_uploaded_file($_FILES['logo_file']['tmp_name'])) {
        return '';
    }
    $mime = mime_content_type($_FILES['logo_file']['tmp_name']);
    $data = base64_encode(file_get_contents($_FILES['logo_file']['tmp_name']));
    return "data:{$mime};base64,{$data}";
}

$message = '';
$previewHtml = '';

if ($user && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = array_map(fn($value) => is_string($value) ? trim($value) : $value, $_POST);
    $template = $_POST['template'] ?? 'about';
    $buttons = gather_buttons();
    $social = gather_social();
    $slug = sanitize_slug($data['slug'] ?? 'site');

    $logoPath = '';
    $favicon = sanitize_text($data['favicon'] ?? '');
    $logoUrl = sanitize_text($data['logo_url'] ?? '');

    $action = $_POST['action'] ?? 'preview';

    if ($action === 'generate') {
        $targetDir = __DIR__ . '/' . $slug;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $logoPath = handle_logo_upload($slug);
        if (!$logoPath && $logoUrl) {
            $logoPath = $logoUrl;
        }
        $html = build_site_html($data, $template, $buttons, $social, $logoPath, $favicon);
        file_put_contents($targetDir . '/index.php', $html);
        add_owned_page_to_user($user['username'], $slug);
        $user = $_SESSION['user'];
        $message = "Generated site at ./{$slug}/index.php";
        $previewHtml = $html;
    } else {
        $logoPath = build_logo_for_preview();
        if (!$logoPath && $logoUrl) {
            $logoPath = $logoUrl;
        }
        $previewHtml = build_site_html($data, $template, $buttons, $social, $logoPath, $favicon);
        $message = 'Preview updated. Use "Generate Site" to write files.';
    }
} else {
    $data = [
        'primary_color' => '#00bcd4',
        'secondary_color' => '#8b5cf6',
        'template' => 'portfolio',
        'name' => 'Zilan Webgen',
        'title' => 'Create your site',
        'subtitle' => 'Cyan & Violet powered builder',
    ];
    $template = 'portfolio';
    $buttons = [];
    $social = [];
    $slug = '';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zilan Webgen</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; margin: 0; background: #050815; color: #e2e8f0; }
        header.hero { padding: 28px 22px; background: linear-gradient(135deg, rgba(0,188,212,0.35), rgba(139,92,246,0.35)); border-bottom: 1px solid #1f2937; }
        h1 { margin: 0; }
        .wrapper { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; padding: 20px; align-items: start; }
        form { background: #0f172a; border: 1px solid #1f2937; border-radius: 12px; padding: 18px; }
        label { display: block; margin-top: 12px; font-weight: 600; }
        input[type=text], input[type=url], input[type=color], input[type=password], textarea, select { width: 100%; padding: 10px; margin-top: 6px; border-radius: 8px; border: 1px solid #1f2937; background: #111827; color: #e2e8f0; }
        textarea { min-height: 80px; }
        .field-group { display: flex; gap: 10px; }
        .field-group > div { flex: 1; }
        .small-note { font-size: 12px; color: #94a3b8; }
        button { margin-top: 14px; padding: 12px 16px; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; }
        .primary { background: linear-gradient(135deg, #22d3ee, #8b5cf6); color: #050815; box-shadow: 0 12px 30px rgba(34,211,238,0.25); }
        .secondary { background: #1e293b; color: #fff; margin-left: 8px; border: 1px solid #334155; }
        .tertiary { background: transparent; color: #22d3ee; border: 1px solid #22d3ee; }
        .template-fields { margin-top: 12px; border: 1px dashed #334155; padding: 12px; border-radius: 10px; }
        .section-title { margin-top: 20px; border-bottom: 1px solid #1f2937; padding-bottom: 8px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.1em; color: #94a3b8; }
        .mini-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 8px; }
        .preview { background: #0f172a; border: 1px solid #1f2937; border-radius: 12px; padding: 12px; }
        iframe { width: 100%; height: 800px; background: #fff; border-radius: 10px; border: 1px solid #1f2937; }
        .message { margin: 12px 0; padding: 10px; background: rgba(34,211,238,0.15); color: #e2e8f0; border: 1px solid #22d3ee; border-radius: 8px; }
        .message a { color: #22d3ee; font-weight: 700; text-decoration: none; }
        .dynamic-group { background: #111827; padding: 10px; border-radius: 8px; margin-top: 8px; border: 1px solid #1f2937; }
        .flex { display: flex; gap: 8px; flex-wrap: wrap; }
        .auth { background: #0f172a; border: 1px solid #1f2937; border-radius: 12px; padding: 18px; }
        .links a { color: #22d3ee; margin-right: 12px; text-decoration: none; font-weight: 700; }
        .nav { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px; }
        .pill { display: inline-flex; align-items: center; gap: 6px; padding: 10px 14px; border-radius: 12px; background: rgba(255,255,255,0.08); text-decoration: none; color: #e2e8f0; border: 1px solid #1f2937; }
        .pill strong { color: #22d3ee; }
    </style>
</head>
<body>
<header class="hero">
    <h1>Zilan Webgen</h1>
    <p class="small-note">Cyan/Violet themed website generator. Use the builder to preview and generate ./&lt;slug&gt;/index.php without any external backend.</p>
    <?php if ($user): ?>
        <div class="nav">
            <span class="pill">👤 <strong><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></strong> · <?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?></span>
            <a class="pill" href="/dashboard.php">📂 Dashboard</a>
            <a class="pill" href="/editor.php">🛠️ Editor</a>
            <?php if ($user['role'] === 'Admin'): ?><a class="pill" href="/admin.php">🛡️ Admin</a><?php endif; ?>
            <a class="pill" href="/logout.php">🚪 Logout</a>
        </div>
    <?php else: ?>
        <p class="small-note">Bitte einloggen oder registrieren, um den Generator zu verwenden.</p>
    <?php endif; ?>
    <?php if ($flash): ?><div class="message"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
</header>

<?php if (!$user): ?>
<div class="wrapper" style="grid-template-columns: 1fr; max-width: 520px; margin: 0 auto;">
    <div class="auth">
        <h2>Login</h2>
        <p class="small-note">Melde dich an, um den Generator nutzen zu können.</p>
        <form action="/login.php" method="post">
            <label for="login-username">Username<input type="text" id="login-username" name="username" required></label>
            <label for="login-password">Password<input type="password" id="login-password" name="password" required></label>
            <button type="submit" class="primary">Login</button>
        </form>
        <h2>Register</h2>
        <form action="/register.php" method="post">
            <label for="reg-username">Username<input type="text" id="reg-username" name="username" required></label>
            <label for="reg-password">Password (min 8 characters)<input type="password" id="reg-password" name="password" minlength="8" required></label>
            <button type="submit" class="secondary">Create Account</button>
        </form>
    </div>
</div>
<?php else: ?>
<div class="wrapper">
    <?php if (!$user): ?>
    <div class="auth">
        <h2>Login</h2>
        <form action="/login.php" method="post">
            <label for="login-username">Username<input type="text" id="login-username" name="username" required></label>
            <label for="login-password">Password<input type="password" id="login-password" name="password" required></label>
            <button type="submit" class="primary">Login</button>
        </form>
        <h2>Register</h2>
        <form action="/register.php" method="post">
            <label for="reg-username">Username<input type="text" id="reg-username" name="username" required></label>
            <label for="reg-password">Password (min 8 characters)<input type="password" id="reg-password" name="password" minlength="8" required></label>
            <button type="submit" class="secondary">Create Account</button>
        </form>
    </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <div class="section-title">Basics</div>
        <label>Name<input type="text" name="name" value="<?= htmlspecialchars($data['name'] ?? '') ?>" required></label>
        <label>Title<input type="text" name="title" value="<?= htmlspecialchars($data['title'] ?? '') ?>" required></label>
        <label>Subtitle<input type="text" name="subtitle" value="<?= htmlspecialchars($data['subtitle'] ?? '') ?>"></label>
        <label>URL slug<input type="text" name="slug" value="<?= htmlspecialchars($data['slug'] ?? $slug ?? '') ?>" placeholder="my-site" required></label>

        <div class="section-title">Branding</div>
        <div class="mini-grid">
            <label>Primary color<input type="color" name="primary_color" value="<?= htmlspecialchars($data['primary_color'] ?? '#00bcd4') ?>"></label>
            <label>Secondary color<input type="color" name="secondary_color" value="<?= htmlspecialchars($data['secondary_color'] ?? '#8b5cf6') ?>"></label>
        </div>
        <label>Logo upload<input type="file" name="logo_file" accept="image/*"></label>
        <label>Logo URL (used if no upload provided)<input type="url" name="logo_url" value="<?= htmlspecialchars($data['logo_url'] ?? '') ?>" placeholder="https://example.com/logo.png"></label>
        <label>Favicon URL<input type="url" name="favicon" value="<?= htmlspecialchars($data['favicon'] ?? '') ?>" placeholder="https://example.com/favicon.ico"></label>

        <div class="section-title">Template</div>
        <label>Choose template
            <select name="template" id="template-select">
                <?php foreach ($templates as $key => $tpl): ?>
                    <option value="<?= $key ?>" <?= $template === $key ? 'selected' : '' ?>><?= $tpl['label'] ?> — <?= $tpl['description'] ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <?php foreach ($templates as $key => $tpl): ?>
            <div class="template-fields" data-template="<?= $key ?>" style="display: <?= $template === $key ? 'block' : 'none' ?>;">
                <div class="small-note">Template fields for <?= $tpl['label'] ?></div>
                <?php foreach ($tpl['fields'] as $field): ?>
                    <?php $value = htmlspecialchars($data[$field['name']] ?? ''); ?>
                    <?php if ($field['type'] === 'textarea'): ?>
                        <label><?= $field['label'] ?><textarea name="<?= $field['name'] ?>" placeholder="<?= $field['placeholder'] ?? '' ?>"><?= $value ?></textarea></label>
                    <?php else: ?>
                        <label><?= $field['label'] ?><input type="text" name="<?= $field['name'] ?>" value="<?= $value ?>" placeholder="<?= $field['placeholder'] ?? '' ?>"></label>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <div class="section-title">Custom buttons</div>
        <div id="button-list"></div>
        <button type="button" class="secondary" id="add-button">+ Add button</button>

        <div class="section-title">Social links</div>
        <label>Email<input type="text" name="social_email" value="<?= htmlspecialchars($_POST['social_email'] ?? '') ?>" placeholder="you@example.com"></label>
        <label>Discord user URL<input type="url" name="social_discord" value="<?= htmlspecialchars($_POST['social_discord'] ?? '') ?>" placeholder="https://discord.com/users/123456789">
            <div class="small-note">Use your Discord user URL (e.g., https://discord.com/users/&lt;id&gt;). Profile &gt; Copy User ID in Discord settings.</div>
        </label>

        <div class="section-title">Actions</div>
        <button class="primary" type="submit" name="action" value="preview">✨ Preview</button>
        <button class="secondary" type="submit" name="action" value="generate">🚀 Generate Site</button>
    </form>

    <div class="preview">
        <?php if ($message): ?><div class="message"><?= htmlspecialchars($message) ?><?php if (!empty($slug)): ?> · <a href="/<?= htmlspecialchars($slug) ?>/" target="_blank" rel="noopener">Seite öffnen</a><?php endif; ?></div><?php endif; ?>
        <?php if ($previewHtml): ?>
            <iframe srcdoc="<?= htmlspecialchars($previewHtml) ?>" title="Preview"></iframe>
        <?php else: ?>
            <p class="small-note">Fill in the fields and click Preview to see the generated page.</p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if ($user): ?>
<script>
const templateSelect = document.getElementById('template-select');
const templateFields = document.querySelectorAll('.template-fields');
const buttonList = document.getElementById('button-list');
const addButton = document.getElementById('add-button');

function updateTemplateVisibility() {
    const value = templateSelect.value;
    templateFields.forEach(block => {
        block.style.display = block.dataset.template === value ? 'block' : 'none';
    });
}

templateSelect.addEventListener('change', updateTemplateVisibility);

function createButtonRow(label = '', url = '', color = '') {
    const wrapper = document.createElement('div');
    wrapper.className = 'dynamic-group';
    wrapper.innerHTML = `
        <div class="flex">
            <input type="text" name="button_label[]" placeholder="Button label" value="${label}" required>
            <input type="url" name="button_url[]" placeholder="https://link" value="${url}" required>
            <input type="color" name="button_color[]" value="${color || '#00bcd4'}" title="Button color">
            <button type="button" class="secondary remove">Remove</button>
        </div>
    `;
    wrapper.querySelector('.remove').addEventListener('click', () => wrapper.remove());
    buttonList.appendChild(wrapper);
}

addButton.addEventListener('click', () => createButtonRow());

const existingLabels = <?= json_encode($_POST['button_label'] ?? []) ?>;
const existingUrls = <?= json_encode($_POST['button_url'] ?? []) ?>;
const existingColors = <?= json_encode($_POST['button_color'] ?? []) ?>;
if (existingLabels.length) {
    existingLabels.forEach((label, idx) => {
        createButtonRow(label, existingUrls[idx] || '', existingColors[idx] || '');
    });
} else {
    createButtonRow('Get in touch', '#', '#00bcd4');
}
updateTemplateVisibility();
</script>
<?php endif; ?>
</body>
</html>
