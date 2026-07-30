<?php
declare(strict_types=1);

/**
 * Session authentication. The manual itself is public reading; signing in
 * unlocks bookmarks, and the admin role unlocks /admin.
 */
final class Auth
{
    /** @return 'ok'|'invalid'|'disabled' */
    public static function attempt(string $username, string $password): string
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return 'invalid';
        }
        if ((int) $user['is_active'] !== 1) {
            return 'disabled';
        }

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'       => (int) $user['id'],
            'username' => $user['username'],
            'name'     => $user['name'],
            'role'     => $user['role'],
            'position' => $user['position'],
        ];
        Database::pdo()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')
            ->execute([(int) $user['id']]);

        return 'ok';
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);
        session_regenerate_id(true);
    }

    /** @return array<string,mixed>|null */
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int) $u['id'] : null;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function isAdmin(): bool
    {
        return (self::user()['role'] ?? '') === 'ผู้ดูแลระบบ';
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            $_SESSION['_intended'] = $_SERVER['REQUEST_URI'] ?? '';
            flash('กรุณาเข้าสู่ระบบก่อนใช้งานส่วนนี้', 'error');
            redirect('login');
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            flash('เฉพาะผู้ดูแลระบบเท่านั้นที่เข้าถึงส่วนนี้ได้', 'error');
            redirect('');
        }
    }
}
