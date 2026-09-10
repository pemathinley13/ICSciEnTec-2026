<?php
/**
 * Vars expected: $authorName, $submissionTitle, $submissionId, $trackName, $baseUrl
 */
?>
<p>Dear <?= htmlspecialchars($authorName) ?>,</p>

<p>Thank you for your submission to ICSciEnTec. We have received the following paper:</p>

<p>
  <strong>Title:</strong> <?= htmlspecialchars($submissionTitle) ?><br>
  <strong>Track:</strong> <?= htmlspecialchars($trackName) ?><br>
  <strong>Submission ID:</strong> #<?= (int) $submissionId ?>
</p>

<p>The Organizing Committee will review your submission and follow up with you
directly regarding its status.</p>

<p>You can check its status at any time from your author dashboard:
  <a href="<?= htmlspecialchars($baseUrl) ?>/author/dashboard.php"><?= htmlspecialchars($baseUrl) ?>/author/dashboard.php</a>
</p>

<p>Regards,<br>ICSciEnTec Organizing Committee</p>
<?php
return "ICSciEnTec — Submission Received: " . $submissionTitle;
