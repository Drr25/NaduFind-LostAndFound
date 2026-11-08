<?php
session_start();
require_once './config/db.php';
require_once './includes/functions.php';
require_once './includes/auth.php';

if (isLoggedIn()) redirect('index.php');

$loginErrors = [];
$registerErrors = [];
$oldEmail = '';
$old = ['full_name' => '', 'email' => '', 'phone' => ''];
$agreedLogin = false;
$agreedRegister = false;
$activeTab = (($_GET['tab'] ?? '') === 'signup') ? 'signup' : 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? 'login';

    if ($mode === 'register') {
        $activeTab = 'signup';
        $old['full_name'] = trim($_POST['full_name'] ?? '');
        $old['email']     = trim($_POST['email'] ?? '');
        $old['phone']     = trim($_POST['phone'] ?? '');
        $password         = $_POST['password'] ?? '';
        $confirm          = $_POST['confirm_password'] ?? '';
        $agreedRegister   = isset($_POST['agree']);

        if ($old['full_name'] === '') $registerErrors[] = 'Full name is required.';
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $registerErrors[] = 'A valid email is required.';
        if (strlen($password) < 6) $registerErrors[] = 'Password must be at least 6 characters.';
        if ($password !== $confirm) $registerErrors[] = 'Passwords do not match.';
        if (!$agreedRegister) $registerErrors[] = 'Please agree to the Data Privacy Agreement to continue.';

        if (!$registerErrors) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$old['email']]);
            if ($stmt->fetch()) {
                $registerErrors[] = 'An account with that email already exists.';
            }
        }

        if (!$registerErrors) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                'INSERT INTO users (full_name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, "user")'
            );
            $stmt->execute([$old['full_name'], $old['email'], $old['phone'] ?: null, $hash]);

            flash('success', 'Account created successfully! Please log in.');
            redirect('login.php?tab=login');
        }
    } else {
        $activeTab = 'login';
        $oldEmail    = trim($_POST['email'] ?? '');
        $password    = $_POST['password'] ?? '';
        $agreedLogin = isset($_POST['agree']);

        if ($oldEmail === '' || $password === '') {
            $loginErrors[] = 'Please enter both email and password.';
        } elseif (!$agreedLogin) {
            $loginErrors[] = 'Please agree to the Data Privacy Agreement to continue.';
        } else {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
            $stmt->execute([$oldEmail]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $loginErrors[] = 'Invalid email or password.';
            } elseif (!$user['is_active']) {
                $loginErrors[] = 'This account has been deactivated. Contact an administrator.';
            } else {
                session_regenerate_id(true); // prevent session fixation
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role']      = $user['role'];
                flash('success', 'Welcome back, ' . $user['full_name'] . '!');
                redirect($user['role'] === 'admin' ? 'admin/admin_dashboard.php' : 'index.php');
            }
        }
    }
}

/* ---------- tiny view helpers (inline SVG icons + field markup) ---------- */
function lgIcon(string $name, string $class): string {
    static $p = [
        'mail'   => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'lock'   => '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
        'user'   => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
        'phone'  => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/>',
        'eye'    => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'eyeoff' => '<path d="M3 3l18 18"/><path d="M10.6 6.1A9.8 9.8 0 0 1 12 5c6 0 10 7 10 7a17 17 0 0 1-3.2 4"/><path d="M6.6 6.6A17 17 0 0 0 2 12s4 7 10 7a9.7 9.7 0 0 0 4.4-1"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/>',
        'login'  => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/>',
        'userplus' => '<circle cx="9" cy="8" r="4"/><path d="M2 21c0-4 3-6 7-6s7 2 7 6"/><path d="M19 8v6M16 11h6"/>',
        'back'   => '<path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/>',
    ];
    return '<svg class="' . $class . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p[$name] . '</svg>';
}

function lgField(string $id, string $name, string $type, string $label, string $icon,
                 string $placeholder, string $value = '', string $autocomplete = '', string $hint = ''): string {
    $isPw = $type === 'password';
    $h  = '<div class="lg-field">';
    $h .= '<label for="' . $id . '">' . $label . '</label>';
    $h .= '<div class="lg-input">' . lgIcon($icon, 'lg-icon');
    $h .= '<input type="' . $type . '" id="' . $id . '" name="' . $name . '" placeholder="' . clean($placeholder) . '"';
    if ($value !== '')        $h .= ' value="' . clean($value) . '"';
    if ($autocomplete !== '') $h .= ' autocomplete="' . $autocomplete . '"';
    $h .= '>';
    if ($isPw) {
        $h .= '<button type="button" class="lg-eye" data-toggle-password="' . $id . '" aria-label="Show password" aria-pressed="false">'
            . lgIcon('eye', 'lg-eye-on') . lgIcon('eyeoff', 'lg-eye-off') . '</button>';
    }
    $h .= '</div>';
    if ($hint !== '') $h .= '<small class="lg-hint">' . $hint . '</small>';
    return $h . '</div>';
}

$pageTitle = $activeTab === 'signup' ? 'Sign Up' : 'Log In';
$bodyClass = 'page-auth';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= clean($pageTitle) ?> | <?= clean(SITE_NAME) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Darker+Grotesque:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="<?= clean($bodyClass) ?>">
<main class="container">

