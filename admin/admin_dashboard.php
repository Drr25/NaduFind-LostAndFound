<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
requireAdmin();

/* ---------- numbers ---------- */
$count = fn(string $sql) => (int) $pdo->query($sql)->fetchColumn();
$pct   = fn(int $n, int $total) => $total > 0 ? (int) round($n / $total * 100) : 0;

$totalItems       = $count('SELECT COUNT(*) FROM items');
$pendingVerify    = $count('SELECT COUNT(*) FROM items WHERE is_verified = 0');
$pendingClaims    = $count('SELECT COUNT(*) FROM claims WHERE status = "Pending"');
$totalUsers       = $count('SELECT COUNT(*) FROM users');
$awaitingHandover = $count('SELECT COUNT(*) FROM claims JOIN items ON items.id = claims.item_id WHERE claims.status = "Approved" AND items.status <> "Returned"');

$statusCounts = ['Lost' => 0, 'Found' => 0, 'Claimed' => 0, 'Returned' => 0];
foreach ($pdo->query('SELECT status, COUNT(*) c FROM items GROUP BY status') as $r) {
    if (isset($statusCounts[$r['status']])) $statusCounts[$r['status']] = (int) $r['c'];
}
$claimCounts = ['Pending' => 0, 'Approved' => 0, 'Rejected' => 0];
foreach ($pdo->query('SELECT status, COUNT(*) c FROM claims GROUP BY status') as $r) {
    if (isset($claimCounts[$r['status']])) $claimCounts[$r['status']] = (int) $r['c'];
}
$totalClaims    = array_sum($claimCounts);
$totalReturned  = $statusCounts['Returned'];
$verifiedCount  = $totalItems - $pendingVerify;
$claimsReviewed = $claimCounts['Approved'] + $claimCounts['Rejected'];

/* ---------- reports per month (last 6 months, lost vs found) ---------- */
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $key = date('Y-m', strtotime("first day of -$i month"));
    $months[$key] = ['label' => date('M', strtotime($key . '-01')), 'lost' => 0, 'found' => 0];
}
$rows = $pdo->query(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') ym, report_type, COUNT(*) c
     FROM items
     WHERE created_at >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
     GROUP BY ym, report_type"
)->fetchAll();
foreach ($rows as $r) {
    if (isset($months[$r['ym']])) {
        $months[$r['ym']][strtolower($r['report_type']) === 'lost' ? 'lost' : 'found'] += (int) $r['c'];
    }
}
$chartMax = 1;
foreach ($months as $m) $chartMax = max($chartMax, $m['lost'] + $m['found']);
$chartTotal = array_sum(array_map(fn($m) => $m['lost'] + $m['found'], $months));

/* ---------- recent activity ---------- */
$recentItems  = $pdo->query('SELECT items.*, users.full_name FROM items JOIN users ON users.id = items.user_id ORDER BY items.created_at DESC LIMIT 5')->fetchAll();
$recentClaims = $pdo->query('SELECT claims.*, items.item_name, users.full_name AS claimer_name FROM claims JOIN items ON items.id = claims.item_id JOIN users ON users.id = claims.claimer_id ORDER BY claims.created_at DESC LIMIT 5')->fetchAll();
$recentUsers  = $pdo->query('SELECT * FROM users ORDER BY created_at DESC LIMIT 5')->fetchAll();

$firstName = explode(' ', trim($_SESSION['full_name'] ?? 'Admin'))[0];

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
include '../includes/admin-header.php';
?>

<!-- ========== Welcome banner ========== -->
<section class="dash-banner">
  <svg class="dash-banner-art" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <path d="M11 4h13a2 2 0 0 1 2 2v10.5a2 2 0 0 1-.6 1.4L14.9 28.4a2 2 0 0 1-2.8 0L4.6 20.9a2 2 0 0 1 0-2.8L11.6 11" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"/>
    <circle cx="11" cy="9" r="2.25" stroke="currentColor" stroke-width="1.2"/>
  </svg>
  <div class="dash-banner-text">
    <h2>Welcome back, <?= clean($firstName) ?>! 👋</h2>
    <p>Here's what's happening in <?= clean(SITE_NAME) ?> today. <?= $totalReturned ?> item<?= $totalReturned === 1 ? '' : 's' ?> returned to their owners so far.</p>
  </div>
  <?php if ($pendingVerify > 0): ?>
    <a class="dash-cta" href="manage-items.php?filter=pending">Review <?= $pendingVerify ?> pending <?= adminIcon('arrow') ?></a>
  <?php endif; ?>
</section>

<!-- ========== Overview ========== -->
<h2 class="dash-section-title">Overview</h2>
<p class="dash-section-sub">A quick summary of your platform stats and recent activity.</p>

