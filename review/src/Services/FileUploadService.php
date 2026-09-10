<?php

namespace App\Services;

use RuntimeException;

/**
 * Moves a validated $_FILES entry into storage/uploads/, which sits OUTSIDE
 * public/ (see config/config.php uploads.storage_path) and is therefore never
 * directly URL-reachable — every read goes through public/download.php with
 * an explicit role/ownership check (built in a later phase alongside the
 * reviewer/track-chair download views).
 *
 * Files are stored under a random name; the user-supplied filename is kept
 * only as a DB column for display, never used on disk (no path traversal
 * surface, no collisions, no leaking of the original name to other authors).
 */
final class FileUploadService
{
    /**
     * @param array $uploadedFile one entry from $_FILES, already checked by
     *              SubmissionValidator for extension/size/upload error
     * @return array{stored_filename:string,original_filename:string,mime_type:string,size_bytes:int}
     */
    public static function store(array $uploadedFile): array
    {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $storageRoot = rtrim($config['uploads']['storage_path'], '/');
        $targetDir = $storageRoot . '/uploads/submissions';

        if (!is_dir($targetDir) && !mkdir($targetDir, 0750, true) && !is_dir($targetDir)) {
            throw new RuntimeException("Could not create upload directory: {$targetDir}");
        }

        $ext = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
        $randomName = bin2hex(random_bytes(24)) . '.' . $ext;
        $destination = $targetDir . '/' . $randomName;

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->file($uploadedFile['tmp_name']) ?: 'application/octet-stream';

        // Defense in depth: block anything that smells executable regardless of
        // the extension whitelist already enforced upstream.
        $dangerous = ['php', 'x-httpd-php', 'x-sh', 'x-shellscript', 'x-executable', 'x-elf'];
        foreach ($dangerous as $needle) {
            if (str_contains($detectedMime, $needle)) {
                throw new RuntimeException('Uploaded file failed a content-type safety check.');
            }
        }

        if (!move_uploaded_file($uploadedFile['tmp_name'], $destination)) {
            throw new RuntimeException('Failed to store the uploaded file.');
        }
        chmod($destination, 0640);

        return [
            'stored_filename'   => 'submissions/' . $randomName,
            'original_filename' => basename($uploadedFile['name']),
            'mime_type'         => $detectedMime,
            'size_bytes'        => (int) $uploadedFile['size'],
        ];
    }

    public static function absolutePath(string $storedFilename): string
    {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        return rtrim($config['uploads']['storage_path'], '/') . '/uploads/' . $storedFilename;
    }
}
