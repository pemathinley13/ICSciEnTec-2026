<?php

require __DIR__ . '/../../src/bootstrap.php';

use App\Auth\Rbac;
use App\Models\Submission;
use App\Models\User;

$user = Rbac::requireRole([User::ROLE_ORGANIZING_COMMITTEE]);
$submissionCount = count(Submission::listAll());

$pageTitle = 'Admin Dashboard';
$activeNav = 'admin';
require __DIR__ . '/../../templates/layout_start.php';
?>
<div class="card">
  <h1>Organizing Committee Dashboard</h1>
  <p class="lede" style="margin-bottom:0;"><?= $submissionCount ?> submission(s) so far. Review and decisions are handled manually — this portal just collects submissions and their files.</p>
</div>
<div class="card">
  <p style="display:flex;gap:12px;flex-wrap:wrap;">
    <a href="/admin/submissions.php" class="btn-primary">All Submissions</a>
    <a href="/admin/users.php" class="btn-secondary">Manage Users &amp; Roles</a>
    <a href="/admin/tracks.php" class="btn-secondary">Manage Tracks</a>
    <a href="/admin/audit_log.php" class="btn-secondary">Audit Log</a>
  </p>
</div>
<?php require __DIR__ . '/../../templates/layout_end.php'; ?>
