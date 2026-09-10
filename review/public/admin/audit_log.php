<?php

require __DIR__ . '/../../src/bootstrap.php';

use App\Auth\Rbac;
use App\Models\User;
use App\Services\AuditLogger;

$user = Rbac::requireRole([User::ROLE_ORGANIZING_COMMITTEE]);
$submissionFilter = !empty($_GET['submission_id']) ? (int) $_GET['submission_id'] : null;

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $rows = AuditLogger::listAll($submissionFilter, 100000, 0);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="audit_log' . ($submissionFilter ? "_submission_{$submissionFilter}" : '') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['id', 'created_at', 'submission_id', 'actor_name', 'action', 'from_status', 'to_status', 'details_json'], ',', '"', '\\');
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id'], $r['created_at'], $r['submission_id'], $r['actor_name'],
            $r['action'], $r['from_status'], $r['to_status'], $r['details_json'],
        ], ',', '"', '\\');
    }
    fclose($out);
    exit;
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 50;
$total = AuditLogger::countAll($submissionFilter);
$rows = AuditLogger::listAll($submissionFilter, $perPage, ($page - 1) * $perPage);
$totalPages = max(1, (int) ceil($total / $perPage));

$pageTitle = 'Audit Log';
$activeNav = 'admin';
require __DIR__ . '/../../templates/layout_start.php';
?>
<div class="card">
  <h1>Audit Log</h1>
  <p class="lede">Every status transition, assignment, review, and role change — append-only,
    per the conference's record-keeping requirements.</p>
  <form method="get" style="display:flex;gap:10px;align-items:center;">
    <label style="margin:0;font-weight:400;">Submission ID</label>
    <input type="text" name="submission_id" value="<?= e((string) ($submissionFilter ?? '')) ?>" style="width:120px;">
    <button type="submit" class="btn-secondary" style="padding:8px 16px;">Filter</button>
    <a href="/admin/audit_log.php<?= $submissionFilter ? '?submission_id=' . $submissionFilter . '&' : '?' ?>export=csv" class="btn-secondary" style="padding:8px 16px;">Export CSV</a>
  </form>
</div>

<div class="card">
  <table>
    <thead><tr><th>When</th><th>Submission</th><th>Actor</th><th>Action</th><th>Status</th></tr></thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= e(date('d M Y H:i', strtotime($r['created_at']))) ?></td>
          <td><?= $r['submission_id'] ? '#' . (int) $r['submission_id'] : '—' ?></td>
          <td><?= e($r['actor_name'] ?? 'system') ?></td>
          <td><?= e(str_replace('_', ' ', $r['action'])) ?></td>
          <td><?= $r['from_status'] || $r['to_status'] ? e(($r['from_status'] ?? 'start') . ' → ' . $r['to_status']) : '—' ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <p style="margin-top:16px;color:var(--muted);font-size:13px;">
    Page <?= $page ?> of <?= $totalPages ?> (<?= $total ?> total rows)
    <?php if ($page > 1): ?> &middot; <a href="?page=<?= $page - 1 ?><?= $submissionFilter ? '&submission_id=' . $submissionFilter : '' ?>">Previous</a><?php endif; ?>
    <?php if ($page < $totalPages): ?> &middot; <a href="?page=<?= $page + 1 ?><?= $submissionFilter ? '&submission_id=' . $submissionFilter : '' ?>">Next</a><?php endif; ?>
  </p>
</div>
<p><a href="/admin/dashboard.php">&larr; Back to dashboard</a></p>
<?php require __DIR__ . '/../../templates/layout_end.php'; ?>
