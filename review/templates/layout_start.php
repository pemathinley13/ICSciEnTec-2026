<?php
/**
 * Expects (all optional): $pageTitle, $user (assoc array from Rbac::requireRole,
 * or null on public pages like login), $activeNav (string key for nav highlight).
 */
$pageTitle = $pageTitle ?? 'ICSciEnTec Review';
$loggedInRoles = isset($user['id']) ? \App\Models\User::rolesFor((int) $user['id']) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> — ICSciEnTec Review</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<header class="topbar">
  <a href="https://icscientec.cst.edu.bt/" class="brand"><span class="brand-name">ICSciEnTec</span><span class="brand-sub">Review Portal</span></a>
  <?php if ($user): ?>
  <nav class="topnav">
    <?php if (in_array('author', $loggedInRoles, true)): ?>
      <a href="/author/dashboard.php" class="<?= ($activeNav ?? '') === 'author' ? 'active' : '' ?>">My Submissions</a>
    <?php endif; ?>
    <?php if (in_array('organizing_committee', $loggedInRoles, true)): ?>
      <a href="/admin/dashboard.php" class="<?= ($activeNav ?? '') === 'admin' ? 'active' : '' ?>">Admin</a>
    <?php endif; ?>
    <span class="topnav-user"><?= e($user['full_name']) ?></span>
    <a href="/auth/logout.php" class="logout-link">Log out</a>
  </nav>
  <?php endif; ?>
</header>

<main class="page">
  <?php if ($flash = \App\Auth\Session::flash('success')): ?>
    <div class="alert alert-success"><?= e($flash) ?></div>
  <?php endif; ?>
  <?php if ($flash = \App\Auth\Session::flash('error')): ?>
    <div class="alert alert-error"><?= e($flash) ?></div>
  <?php endif; ?>
