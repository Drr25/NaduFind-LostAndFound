<?php
/**
 * includes/header.php — shared PUBLIC / student header
 * Opens <html>, <head>, <body>, the top bar and <main class="container">.
 * (includes/footer.php closes <main>, the footer, </body> and </html>)
 * Expects: session started, $pdo available (config/db.php), functions.php + auth.php loaded.
 * Optional: set $pageTitle before including.
 */
$_h_root  = dirname(__DIR__);
$_h_title = $pageTitle ?? 'Lost & Found';
$_h_site  = defined('SITE_NAME') ? SITE_NAME : 'Lost & Found';

/* stylesheet auto-detect */
$_h_css = null;
foreach (['assets/css/style.css', 'css/style.css', 'assets/style.css', 'style.css', 'public/css/style.css'] as $_c) {
    if (file_exists($_h_root . '/' . $_c)) { $_h_css = $_c; break; }
}
if (!$_h_css) {
    $_f = glob($_h_root . '/{assets/css,assets,css,public/css}/*.css', GLOB_BRACE) ?: [];
    if ($_f) $_h_css = str_replace('\\', '/', substr($_f[0], strlen($_h_root) + 1));
}

/* who is logged in */
$_h_me = null;
if (function_exists('currentUserId') && currentUserId() && isset($pdo)) {
    $_q = $pdo->prepare('SELECT full_name, role FROM users WHERE id = ?');
    $_q->execute([currentUserId()]);
    $_h_me = $_q->fetch() ?: null;
}
$_h_isAdmin = $_h_me && $_h_me['role'] === 'admin';

$_h_first = function (array $files, string $default) use ($_h_root) {
    foreach ($files as $f) if (file_exists($_h_root . '/' . $f)) return $f;
    return $default;
};
$_h_login  = $_h_first(['login.php', 'auth.php', 'signin.php'], 'login.php');
$_h_logout = $_h_first(['logout.php', 'auth/logout.php'], 'logout.php');

/* nav links (only the ones that exist) */
$_h_nav = ['index.php' => 'Browse', 'report-item.php' => 'Report an Item'];
if ($_h_me && !$_h_isAdmin) {
    foreach (['my-reports.php' => 'My Reports', 'my-claims.php' => 'My Claims'] as $f => $label) {
        if (file_exists($_h_root . '/' . $f)) $_h_nav[$f] = $label;
    }
}
$_h_current = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= clean($_h_title) ?> | <?= clean($_h_site) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Darker+Grotesque:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <?php if ($_h_css): ?><link rel="stylesheet" href="<?= clean($_h_css) ?>"><?php endif; ?>
</head>
<body>

<header class="pf-topbar">
  <div class="pf-topbar-in">
    <a class="pf-brand" href="index.php">
      <span class="pf-brand-ico">
        <svg viewBox="0 0 32 32" width="24" height="24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M11 4h13a2 2 0 0 1 2 2v10.5a2 2 0 0 1-.6 1.4L14.9 28.4a2 2 0 0 1-2.8 0L4.6 20.9a2 2 0 0 1 0-2.8L11.6 11" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="11" cy="9" r="2.25" stroke="currentColor" stroke-width="1.8"/></svg>
      </span>
      <span><strong><?= clean($_h_site) ?></strong><small>Find what you lost</small></span>
    </a>

    <div class="pf-topbar-right">
      <?php foreach ($_h_nav as $_href => $_label): ?>
        <a class="pf-navlink<?= $_h_current === $_href ? ' active' : '' ?>" href="<?= $_href ?>"><?= $_label ?></a>
      <?php endforeach; ?>
      <?php if ($_h_isAdmin): ?><a class="pf-navlink" href="admin/admin_dashboard.php">Dashboard</a><?php endif; ?>

      <?php if ($_h_me): ?>
        <a class="pf-user" href="profile.php" title="My profile">
          <span class="pf-user-av"><?= clean(strtoupper(substr($_h_me['full_name'], 0, 1))) ?></span>
          <span class="pf-user-name"><?= clean($_h_me['full_name']) ?></span>
        </a>
        <a class="btn btn-outline btn-sm" href="<?= clean($_h_logout) ?>">Logout</a>
      <?php else: ?>
        <a class="btn btn-gold btn-sm" href="<?= clean($_h_login) ?>">Login</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<main class="container">
<?php
foreach (['success', 'error'] as $_k) {
    $_m = function_exists('flash') ? flash($_k) : null;
    if ($_m) echo '<div class="alert alert-' . $_k . '" role="alert">' . clean($_m) . '</div>';
}