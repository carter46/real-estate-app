<?php
/**
 * Authentication helpers — Phase 2 / hardened Phase 7.
 */

declare(strict_types=1);

function auth_is_https(): bool
{
    $forced = app_config('security.cookie_secure', null);
    if ($forced === true || $forced === false || $forced === 1 || $forced === 0 || $forced === '1' || $forced === '0') {
        return (bool) $forced;
    }

    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    if (!empty(app_config('security.trust_forwarded_proto', false))) {
        $proto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        if ($proto === 'https') {
            return true;
        }
    }

    return false;
}

function auth_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $name = (string) app_config('security.session_name', 'sdc_re_session');
    session_name($name);
    @ini_set('session.use_strict_mode', '1');
    @ini_set('session.use_only_cookies', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => auth_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function auth_user(): ?array
{
    auth_session_start();
    $user = $_SESSION['auth_user'] ?? null;
    return is_array($user) ? $user : null;
}

function auth_check(): bool
{
    $user = auth_user();
    if ($user === null || empty($user['id'])) {
        return false;
    }

    $last = (int) ($_SESSION['auth_last_activity'] ?? 0);
    if ($last > 0 && (time() - $last) > 28800) {
        auth_logout();
        return false;
    }

    // Re-validate active admin periodically (every 5 minutes).
    $checked = (int) ($_SESSION['auth_user_checked_at'] ?? 0);
    if ($checked === 0 || (time() - $checked) > 300) {
        try {
            $stmt = db()->prepare(
                "SELECT id, email, name, role, is_active
                 FROM users WHERE id = ? LIMIT 1"
            );
            $stmt->execute([(int) $user['id']]);
            $row = $stmt->fetch();
            if (
                !is_array($row)
                || (int) ($row['is_active'] ?? 0) !== 1
                || ($row['role'] ?? '') !== 'admin'
            ) {
                auth_logout();
                return false;
            }
            $_SESSION['auth_user'] = [
                'id' => (int) $row['id'],
                'email' => (string) $row['email'],
                'name' => (string) ($row['name'] ?? ''),
                'role' => (string) $row['role'],
            ];
            $_SESSION['auth_user_checked_at'] = time();
        } catch (Throwable $e) {
            app_log('auth', 'auth_check revalidate failed: ' . $e->getMessage());
            // Fail closed on DB errors for admin pages
            auth_logout();
            return false;
        }
    }

    $_SESSION['auth_last_activity'] = time();
    return true;
}

function auth_require(): void
{
    if (admin_setup_required()) {
        redirect('admin/setup.php');
    }

    if (!auth_check()) {
        flash_set('auth_error', 'Please sign in to continue.');
        redirect('admin/login.php');
    }
}

/**
 * Whether the one-time admin setup is still required.
 * True when no active admin with a password_hash exists.
 */
function admin_setup_required(): bool
{
    try {
        $stmt = db()->query(
            "SELECT COUNT(*) AS c
             FROM users
             WHERE role = 'admin'
               AND is_active = 1
               AND password_hash IS NOT NULL
               AND password_hash != ''"
        );
        $count = (int) ($stmt->fetchColumn() ?: 0);
        return $count === 0;
    } catch (Throwable $e) {
        return true;
    }
}

/**
 * @return array{ok: bool, error: ?string, user: ?array}
 */
function auth_attempt_login(string $email, string $password): array
{
    $email = strtolower(trim($email));
    if ($email === '' || $password === '') {
        return ['ok' => false, 'error' => 'Email and password are required.', 'user' => null];
    }

    $max = (int) app_config('security.login_max_attempts', 5);
    $window = (int) app_config('security.login_lockout_seconds', 900);
    $limit = rate_limit_hit('login', $max, $window);
    if (!$limit['allowed']) {
        return [
            'ok' => false,
            'error' => 'Too many sign-in attempts. Try again in ' . (int) $limit['retry_after'] . ' seconds.',
            'user' => null,
        ];
    }

    $stmt = db()->prepare(
        "SELECT id, email, name, role, password_hash, is_active
         FROM users
         WHERE email = ?
         LIMIT 1"
    );
    $stmt->execute([$email]);
    $row = $stmt->fetch();

    $hash = is_array($row) ? (string) ($row['password_hash'] ?? '') : '';
    $valid = $hash !== '' && password_verify($password, $hash);

    if (!is_array($row) || !$valid || (int) ($row['is_active'] ?? 0) !== 1 || ($row['role'] ?? '') !== 'admin') {
        return ['ok' => false, 'error' => 'Invalid email or password.', 'user' => null];
    }

    rate_limit_clear('login');

    auth_login_user([
        'id' => (int) $row['id'],
        'email' => (string) $row['email'],
        'name' => (string) ($row['name'] ?? ''),
        'role' => (string) $row['role'],
    ]);

    $upd = db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
    $upd->execute([(int) $row['id']]);

    return ['ok' => true, 'error' => null, 'user' => auth_user()];
}

/**
 * @param array{id:int,email:string,name?:string,role:string} $user
 */
function auth_login_user(array $user): void
{
    auth_session_start();
    session_regenerate_id(true);
    $_SESSION['auth_user'] = [
        'id' => (int) $user['id'],
        'email' => (string) $user['email'],
        'name' => (string) ($user['name'] ?? ''),
        'role' => (string) $user['role'],
    ];
    $_SESSION['auth_last_activity'] = time();
    $_SESSION['auth_user_checked_at'] = time();
    csrf_rotate();
}

function auth_logout(): void
{
    auth_session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'] ?? '/', $params['domain'] ?? '', (bool) ($params['secure'] ?? false), (bool) ($params['httponly'] ?? true));
    }
    session_destroy();
}

