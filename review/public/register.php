<?php

require __DIR__ . '/../src/bootstrap.php';

use App\Auth\Session;
use App\Models\Registration;
use App\Models\Submission;
use App\Models\User;
use App\Services\FileUploadService;
use App\Validation\RegistrationValidator;

$errors = [];
$done = false;

$receiptPolicy = [
    'allowed_ext'    => ['jpg', 'jpeg', 'png', 'pdf'],
    'max_size_bytes' => 10 * 1024 * 1024, // 10 MB
];

// Reached via the "your paper was accepted" email as
// /register.php?submission_id=123 — pre-fills name, email, affiliation and
// paper title for convenience. The form still works perfectly well without
// this (anyone — students, academicians, delegates — can fill it in).
$submissionId = isset($_GET['submission_id']) ? (int) $_GET['submission_id'] : null;
$prefill = ['full_name' => '', 'email' => '', 'organisation' => '', 'paper_title' => ''];
$prefillMode = null;
if ($submissionId) {
    $submission = Submission::findById($submissionId);
    if ($submission) {
        $prefill['paper_title'] = $submission['title'];
        $prefillMode = 'with_paper';
        $author = User::findById((int) $submission['corresponding_author_id']);
        if ($author) {
            $prefill['full_name'] = $author['full_name'];
            $prefill['email'] = $author['email'];
            $prefill['organisation'] = $author['affiliation'] ?? '';
        }
    } else {
        $submissionId = null; // bad id in the URL — just fall back to a blank, standalone form
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please reload the page and try again.';
    } else {
        $receiptFile = $_FILES['receipt'] ?? null;
        $errors = RegistrationValidator::validate($_POST, $receiptFile, $receiptPolicy);
        if (empty($errors)) {
            try {
                $stored = FileUploadService::store($receiptFile, 'registrations');
                Registration::create([
                    'submission_id'       => $submissionId,
                    'full_name'           => trim($_POST['full_name']),
                    'email'               => trim($_POST['email']),
                    'sex'                 => $_POST['sex'],
                    'role'                => $_POST['role'],
                    'role_other'          => trim($_POST['role_other'] ?? ''),
                    'participation_mode'  => $_POST['participation_mode'],
                    'participation_other' => trim($_POST['participation_other'] ?? ''),
                    'paper_title'         => trim($_POST['paper_title']),
                    'organisation'        => trim($_POST['organisation']),
                    'country'             => trim($_POST['country']),
                ], $stored);
                $done = true;
            } catch (\Throwable $e) {
                error_log('Registration failed: ' . $e->getMessage());
                $errors['form'] = 'Something went wrong while saving your registration. Please try again.';
            }
        }
    }
}

function regOld(string $field, string $default = ''): string
{
    return $_POST[$field] ?? $default;
}

$pageTitle = 'Registration Form';
$user = null; // public page — no login required
require __DIR__ . '/../templates/layout_start.php';
?>
<div class="portal-hero">
  <span class="portal-hero-badge"><span class="portal-hero-badge-dot"></span>5th ICSciEnTec &middot; 5&ndash;7 May 2027</span>
  <h1>Conference <span class="gold">Registration</span> Form</h1>
  <p>5th International Conference on Science, Engineering and Technology (ICSciEnTec 2027) — kindly register here for participation in the conference.</p>
  <div class="portal-hero-bar"></div>
</div>

<div class="card" style="max-width:760px;margin:0 auto;">

<?php if ($done): ?>
  <h2>Thank you — your registration is received.</h2>
  <p class="lede" style="margin-bottom:0;">We've recorded your details and payment receipt for the 5th ICSciEnTec 2027. You'll hear from the organising committee with further information closer to the conference.</p>
