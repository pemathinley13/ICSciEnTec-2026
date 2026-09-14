<?php

namespace App\Models;

use App\Db\Database;

final class Submission
{
    /** Simple, informational labels an admin can set by hand — no workflow engine behind these. */
    public const STATUSES = ['submitted', 'under_review', 'accepted', 'rejected'];

    /**
     * Creates the submission (status = submitted) plus its co-author rows and
     * its two files (abstract + manuscript), all in one transaction. Review
     * and decisions happen manually outside this system from here on.
     *
     * @param array<int,array{name:string,email:string,affiliation:?string,is_corresponding:bool}> $authors
     * @param array{stored_filename:string,original_filename:string,mime_type:string,size_bytes:int} $abstractFile
     * @param array{stored_filename:string,original_filename:string,mime_type:string,size_bytes:int} $manuscriptFile
     */
    public static function create(
        int $trackId,
        string $title,
        ?string $keywords,
        int $correspondingAuthorUserId,
        array $authors,
        array $abstractFile,
        array $manuscriptFile
    ): int {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO submissions (track_id, title, keywords, status, corresponding_author_id)
                 VALUES (?, ?, ?, \'submitted\', ?)'
            );
            $stmt->execute([$trackId, $title, $keywords, $correspondingAuthorUserId]);
            $submissionId = (int) $pdo->lastInsertId();

            $authorStmt = $pdo->prepare(
                'INSERT INTO submission_authors
                    (submission_id, user_id, name, email, affiliation, author_order, is_corresponding)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            foreach ($authors as $i => $author) {
                $authorStmt->execute([
                    $submissionId,
                    $author['is_corresponding'] ? $correspondingAuthorUserId : null,
                    $author['name'],
                    $author['email'],
                    $author['affiliation'] ?? null,
                    $i + 1,
                    $author['is_corresponding'] ? 1 : 0,
                ]);
            }

            $fileStmt = $pdo->prepare(
                'INSERT INTO submission_files
                    (submission_id, file_role, stored_filename, original_filename, mime_type, size_bytes, uploaded_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            foreach (['abstract' => $abstractFile, 'manuscript' => $manuscriptFile] as $role => $file) {
                $fileStmt->execute([
                    $submissionId, $role, $file['stored_filename'], $file['original_filename'],
                    $file['mime_type'], $file['size_bytes'], $correspondingAuthorUserId,
                ]);
            }

            \App\Services\AuditLogger::log($submissionId, $correspondingAuthorUserId, 'submission_created', null, 'submitted');

            $pdo->commit();
            return $submissionId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM submissions WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function listForAuthor(int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT s.*, t.name AS track_name
               FROM submissions s
               JOIN tracks t ON t.id = s.track_id
              WHERE s.corresponding_author_id = ?
              ORDER BY s.created_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Every submission — admin oversight view. */
    public static function listAll(): array
    {
        $stmt = Database::pdo()->query(
            'SELECT s.*, t.name AS track_name, u.full_name AS author_name, u.email AS author_email
               FROM submissions s
               JOIN tracks t ON t.id = s.track_id
               JOIN users u ON u.id = s.corresponding_author_id
              ORDER BY s.created_at DESC'
        );
        return $stmt->fetchAll();
    }

    public static function authorsFor(int $submissionId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM submission_authors WHERE submission_id = ? ORDER BY author_order ASC'
        );
        $stmt->execute([$submissionId]);
        return $stmt->fetchAll();
    }

    public static function filesFor(int $submissionId): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM submission_files WHERE submission_id = ? ORDER BY FIELD(file_role,'abstract','manuscript')"
        );
        $stmt->execute([$submissionId]);
        return $stmt->fetchAll();
    }

    public static function fileById(int $fileId): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM submission_files WHERE id = ?');
        $stmt->execute([$fileId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function trackName(int $trackId): string
    {
        $stmt = Database::pdo()->prepare('SELECT name FROM tracks WHERE id = ?');
        $stmt->execute([$trackId]);
        $row = $stmt->fetch();
        return $row['name'] ?? 'Unknown track';
    }

    public static function belongsToAuthor(int $submissionId, int $userId): bool
    {
        $stmt = Database::pdo()->prepare('SELECT 1 FROM submissions WHERE id = ? AND corresponding_author_id = ?');
        $stmt->execute([$submissionId, $userId]);
        return (bool) $stmt->fetch();
    }

    /** Admin sets this by hand — purely a label, reviewing/deciding happens outside the system. */
    public static function updateStatus(int $submissionId, string $newStatus, int $actorUserId): void
    {
        if (!in_array($newStatus, self::STATUSES, true)) {
            throw new \InvalidArgumentException("Unknown status: {$newStatus}");
        }
        $current = self::findById($submissionId);
        if (!$current) {
            throw new \RuntimeException("Submission #{$submissionId} not found.");
        }
        Database::pdo()->prepare('UPDATE submissions SET status = ? WHERE id = ?')
            ->execute([$newStatus, $submissionId]);

        \App\Services\AuditLogger::log($submissionId, $actorUserId, 'status_updated', $current['status'], $newStatus);

        if ($newStatus !== $current['status']) {
            $notified = [];
            foreach (self::authorsFor($submissionId) as $coAuthor) {
                $email = strtolower(trim($coAuthor['email'] ?? ''));
                if ($email === '' || isset($notified[$email])) {
                    continue;
                }
                $notified[$email] = true;
                \App\Services\EmailService::send('status_update', $email, [
                    'authorName'      => $coAuthor['name'],
                    'submissionTitle' => $current['title'],
                    'submissionId'    => $submissionId,
                    'newStatus'       => $newStatus,
                    'baseUrl'         => config('app.base_url'),
                ], $submissionId);
            }
        }
    }
}
