<?php
/**
 * Vars expected: $authorName, $submissionTitle, $submissionId, $newStatus, $baseUrl
 */
$statusLabels = [
    'submitted'    => 'Submitted',
    'under_review' => 'Under Review',
    'accepted'     => 'Accepted',
    'rejected'     => 'Rejected',
];
$statusLabel = $statusLabels[$newStatus] ?? ucfirst(str_replace('_', ' ', $newStatus));
?>
<p>Dear <?= htmlspecialchars($authorName) ?>,</p>

<p>The status of your submission to ICSciEnTec has been updated:</p>

<p>
  <strong>Title:</strong> <?= htmlspecialchars($submissionTitle) ?><br>
  <strong>Submission ID:</strong> #<?= (int) $submissionId ?><br>
  <strong>New Status:</strong> <?= htmlspecialchars($statusLabel) ?>
</p>

<p>You can view the full details from your author dashboard:
  <a href="<?= htmlspecialchars($baseUrl) ?>/author/dashboard.php"><?= htmlspecialchars($baseUrl) ?>/author/dashboard.php</a>
</p>

<p>Regards,<br>ICSciEnTec Organizing Committee</p>
<?php
return "ICSciEnTec — Submission Status Update: " . $submissionTitle;
