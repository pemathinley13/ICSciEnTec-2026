<?php

namespace App\Models;

use App\Db\Database;

final class User
{
    public const ROLE_AUTHOR               = 'author';
    public const ROLE_ORGANIZING_COMMITTEE = 'organizing_committee';

    public static function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE id = ? AND is_active = 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function emailExists(string $email): bool
    {
        $stmt = Database::pdo()->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        return (bool) $stmt->fetch();
    }

    /**
     * Creates a new user with the `author` role — every self-registered
     * account starts as an author; the organizing_committee role is granted
     * by an existing admin via the Admin > Users page, never by self-registration.
     */
    public static function createAuthor(string $email, string $password, string $fullName, ?string $affiliation): int
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO users (email, password_hash, full_name, affiliation) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT), $fullName, $affiliation]);
            $userId = (int) $pdo->lastInsertId();

            $roleId = self::roleId(self::ROLE_AUTHOR);
            $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)')
                ->execute([$userId, $roleId]);

            $pdo->commit();
            return $userId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function roleId(string $roleName): int
    {
        $stmt = Database::pdo()->prepare('SELECT id FROM roles WHERE name = ?');
        $stmt->execute([$roleName]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new \RuntimeException("Unknown role: $roleName");
        }
        return (int) $row['id'];
    }

    /** @return string[] role names this user holds (deduped) */
    public static function rolesFor(int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT DISTINCT r.name
               FROM user_roles ur
               JOIN roles r ON r.id = ur.role_id
              WHERE ur.user_id = ?'
        );
        $stmt->execute([$userId]);
        return array_column($stmt->fetchAll(), 'name');
    }

    public static function hasRole(int $userId, string $roleName): bool
    {
        return in_array($roleName, self::rolesFor($userId), true);
    }

    public static function listByRole(string $roleName): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT DISTINCT u.*
               FROM users u
               JOIN user_roles ur ON ur.user_id = u.id
               JOIN roles r ON r.id = ur.role_id
              WHERE r.name = ? AND u.is_active = 1
              ORDER BY u.full_name ASC'
        );
        $stmt->execute([$roleName]);
        return $stmt->fetchAll();
    }

    public static function listAll(): array
    {
        $stmt = Database::pdo()->query('SELECT * FROM users ORDER BY full_name ASC');
        return $stmt->fetchAll();
    }

    public static function allRoleNames(): array
    {
        return [self::ROLE_AUTHOR, self::ROLE_ORGANIZING_COMMITTEE];
    }

    /** Grants $roleName to $userId (idempotent). */
    public static function grantRole(int $userId, string $roleName, int $grantedByUserId): void
    {
        $pdo = Database::pdo();
        $roleId = self::roleId($roleName);

        $exists = $pdo->prepare('SELECT id FROM user_roles WHERE user_id = ? AND role_id = ?');
        $exists->execute([$userId, $roleId]);
        if ($exists->fetch()) {
            return;
        }

        $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)')
            ->execute([$userId, $roleId]);

        \App\Services\AuditLogger::log(null, $grantedByUserId, 'role_granted', null, null, ['role' => $roleName, 'user_id' => $userId]);
    }

    public static function revokeRole(int $userId, string $roleName, int $revokedByUserId): void
    {
        $pdo = Database::pdo();
        $roleId = self::roleId($roleName);
        $pdo->prepare('DELETE FROM user_roles WHERE user_id = ? AND role_id = ?')
            ->execute([$userId, $roleId]);

        \App\Services\AuditLogger::log(null, $revokedByUserId, 'role_revoked', null, null, ['role' => $roleName, 'user_id' => $userId]);
    }

    /**
     * Where to send a logged-in user with no specific destination in mind
     * (post-login, or hitting "/"). A user can hold both roles, so this
     * picks the more senior one.
     */
    public static function defaultDashboardUrl(int $userId): string
    {
        $roles = self::rolesFor($userId);
        if (in_array(self::ROLE_ORGANIZING_COMMITTEE, $roles, true)) {
            return '/admin/dashboard.php';
        }
        if (in_array(self::ROLE_AUTHOR, $roles, true)) {
            return '/author/dashboard.php';
        }
        return '/auth/login.php';
    }
}
