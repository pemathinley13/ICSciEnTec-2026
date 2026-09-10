<?php
/**
 * One-time CLI helper to create the first Organizing Committee (admin)
 * account. Run this on the real server after importing schema.sql +
 * seed.sql — a hand-written password hash committed to a SQL file is either
 * fake or an unsafe secret-in-source-control, so the real hash is always
 * generated here, at runtime, with PHP's own password_hash().
 *
 * Usage:  php database/create_admin.php
 */

require __DIR__ . '/../src/bootstrap.php';

use App\Db\Database;
use App\Models\User;

if (PHP_SAPI !== 'cli') {
    die("This script must be run from the command line: php database/create_admin.php\n");
}

function prompt(string $label): string
{
    echo $label;
    return trim(fgets(STDIN));
}

function promptPassword(string $label): string
{
    echo $label;
    system('stty -echo');
    $password = trim(fgets(STDIN));
    system('stty echo');
    echo "\n";
    return $password;
}

echo "=== ICSciEnTec Review — Create Organizing Committee Admin ===\n\n";

$email = prompt('Email: ');
$fullName = prompt('Full name: ');
$password = promptPassword('Password (min 8 chars): ');
$confirm = promptPassword('Confirm password: ');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Invalid email address.\n");
}
if (strlen($password) < 8) {
    die("Password must be at least 8 characters.\n");
}
if ($password !== $confirm) {
    die("Passwords do not match.\n");
}
if (User::emailExists($email)) {
    die("An account with this email already exists.\n");
}

$pdo = Database::pdo();
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, full_name) VALUES (?, ?, ?)');
    $stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT), $fullName]);
    $userId = (int) $pdo->lastInsertId();

    $roleId = User::roleId(User::ROLE_ORGANIZING_COMMITTEE);
    $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)')
        ->execute([$userId, $roleId]);

    $pdo->commit();
    echo "\nAdmin account created (user #{$userId}). Log in at /auth/login.php.\n";
} catch (\Throwable $e) {
    $pdo->rollBack();
    echo "\nFailed: " . $e->getMessage() . "\n";
    exit(1);
}
