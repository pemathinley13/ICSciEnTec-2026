<?php

require __DIR__ . '/../../src/bootstrap.php';

use App\Auth\Rbac;
use App\Auth\Session;
use App\Models\Submission;
use App\Models\Track;
use App\Models\User;
use App\Services\FileUploadService;
use App\Validation\SubmissionValidator;

$user = Rbac::requireRole([User::ROLE_AUTHOR]);
$tracks = Track::all();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please reload the page and try again.';
    } else {
        $policy = [
            'allowed_ext'    => config('uploads.allowed_ext'),
            'max_size_bytes' => (int) config('uploads.max_size_bytes'),
        ];
        $abstractFile = $_FILES['abstract'] ?? null;
        $manuscriptFile = $_FILES['manuscript'] ?? null;
        $errors = SubmissionValidator::validate($_POST, $abstractFile, $manuscriptFile, $policy);

        if (empty($errors['track_id']) && !Track::exists((int) $_POST['track_id'])) {
            $errors['track_id'] = 'Please select a valid track.';
        }

        if (empty($errors)) {
            try {
                $storedAbstract = FileUploadService::store($abstractFile);
                $storedManuscript = FileUploadService::store($manuscriptFile);

                $authorNames = $_POST['author_name'];
                $authorEmails = $_POST['author_email'];
                $authorAffiliations = $_POST['author_affiliation'] ?? [];
                $correspondingIndex = (int) ($_POST['corresponding_index'] ?? 0);

                $authors = [];
                foreach ($authorNames as $i => $name) {
                    $name = trim($name);
                    if ($name === '') {
                        continue;
                    }
                    $authors[] = [
                        'name'             => $name,
                        'email'            => trim($authorEmails[$i] ?? ''),
                        'affiliation'      => trim($authorAffiliations[$i] ?? '') ?: null,
                        'is_corresponding' => $i === $correspondingIndex,
                    ];
                }
                if (!array_filter($authors, fn ($a) => $a['is_corresponding'])) {
                    $authors[0]['is_corresponding'] = true;
                }

                $submissionId = Submission::create(
                    (int) $_POST['track_id'],
                    trim($_POST['title']),
                    trim($_POST['keywords'] ?? '') ?: null,
                    (int) $user['id'],
                    $authors,
                    $storedAbstract,
                    $storedManuscript
                );

                // Confirmation to the author.
                \App\Services\EmailService::send('submission_received', $user['email'], [
                    'authorName'      => $user['full_name'],
                    'submissionTitle' => trim($_POST['title']),
                    'submissionId'    => $submissionId,
                    'trackName'       => Submission::trackName((int) $_POST['track_id']),
                    'baseUrl'         => config('app.base_url'),
                ], $submissionId);

                // Heads-up to every Organizing Committee member.
                foreach (User::listByRole(User::ROLE_ORGANIZING_COMMITTEE) as $admin) {
                    \App\Services\EmailService::send('new_submission_alert', $admin['email'], [
                        'recipientName'   => $admin['full_name'],
                        'submissionTitle' => trim($_POST['title']),
                        'submissionId'    => $submissionId,
                        'trackName'       => Submission::trackName((int) $_POST['track_id']),
                        'baseUrl'         => config('app.base_url'),
                    ], $submissionId);
                }

                Session::flash('success', 'Your submission has been received.');
                header('Location: /author/view.php?id=' . $submissionId);
                exit;
            } catch (\Throwable $e) {
                error_log('Submission failed: ' . $e->getMessage());
                $errors['form'] = 'Something went wrong while saving your submission. Please try again.';
            }
        }
    }
}

$pageTitle = 'New Submission';
$activeNav = 'author';
require __DIR__ . '/../../templates/layout_start.php';
?>
<div class="portal-hero">
  <span class="portal-hero-badge"><span class="portal-hero-badge-dot"></span>ICSciEnTec 2027</span>
  <h1>Submit Your <span class="gold">Paper</span></h1>
  <p>Upload your abstract and manuscript as separate files. Accepted formats: <?= e(implode(', ', config('uploads.allowed_ext'))) ?>.</p>
  <div class="portal-hero-bar"></div>
</div>

