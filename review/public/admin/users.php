<?php

require __DIR__ . '/../../src/bootstrap.php';

use App\Auth\Rbac;
use App\Auth\Session;
use App\Models\User;

$user = Rbac::requireRole([User::ROLE_ORGANIZING_COMMITTEE]);
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $targetUserId = (int) ($_POST['user_id'] ?? 0);
        $role = $_POST['role'] ?? '';
        $op = $_POST['op'] ?? 'grant';

        if (!$targetUserId || !in_array($role, User::allRoleNames(), true)) {
            $error = 'Invalid user or role.';
        } else {
            if ($op === 'revoke') {
                User::revokeRole($targetUserId, $role, (int) $user['id']);
                Session::flash('success', 'Role revoked.');
            } else {
                User::grantRole($targetUserId, $role, (int) $user['id']);
                Session::flash('success', 'Role granted.');
            }
            header('Location: /admin/users.php');
            exit;
        }
    }
}

$allUsers = User::listAll();
$roleLabels = [
    User::ROLE_AUTHOR              => 'Author',
    User::ROLE_ORGANIZING_COMMITTEE => 'Organizing Committee',
];

$pageTitle = 'Manage Users & Roles';
$activeNav = 'admin';
require __DIR__ . '/../../templates/layout_start.php';
?>
<div class="card">
  <h1>Users &amp; Roles</h1>
  <p class="lede">Grant or revoke Organizing Committee (admin) access. Every self-registered
  account starts as an author only.</p>
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
</div>

<div class="card">
  <table>
    <thead><tr><th>Name</th><th>Email</th><th>Current Roles</th><th>Grant a Role</th></tr></thead>
    <tbody>
      <?php foreach ($allUsers as $u): ?>
        <?php $roles = User::rolesFor((int) $u['id']); ?>
        <tr>
          <td><?= e($u['full_name']) ?></td>
          <td><?= e($u['email']) ?></td>
          <td>
            <?php foreach ($roles as $r): ?>
              <form method="post" style="display:inline;" onsubmit="return confirm('Revoke this role?');">
                <?= csrf_field() ?>
                <input type="hidden" name="op" value="revoke">
                <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                <input type="hidden" name="role" value="<?= e($r) ?>">
                <button type="submit" class="status-pill" style="border:none;cursor:pointer;margin:2px;" title="Click to revoke">
                  <?= e($roleLabels[$r] ?? $r) ?> &times;
                </button>
              </form>
            <?php endforeach; ?>
            <?php if (empty($roles)): ?><span style="color:var(--muted);">—</span><?php endif; ?>
          </td>
          <td>
            <form method="post" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
              <?= csrf_field() ?>
              <input type="hidden" name="op" value="grant">
              <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
              <select name="role" style="width:auto;">
                <?php foreach ($roleLabels as $value => $label): ?>
                  <option value="<?= e($value) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="btn-secondary" style="padding:6px 14px;">Grant</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<p><a href="/admin/dashboard.php">&larr; Back to dashboard</a></p>
<?php require __DIR__ . '/../../templates/layout_end.php'; ?>
