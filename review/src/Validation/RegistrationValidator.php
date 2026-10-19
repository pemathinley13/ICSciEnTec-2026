<?php

namespace App\Validation;

use App\Models\Registration;

/**
 * Validates the public Registration Form. Returns an array of
 * field => error message; empty array means valid.
 */
final class RegistrationValidator
{
    /**
     * @param array $receiptFile one entry from $_FILES (the payment receipt)
     * @param array $policy ['allowed_ext' => string[], 'max_size_bytes' => int]
     */
    public static function validate(array $input, ?array $receiptFile, array $policy): array
    {
        $errors = [];

        if (trim($input['full_name'] ?? '') === '') {
            $errors['full_name'] = 'Name is required.';
        }

        $email = trim($input['email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'A valid email address is required.';
        }

        if (!in_array($input['sex'] ?? '', ['male', 'female'], true)) {
            $errors['sex'] = 'Please select your sex.';
        }

        $role = $input['role'] ?? '';
        if (!in_array($role, Registration::ROLES, true)) {
            $errors['role'] = 'Please select your role.';
        } elseif ($role === 'other' && trim($input['role_other'] ?? '') === '') {
            $errors['role_other'] = 'Please specify your role.';
        }

        $mode = $input['participation_mode'] ?? '';
        if (!in_array($mode, Registration::PARTICIPATION_MODES, true)) {
            $errors['participation_mode'] = 'Please select your participation mode.';
        } elseif ($mode === 'other' && trim($input['participation_other'] ?? '') === '') {
            $errors['participation_other'] = 'Please specify your participation mode.';
        }

        if (trim($input['paper_title'] ?? '') === '') {
            $errors['paper_title'] = 'Title of paper is required.';
        }

        if (trim($input['organisation'] ?? '') === '') {
            $errors['organisation'] = 'Organisation / affiliation is required.';
        }

        if (trim($input['country'] ?? '') === '') {
            $errors['country'] = 'Country of origin is required.';
        }

        $errors = array_merge($errors, self::validateReceipt($receiptFile, $policy));

        return $errors;
    }

    private static function validateReceipt(?array $file, array $policy): array
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['receipt' => 'Please upload your payment transaction screenshot or PDF.'];
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['receipt' => "Upload failed (error code {$file['error']}). Please try again."];
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $policy['allowed_ext'], true)) {
            return ['receipt' => 'File must be one of: ' . implode(', ', $policy['allowed_ext']) . '.'];
        }
        if ($file['size'] > $policy['max_size_bytes']) {
            return ['receipt' => sprintf('File is too large (max %.0f MB).', $policy['max_size_bytes'] / (1024 * 1024))];
        }
        return [];
    }
}
