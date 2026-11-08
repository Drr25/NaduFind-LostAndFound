<?php
/*
 * Shared layout (top part) for public / student pages.
 * Same shell as the admin side: sidebar + topbar + main content.
 *
 * Set BEFORE including:
 *   $pageTitle  (string)  - used in <title> and the topbar
 *   $activeNav  (string)  - 'browse' | 'report' | 'items' | 'claims' | 'profile'
 *   $topTitle   (optional) - shorter title for the topbar
 */
$pfUid = function_exists('currentUserId') ? currentUserId() : null;
$pfMe = null;
if ($pfUid) {
    $pfq = $pdo->prepare('SELECT full_name, role FROM users WHERE id = ?');
    $pfq->execute([$pfUid]);
    $pfMe = $pfq->fetch() ?: null;
}
$pfIsAdmin = $pfMe && $pfMe['role'] === 'admin';

$pfRoot = dirname(__DIR__);
$pfFirst = function (array $files, string $default) use ($pfRoot) {
    foreach ($files as $f) if (file_exists($pfRoot . '/' . $f)) return $f;
    return $default;
};
$pfLoginUrl  = $pfFirst(['login.php', 'auth.php', 'signin.php'], 'login.php');
$pfLogoutUrl = $pfFirst(['logout.php', 'auth/logout.php'], 'logout.php');

$pfCss = null;
foreach (['assets/css/style.css', 'css/style.css', 'assets/style.css', 'style.css', 'public/css/style.css'] as $pfC) {
    if (file_exists($pfRoot . '/' . $pfC)) { $pfCss = $pfC; break; }
}
if (!$pfCss) {
    $pfFound = glob($pfRoot . '/{assets/css,assets,css,public/css}/*.css', GLOB_BRACE) ?: [];
    if ($pfFound) $pfCss = str_replace('\\', '/', substr($pfFound[0], strlen($pfRoot) + 1));
}

$pfSite   = defined('SITE_NAME') ? SITE_NAME : 'Lost & Found';
$pfActive = $activeNav ?? '';
$pfTop    = $topTitle ?? ($pageTitle ?? 'Page');

/* ---- icons (20x20 stroke icons) ---- */
$pfIconPaths = [
    'browse'    => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
    'report'    => '<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>',
    'items'     => '<path d="M4 6h16M4 12h16M4 18h10"/>',
    'claims'    => '<path d="M12 3l8 3v6c0 4.5-3.2 8-8 9-4.8-1-8-4.5-8-9V6l8-3z"/><path d="M9 12l2 2 4-4"/>',
    'profile'   => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/>',
    'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
    'logout'    => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/>',
    'login'     => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="M10 17l5-5-5-5M15 12H3"/>',
];
$pfIcon = function (string $name) use ($pfIconPaths) {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
         . ($pfIconPaths[$name] ?? '') . '</svg>';
};

