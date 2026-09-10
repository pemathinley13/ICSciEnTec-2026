<?php

namespace App\Validation;

/**
 * Submission form validation. The abstract is a separate uploaded file
 * (not typed text) — review and decisions now happen manually outside this
 * system, so there's no automated word-count/keyword-count enforcement left
 * to do; this just checks the basics (title, track, authors, two files).
 *
 * Returns an array of field => error message; empty array means valid.
 */
final class SubmissionValidator
{
    /**
     * @param array $abstractFile one entry from $_FILES
     * @param array $manuscriptFile one entry from $_FILES
     */
    public static function validate(array $input, ?array $abstractFile, ?array $manuscriptFile, array $policy): array
    {
        $errors = [];

        if (trim($input['title'] ?? '') === '') {
            $errors['title'] = 'Title is required.';
        } elseif (mb_strlen($input['title']) > 300) {
            $errors['title'] = 'Title must be 300 characters or fewer.';
        }

        if (empty($input['track_id']) || !ctype_digit((string) $input['track_id'])) {
            $errors['track_id'] = 'Please select a track.';
        }

        $authorNames = $input['author_name'] ?? [];
        $authorEmails = $input['author_email'] ?? [];
        if (empty($authorNames) || trim($authorNames[0] ?? '') === '') {
            $errors['authors'] = 'At least one author is required.';
        } else {
            foreach ($authorEmails as $email) {
                if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors['authors'] = 'One or more author emails are invalid.';
                    break;
                }
            }
        }

        $errors = array_merge($errors, self::validateFile($abstractFile, $policy, 'abstract', 'Abstract file'));
        $errors = array_merge($errors, self::validateFile($manuscriptFile, $policy, 'manuscript', 'Manuscript file'));

        return $errors;
    }

    private static function validateFile(?array $file, array $policy, string $fieldKey, string $label): array
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return [$fieldKey => "{$label} is required."];
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [$fieldKey => "{$label} upload failed (error code {$file['error']}). Please try again."];
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $policy['allowed_ext'], true)) {
            return [$fieldKey => "{$label} must be one of: " . implode(', ', $policy['allowed_ext']) . '.'];
        }
        if ($file['size'] > $policy['max_size_bytes']) {
            return [$fieldKey => sprintf('%s is too large (max %.0f MB).', $label, $policy['max_size_bytes'] / (1024 * 1024))];
        }
        return [];
    }
}
