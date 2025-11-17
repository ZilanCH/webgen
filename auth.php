<?php
declare(strict_types=1);

const USER_DATA_FILE = __DIR__ . '/data/user.json';

/**
 * Load all users from the JSON data store.
 */
function load_users(): array
{
    if (!file_exists(USER_DATA_FILE)) {
        return [];
    }

    $raw = file_get_contents(USER_DATA_FILE);
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

/**
 * Persist the given users to the JSON data store.
 */
function save_users(array $users): void
{
    $encoded = json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    file_put_contents(USER_DATA_FILE, $encoded);
}

function update_users(callable $callback): array
{
    $users = load_users();
    $callback($users);
    save_users($users);
    return $users;
}

/**
 * Find a user by username.
 */
function find_user(string $username): ?array
{
    $username = trim($username);
    foreach (load_users() as $user) {
        if (strcasecmp($user['username'], $username) === 0) {
            return $user;
        }
    }

    return null;
}

function refresh_session_user(string $username): void
{
    ensure_session_started();
    $user = find_user($username);
    if ($user) {
        $_SESSION['user'] = [
            'username' => $user['username'],
            'role' => $user['role'] ?? 'User',
            'owned_pages' => $user['owned_pages'] ?? [],
        ];
    }
}

/**
 * Register a new user with hashed password.
 *
 * @throws InvalidArgumentException when validation fails.
 */
function register_user(string $username, string $password, array $ownedPages = [], string $role = 'User'): array
{
    $username = trim($username);
    if ($username === '') {
        throw new InvalidArgumentException('Username is required.');
    }

    if (strlen($password) < 8) {
        throw new InvalidArgumentException('Password must be at least 8 characters long.');
    }

    if (find_user($username) !== null) {
        throw new InvalidArgumentException('Username already exists.');
    }

    $users = load_users();
    $user = [
        'username' => $username,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role,
        'owned_pages' => array_values($ownedPages),
    ];

    $users[] = $user;
    save_users($users);

    return $user;
}

function set_user_role(string $username, string $role): bool
{
    $role = in_array($role, ['User', 'Admin'], true) ? $role : 'User';
    $found = false;
    update_users(function (&$users) use ($username, $role, &$found) {
        foreach ($users as &$user) {
            if (strcasecmp($user['username'], $username) === 0) {
                $user['role'] = $role;
                $found = true;
                break;
            }
        }
    });

    if ($found) {
        refresh_session_user($username);
    }

    return $found;
}

function reset_user_password(string $username, string $password): bool
{
    if (strlen($password) < 8) {
        throw new InvalidArgumentException('Password must be at least 8 characters long.');
    }

    $found = false;
    update_users(function (&$users) use ($username, $password, &$found) {
        foreach ($users as &$user) {
            if (strcasecmp($user['username'], $username) === 0) {
                $user['password'] = password_hash($password, PASSWORD_DEFAULT);
                $found = true;
                break;
            }
        }
    });

    if ($found) {
        refresh_session_user($username);
    }

    return $found;
}

function delete_user_record(string $username): bool
{
    $changed = false;
    update_users(function (&$users) use ($username, &$changed) {
        $users = array_values(array_filter($users, function ($user) use ($username, &$changed) {
            if (strcasecmp($user['username'], $username) === 0) {
                $changed = true;
                return false;
            }
            return true;
        }));
    });

    return $changed;
}

function add_owned_page_to_user(string $username, string $slug): void
{
    update_users(function (&$users) use ($username, $slug) {
        foreach ($users as &$user) {
            if (strcasecmp($user['username'], $username) === 0) {
                $pages = $user['owned_pages'] ?? [];
                if (!in_array($slug, $pages, true)) {
                    $pages[] = $slug;
                }
                $user['owned_pages'] = $pages;
                break;
            }
        }
    });
    refresh_session_user($username);
}

function remove_page_from_all(string $slug): void
{
    update_users(function (&$users) use ($slug) {
        foreach ($users as &$user) {
            $user['owned_pages'] = array_values(array_filter($user['owned_pages'] ?? [], fn($page) => $page !== $slug));
        }
    });
    ensure_session_started();
    if (isset($_SESSION['user'])) {
        refresh_session_user($_SESSION['user']['username']);
    }
}

/**
 * Verify credentials and return sanitized user data if valid.
 */
function authenticate_user(string $username, string $password): ?array
{
    $user = find_user($username);
    if ($user === null) {
        return null;
    }

    if (!password_verify($password, $user['password'])) {
        return null;
    }

    return [
        'username' => $user['username'],
        'role' => $user['role'] ?? 'User',
        'owned_pages' => $user['owned_pages'] ?? [],
    ];
}

/**
 * Start the session if it has not been started yet.
 */
function ensure_session_started(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Require that a user is logged in; otherwise redirect to the index.
 */
function require_login(): void
{
    ensure_session_started();
    if (!isset($_SESSION['user'])) {
        header('Location: /index.php');
        exit;
    }
}

/**
 * Require that the user is logged in and has one of the allowed roles.
 */
function require_role(array $allowedRoles): void
{
    require_login();

    $userRole = $_SESSION['user']['role'] ?? 'User';
    if (!in_array($userRole, $allowedRoles, true)) {
        http_response_code(403);
        echo 'Access denied';
        exit;
    }
}

function list_generated_sites(): array
{
    $exclude = ['data', 'static', 'templates', 'vendor'];
    $sites = [];
    foreach (scandir(__DIR__) as $entry) {
        if ($entry === '.' || $entry === '..' || in_array($entry, $exclude, true)) {
            continue;
        }
        $path = __DIR__ . '/' . $entry;
        if (is_dir($path) && file_exists($path . '/index.php')) {
            $sites[] = $entry;
        }
    }
    sort($sites);
    return $sites;
}

function delete_directory_recursively(string $path): void
{
    if (!is_dir($path)) {
        return;
    }
    $items = scandir($path);
    if ($items === false) {
        return;
    }
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $full = $path . '/' . $item;
        if (is_dir($full)) {
            delete_directory_recursively($full);
        } else {
            @unlink($full);
        }
    }
    @rmdir($path);
}

function delete_site(string $slug): bool
{
    $slug = trim($slug);
    if ($slug === '' || $slug === '.' || $slug === '..') {
        return false;
    }
    $path = __DIR__ . '/' . $slug;
    if (!is_dir($path) || strpos(realpath($path) ?: '', realpath(__DIR__)) !== 0) {
        return false;
    }
    delete_directory_recursively($path);
    remove_page_from_all($slug);
    return true;
}
