<?php
/**
 * Vars expected: $authorName, $submissionTitle, $submissionId, $newStatus, $baseUrl
 */
$messages = [
    'under_review' => 'We are writing to inform you that your submission is now under review by the '
        . 'Organizing Committee. No further action is required from you at this stage; we will '
        . 'notify you by email as soon as a decision has been reached.',
    'accepted' => 'We are pleased to inform you that your submission has been accepted. Congratulations, '
        . 'and thank you for your valuable contribution to ICSciEnTec. Further instructions regarding '
        . 'next steps will follow in due course.',
    'rejected' => 'After careful consideration by the Organizing Committee, we regret to inform you that '
        . 'your submission has not been accepted for this edition of ICSciEnTec. We sincerely '
        . 'appreciate the effort behind your work and encourage you to consider submitting to future '
        . 'editions of the conference.',
];
$statusLabels = [
    'submitted'    => 'Submitted',
    'under_review' => 'Under Review',
    'accepted'     => 'Accepted',
    'rejected'     => 'Rejected',
];
$statusLabel = $statusLabels[$newStatus] ?? ucfirst(str_replace('_', ' ', $newStatus));
$message = $messages[$newStatus]
    ?? 'We are writing to inform you that the status of your submission has been updated.';
?>
<p>Dear <?= htmlspecialchars($authorName) ?>,</p>

<p><?= $message ?></p>

<p>
  <strong>Title:</strong> <?= htmlspecialchars($submissionTitle) ?><br>
  <strong>Submission ID:</strong> #<?= (int) $submissionId ?><br>
  <strong>Status:</strong> <?= htmlspecialchars($statusLabel) ?>
</p>

<p>You may view the full details of your submission at any time through your author
dashboard:
  <a href="<?= htmlspecialchars($baseUrl) ?>/author/dashboard.php"><?= htmlspecialchars($baseUrl) ?>/author/dashboard.php</a>
</p>

<p>Sincerely,<br>ICSciEnTec Organizing Committee</p>
<?php
return "ICSciEnTec — Submission Status Update: " . $submissionTitle;
