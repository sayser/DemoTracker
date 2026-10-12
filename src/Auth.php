<?php

declare(strict_types=1);

final class Auth
{
    public static function user(): ?array
    {
        static $cached = false;
        static $user = null;
        if ($cached) {
            return $user;
        }
        $cached = true;
        $id = $_SESSION['user_id'] ?? null;
        if (!is_int($id) && !is_string($id)) {
            return null;
        }
        $stmt = Database::pdo()->prepare(
            'SELECT id, username, email, role, active FROM users WHERE id = ? LIMIT 1'
        );
        $stmt->execute([(int) $id]);
        $row = $stmt->fetch();
        if (!$row || (int) $row['active'] !== 1) {
            unset($_SESSION['user_id']);
            return null;
        }
        $user = $row;
        return $user;
    }

    /** @return 'ok'|'bad'|'throttled' */
    public static function login(string $username, string $password): string
    {
        global $config;
        $ip = client_ip();
        $max = (int) ($config['app']['login_max_attempts'] ?? 5);
        $window = (int) ($config['app']['login_window_minutes'] ?? 15);

        if (self::tooManyAttempts($ip, $window, $max)) {
            return 'throttled';
        }

        $stmt = Database::pdo()->prepare(
            'SELECT id, username, email, role, active, password_hash FROM users WHERE username = ? LIMIT 1'
        );
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        $ok = $row
            && (int) $row['active'] === 1
            && password_verify($password, $row['password_hash']);

        if (!$ok) {
            self::recordAttempt($ip, $username);
            return 'bad';
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $row['id'];
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        return 'ok';
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', (bool) $p['secure'], (bool) $p['httponly']);
        }
        session_destroy();
    }

    public static function createUser(string $username, string $email, string $password, string $role): int
    {
        self::assertRole($role);
        self::assertUsername($username);
        self::assertEmail($email);
        self::assertPassword($password);

        try {
            $stmt = Database::pdo()->prepare(
                'INSERT INTO users (username, email, password_hash, role, active) VALUES (?, ?, ?, ?, 1)'
            );
            $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
        } catch (PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                throw new RuntimeException('That username is already taken.');
            }
            throw $e;
        }
        return (int) Database::pdo()->lastInsertId();
    }

    public static function updateUser(int $id, string $email, string $role, bool $active, ?string $newPassword): void
    {
        self::assertRole($role);
        self::assertEmail($email);
        $current = self::find($id);
        if ($current === null) {
            throw new RuntimeException('User not found.');
        }

        $me = self::user();
        if ($me && (int) $me['id'] === $id && !$active) {
            throw new RuntimeException('You cannot disable your own account.');
        }
        if ($current['role'] === 'admin' && $role !== 'admin') {
            self::assertNotLastAdmin($id);
        }
        if ($current['role'] === 'admin' && !$active) {
            self::assertNotLastAdmin($id);
        }

        if ($newPassword !== null && $newPassword !== '') {
            self::assertPassword($newPassword);
            $stmt = Database::pdo()->prepare(
                'UPDATE users SET email = ?, role = ?, active = ?, password_hash = ? WHERE id = ?'
            );
            $stmt->execute([$email, $role, $active ? 1 : 0, password_hash($newPassword, PASSWORD_DEFAULT), $id]);
        } else {
            $stmt = Database::pdo()->prepare(
                'UPDATE users SET email = ?, role = ?, active = ? WHERE id = ?'
            );
            $stmt->execute([$email, $role, $active ? 1 : 0, $id]);
        }
    }

    public static function changePassword(int $userId, string $current, string $next): void
    {
        $stmt = Database::pdo()->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($current, $row['password_hash'])) {
            throw new RuntimeException('Current password is incorrect.');
        }
        self::assertPassword($next);
        $upd = Database::pdo()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $upd->execute([password_hash($next, PASSWORD_DEFAULT), $userId]);
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, username, email, role, active, created_at FROM users WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function all(): array
    {
        return Database::pdo()->query(
            'SELECT id, username, email, role, active, created_at FROM users ORDER BY username'
        )->fetchAll();
    }

    public static function assertRole(string $role): void
    {
        if (!in_array($role, ['admin', 'standard', 'view'], true)) {
            throw new InvalidArgumentException('Invalid role.');
        }
    }

    public static function assertEmail(string $email): void
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('A valid email address is required.');
        }
    }

    public static function assertUsername(string $username): void
    {
        if (!preg_match('/^[A-Za-z0-9._-]{3,64}$/', $username)) {
            throw new InvalidArgumentException('Username must be 3–64 characters (letters, numbers, . _ -).');
        }
    }

    public static function assertPassword(string $password): void
    {
        if (strlen($password) < 8) {
            throw new InvalidArgumentException('Password must be at least 8 characters.');
        }
    }

    private static function assertNotLastAdmin(int $exceptId): void
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM users WHERE role = "admin" AND active = 1 AND id <> ?'
        );
        $stmt->execute([$exceptId]);
        if ((int) $stmt->fetchColumn() < 1) {
            throw new RuntimeException('At least one active admin is required.');
        }
    }

    private static function tooManyAttempts(string $ip, int $windowMinutes, int $max): bool
    {
        $since = Timezone::nowUtc()->sub(new DateInterval('PT' . $windowMinutes . 'M'));
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at >= ?'
        );
        $stmt->execute([$ip, Timezone::utcSql($since)]);
        return (int) $stmt->fetchColumn() >= $max;
    }

    private static function recordAttempt(string $ip, string $username): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO login_attempts (ip, username, attempted_at) VALUES (?, ?, ?)'
        );
        $stmt->execute([$ip, substr($username, 0, 64), Timezone::utcSql(Timezone::nowUtc())]);
    }
}
