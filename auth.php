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
