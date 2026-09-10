<?php

require __DIR__ . '/../../src/bootstrap.php';

use App\Auth\Rbac;
use App\Models\Submission;
use App\Models\User;
use App\Services\AuditLogger;

$user = Rbac::requireRole([User::ROLE_AUTHOR]);
$submissionId = (int) ($_GET['id'] ?? 0);

if (!$submissionId || !Submission::belongsToAuthor($submissionId, (int) $user['id'])) {
    http_response_code(404);
    $pageTitle = 'Not Found';
    require __DIR__ . '/../../templates/layout_start.php';
    echo '<div class="card"><p>Submission not found.</p></div>';
    require __DIR__ . '/../../templates/layout_end.php';
    exit;
}

$submission = Submission::findById($submissionId);
$authors = Submission::authorsFor($submissionId);
$files = Submission::filesFor($submissionId);
$history = AuditLogger::forSubmission($submissionId);

$pageTitle = $submission['title'];
$activeNav = 'author';
require __DIR__ . '/../../templates/layout_start.php';
?>
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
    <div>
      <h1><?= e($submission['title']) ?></h1>
      <p class="lede" style="margin-bottom:8px;">Track: <?= e(Submission::trackName((int) $submission['track_id'])) ?></p>
    </div>
    <span class="status-pill <?= e($submission['status']) ?>"><?= e(str_replace('_', ' ', $submission['status'])) ?></span>
  </div>

  <?php if (!empty($submission['keywords'])): ?>
    <h2 style="margin-top:22px;">Keywords</h2>
    <p><?= e($submission['keywords']) ?></p>
  <?php endif; ?>

  <h2 style="margin-top:22px;">Authors</h2>
  <table>
    <thead><tr><th>Name</th><th>Email</th><th>Affiliation</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($authors as $a): ?>
        <tr>
          <td><?= e($a['name']) ?></td>
          <td><?= e($a['email']) ?></td>
          <td><?= e($a['affiliation'] ?? '—') ?></td>
          <td><?= $a['is_corresponding'] ? '<span class="status-pill">Corresponding</span>' : '' ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <h2 style="margin-top:22px;">Files</h2>
  <table>
    <thead><tr><th>File</th><th>Filename</th><th>Uploaded</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($files as $f): ?>
        <tr>
          <td><?= e(ucfirst($f['file_role'])) ?></td>
          <td><?= e($f['original_filename']) ?></td>
          <td><?= e(date('d M Y H:i', strtotime($f['uploaded_at']))) ?></td>
          <td><a href="/download.php?file=<?= (int) $f['id'] ?>">Download</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="card">
  <h2>Status Timeline</h2>
  <ul class="timeline">
    <?php foreach ($history as $h): ?>
      <li>
        <div class="ts"><?= e(date('d M Y H:i', strtotime($h['created_at']))) ?></div>
        <?= e(str_replace('_', ' ', $h['action'])) ?>
        <?php if ($h['from_status'] || $h['to_status']): ?>
          — <?= e($h['from_status'] ?? 'start') ?> → <strong><?= e($h['to_status']) ?></strong>
        <?php endif; ?>
        <?php if ($h['actor_name']): ?><span style="color:var(--muted);"> (by <?= e($h['actor_name']) ?>)</span><?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
</div>

<p><a href="/author/dashboard.php">&larr; Back to My Submissions</a></p>
<?php require __DIR__ . '/../../templates/layout_end.php'; ?>
