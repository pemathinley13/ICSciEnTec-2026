<?php

namespace App\Services;

use App\Db\Database;

/** Simple, append-only record of what happened to a submission. */
final class AuditLogger
{
    public static function log(
        ?int $submissionId,
        ?int $actorUserId,
        string $action,
        ?string $fromStatus = null,
        ?string $toStatus = null,
        array $details = []
    ): void {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO audit_log (submission_id, actor_user_id, action, from_status, to_status, details_json)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $submissionId,
            $actorUserId,
            $action,
            $fromStatus,
            $toStatus,
            empty($details) ? null : json_encode($details, JSON_UNESCAPED_SLASHES),
        ]);
    }

    public static function forSubmission(int $submissionId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT al.*, u.full_name AS actor_name
               FROM audit_log al
               LEFT JOIN users u ON u.id = al.actor_user_id
              WHERE al.submission_id = ?
              ORDER BY al.created_at ASC, al.id ASC'
        );
        $stmt->execute([$submissionId]);
        return $stmt->fetchAll();
    }

    /** Admin-wide browser: newest first, optionally filtered to one submission. */
    public static function listAll(?int $submissionIdFilter, int $limit, int $offset): array
    {
        $sql = 'SELECT al.*, u.full_name AS actor_name
                  FROM audit_log al
                  LEFT JOIN users u ON u.id = al.actor_user_id';
        $params = [];
        if ($submissionIdFilter) {
            $sql .= ' WHERE al.submission_id = ?';
            $params[] = $submissionIdFilter;
        }
        $sql .= ' ORDER BY al.created_at DESC, al.id DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function countAll(?int $submissionIdFilter): int
    {
        $sql = 'SELECT COUNT(*) AS c FROM audit_log';
        $params = [];
        if ($submissionIdFilter) {
            $sql .= ' WHERE submission_id = ?';
            $params[] = $submissionIdFilter;
        }
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetch()['c'];
    }
}
