<?php

require __DIR__ . '/../../src/bootstrap.php';

use App\Auth\Rbac;
use App\Models\Submission;
use App\Models\User;

$user = Rbac::requireRole([User::ROLE_AUTHOR]);
$submissions = Submission::listForAuthor((int) $user['id']);

$pageTitle = 'My Submissions';
$activeNav = 'author';
require __DIR__ . '/../../templates/layout_start.php';
?>
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
    <div>
      <h1>My Submissions</h1>
      <p class="lede" style="margin-bottom:0;">Track the status of every paper you've submitted to ICSciEnTec.</p>
    </div>
    <a href="/author/submit.php" class="btn-primary">+ New Submission</a>
  </div>
</div>

<div class="card">
  <?php if (empty($submissions)): ?>
    <p style="color:var(--muted);">You haven't submitted a paper yet.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr><th>Title</th><th>Track</th><th>Status</th><th>Submitted</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($submissions as $s): ?>
          <tr>
            <td><?= e($s['title']) ?></td>
            <td><?= e($s['track_name']) ?></td>
            <td><span class="status-pill <?= e($s['status']) ?>"><?= e(str_replace('_', ' ', $s['status'])) ?></span></td>
            <td><?= e(date('d M Y', strtotime($s['created_at']))) ?></td>
            <td><a href="/author/view.php?id=<?= (int) $s['id'] ?>">View</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../../templates/layout_end.php'; ?>
