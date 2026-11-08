<?php
// Admin shell: <html>, sidebar and top bar. Pair with includes/admin-footer.php.
$pageTitle = $pageTitle ?? 'Admin';
$activeNav = $activeNav ?? '';

$adminCount       = fn(string $sql) => (int) $pdo->query($sql)->fetchColumn();
$navPendingItems  = $adminCount('SELECT COUNT(*) FROM items WHERE is_verified = 0');
$navPendingClaims = $adminCount('SELECT COUNT(*) FROM claims WHERE status = "Pending"');

/* ---------- inline SVG icons (stroke icons, inherit text color) ---------- */
if (!function_exists('adminIcon')) {
    function adminIcon(string $name): string {
        static $p = [
            'home'   => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/>',
            'items'  => '<path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>',
            'claims' => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4h6v3H9z"/><path d="M9 14l2 2 4-4"/>',
            'users'  => '<circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-5.5 7-5.5s7 2 7 5.5"/><circle cx="17" cy="9" r="2.5"/><path d="M17 14.5c2.7 0 5 1.5 5 4.5"/>',
            'user'   => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
            'logout' => '<path d="M9 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
            'globe'  => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a14 14 0 0 1 0 18 14 14 0 0 1 0-18z"/>',
            'clock'  => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
            'arrow'  => '<path d="M5 12h14"/><path d="M13 6l6 6-6 6"/>',
            'chart'  => '<path d="M4 20V4"/><path d="M4 20h16"/><path d="M8 16l4-5 3 3 5-7"/>',
            'shield' => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3z"/><path d="M9 12l2 2 4-4"/>',
            'search' => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
            'swap'   => '<path d="M7 7h13l-3-3"/><path d="M17 17H4l3 3"/>',
            'check'  => '<path d="M5 12l5 5 9-10"/>',
        ];
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p[$name] . '</svg>';
    }
}

/* ---------- flash messages (tries your own helper first, then $_SESSION['flash']) ---------- */
if (!function_exists('adminFlash')) {
    function adminFlash(): void {
        foreach (['showFlash', 'displayFlash', 'renderFlash', 'flashMessages'] as $fn) {
            if (function_exists($fn)) {
                try { $fn(); return; } catch (Throwable $e) { /* wrong signature, try fallback */ }
            }
        }
        if (empty($_SESSION['flash'])) return;
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        $list = isset($f['message']) ? [$f] : (is_array($f) ? $f : [['type' => 'success', 'message' => (string) $f]]);
        foreach ($list as $k => $m) {
            if (is_array($m)) { $type = $m['type'] ?? 'success'; $msg = $m['message'] ?? ''; }
            else { $type = is_string($k) ? $k : 'success'; $msg = (string) $m; }
            $cls = $type === 'error' ? 'alert-error' : 'alert-success';
            echo '<div class="alert ' . $cls . '" role="status">' . clean($msg) . '</div>';
        }
    }
}

$pageIcons = ['dashboard' => 'home', 'items' => 'items', 'claims' => 'claims', 'users' => 'users'];
$navLink = function (string $key, string $href, string $icon, string $label, int $badge = 0) use ($activeNav): void {
    echo '<a href="' . $href . '"' . ($activeNav === $key ? ' class="active" aria-current="page"' : '') . '>'
       . adminIcon($icon) . '<span>' . $label . '</span>'
       . ($badge > 0 ? '<span class="nav-badge">' . $badge . '</span>' : '')
       . '</a>';
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= clean($pageTitle) ?> | <?= clean(SITE_NAME) ?> Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Darker+Grotesque:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-body">

<div class="admin-shell">
  <div class="admin-sidebar-overlay" id="adminOverlay"></div>

  <aside class="admin-sidebar" id="adminSidebar" aria-label="Admin navigation">
    <a class="admin-sidebar-brand" href="admin_dashboard.php">
      <svg class="brand-mark" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M11 4h13a2 2 0 0 1 2 2v10.5a2 2 0 0 1-.6 1.4L14.9 28.4a2 2 0 0 1-2.8 0L4.6 20.9a2 2 0 0 1 0-2.8L11.6 11" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
        <circle cx="11" cy="9" r="2.25" stroke="currentColor" stroke-width="2"/>
      </svg>
      <span><?= clean(SITE_NAME) ?><small>Admin panel</small></span>
    </a>

    <nav class="admin-nav">
      <div class="admin-nav-group">
        <div class="admin-nav-label">Overview</div>
        <?php $navLink('dashboard', 'admin_dashboard.php', 'home', 'Dashboard'); ?>
      </div>
      <div class="admin-nav-group">
        <div class="admin-nav-label">Manage</div>
        <?php
        $navLink('items',  'manage-items.php',  'items',  'Item Reports', $navPendingItems);
        $navLink('claims', 'manage-claims.php', 'claims', 'Claims',       $navPendingClaims);
        $navLink('users',  'user_manage.php',   'users',  'Users');
        ?>
      </div>
    </nav>

    <div class="admin-sidebar-foot">
      <a href="../index.php"><?= adminIcon('globe') ?><span>View public site</span></a>
      <a href="../logout.php"><?= adminIcon('logout') ?><span>Logout</span></a>
    </div>
  </aside>

  <div class="admin-content">
    <header class="admin-topbar">
      <button class="admin-sidebar-toggle" id="adminToggle" type="button" aria-label="Open menu" aria-controls="adminSidebar" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
      <div class="admin-topbar-title"><?= adminIcon($pageIcons[$activeNav] ?? 'home') ?> <?= clean($pageTitle) ?></div>
      <div class="admin-topbar-actions">
    <a class="admin-topbar-user" href="../profile.php" title="My profile">
      <span class="admin-avatar"><?= clean(strtoupper(substr($_SESSION['full_name'] ?? 'A', 0, 1))) ?></span>
      <span class="admin-user-name"><?= clean($_SESSION['full_name'] ?? 'Admin') ?></span>
    </a>
      </div>
    </header>

    <main class="admin-main">
      <?php adminFlash(); ?>