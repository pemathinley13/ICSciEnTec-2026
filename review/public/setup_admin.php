<?php

/**
 * One-time, web-based fallback for creating the first Organizing Committee
 * account on hosts with no SSH/terminal access (so `database/create_admin.php`
 * isn't runnable). Locked down two ways:
 *   1. Requires config('app.setup_token') to be set to a long random value —
 *      it's blank by default, so this endpoint does nothing until you
 *      deliberately turn it on.
 *   2. Refuses to run at all once ANY organizing_committee account exists,
 *      so it can only ever create the very first admin, never be reused.
 *
 * DELETE THIS FILE (or blank app.setup_token back out) immediately after use.
 */

require __DIR__ . '/../src/bootstrap.php';

use App\Db\Database;
use App\Models\User;

$setupToken = config('app.setup_token', '');
$adminAlreadyExists = (bool) Database::pdo()
    ->query("SELECT ur.id FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE r.name = 'organizing_committee' LIMIT 1")
    ->fetch();

$error = null;
$done = false;

if ($setupToken === '') {
    http_response_code(404);
    die('Not available.');
}
if ($adminAlreadyExists) {
    http_response_code(404);
    die('Setup already completed. Delete public/setup_admin.php.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $suppliedToken = $_POST['token'] ?? '';
    if (!hash_equals($setupToken, $suppliedToken)) {
        $error = 'Incorrect setup token.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter a valid email address.';
        } elseif ($fullName === '') {
            $error = 'Full name is required.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($password !== $passwordConfirm) {
            $error = 'Passwords do not match.';
        } elseif (User::emailExists($email)) {
            $error = 'An account with this email already exists.';
        } else {
            $pdo = Database::pdo();
            $pdo->beginTransaction();
            try {
                $pdo->prepare('INSERT INTO users (email, password_hash, full_name) VALUES (?, ?, ?)')
                    ->execute([$email, password_hash($password, PASSWORD_DEFAULT), $fullName]);
                $userId = (int) $pdo->lastInsertId();

                $roleId = User::roleId(User::ROLE_ORGANIZING_COMMITTEE);
                $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)')
                    ->execute([$userId, $roleId]);

                $pdo->commit();
                $done = true;
            } catch (\Throwable $e) {
                $pdo->rollBack();
                $error = 'Something went wrong: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>First-Time Admin Setup</title></head>
<body style="font-family:sans-serif;max-width:480px;margin:60px auto;padding:0 20px;">
<h1>Create the First Admin Account</h1>
<?php if ($done): ?>
  <p style="color:green;"><strong>Done.</strong> The admin account was created. Now
  <strong>delete public/setup_admin.php</strong> (or blank out <code>app.setup_token</code>
  in config.php) so this page can never run again.</p>
  <p><a href="/auth/login.php">Go to login &rarr;</a></p>
<?php else: ?>
  <?php if ($error): ?><p style="color:red;"><?= htmlspecialchars($error) ?></p><?php endif; ?>
  <form method="post">
    <p><label>Setup token<br><input type="text" name="token" style="width:100%;" required></label></p>
    <p><label>Full name<br><input type="text" name="full_name" style="width:100%;" required></label></p>
    <p><label>Email<br><input type="email" name="email" style="width:100%;" required></label></p>
    <p><label>Password<br><input type="password" name="password" style="width:100%;" required></label></p>
    <p><label>Confirm password<br><input type="password" name="password_confirm" style="width:100%;" required></label></p>
    <p><button type="submit">Create Admin Account</button></p>
  </form>
<?php endif; ?>
</body>
</html>