/* ---- sidebar menu ---- */
$pfMenu = [['key' => 'browse', 'label' => 'Browse Items', 'href' => 'index.php']];
if (!$pfIsAdmin) {
    $pfMenu[] = ['key' => 'report', 'label' => 'Report an Item', 'href' => 'report-item.php'];
}
$pfAccount = [];
if ($pfMe) {
    if (!$pfIsAdmin) {
        $pfAccount[] = ['key' => 'items',  'label' => 'My Reports', 'href' => 'my-items.php'];
        $pfAccount[] = ['key' => 'claims', 'label' => 'My Claims',  'href' => 'my-claims.php'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= clean($pageTitle ?? 'Page') ?> | <?= clean($pfSite) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Darker+Grotesque:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <?php if ($pfCss): ?><link rel="stylesheet" href="<?= clean($pfCss) ?>"><?php endif; ?>
</head>
<body>
<div class="admin-shell">

  <div class="admin-sidebar-overlay" id="pfOverlay"></div>

  <!-- ===== Sidebar ===== -->
  <aside class="admin-sidebar" id="pfSidebar">
    <a class="admin-sidebar-brand" href="index.php">
      <svg class="brand-mark" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M11 4h13a2 2 0 0 1 2 2v10.5a2 2 0 0 1-.6 1.4L14.9 28.4a2 2 0 0 1-2.8 0L4.6 20.9a2 2 0 0 1 0-2.8L11.6 11" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="11" cy="9" r="2.25" stroke="currentColor" stroke-width="1.8"/></svg>
      <span><?= clean($pfSite) ?><small>Find what you lost</small></span>
    </a>

    <nav class="admin-nav">
      <div class="admin-nav-group">
        <div class="admin-nav-label">Menu</div>
        <?php foreach ($pfMenu as $n): ?>
          <a href="<?= $n['href'] ?>" class="<?= $pfActive === $n['key'] ? 'active' : '' ?>"><?= $pfIcon($n['key']) ?><span><?= clean($n['label']) ?></span></a>
        <?php endforeach; ?>
      </div>

      <?php if ($pfAccount): ?>
      <div class="admin-nav-group">
        <div class="admin-nav-label">My Account</div>
        <?php foreach ($pfAccount as $n): ?>
          <a href="<?= $n['href'] ?>" class="<?= $pfActive === $n['key'] ? 'active' : '' ?>"><?= $pfIcon($n['key']) ?><span><?= clean($n['label']) ?></span></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </nav>

    <div class="admin-sidebar-foot">
      <?php if ($pfIsAdmin): ?>
        <a href="admin/admin_dashboard.php"><?= $pfIcon('dashboard') ?><span>Admin Dashboard</span></a>
      <?php endif; ?>
      <?php if ($pfMe): ?>
        <a href="<?= clean($pfLogoutUrl) ?>"><?= $pfIcon('logout') ?><span>Logout</span></a>
      <?php else: ?>
        <a href="<?= clean($pfLoginUrl) ?>"><?= $pfIcon('login') ?><span>Login / Sign up</span></a>
      <?php endif; ?>
    </div>
  </aside>

  <!-- ===== Content ===== -->
  <div class="admin-content">
    <header class="admin-topbar">
      <button type="button" class="admin-sidebar-toggle" id="pfToggle" aria-label="Open menu" aria-controls="pfSidebar">
        <span></span><span></span><span></span>
      </button>
      <div class="admin-page-title"><?= clean($pfTop) ?></div>

      <?php if ($pfMe): ?>
        <a class="admin-topbar-user" href="profile.php" title="My profile">
          <span class="admin-avatar"><?= clean(strtoupper(substr($pfMe['full_name'], 0, 1))) ?></span>
          <span class="admin-user-name"><?= clean($pfMe['full_name']) ?></span>
        </a>
      <?php else: ?>
        <a class="btn btn-gold btn-sm" href="<?= clean($pfLoginUrl) ?>">Login</a>
      <?php endif; ?>
    </header>

    <main class="admin-main">
<?php
// flash messages
if (!empty($_SESSION['flash']) && is_array($_SESSION['flash'])) {
    $pfFl = $_SESSION['flash'];
    $pfFlash = isset($pfFl['message']) ? [$pfFl] : (isset($pfFl[0]) && is_array($pfFl[0]) ? $pfFl : []);
    if (!$pfFlash) { foreach ($pfFl as $pfT => $pfM) { if (is_string($pfM)) $pfFlash[] = ['type' => $pfT, 'message' => $pfM]; } }
    foreach ($pfFlash as $pfIt) {
        // skip a stale "please log in" notice once the user is already logged in
        if ($pfMe && stripos($pfIt['message'] ?? '', 'log in to continue') !== false) continue;
        $pfFt = ($pfIt['type'] ?? 'success') === 'error' ? 'error' : 'success';
        echo '<div class="alert alert-' . $pfFt . '">' . clean($pfIt['message'] ?? '') . '</div>';
    }
    unset($_SESSION['flash']);
}
?>