<?php
/**
 * Vars expected: $authorName, $authorEmail, $submissionTitle, $submissionId, $baseUrl
 * Sent alongside the "accepted" status_update email — gives the author (and
 * every listed co-author) the link to complete their conference registration.
 * Each recipient's own email is included in the link so the form pre-fills
 * THEIR details, not just the corresponding author's.
 */
$registerUrl = $baseUrl . '/register.php?submission_id=' . (int) $submissionId . '&email=' . urlencode($authorEmail);
?>
<p>Dear <?= htmlspecialchars($authorName) ?>,</p>

<p>Congratulations once again on the acceptance of your paper, <strong><?= htmlspecialchars($submissionTitle) ?></strong>
(Submission #<?= (int) $submissionId ?>), to the 5th International Conference on Science, Engineering and
Technology (ICSciEnTec 2027), taking place <strong>5&ndash;7 May 2027</strong>.</p>

<p>To confirm your participation, please complete the Conference Registration Form using the link below.
Your name and email have already been filled in for you:</p>

<p><a href="<?= htmlspecialchars($registerUrl) ?>"><?= htmlspecialchars($registerUrl) ?></a></p>

<p>The registration form also includes the bank details for your conference fee payment — please use
<strong>ICSciEnTec2027</strong> as the payment reference, and email your transaction confirmation to the
secretary and treasurer as instructed on the form.</p>

<p>Sincerely,<br>ICSciEnTec Organizing Committee</p>
<?php
return "ICSciEnTec 2027 — Complete Your Conference Registration";