<?php else: ?>

  <?php if (!empty($errors['form'])): ?><div class="alert alert-error"><?= e($errors['form']) ?></div><?php endif; ?>

  <form method="post" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>

    <div class="form-section">
      <div class="form-section-title">Your Details</div>

      <div class="grid-2">
        <div>
          <label for="full_name">Name <span class="required">*</span></label>
          <input type="text" id="full_name" name="full_name" value="<?= e(regOld('full_name', $prefill['full_name'])) ?>" required>
          <?php if (!empty($errors['full_name'])): ?><div class="field-error"><?= e($errors['full_name']) ?></div><?php endif; ?>
        </div>
        <div>
          <label for="email">Email <span class="required">*</span></label>
          <input type="email" id="email" name="email" value="<?= e(regOld('email', $prefill['email'])) ?>" required>
          <?php if (!empty($errors['email'])): ?><div class="field-error"><?= e($errors['email']) ?></div><?php endif; ?>
        </div>
      </div>

      <div class="grid-2">
        <div>
          <label>Sex <span class="required">*</span></label>
          <div class="radio-group">
            <label class="radio-option"><input type="radio" name="sex" value="male" <?= regOld('sex') === 'male' ? 'checked' : '' ?> required> Male</label>
            <label class="radio-option"><input type="radio" name="sex" value="female" <?= regOld('sex') === 'female' ? 'checked' : '' ?>> Female</label>
          </div>
          <?php if (!empty($errors['sex'])): ?><div class="field-error"><?= e($errors['sex']) ?></div><?php endif; ?>
        </div>
        <div>
          <label>Your Role <span class="required">*</span></label>
          <div class="radio-group">
            <label class="radio-option"><input type="radio" name="role" value="student" <?= regOld('role') === 'student' ? 'checked' : '' ?> onchange="toggleOther('role')" required> Student</label>
            <label class="radio-option"><input type="radio" name="role" value="academician" <?= regOld('role') === 'academician' ? 'checked' : '' ?> onchange="toggleOther('role')"> Academician</label>
            <label class="radio-option"><input type="radio" name="role" value="author_org" <?= regOld('role') === 'author_org' ? 'checked' : '' ?> onchange="toggleOther('role')"> Author for Organisation</label>
            <label class="radio-option"><input type="radio" name="role" value="other" <?= regOld('role') === 'other' ? 'checked' : '' ?> onchange="toggleOther('role')"> Other</label>
          </div>
          <input type="text" id="role_other" name="role_other" placeholder="Please specify" value="<?= e(regOld('role_other')) ?>"
                 style="<?= regOld('role') === 'other' ? '' : 'display:none;' ?> margin-top:8px;">
          <?php if (!empty($errors['role'])): ?><div class="field-error"><?= e($errors['role']) ?></div><?php endif; ?>
          <?php if (!empty($errors['role_other'])): ?><div class="field-error"><?= e($errors['role_other']) ?></div><?php endif; ?>
        </div>
      </div>
    </div>

    <div class="form-section">
      <div class="form-section-title">Participation</div>

      <label>Participation Mode <span class="required">*</span></label>
      <div class="radio-group">
        <label class="radio-option"><input type="radio" name="participation_mode" value="with_paper" <?= regOld('participation_mode', $prefillMode ?? '') === 'with_paper' ? 'checked' : '' ?> onchange="toggleOther('participation_mode')" required> With Paper/Abstract</label>
        <label class="radio-option"><input type="radio" name="participation_mode" value="without_paper" <?= regOld('participation_mode', $prefillMode ?? '') === 'without_paper' ? 'checked' : '' ?> onchange="toggleOther('participation_mode')"> Without Paper/Abstract <span class="field-hint">(if you only want to attend and not publish)</span></label>
        <label class="radio-option"><input type="radio" name="participation_mode" value="other" <?= regOld('participation_mode', $prefillMode ?? '') === 'other' ? 'checked' : '' ?> onchange="toggleOther('participation_mode')"> Other</label>
      </div>
      <input type="text" id="participation_other" name="participation_other" placeholder="Please specify" value="<?= e(regOld('participation_other')) ?>"
             style="<?= regOld('participation_mode', $prefillMode ?? '') === 'other' ? '' : 'display:none;' ?> margin-top:8px;">
      <?php if (!empty($errors['participation_mode'])): ?><div class="field-error"><?= e($errors['participation_mode']) ?></div><?php endif; ?>
      <?php if (!empty($errors['participation_other'])): ?><div class="field-error"><?= e($errors['participation_other']) ?></div><?php endif; ?>

      <label for="paper_title">Title of Paper <span class="required">*</span></label>
      <input type="text" id="paper_title" name="paper_title" value="<?= e(regOld('paper_title', $prefill['paper_title'])) ?>" required>
      <?php if (!empty($errors['paper_title'])): ?><div class="field-error"><?= e($errors['paper_title']) ?></div><?php endif; ?>
    </div>

    <div class="form-section">
      <div class="form-section-title">Affiliation</div>

      <div class="grid-2">
        <div>
          <label for="organisation">Organisation / Affiliation <span class="required">*</span></label>
          <input type="text" id="organisation" name="organisation" value="<?= e(regOld('organisation', $prefill['organisation'])) ?>" required>
          <?php if (!empty($errors['organisation'])): ?><div class="field-error"><?= e($errors['organisation']) ?></div><?php endif; ?>
        </div>
        <div>
          <label for="country">Country of Origin <span class="required">*</span></label>
          <input type="text" id="country" name="country" value="<?= e(regOld('country')) ?>" required>
          <?php if (!empty($errors['country'])): ?><div class="field-error"><?= e($errors['country']) ?></div><?php endif; ?>
        </div>
      </div>
    </div>

    <div class="form-section">
      <div class="form-section-title">Payment</div>

      <div class="split-panel">
        <div class="split-col">
          <label for="receipt">Upload Payment Transaction Screenshot <span class="required">*</span></label>
          <input type="file" id="receipt" name="receipt" accept=".jpg,.jpeg,.png,.pdf" required>
          <p class="field-hint">Accepted formats: JPG, JPEG, PNG or PDF. Max 10 MB.</p>
          <?php if (!empty($errors['receipt'])): ?><div class="field-error"><?= e($errors['receipt']) ?></div><?php endif; ?>
        </div>
        <div class="split-divider"></div>
        <div class="split-col">
          <div class="form-section-title" style="margin-bottom:10px;">Bank Details</div>
          <table style="margin-bottom:10px;">
            <tr><th style="width:42%;">Account Name</th><td>College Development Fund (CDF)</td></tr>
            <tr><th>Bank Name</th><td>Bhutan National Bank</td></tr>
            <tr><th>Branch</th><td>Phuentsholing Main Branch</td></tr>
            <tr><th>Account No.</th><td>1234567890</td></tr>
            <tr><th>SWIFT Code</th><td>BHUBTBT001</td></tr>
          </table>
          <p class="field-hint">
            Please use <strong>ICSciEnTec2027</strong> as the payment remark / reference.
            Also email a copy of your transaction to
            <a href="mailto:treasurer_icscientec.cst@rub.edu.bt">treasurer_icscientec.cst@rub.edu.bt</a>
            and <a href="mailto:secretary_icscientec.cst@rub.edu.bt">secretary_icscientec.cst@rub.edu.bt</a>.
          </p>
        </div>
      </div>
    </div>

    <p style="margin-top:24px;"><button type="submit" class="btn-primary" style="width:100%;">Submit Registration</button></p>
  </form>
<?php endif; ?>
</div>

<script>
function toggleOther(group) {
  const checked = document.querySelector('input[name="' + group + '"]:checked');
  const other = document.getElementById(group + '_other');
  if (!other) return;
  other.style.display = (checked && checked.value === 'other') ? '' : 'none';
}
</script>

<?php require __DIR__ . '/../templates/layout_end.php'; ?>
