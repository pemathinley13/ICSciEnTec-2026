<?php

require __DIR__ . '/../../src/bootstrap.php';

use App\Auth\Rbac;
use App\Models\Registration;
use App\Models\User;
use App\Services\FileUploadService;

$user = Rbac::requireRole([User::ROLE_ORGANIZING_COMMITTEE]);

$registrationId = (int) ($_GET['id'] ?? 0);
$registration = $registrationId ? Registration::findById($registrationId) : null;

if (!$registration) {
    http_response_code(404);
    die('Registration not found.');
}

$absolutePath = FileUploadService::absolutePath($registration['receipt_stored_filename']);
if (!is_file($absolutePath)) {
    http_response_code(404);
    die('File not found on disk.');
}

header('Content-Type: ' . $registration['receipt_mime_type']);
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $registration['receipt_original_filename']) . '"');
header('Content-Length: ' . filesize($absolutePath));
header('X-Content-Type-Options: nosniff');
readfile($absolutePath);
exit;
