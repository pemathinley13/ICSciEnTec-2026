<?php

require __DIR__ . '/../../src/bootstrap.php';

use App\Auth\Rbac;
use App\Auth\Session;
use App\Models\Track;
use App\Models\User;

$user = Rbac::requireRole([User::ROLE_ORGANIZING_COMMITTEE]);
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '') ?: null;
        if ($name === '') {
            $error = 'Track name is required.';
        } else {
            Track::create($name, $description);
            Session::flash('success', 'Track added.');
            header('Location: /admin/tracks.php');
            exit;
        }
    }
}

$tracks = Track::all();

$pageTitle = 'Manage Tracks';
$activeNav = 'admin';
require __DIR__ . '/../../templates/layout_start.php';
?>
<div class="card">
  <h1>Tracks</h1>
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
</div>

<div class="card">
  <table>
    <thead><tr><th>Name</th><th>Description</th></tr></thead>
    <tbody>
      <?php foreach ($tracks as $t): ?>
        <tr><td><?= e($t['name']) ?></td><td><?= e($t['description'] ?? '—') ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="card">
  <h2>Add a Track</h2>
  <form method="post">
    <?= csrf_field() ?>
    <label for="name">Name</label>
    <input type="text" id="name" name="name" required>
    <label for="description">Description (optional)</label>
    <textarea id="description" name="description"></textarea>
    <p style="margin-top:18px;"><button type="submit" class="btn-primary">Add Track</button></p>
  </form>
</div>
<p><a href="/admin/dashboard.php">&larr; Back to dashboard</a></p>
<?php require __DIR__ . '/../../templates/layout_end.php'; ?>