<div class="stat-row">
  <a class="stat-tile" href="manage-items.php">
    <span class="stat-ico blue"><?= adminIcon('items') ?></span>
    <span><span class="stat-num"><?= $totalItems ?></span><span class="stat-label">Total Reports</span></span>
  </a>
  <a class="stat-tile" href="manage-items.php?filter=pending">
    <span class="stat-ico rust"><?= adminIcon('clock') ?></span>
    <span><span class="stat-num"><?= $pendingVerify ?></span><span class="stat-label">Pending Verification</span></span>
  </a>
  <a class="stat-tile" href="manage-claims.php?filter=pending">
    <span class="stat-ico teal"><?= adminIcon('claims') ?></span>
    <span><span class="stat-num"><?= $pendingClaims ?></span><span class="stat-label">Pending Claims</span></span>
  </a>
  <a class="stat-tile" href="user_manage.php">
    <span class="stat-ico sage"><?= adminIcon('users') ?></span>
    <span><span class="stat-num"><?= $totalUsers ?></span><span class="stat-label">Registered Users</span></span>
  </a>
</div>

<!-- ========== Recent reports + recent claims (side by side) ========== -->
<div class="dash-grid g-1-1">

  <section class="panel">
    <div class="panel-head">
      <h3 class="panel-title"><?= adminIcon('items') ?> Recent Reports</h3>
      <a class="panel-link" href="manage-items.php">View All <?= adminIcon('arrow') ?></a>
    </div>
    <div class="table-wrap admin-table">
      <table>
        <thead><tr><th>Item</th><th>Reporter</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
          <?php foreach ($recentItems as $item): ?>
            <tr>
              <td class="wrap"><a href="../item-details.php?id=<?= $item['id'] ?>" target="_blank" rel="noopener"><strong><?= clean($item['item_name']) ?></strong></a>
                <small class="cell-sub"><?= $item['report_type'] === 'lost' ? 'Lost' : 'Found' ?> &middot; <?= $item['is_verified'] ? 'Verified' : 'Pending' ?></small></td>
              <td><?= clean($item['full_name']) ?></td>
              <td><span class="<?= statusBadgeClass($item['status']) ?>"><?= clean($item['status']) ?></span></td>
              <td><?= timeAgo($item['created_at']) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$recentItems): ?>
            <tr><td colspan="4" class="text-center">No reports yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="panel">
    <div class="panel-head">
      <h3 class="panel-title"><?= adminIcon('claims') ?> Recent Claims</h3>
      <a class="panel-link" href="manage-claims.php?filter=all">View All <?= adminIcon('arrow') ?></a>
    </div>
    <?php foreach ($recentClaims as $c): ?>
      <div class="list-row">
        <div class="list-main">
          <div class="list-title"><?= clean($c['item_name']) ?></div>
          <div class="list-sub"><?= clean($c['claimer_name']) ?> &middot; <?= timeAgo($c['created_at']) ?></div>
        </div>
        <span class="badge badge-<?= strtolower($c['status']) ?>"><?= clean($c['status']) ?></span>
      </div>
    <?php endforeach; ?>
    <?php if (!$recentClaims): ?>
      <div class="panel-empty">No claims yet.</div>
    <?php endif; ?>
  </section>
</div>

