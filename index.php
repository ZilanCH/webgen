<?php
session_start();

if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $fullPath = __DIR__ . $path;
    if ($path !== '/' && file_exists($fullPath)) {
        return false;
    }
}

const DATA_FILE = __DIR__ . '/webgen.json';

function load_data(): array {
    if (!file_exists(DATA_FILE)) {
        $default = [
            'users' => [
                1 => [
                    'id' => 1,
                    'email' => 'admin@example.com',
                    'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
                    'role' => 'admin',
                    'name' => 'Admin User'
                ],
            ],
            'next_user_id' => 2,
            'pages' => [],
            'next_page_id' => 1,
            'footer_settings' => [
                'text' => ''
            ],
            'footer_links' => [],
            'next_footer_link_id' => 1
        ];
        save_data($default);
        return $default;
    }

    $contents = file_get_contents(DATA_FILE);
    $decoded = json_decode($contents, true);
    if (!is_array($decoded)) {
        $decoded = [];
    }

    // Ensure required keys exist to avoid undefined indexes after upgrades.
    $decoded += [
        'users' => [],
        'next_user_id' => 1,
        'pages' => [],
        'next_page_id' => 1,
        'footer_settings' => ['text' => ''],
        'footer_links' => [],
        'next_footer_link_id' => 1
    ];

    return $decoded;
}

function save_data(array $data): void {
    $fp = fopen(DATA_FILE, 'c+');
    if (!$fp) {
        throw new RuntimeException('Unable to open data file');
    }
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        throw new RuntimeException('Unable to lock data file');
    }
    ftruncate($fp, 0);
    fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
}

function current_user(array $data): ?array {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $id = $_SESSION['user_id'];
    return $data['users'][$id] ?? null;
}

function require_login(array $data): void {
    if (!current_user($data)) {
        header('Location: ?route=login');
        exit;
    }
}

function require_admin(array $data): void {
    $user = current_user($data);
    if (!$user || $user['role'] !== 'admin') {
        header('Location: ?route=login');
        exit;
    }
}

function render(string $template, array $vars = []): void {
    $template_file = $template;
    extract($vars);
    include __DIR__ . '/templates/layout.php';
}

function sanitize(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function footer_text(array $data, string $pageName = 'Webgen'): string {
    $text = trim($data['footer_settings']['text'] ?? '');
    if ($text !== '') {
        return $text;
    }
    return "©️{$pageName} 2025 - All rights reserved!";
}

function sorted_footer_links(array $data): array {
    $links = array_values($data['footer_links']);
    usort($links, function ($a, $b) {
        return ($a['position'] ?? 0) <=> ($b['position'] ?? 0);
    });
    return $links;
}

function handle_login(array &$data): void {
    $error = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        foreach ($data['users'] as $user) {
            if (strtolower($user['email']) === $email && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                header('Location: ?route=pages');
                exit;
            }
        }
        $error = 'Ungültige Anmeldedaten';
    }

    render('login.php', [
        'title' => 'Login',
        'error' => $error,
        'data' => $data,
        'user' => current_user($data)
    ]);
}

function handle_logout(): void {
    session_destroy();
    header('Location: ?route=login');
    exit;
}

function handle_pages(array &$data): void {
    require_login($data);
    $user = current_user($data);
    $pages = array_values(array_filter($data['pages'], function ($page) use ($user) {
        return $page['owner_id'] === $user['id'] || $user['role'] === 'admin';
    }));
    usort($pages, function ($a, $b) {
        return $b['id'] <=> $a['id'];
    });
    render('pages/index.php', [
        'title' => 'Seiten',
        'pages' => $pages,
        'data' => $data,
        'user' => $user
    ]);
}

function handle_page_view(array &$data, int $id): void {
    $page = $data['pages'][$id] ?? null;
    if (!$page) {
        http_response_code(404);
        echo 'Seite nicht gefunden';
        return;
    }
    $owner = $data['users'][$page['owner_id']] ?? null;
    render('pages/view.php', [
        'title' => $page['title'],
        'page' => $page,
        'owner' => $owner,
        'data' => $data,
        'user' => current_user($data)
    ]);
}

