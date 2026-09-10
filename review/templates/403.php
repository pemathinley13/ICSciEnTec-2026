<?php $pageTitle = 'Access Denied'; $user = $user ?? null; require __DIR__ . '/layout_start.php'; ?>
<div class="card" style="max-width:520px;margin:60px auto;text-align:center;">
  <h1 style="margin-bottom:10px;">403 — Access Denied</h1>
  <p style="color:var(--muted);">You don't have the right role to view this page.</p>
  <p><a href="/" class="btn-primary" style="display:inline-block;margin-top:16px;">Back to Home</a></p>
</div>
<?php require __DIR__ . '/layout_end.php'; ?>
