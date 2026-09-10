<?php

require __DIR__ . '/../../src/bootstrap.php';

use App\Auth\Rbac;
use App\Auth\Session;
use App\Models\Submission;
use App\Models\User;

$user = Rbac::requireRole([User::ROLE_ORGANIZING_COMMITTEE]);
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $submissionId = (int) ($_POST['submission_id'] ?? 0);
        $newStatus = $_POST['status'] ?? '';
        try {
            Submission::updateStatus($submissionId, $newStatus, (int) $user['id']);
            Session::flash('success', 'Status updated.');
            header('Location: /admin/submissions.php');
            exit;
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$submissions = Submission::listAll();

$pageTitle = 'All Submissions';
$activeNav = 'admin';
require __DIR__ . '/../../templates/layout_start.php';
?>
<div class="card">
  <h1>All Submissions</h1>
  <p class="lede" style="margin-bottom:0;">Every paper submitted, with its files. Review and decisions happen manually — update the status label here just to keep a record.</p>
  <?php if ($error): ?><div class="alert alert-error" style="margin-top:14px;"><?= e($error) ?></div><?php endif; ?>
</div>

<div class="card">
  <?php if (empty($submissions)): ?>
    <p style="color:var(--muted);">No submissions yet.</p>
  <?php else: ?>
    <table>
      <thead><tr><th>Title</th><th>Author</th><th>Track</th><th>Files</th><th>Status</th><th>Submitted</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($submissions as $s): ?>
          <?php $files = Submission::filesFor((int) $s['id']); ?>
          <tr>
            <td><?= e($s['title']) ?></td>
            <td><?= e($s['author_name']) ?><br><span style="color:var(--muted);font-size:12px;"><?= e($s['author_email']) ?></span></td>
            <td><?= e($s['track_name']) ?></td>
            <td>
              <?php foreach ($files as $f): ?>
                <a href="/download.php?file=<?= (int) $f['id'] ?>" class="btn-secondary" style="padding:4px 12px;font-size:12px;display:inline-block;margin:2px 0;">
                  &darr; <?= e(ucfirst($f['file_role'])) ?>
                </a><br>
              <?php endforeach; ?>
            </td>
            <td>
              <form method="post" style="display:flex;gap:6px;align-items:center;">
                <?= csrf_field() ?>
                <input type="hidden" name="submission_id" value="<?= (int) $s['id'] ?>">
                <select name="status" style="width:auto;font-size:12.5px;padding:4px 8px;">
                  <?php foreach (Submission::STATUSES as $st): ?>
                    <option value="<?= e($st) ?>" <?= $st === $s['status'] ? 'selected' : '' ?>><?= e(str_replace('_', ' ', $st)) ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-secondary" style="padding:4px 10px;font-size:12px;">Save</button>
              </form>
            </td>
            <td><?= e(date('d M Y', strtotime($s['created_at']))) ?></td>
            <td><a href="/admin/audit_log.php?submission_id=<?= (int) $s['id'] ?>">History</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<p><a href="/admin/dashboard.php">&larr; Back to dashboard</a></p>
<?php require __DIR__ . '/../../templates/layout_end.php'; ?>
