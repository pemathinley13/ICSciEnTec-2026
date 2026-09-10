<?php

require __DIR__ . '/../../src/bootstrap.php';

use App\Auth\Session;
use App\Models\User;

if (Session::isLoggedIn()) {
    header('Location: ' . User::defaultDashboardUrl(Session::userId()));
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $fullName = trim($_POST['full_name'] ?? '');
        $affiliation = trim($_POST['affiliation'] ?? '') ?: null;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        } elseif (User::emailExists($email)) {
            $errors['email'] = 'An account with this email already exists.';
        }
        if ($fullName === '') {
            $errors['full_name'] = 'Full name is required.';
        }
        if (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        } elseif ($password !== $passwordConfirm) {
            $errors['password_confirm'] = 'Passwords do not match.';
        }

        if (empty($errors)) {
            $userId = User::createAuthor($email, $password, $fullName, $affiliation);
            Session::login($userId);
            Session::flash('success', 'Welcome! Your author account has been created.');
            header('Location: /author/dashboard.php');
            exit;
        }
    }
}

$pageTitle = 'Create Account';
$user = null;
require __DIR__ . '/../../templates/layout_start.php';
?>
<div class="portal-hero">
  <span class="portal-hero-badge"><span class="portal-hero-badge-dot"></span>ICSciEnTec Review Portal</span>
  <h1>Create Your <span class="gold">Author Account</span></h1>
  <p>Register once to submit papers, track their status through review, and receive decision notifications.</p>
  <div class="portal-hero-bar"></div>
</div>

<div class="card auth-card" style="max-width:480px;">
  <?php if (!empty($errors['form'])): ?><div class="alert alert-error"><?= e($errors['form']) ?></div><?php endif; ?>

  <form method="post" novalidate>
    <?= csrf_field() ?>
    <label for="full_name">Full name</label>
    <input type="text" id="full_name" name="full_name" value="<?= e($_POST['full_name'] ?? '') ?>" required>
    <?php if (!empty($errors['full_name'])): ?><div class="field-error"><?= e($errors['full_name']) ?></div><?php endif; ?>

    <label for="affiliation">Affiliation (optional)</label>
    <input type="text" id="affiliation" name="affiliation" value="<?= e($_POST['affiliation'] ?? '') ?>">

    <label for="email">Email</label>
    <input type="email" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>
    <?php if (!empty($errors['email'])): ?><div class="field-error"><?= e($errors['email']) ?></div><?php endif; ?>

    <label for="password">Password</label>
    <input type="password" id="password" name="password" required>
    <?php if (!empty($errors['password'])): ?><div class="field-error"><?= e($errors['password']) ?></div><?php endif; ?>

    <label for="password_confirm">Confirm password</label>
    <input type="password" id="password_confirm" name="password_confirm" required>
    <?php if (!empty($errors['password_confirm'])): ?><div class="field-error"><?= e($errors['password_confirm']) ?></div><?php endif; ?>

    <p style="margin-top:22px;"><button type="submit" class="btn-primary" style="width:100%;">Create Account</button></p>
  </form>
  <p class="form-footnote">Already have an account? <a href="/auth/login.php">Log in</a></p>
</div>
<?php require __DIR__ . '/../../templates/layout_end.php'; ?>
