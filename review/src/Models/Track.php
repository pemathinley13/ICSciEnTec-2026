<?php

namespace App\Models;

use App\Db\Database;

final class Track
{
    public static function all(): array
    {
        $stmt = Database::pdo()->query('SELECT * FROM tracks ORDER BY name ASC');
        return $stmt->fetchAll();
    }

    public static function exists(int $trackId): bool
    {
        $stmt = Database::pdo()->prepare('SELECT 1 FROM tracks WHERE id = ?');
        $stmt->execute([$trackId]);
        return (bool) $stmt->fetch();
    }

    public static function findById(int $trackId): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM tracks WHERE id = ?');
        $stmt->execute([$trackId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(string $name, ?string $description): int
    {
        $pdo = Database::pdo();
        $pdo->prepare('INSERT INTO tracks (name, description) VALUES (?, ?)')
            ->execute([$name, $description]);
        return (int) $pdo->lastInsertId();
    }
}