<div class="card" style="max-width:720px;margin:0 auto;">
  <?php if (!empty($errors['form'])): ?><div class="alert alert-error"><?= e($errors['form']) ?></div><?php endif; ?>

  <form method="post" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>

    <div class="form-section">
      <div class="form-section-title">01 &middot; Paper Details</div>
      <label for="title">Title</label>
      <input type="text" id="title" name="title" value="<?= e($_POST['title'] ?? '') ?>" required>
      <?php if (!empty($errors['title'])): ?><div class="field-error"><?= e($errors['title']) ?></div><?php endif; ?>

      <label for="track_id">Track</label>
      <select id="track_id" name="track_id" required>
        <option value="">— Select a track —</option>
        <?php foreach ($tracks as $t): ?>
          <option value="<?= (int) $t['id'] ?>" <?= (($_POST['track_id'] ?? '') == $t['id']) ? 'selected' : '' ?>>
            <?= e($t['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php if (!empty($errors['track_id'])): ?><div class="field-error"><?= e($errors['track_id']) ?></div><?php endif; ?>

      <label for="keywords">Keywords <span class="field-hint">(optional, separated by semicolons)</span></label>
      <input type="text" id="keywords" name="keywords" placeholder="e.g. smart grids; renewable energy; storage"
             value="<?= e($_POST['keywords'] ?? '') ?>">
    </div>

    <div class="form-section">
      <div class="form-section-title">02 &middot; Authors</div>
      <label style="margin-top:0;">Authors <span class="field-hint">(mark the corresponding author)</span></label>
      <div id="authorRows">
        <?php
          $names = $_POST['author_name'] ?? [$user['full_name']];
          $emails = $_POST['author_email'] ?? [$user['email']];
          $affils = $_POST['author_affiliation'] ?? [$user['affiliation'] ?? ''];
          $corrIndex = (int) ($_POST['corresponding_index'] ?? 0);
          foreach ($names as $i => $name):
        ?>
          <div class="author-row">
            <input type="text" name="author_name[]" placeholder="Full name" value="<?= e($name) ?>">
            <input type="email" name="author_email[]" placeholder="Email" value="<?= e($emails[$i] ?? '') ?>">
            <input type="text" name="author_affiliation[]" placeholder="Affiliation" value="<?= e($affils[$i] ?? '') ?>">
            <label style="margin:0;display:flex;align-items:center;gap:4px;font-weight:400;font-size:12.5px;white-space:nowrap;">
              <input type="radio" name="corresponding_index" value="<?= $i ?>" <?= $i === $corrIndex ? 'checked' : '' ?> style="width:auto;">
              Corresponding
            </label>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="btn-secondary" onclick="addAuthorRow()">+ Add author</button>
      <?php if (!empty($errors['authors'])): ?><div class="field-error"><?= e($errors['authors']) ?></div><?php endif; ?>
    </div>

    <div class="form-section">
      <div class="form-section-title">03 &middot; Files</div>
      <label for="abstract" style="margin-top:0;">Abstract file</label>
      <input type="file" id="abstract" name="abstract" required>
      <?php if (!empty($errors['abstract'])): ?><div class="field-error"><?= e($errors['abstract']) ?></div><?php endif; ?>

      <label for="manuscript">Manuscript file</label>
      <input type="file" id="manuscript" name="manuscript" required>
      <?php if (!empty($errors['manuscript'])): ?><div class="field-error"><?= e($errors['manuscript']) ?></div><?php endif; ?>
    </div>

    <p style="margin-top:24px;"><button type="submit" class="btn-primary" style="width:100%;">Submit Paper</button></p>
  </form>
</div>

<script>
function addAuthorRow() {
  const wrap = document.getElementById('authorRows');
  const idx = wrap.querySelectorAll('.author-row').length;
  const row = document.createElement('div');
  row.className = 'author-row';
  row.innerHTML = `
    <input type="text" name="author_name[]" placeholder="Full name">
    <input type="email" name="author_email[]" placeholder="Email">
    <input type="text" name="author_affiliation[]" placeholder="Affiliation">
    <label style="margin:0;display:flex;align-items:center;gap:4px;font-weight:400;font-size:12.5px;white-space:nowrap;">
      <input type="radio" name="corresponding_index" value="${idx}" style="width:auto;">
      Corresponding
    </label>`;
  wrap.appendChild(row);
}
</script>
<?php require __DIR__ . '/../../templates/layout_end.php'; ?>