/**
 * Create the first admin (one-time setup).
 *
 * @return array{ok: bool, error: ?string}
 */
function auth_create_first_admin(string $name, string $email, string $password, string $passwordConfirm): array
{
    if (!admin_setup_required()) {
        return ['ok' => false, 'error' => 'Admin setup is already complete.'];
    }

    $name = trim($name);
    $email = strtolower(trim($email));

    if ($name === '' || $email === '' || $password === '') {
        return ['ok' => false, 'error' => 'Name, email, and password are required.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Enter a valid email address.'];
    }

    if (strlen($password) < 12) {
        return ['ok' => false, 'error' => 'Password must be at least 12 characters.'];
    }

    if (!hash_equals($password, $passwordConfirm)) {
        return ['ok' => false, 'error' => 'Password confirmation does not match.'];
    }

    $algo = app_config('security.password_algo', PASSWORD_DEFAULT);
    $hash = password_hash($password, is_int($algo) || is_string($algo) ? $algo : PASSWORD_DEFAULT);
    if ($hash === false) {
        return ['ok' => false, 'error' => 'Unable to hash password.'];
    }

    try {
        $check = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $check->execute([$email]);
        $existing = $check->fetch();

        if (is_array($existing)) {
            $upd = db()->prepare(
                "UPDATE users
                 SET name = ?, password_hash = ?, role = 'admin', is_active = 1,
                     setup_completed_at = NOW(), updated_at = NOW()
                 WHERE id = ?"
            );
            $upd->execute([$name, $hash, (int) $existing['id']]);
            $userId = (int) $existing['id'];
        } else {
            $ins = db()->prepare(
                "INSERT INTO users (email, password_hash, name, role, is_active, setup_completed_at)
                 VALUES (?, ?, ?, 'admin', 1, NOW())"
            );
            $ins->execute([$email, $hash, $name]);
            $userId = (int) db()->lastInsertId();
        }

        auth_login_user([
            'id' => $userId,
            'email' => $email,
            'name' => $name,
            'role' => 'admin',
        ]);

        return ['ok' => true, 'error' => null];
    } catch (Throwable $e) {
        app_log('auth', 'admin setup failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Could not create admin account. Check database connection.'];
    }
}
