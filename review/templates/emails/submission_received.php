<?php
/**
 * Vars expected: $authorName, $submissionTitle, $submissionId, $trackName, $baseUrl
 */
?>
<p>Dear <?= htmlspecialchars($authorName) ?>,</p>

<p>We are writing to confirm that your submission to ICSciEnTec has been successfully
received. The details of your submission are as follows:</p>

<p>
  <strong>Title:</strong> <?= htmlspecialchars($submissionTitle) ?><br>
  <strong>Track:</strong> <?= htmlspecialchars($trackName) ?><br>
  <strong>Submission ID:</strong> #<?= (int) $submissionId ?>
</p>

<p>Your submission will now be reviewed by the Organizing Committee. You will
receive a further notification by email as soon as a decision has been made
regarding its acceptance.</p>

<p>You may check the status of your submission at any time through your author
dashboard:
  <a href="<?= htmlspecialchars($baseUrl) ?>/author/dashboard.php"><?= htmlspecialchars($baseUrl) ?>/author/dashboard.php</a>
</p>

<p>Thank you for your interest in ICSciEnTec. We look forward to reviewing your work.</p>

<p>Sincerely,<br>ICSciEnTec Organizing Committee</p>
<?php
return "ICSciEnTec — Submission Received: " . $submissionTitle;
