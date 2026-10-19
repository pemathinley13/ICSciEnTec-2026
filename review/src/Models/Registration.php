<?php

namespace App\Models;

use App\Db\Database;

/**
 * The public "5th ICSciEnTec 2027 Registration Form" — replaces the old
 * Google Form. Standalone (anyone can fill it in), optionally linked to a
 * submission when reached via the "your paper was accepted" email.
 */
final class Registration
{
    public const ROLES = ['student', 'academician', 'author_org', 'other'];
    public const PARTICIPATION_MODES = ['with_paper', 'without_paper', 'other'];

    /**
     * @param array{stored_filename:string,original_filename:string,mime_type:string,size_bytes:int} $receiptFile
     */
    public static function create(array $data, array $receiptFile): int
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO registrations
                (submission_id, full_name, email, sex, role, role_other,
                 participation_mode, participation_other, paper_title,
                 organisation, country,
                 receipt_stored_filename, receipt_original_filename, receipt_mime_type, receipt_size_bytes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['submission_id'] ?: null,
            $data['full_name'],
            $data['email'],
            $data['sex'],
            $data['role'],
            $data['role_other'] ?: null,
            $data['participation_mode'],
            $data['participation_other'] ?: null,
            $data['paper_title'],
            $data['organisation'],
            $data['country'],
            $receiptFile['stored_filename'],
            $receiptFile['original_filename'],
            $receiptFile['mime_type'],
            $receiptFile['size_bytes'],
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM registrations WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Admin oversight view — every registration received so far. */
    public static function listAll(): array
    {
        $stmt = Database::pdo()->query(
            'SELECT r.*, s.title AS submission_title
               FROM registrations r
               LEFT JOIN submissions s ON s.id = r.submission_id
              ORDER BY r.created_at DESC'
        );
        return $stmt->fetchAll();
    }
}