<!-- ========== Newest users ========== -->
<section class="panel" style="margin-bottom:24px;">
  <div class="panel-head">
    <h3 class="panel-title"><?= adminIcon('users') ?> Newest Users</h3>
    <a class="panel-link" href="user_manage.php">View All <?= adminIcon('arrow') ?></a>
  </div>
  <div class="table-wrap admin-table">
    <table>
      <thead><tr><th>User</th><th>Role</th><th>Status</th><th>Joined</th></tr></thead>
      <tbody>
        <?php foreach ($recentUsers as $u): ?>
          <tr>
            <td class="wrap">
              <div class="user-cell">
                <span class="avatar"><?= clean(strtoupper(substr($u['full_name'], 0, 1))) ?></span>
                <span><strong><?= clean($u['full_name']) ?></strong><small class="cell-sub"><?= clean($u['email']) ?></small></span>
              </div>
            </td>
            <td><span class="badge <?= $u['role'] === 'admin' ? 'badge-approved' : 'badge-unverified' ?>"><?= ucfirst($u['role']) ?></span></td>
            <td><?= $u['is_active'] ? '<span class="badge badge-approved">Active</span>' : '<span class="badge badge-rejected">Deactivated</span>' ?></td>
            <td><?= timeAgo($u['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<!-- ========== Insights: chart + progress ========== -->
<h2 class="dash-section-title">Insights</h2>
<p class="dash-section-sub">Report trends and how far along your cases are.</p>

<div class="dash-grid g-2-1">

  <section class="panel">
    <div class="panel-head">
      <h3 class="panel-title"><?= adminIcon('chart') ?> Reports in the last 6 months</h3>
      <div class="chart-legend"><span><i class="seg-lost"></i>Lost</span><span><i class="seg-found"></i>Found</span></div>
    </div>
    <div class="panel-body">
      <?php if ($chartTotal === 0): ?>
        <div class="panel-empty">No reports in the last 6 months yet.</div>
      <?php else: ?>
        <div class="chart" role="img" aria-label="Stacked bar chart of lost and found reports per month">
          <?php foreach ($months as $m):
              $t = $m['lost'] + $m['found'];
              $h = $t > 0 ? max(6, (int) round($t / $chartMax * 150)) : 0; ?>
            <div class="chart-col">
              <div class="chart-area">
                <?php if ($t > 0): ?>
                  <span class="chart-total"><?= $t ?></span>
                  <div class="chart-bar" style="height:<?= $h ?>px" title="<?= clean($m['label']) ?>: <?= $m['lost'] ?> lost, <?= $m['found'] ?> found">
                    <?php if ($m['lost'] > 0): ?><span class="seg-lost" style="flex-grow:<?= $m['lost'] ?>"></span><?php endif; ?>
                    <?php if ($m['found'] > 0): ?><span class="seg-found" style="flex-grow:<?= $m['found'] ?>"></span><?php endif; ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="chart-labels">
          <?php foreach ($months as $m): ?><span><?= clean($m['label']) ?></span><?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <section class="panel">
    <div class="panel-head">
      <h3 class="panel-title"><?= adminIcon('shield') ?> Progress tracker</h3>
    </div>
    <div class="panel-body">
      <?php $p1 = $pct($verifiedCount, $totalItems); $p2 = $pct($claimsReviewed, $totalClaims); $p3 = $pct($totalReturned, $totalItems); ?>
      <div class="meter">
        <div class="meter-head"><span>Reports verified</span><strong><?= $p1 ?>%</strong></div>
        <div class="meter-track" role="progressbar" aria-label="Reports verified" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $p1 ?>"><span class="meter-fill" style="--w:<?= $p1 ?>%"></span></div>
        <span class="meter-note"><?= $verifiedCount ?> of <?= $totalItems ?> reports are public</span>
      </div>
      <div class="meter">
        <div class="meter-head"><span>Claims reviewed</span><strong><?= $p2 ?>%</strong></div>
        <div class="meter-track" role="progressbar" aria-label="Claims reviewed" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $p2 ?>"><span class="meter-fill is-claimed" style="--w:<?= $p2 ?>%"></span></div>
        <span class="meter-note"><?= $claimsReviewed ?> of <?= $totalClaims ?> claims decided</span>
      </div>
      <div class="meter">
        <div class="meter-head"><span>Items returned</span><strong><?= $p3 ?>%</strong></div>
        <div class="meter-track" role="progressbar" aria-label="Items returned" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $p3 ?>"><span class="meter-fill is-returned" style="--w:<?= $p3 ?>%"></span></div>
        <span class="meter-note"><?= $totalReturned ?> of <?= $totalItems ?> reports closed</span>
      </div>
    </div>
  </section>
</div>

<!-- ========== Status breakdown + quick actions ========== -->
<div class="dash-grid g-1-1">

  <section class="panel">
    <div class="panel-head">
      <h3 class="panel-title"><?= adminIcon('swap') ?> Reports by status</h3>
      <a class="panel-link" href="manage-items.php">Manage <?= adminIcon('arrow') ?></a>
    </div>
    <div class="panel-body">
      <?php foreach ($statusCounts as $label => $n): $p = $pct($n, $totalItems); ?>
        <div class="meter">
          <div class="meter-head"><span><?= $label ?></span><strong><?= $n ?> <small>(<?= $p ?>%)</small></strong></div>
          <div class="meter-track" role="progressbar" aria-label="<?= $label ?> reports" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $p ?>"><span class="meter-fill is-<?= strtolower($label) ?>" style="--w:<?= $p ?>%"></span></div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="panel">
    <div class="panel-head">
      <h3 class="panel-title"><?= adminIcon('check') ?> Quick actions</h3>
    </div>
    <div class="panel-body">
      <div class="qa-grid">
        <a class="qa" href="manage-items.php?filter=pending"><?= adminIcon('shield') ?><span><strong>Verify new reports</strong><small><?= $pendingVerify ?> waiting</small></span></a>
        <a class="qa" href="manage-claims.php?filter=pending"><?= adminIcon('claims') ?><span><strong>Review claims</strong><small><?= $pendingClaims ?> pending</small></span></a>
        <a class="qa" href="manage-claims.php?filter=approved"><?= adminIcon('check') ?><span><strong>Hand over items</strong><small><?= $awaitingHandover ?> approved, not yet returned</small></span></a>
        <a class="qa" href="manage-items.php"><?= adminIcon('swap') ?><span><strong>Update item status</strong><small>Lost, Found, Claimed, Returned</small></span></a>
        <a class="qa" href="user_manage.php"><?= adminIcon('users') ?><span><strong>Manage users</strong><small><?= $totalUsers ?> accounts</small></span></a>
        <a class="qa" href="../index.php"><?= adminIcon('globe') ?><span><strong>View public site</strong><small>See what students see</small></span></a>
      </div>
    </div>
  </section>
</div>

<?php include '../includes/admin-footer.php'; ?>