function handle_page_create(array &$data): void {
    require_login($data);
    $user = current_user($data);
    $errors = [];
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($title === '') {
            $errors[] = 'Titel ist erforderlich';
        }
        if ($content === '') {
            $errors[] = 'Inhalt ist erforderlich';
        }

        if (!$errors) {
            $id = $data['next_page_id']++;
            $data['pages'][$id] = [
                'id' => $id,
                'title' => $title,
                'content' => $content,
                'owner_id' => $user['id']
            ];
            save_data($data);
            header('Location: ?route=pages');
            exit;
        }
    }

    render('pages/new.php', [
        'title' => 'Neue Seite',
        'errors' => $errors,
        'values' => ['title' => $title, 'content' => $content],
        'data' => $data,
        'user' => $user
    ]);
}

function handle_page_edit(array &$data, int $id): void {
    require_login($data);
    $user = current_user($data);
    $page = $data['pages'][$id] ?? null;
    if (!$page) {
        http_response_code(404);
        echo 'Seite nicht gefunden';
        return;
    }
    if ($page['owner_id'] !== $user['id'] && $user['role'] !== 'admin') {
        http_response_code(403);
        echo 'Keine Berechtigung';
        return;
    }

    $errors = [];
    $title = $_POST['title'] ?? $page['title'];
    $content = $_POST['content'] ?? $page['content'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = trim($title);
        $content = trim($content);
        if ($title === '') {
            $errors[] = 'Titel ist erforderlich';
        }
        if ($content === '') {
            $errors[] = 'Inhalt ist erforderlich';
        }
        if (!$errors) {
            $page['title'] = $title;
            $page['content'] = $content;
            $data['pages'][$id] = $page;
            save_data($data);
            header('Location: ?route=pages');
            exit;
        }
    }

    render('pages/edit.php', [
        'title' => 'Seite bearbeiten',
        'errors' => $errors,
        'page' => $page,
        'values' => ['title' => $title, 'content' => $content],
        'data' => $data,
        'user' => $user
    ]);
}

function handle_page_delete(array &$data, int $id): void {
    require_login($data);
    $user = current_user($data);
    $page = $data['pages'][$id] ?? null;
    if (!$page) {
        header('Location: ?route=pages');
        return;
    }
    if ($page['owner_id'] !== $user['id'] && $user['role'] !== 'admin') {
        header('Location: ?route=pages');
        return;
    }
    unset($data['pages'][$id]);
    save_data($data);
    header('Location: ?route=pages');
    exit;
}

function handle_admin_dashboard(array &$data): void {
    require_admin($data);
    render('admin/dashboard.php', [
        'title' => 'Admin Dashboard',
        'data' => $data,
        'user' => current_user($data)
    ]);
}

function handle_admin_pages(array &$data): void {
    require_admin($data);
    $pages = array_values($data['pages']);
    usort($pages, function ($a, $b) {
        return $b['id'] <=> $a['id'];
    });
    render('admin/pages/index.php', [
        'title' => 'Seitenverwaltung',
        'pages' => $pages,
        'data' => $data,
        'user' => current_user($data)
    ]);
}

function handle_admin_page_view(array &$data, int $id): void {
    require_admin($data);
    $page = $data['pages'][$id] ?? null;
    if (!$page) {
        http_response_code(404);
        echo 'Seite nicht gefunden';
        return;
    }
    $owner = $data['users'][$page['owner_id']] ?? null;
    render('admin/pages/view.php', [
        'title' => 'Seite ansehen',
        'page' => $page,
        'owner' => $owner,
        'data' => $data,
        'user' => current_user($data)
    ]);
}

function handle_admin_page_delete(array &$data, int $id): void {
    require_admin($data);
    unset($data['pages'][$id]);
    save_data($data);
    header('Location: ?route=admin_pages');
    exit;
}

function handle_admin_users(array &$data): void {
    require_admin($data);
    $users = array_values($data['users']);
    usort($users, function ($a, $b) {
        return $a['id'] <=> $b['id'];
    });
    render('admin/users/index.php', [
        'title' => 'Benutzerverwaltung',
        'users' => $users,
        'data' => $data,
        'user' => current_user($data)
    ]);
}

