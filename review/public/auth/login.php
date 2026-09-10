<?php

require __DIR__ . '/../../src/bootstrap.php';

use App\Auth\Session;
use App\Models\User;

if (Session::isLoggedIn()) {
    header('Location: ' . User::defaultDashboardUrl(Session::userId()));
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $user = User::findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $error = 'Incorrect email or password.';
        } else {
            Session::login((int) $user['id']);
            $redirect = $_SESSION['redirect_after_login'] ?? User::defaultDashboardUrl((int) $user['id']);
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        }
    }
}

$pageTitle = 'Log In';
$user = null;
require __DIR__ . '/../../templates/layout_start.php';
?>
<div class="portal-hero">
  <span class="portal-hero-badge"><span class="portal-hero-badge-dot"></span>ICSciEnTec Review Portal</span>
  <h1>Welcome <span class="gold">Back</span></h1>
  <p>Log in to submit a paper, track your submissions, or manage your reviews.</p>
  <div class="portal-hero-bar"></div>
</div>

<div class="card auth-card">
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

  <form method="post" novalidate>
    <?= csrf_field() ?>
    <label for="email">Email</label>
    <input type="email" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required autofocus>

    <label for="password">Password</label>
    <input type="password" id="password" name="password" required>

    <p style="margin-top:22px;"><button type="submit" class="btn-primary" style="width:100%;">Log In</button></p>
  </form>
  <p class="form-footnote">New author? <a href="/auth/register.php">Create an account</a></p>
</div>
<?php require __DIR__ . '/../../templates/layout_end.php'; ?>
