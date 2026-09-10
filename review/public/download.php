<?php

require __DIR__ . '/../src/bootstrap.php';

use App\Auth\Rbac;
use App\Models\Submission;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\FileUploadService;

$user = Rbac::requireRole([]); // any logged-in user; the check below is per-file
$fileId = (int) ($_GET['file'] ?? 0);
$file = $fileId ? Submission::fileById($fileId) : null;

if (!$file) {
    http_response_code(404);
    die('File not found.');
}

$submissionId = (int) $file['submission_id'];
$submission = Submission::findById($submissionId);
$userId = (int) $user['id'];
$roles = User::rolesFor($userId);

$allowed = (int) $submission['corresponding_author_id'] === $userId
    || in_array(User::ROLE_ORGANIZING_COMMITTEE, $roles, true);

if (!$allowed) {
    http_response_code(403);
    die('You do not have access to this file.');
}

$absolutePath = FileUploadService::absolutePath($file['stored_filename']);
if (!is_file($absolutePath)) {
    http_response_code(404);
    die('File not found on disk.');
}

AuditLogger::log($submissionId, $userId, 'file_downloaded');

header('Content-Type: ' . $file['mime_type']);
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $file['original_filename']) . '"');
header('Content-Length: ' . filesize($absolutePath));
header('X-Content-Type-Options: nosniff');
readfile($absolutePath);
exit;
