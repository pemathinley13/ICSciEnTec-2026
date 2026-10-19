<?php

require __DIR__ . '/../../src/bootstrap.php';

use App\Auth\Rbac;
use App\Models\Registration;
use App\Models\User;

$user = Rbac::requireRole([User::ROLE_ORGANIZING_COMMITTEE]);

$roleLabels = [
    'student'     => 'Student',
    'academician' => 'Academician',
    'author_org'  => 'Author for Organisation',
    'other'       => 'Other',
];
$modeLabels = [
    'with_paper'    => 'With Paper/Abstract',
    'without_paper' => 'Without Paper/Abstract',
    'other'         => 'Other',
];

$registrations = Registration::listAll();

$pageTitle = 'Conference Registrations';
$activeNav = 'admin';
require __DIR__ . '/../../templates/layout_start.php';
?>
<div class="card">
  <h1>Conference Registrations</h1>
  <p class="lede" style="margin-bottom:0;"><?= count($registrations) ?> registration(s) received so far via the 5th ICSciEnTec 2027 registration form.</p>
</div>

<div class="card">
  <?php if (empty($registrations)): ?>
    <p style="color:var(--muted);">No registrations yet.</p>
  <?php else: ?>
    <table>
      <thead><tr><th>Name</th><th>Role</th><th>Mode</th><th>Paper / Submission</th><th>Organisation</th><th>Country</th><th>Registered</th><th>Receipt</th></tr></thead>
      <tbody>
        <?php foreach ($registrations as $r): ?>
          <tr>
            <td><?= e($r['full_name']) ?><br><span style="color:var(--muted);font-size:12px;"><?= e($r['email']) ?></span></td>
            <td><?= e($roleLabels[$r['role']] ?? $r['role']) ?><?= $r['role'] === 'other' && $r['role_other'] ? ' — ' . e($r['role_other']) : '' ?></td>
            <td><?= e($modeLabels[$r['participation_mode']] ?? $r['participation_mode']) ?><?= $r['participation_mode'] === 'other' && $r['participation_other'] ? ' — ' . e($r['participation_other']) : '' ?></td>
            <td>
              <?php if ($r['submission_id']): ?>
                <a href="/admin/submissions.php">#<?= (int) $r['submission_id'] ?></a> — <?= e($r['submission_title'] ?? $r['paper_title'] ?? '') ?>
              <?php else: ?>
                <?= e($r['paper_title'] ?: '—') ?>
              <?php endif; ?>
            </td>
            <td><?= e($r['organisation']) ?></td>
            <td><?= e($r['country']) ?></td>
            <td><?= e(date('d M Y', strtotime($r['created_at']))) ?></td>
            <td>
              <a href="/admin/download_registration.php?id=<?= (int) $r['id'] ?>" class="btn-secondary" style="padding:4px 12px;font-size:12px;display:inline-block;">
                &darr; Receipt
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<p><a href="/admin/dashboard.php">&larr; Back to dashboard</a></p>
<?php require __DIR__ . '/../../templates/layout_end.php'; ?>
