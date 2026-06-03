<?php

declare(strict_types=1);

final class Auth
{
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'httponly' => true,
                'secure' => $secure,
                'samesite' => 'Lax',
            ]);
            session_name('SPANGLE_ADMIN');
            session_start();
        }
    }

    public static function check(): bool
    {
        return !empty($_SESSION['admin_id']);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect('login.php');
        }
    }

    public static function login(PDO $pdo, string $username, string $password): bool
    {
        $stmt = $pdo->prepare('SELECT id, password_hash, display_name FROM admins WHERE username = ? LIMIT 1');
        $stmt->execute([trim($username)]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($password, $row['password_hash'])) {
            return false;
        }
        $_SESSION['admin_id'] = (int) $row['id'];
        $_SESSION['admin_name'] = (string) $row['display_name'];
        session_regenerate_id(true);

        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], (bool) $p['secure'], (bool) $p['httponly']);
        }
        session_destroy();
    }

    public static function adminName(): string
    {
        return (string) ($_SESSION['admin_name'] ?? 'Admin');
    }
}