<div class="lg-page">

  <a class="lg-brand" href="index.php">
    <span class="lg-logo">
      <svg viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M11 4h13a2 2 0 0 1 2 2v10.5a2 2 0 0 1-.6 1.4L14.9 28.4a2 2 0 0 1-2.8 0L4.6 20.9a2 2 0 0 1 0-2.8L11.6 11" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
        <circle cx="11" cy="9" r="2.25" stroke="currentColor" stroke-width="2"/>
      </svg>
    </span>
    <span class="lg-wordmark"><?= clean(SITE_NAME) ?></span>
    <span class="lg-tagline">Find what's lost. Return what's found.</span>
  </a>

  <div class="lg-card">

<?php if ($activeTab === 'login'): ?>
    <!-- ================= LOG IN ================= -->
    <h1 class="lg-title">Sign in to your account</h1>

    <?php foreach ($loginErrors as $e): ?>
      <div class="alert alert-error" role="alert"><?= clean($e) ?></div>
    <?php endforeach; ?>

    <form method="POST" class="lg-form" novalidate>
      <input type="hidden" name="mode" value="login">

      <?= lgField('email', 'email', 'email', 'Email address', 'mail', 'you@example.com', $oldEmail, 'email') ?>
      <?= lgField('password', 'password', 'password', 'Password', 'lock', 'Enter your password', '', 'current-password') ?>

      <label class="lg-check">
        <input type="checkbox" name="agree" value="1" <?= $agreedLogin ? 'checked' : '' ?>>
        <span>I have read and agree to <?= clean(SITE_NAME) ?>'s
          <button type="button" class="lg-link" data-open-privacy>Data Privacy Agreement</button>,
          in accordance with the Data Privacy Act of 2012 (RA 10173).</span>
      </label>

      <div class="lg-actions">
        <button type="submit" class="lg-btn lg-btn-primary"><?= lgIcon('login', 'lg-btn-icon') ?> Sign In</button>
        <a href="index.php" class="lg-btn lg-btn-ghost"><?= lgIcon('back', 'lg-btn-icon') ?> Back</a>
      </div>
    </form>

    <p class="lg-switch">No account yet? <a href="login.php?tab=signup">Create one</a></p>

<?php else: ?>
    <!-- ================= SIGN UP ================= -->
    <h1 class="lg-title">Create your account</h1>

    <?php foreach ($registerErrors as $e): ?>
      <div class="alert alert-error" role="alert"><?= clean($e) ?></div>
    <?php endforeach; ?>

    <form method="POST" class="lg-form" novalidate>
      <input type="hidden" name="mode" value="register">

      <?= lgField('full_name', 'full_name', 'text', 'Full name', 'user', 'Juan Dela Cruz', $old['full_name'], 'name') ?>
      <?= lgField('reg_email', 'email', 'email', 'Email address', 'mail', 'you@example.com', $old['email'], 'email') ?>
      <?= lgField('phone', 'phone', 'tel', 'Phone number (optional)', 'phone', '09XX XXX XXXX', $old['phone'], 'tel') ?>
      <?= lgField('reg_password', 'password', 'password', 'Password', 'lock', 'At least 6 characters', '', 'new-password') ?>
      <?= lgField('confirm_password', 'confirm_password', 'password', 'Confirm password', 'lock', 'Re-enter your password', '', 'new-password') ?>

      <label class="lg-check">
        <input type="checkbox" name="agree" value="1" <?= $agreedRegister ? 'checked' : '' ?>>
        <span>I have read and agree to <?= clean(SITE_NAME) ?>'s
          <button type="button" class="lg-link" data-open-privacy>Data Privacy Agreement</button>,
          in accordance with the Data Privacy Act of 2012 (RA 10173).</span>
      </label>

      <div class="lg-actions">
        <button type="submit" class="lg-btn lg-btn-primary"><?= lgIcon('userplus', 'lg-btn-icon') ?> Create Account</button>
        <a href="index.php" class="lg-btn lg-btn-ghost"><?= lgIcon('back', 'lg-btn-icon') ?> Back</a>
      </div>
    </form>

    <p class="lg-switch">Already registered? <a href="login.php?tab=login">Sign in</a></p>
<?php endif; ?>

  </div>
</div>

<?php
/* SAMPLE TEXT: review and edit this so it matches how your system really handles personal data
   (who can see it, how long it is kept, etc.) before going live. */
?>
<dialog id="privacyDialog" class="lg-dialog" aria-labelledby="privacyTitle">
  <div class="lg-dialog-inner">
    <h2 id="privacyTitle">Data Privacy Agreement</h2>
    <p>In accordance with the Data Privacy Act of 2012 (RA 10173), <?= clean(SITE_NAME) ?> collects and processes the following information:</p>
    <ul>
      <li>Your name, email address, and optional phone number, used to run your account and contact you about reports and claims.</li>
      <li>The item details, photos, and proof-of-ownership information you submit when reporting or claiming an item.</li>
    </ul>
    <p>This information is used only to post and verify lost-and-found items and to return them to their owners. It is accessible to authorized administrators and is not sold or shared with outside parties. You may ask an administrator to correct or delete your data at any time.</p>
    <form method="dialog"><button class="lg-btn lg-btn-primary">I understand</button></form>
  </div>
</dialog>

<script>
(function () {
  // Show / hide password
  document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = document.getElementById(btn.getAttribute('data-toggle-password'));
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.classList.toggle('is-on', show);
      btn.setAttribute('aria-pressed', show ? 'true' : 'false');
      btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
  });
  // Privacy agreement dialog
  var dlg = document.getElementById('privacyDialog');
  document.querySelectorAll('[data-open-privacy]').forEach(function (b) {
    b.addEventListener('click', function () { if (dlg && dlg.showModal) dlg.showModal(); });
  });
})();
</script>

</main>
</body>
</html>