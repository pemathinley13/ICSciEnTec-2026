<?php
/**
 * Vars expected: $recipientName, $submissionTitle, $submissionId, $trackName, $baseUrl
 */
?>
<p>Dear <?= htmlspecialchars($recipientName) ?>,</p>

<p>A new paper has been submitted to ICSciEnTec:</p>

<p>
  <strong>Title:</strong> <?= htmlspecialchars($submissionTitle) ?><br>
  <strong>Track:</strong> <?= htmlspecialchars($trackName) ?><br>
  <strong>Submission ID:</strong> #<?= (int) $submissionId ?>
</p>

<p>View it and download its files from the admin dashboard:
  <a href="<?= htmlspecialchars($baseUrl) ?>/admin/submissions.php"><?= htmlspecialchars($baseUrl) ?>/admin/submissions.php</a>
</p>

<p>Regards,<br>ICSciEnTec Review System</p>
<?php
return "ICSciEnTec — New Submission: " . $submissionTitle;