function handle_admin_user_new(array &$data): void {
    require_admin($data);
    $errors = [];
    $values = [
        'name' => trim($_POST['name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'role' => $_POST['role'] ?? 'user',
        'password' => $_POST['password'] ?? ''
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($values['name'] === '') {
            $errors[] = 'Name ist erforderlich';
        }
        if ($values['email'] === '') {
            $errors[] = 'E-Mail ist erforderlich';
        }
        if ($values['password'] === '') {
            $errors[] = 'Passwort ist erforderlich';
        }
        foreach ($data['users'] as $u) {
            if (strtolower($u['email']) === strtolower($values['email'])) {
                $errors[] = 'E-Mail ist bereits vergeben';
                break;
            }
        }

        if (!$errors) {
            $id = $data['next_user_id']++;
            $data['users'][$id] = [
                'id' => $id,
                'name' => $values['name'],
                'email' => $values['email'],
                'role' => $values['role'] === 'admin' ? 'admin' : 'user',
                'password_hash' => password_hash($values['password'], PASSWORD_DEFAULT)
            ];
            save_data($data);
            header('Location: ?route=admin_users');
            exit;
        }
    }

    render('admin/users/new.php', [
        'title' => 'Neuen Benutzer anlegen',
        'errors' => $errors,
        'values' => $values,
        'data' => $data,
        'user' => current_user($data)
    ]);
}

function handle_admin_user_edit(array &$data, int $id): void {
    require_admin($data);
    $userRecord = $data['users'][$id] ?? null;
    if (!$userRecord) {
        http_response_code(404);
        echo 'Benutzer nicht gefunden';
        return;
    }

    $errors = [];
    $values = [
        'name' => $_POST['name'] ?? $userRecord['name'],
        'email' => $_POST['email'] ?? $userRecord['email'],
        'role' => $_POST['role'] ?? $userRecord['role']
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (trim($values['name']) === '') {
            $errors[] = 'Name ist erforderlich';
        }
        if (trim($values['email']) === '') {
            $errors[] = 'E-Mail ist erforderlich';
        }
        foreach ($data['users'] as $otherId => $u) {
            if ($otherId !== $id && strtolower($u['email']) === strtolower($values['email'])) {
                $errors[] = 'E-Mail ist bereits vergeben';
                break;
            }
        }

        if (!$errors) {
            $userRecord['name'] = trim($values['name']);
            $userRecord['email'] = trim($values['email']);
            $userRecord['role'] = $values['role'] === 'admin' ? 'admin' : 'user';
            $data['users'][$id] = $userRecord;
            save_data($data);
            header('Location: ?route=admin_users');
            exit;
        }
    }

    render('admin/users/edit.php', [
        'title' => 'Benutzer bearbeiten',
        'errors' => $errors,
        'userRecord' => $userRecord,
        'values' => $values,
        'data' => $data,
        'user' => current_user($data)
    ]);
}

function handle_admin_user_delete(array &$data, int $id): void {
    require_admin($data);
    if (isset($data['users'][$id])) {
        unset($data['users'][$id]);
        // Clean up owned pages
        foreach ($data['pages'] as $pid => $page) {
            if ($page['owner_id'] === $id) {
                unset($data['pages'][$pid]);
            }
        }
        save_data($data);
    }
    header('Location: ?route=admin_users');
    exit;
}

function handle_admin_user_reset(array &$data, int $id): void {
    require_admin($data);
    $userRecord = $data['users'][$id] ?? null;
    if ($userRecord) {
        $newPassword = 'changeme123';
        $userRecord['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
        $data['users'][$id] = $userRecord;
        save_data($data);
    }
    header('Location: ?route=admin_users');
    exit;
}

function handle_admin_footer(array &$data): void {
    require_admin($data);
    $links = sorted_footer_links($data);
    render('admin/footer/index.php', [
        'title' => 'Footer verwalten',
        'links' => $links,
        'footerText' => $data['footer_settings']['text'] ?? '',
        'data' => $data,
        'user' => current_user($data)
    ]);
}

function handle_admin_footer_text(array &$data): void {
    require_admin($data);
    $text = trim($_POST['text'] ?? '');
    $data['footer_settings']['text'] = $text;
    save_data($data);
    header('Location: ?route=admin_footer');
    exit;
}

function handle_admin_footer_new_link(array &$data): void {
    require_admin($data);
    $errors = [];
    $values = [
        'label' => trim($_POST['label'] ?? ''),
        'url' => trim($_POST['url'] ?? ''),
        'position' => trim($_POST['position'] ?? '')
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($values['label'] === '') {
            $errors[] = 'Label ist erforderlich';
        }
        if ($values['url'] === '') {
            $errors[] = 'URL ist erforderlich';
        }
        $pos = ($values['position'] === '') ? count($data['footer_links']) + 1 : (int) $values['position'];

        if (!$errors) {
            $id = $data['next_footer_link_id']++;
            $data['footer_links'][$id] = [
                'id' => $id,
                'label' => $values['label'],
                'url' => $values['url'],
                'position' => $pos
            ];
            save_data($data);
            header('Location: ?route=admin_footer');
            exit;
        }
    }

    render('admin/footer/new.php', [
        'title' => 'Footer-Link hinzufügen',
        'errors' => $errors,
        'values' => $values,
        'data' => $data,
        'user' => current_user($data)
    ]);
}

function handle_admin_footer_edit_link(array &$data, int $id): void {
    require_admin($data);
    $link = $data['footer_links'][$id] ?? null;
    if (!$link) {
        http_response_code(404);
        echo 'Link nicht gefunden';
        return;
    }

    $errors = [];
    $values = [
        'label' => $_POST['label'] ?? $link['label'],
        'url' => $_POST['url'] ?? $link['url'],
        'position' => $_POST['position'] ?? $link['position']
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (trim($values['label']) === '') {
            $errors[] = 'Label ist erforderlich';
        }
        if (trim($values['url']) === '') {
            $errors[] = 'URL ist erforderlich';
        }

        if (!$errors) {
            $link['label'] = trim($values['label']);
            $link['url'] = trim($values['url']);
            $link['position'] = (int) $values['position'];
            $data['footer_links'][$id] = $link;
            save_data($data);
            header('Location: ?route=admin_footer');
            exit;
        }
    }

    render('admin/footer/edit.php', [
        'title' => 'Footer-Link bearbeiten',
        'errors' => $errors,
        'link' => $link,
        'values' => $values,
        'data' => $data,
        'user' => current_user($data)
    ]);
}

function handle_admin_footer_delete_link(array &$data, int $id): void {
    require_admin($data);
    unset($data['footer_links'][$id]);
    save_data($data);
    header('Location: ?route=admin_footer');
    exit;
}

$data = load_data();
$route = $_GET['route'] ?? 'pages';
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

switch ($route) {
    case 'login':
        handle_login($data);
        break;
    case 'logout':
        handle_logout();
        break;
    case 'pages':
        handle_pages($data);
        break;
    case 'page_view':
        handle_page_view($data, $id);
        break;
    case 'page_new':
        handle_page_create($data);
        break;
    case 'page_edit':
        handle_page_edit($data, $id);
        break;
    case 'page_delete':
        handle_page_delete($data, $id);
        break;
    case 'admin_dashboard':
        handle_admin_dashboard($data);
        break;
    case 'admin_pages':
        handle_admin_pages($data);
        break;
    case 'admin_page_view':
        handle_admin_page_view($data, $id);
        break;
    case 'admin_page_delete':
        handle_admin_page_delete($data, $id);
        break;
    case 'admin_users':
        handle_admin_users($data);
        break;
    case 'admin_user_new':
        handle_admin_user_new($data);
        break;
    case 'admin_user_edit':
        handle_admin_user_edit($data, $id);
        break;
    case 'admin_user_delete':
        handle_admin_user_delete($data, $id);
        break;
    case 'admin_user_reset':
        handle_admin_user_reset($data, $id);
        break;
    case 'admin_footer':
        handle_admin_footer($data);
        break;
    case 'admin_footer_text':
        handle_admin_footer_text($data);
        break;
    case 'admin_footer_new':
        handle_admin_footer_new_link($data);
        break;
    case 'admin_footer_edit':
        handle_admin_footer_edit_link($data, $id);
        break;
    case 'admin_footer_delete':
        handle_admin_footer_delete_link($data, $id);
        break;
    default:
        handle_pages($data);
}

