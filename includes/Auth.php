<?php

declare(strict_types=1);

final class Auth
{
    private const DEFAULT_SESSION_TIMEOUT = 7200;
    private const DEFAULT_MAX_ATTEMPTS = 5;
    private const DEFAULT_LOCKOUT_SECONDS = 900;
    private const SESSION_AUTH_KEY = 'admin_auth';

    public static function configureSession(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => admin_base_path(),
            'httponly' => true,
            'secure' => $secure,
            'samesite' => 'Lax',
        ]);
        session_name('SPANGLE_ADMIN');
    }

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            self::configureSession();
            session_start();
        }
    }

    public static function isAuthenticated(): bool
    {
        self::startSession();

        $id = (int) ($_SESSION['admin_id'] ?? 0);
        $token = (string) ($_SESSION[self::SESSION_AUTH_KEY] ?? '');

        return $id > 0 && $token !== '' && ctype_xdigit($token);
    }

    /** @deprecated Use isAuthenticated() */
    public static function check(): bool
    {
        return self::isAuthenticated();
    }

    public static function requireAdmin(): void
    {
        self::startSession();

        if (!self::isAuthenticated()) {
            self::clearAdminSession();
            self::redirectToLogin();
        }

        if (self::isSessionExpired()) {
            self::logout();
            redirect('login.php?timeout=1');
        }

        $_SESSION['admin_last_activity'] = time();
    }

    /** @deprecated Use requireAdmin() */
    public static function requireLogin(): void
    {
        self::requireAdmin();
    }

    public static function login(PDO $pdo, string $username, string $password): bool
    {
        self::startSession();

        if (self::isLoginLocked()) {
            return false;
        }

        $stmt = $pdo->prepare('SELECT id, password_hash, display_name FROM admins WHERE username = ? LIMIT 1');
        $stmt->execute([trim($username)]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($password, $row['password_hash'])) {
            self::recordFailedLogin();

            return false;
        }

        self::clearLoginAttempts();
        self::clearAdminSession();
        session_regenerate_id(true);

        $_SESSION['admin_id'] = (int) $row['id'];
        $_SESSION['admin_name'] = (string) $row['display_name'];
        $_SESSION[self::SESSION_AUTH_KEY] = bin2hex(random_bytes(32));
        $_SESSION['admin_last_activity'] = time();

        return true;
    }

    public static function logout(): void
    {
        self::startSession();
        self::clearAdminSession();

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $p['path'],
                $p['domain'],
                (bool) $p['secure'],
                (bool) $p['httponly']
            );
        }
        session_destroy();
    }

    public static function adminName(): string
    {
        return (string) ($_SESSION['admin_name'] ?? 'Admin');
    }

    public static function adminId(): int
    {
        return (int) ($_SESSION['admin_id'] ?? 0);
    }

    public static function isLoginLocked(): bool
    {
        self::startSession();
        $lockedUntil = (int) ($_SESSION['login_locked_until'] ?? 0);
        if ($lockedUntil > time()) {
            return true;
        }
        if ($lockedUntil > 0) {
            self::clearLoginAttempts();
        }

        return false;
    }

    public static function loginLockoutRemaining(): int
    {
        $lockedUntil = (int) ($_SESSION['login_locked_until'] ?? 0);

        return max(0, $lockedUntil - time());
    }

    public static function recordFailedLogin(): void
    {
        self::startSession();
        $max = (int) (app_config('login_max_attempts') ?? self::DEFAULT_MAX_ATTEMPTS);
        $lockSeconds = (int) (app_config('login_lockout_seconds') ?? self::DEFAULT_LOCKOUT_SECONDS);
        $_SESSION['login_attempts'] = (int) ($_SESSION['login_attempts'] ?? 0) + 1;
        if ($_SESSION['login_attempts'] >= $max) {
            $_SESSION['login_locked_until'] = time() + $lockSeconds;
        }
    }

    public static function clearLoginAttempts(): void
    {
        unset($_SESSION['login_attempts'], $_SESSION['login_locked_until']);
    }

    public static function redirectToLogin(): void
    {
        $script = basename((string) ($_SERVER['PHP_SELF'] ?? ''));
        if ($script !== '' && $script !== 'login.php' && preg_match('/^[a-z0-9_-]+\.php$/i', $script)) {
            redirect('login.php?next=' . rawurlencode($script));
        }
        redirect('login.php');
    }

    private static function clearAdminSession(): void
    {
        unset(
            $_SESSION['admin_id'],
            $_SESSION['admin_name'],
            $_SESSION[self::SESSION_AUTH_KEY],
            $_SESSION['admin_last_activity']
        );
    }

    private static function isSessionExpired(): bool
    {
        $timeout = (int) (app_config('admin_session_timeout') ?? self::DEFAULT_SESSION_TIMEOUT);
        $last = (int) ($_SESSION['admin_last_activity'] ?? 0);
        if ($last <= 0) {
            return true;
        }

        return (time() - $last) > $timeout;
    }
}
