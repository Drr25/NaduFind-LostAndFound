<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
requireAdmin();

// ----- Handle actions -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $itemId = (int) ($_POST['item_id'] ?? 0);

    if ($action === 'verify') {
        $pdo->prepare('UPDATE items SET is_verified = 1 WHERE id = ?')->execute([$itemId]);
        flash('success', 'Item verified and now visible to the public.');
    } elseif ($action === 'unverify') {
        $pdo->prepare('UPDATE items SET is_verified = 0 WHERE id = ?')->execute([$itemId]);
        flash('success', 'Item hidden from public listing.');
    } elseif ($action === 'update_status') {
        $newStatus = $_POST['status'] ?? '';
        if (in_array($newStatus, ['Lost', 'Found', 'Claimed', 'Returned'], true)) {
            $old = $pdo->prepare('SELECT status FROM items WHERE id = ?');
            $old->execute([$itemId]);
            $oldStatus = $old->fetchColumn();

            $pdo->prepare('UPDATE items SET status = ? WHERE id = ?')->execute([$newStatus, $itemId]);
            $log = $pdo->prepare('INSERT INTO item_status_log (item_id, old_status, new_status, changed_by) VALUES (?, ?, ?, ?)');
            $log->execute([$itemId, $oldStatus, $newStatus, currentUserId()]);

            flash('success', 'Status updated to "' . $newStatus . '".');
        }
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare('SELECT photo FROM items WHERE id = ?');
        $stmt->execute([$itemId]);
        $photo = $stmt->fetchColumn();
        $pdo->prepare('DELETE FROM items WHERE id = ?')->execute([$itemId]);
        if ($photo && file_exists(UPLOAD_DIR . $photo)) {
            @unlink(UPLOAD_DIR . $photo);
        }
        flash('success', 'Item report deleted.');
    }

    $back = [];
    if (!empty($_GET['filter'])) $back[] = 'filter=' . urlencode($_GET['filter']);
    if (!empty($_GET['q']))      $back[] = 'q=' . urlencode($_GET['q']);
    redirect('manage-items.php' . ($back ? '?' . implode('&', $back) : ''));
}

// ----- Filter -----
$filter = $_GET['filter'] ?? 'all';
if (!in_array($filter, ['all', 'pending', 'verified'], true)) $filter = 'all';
$search = trim($_GET['q'] ?? '');

$cntAll      = (int) $pdo->query('SELECT COUNT(*) FROM items')->fetchColumn();
$cntPending  = (int) $pdo->query('SELECT COUNT(*) FROM items WHERE is_verified = 0')->fetchColumn();
$cntVerified = $cntAll - $cntPending;

$sql = 'SELECT items.*, users.full_name FROM items JOIN users ON users.id = items.user_id';
$where = [];
$params = [];

if ($filter === 'pending') $where[] = 'items.is_verified = 0';
elseif ($filter === 'verified') $where[] = 'items.is_verified = 1';

if ($search !== '') {
    $where[] = '(items.item_name LIKE ? OR users.full_name LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY items.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

$qs = $search !== '' ? '&q=' . urlencode($search) : '';

$pageTitle = 'Manage Items';
$activeNav = 'items';
include '../includes/admin-header.php';
?>

<div class="page-head">
  <h1>Manage Item Reports</h1>
  <p>Verify new reports and update item status as cases progress.</p>
</div>

<div class="manage-bar">
  <div class="pill-tabs">
    <a href="?filter=all<?= $qs ?>" class="<?= $filter === 'all' ? 'active' : '' ?>">All <span class="count"><?= $cntAll ?></span></a>
    <a href="?filter=pending<?= $qs ?>" class="<?= $filter === 'pending' ? 'active' : '' ?>">Pending Verification <span class="count"><?= $cntPending ?></span></a>
    <a href="?filter=verified<?= $qs ?>" class="<?= $filter === 'verified' ? 'active' : '' ?>">Verified <span class="count"><?= $cntVerified ?></span></a>
  </div>
  <form class="search-inline" method="GET" role="search">
    <input type="hidden" name="filter" value="<?= clean($filter) ?>">
    <input type="search" name="q" value="<?= clean($search) ?>" placeholder="Search item or reporter..." aria-label="Search items">
    <button class="btn btn-accent btn-sm" type="submit">Search</button>
  </form>
</div>

<?php if ($search !== ''): ?>
  <p class="results-count">Search results for "<?= clean($search) ?>" &mdash; <a href="?filter=<?= clean($filter) ?>">clear search</a></p>
<?php endif; ?>

<section class="panel">
  <div class="table-wrap admin-table">
    <table>
      <thead>
        <tr><th>Photo</th><th>Item</th><th>Type</th><th>Reporter</th><th>Status</th><th>Verified</th><th>Date</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($items as $item): ?>
          <tr>
            <td>
              <?php if ($item['photo']): ?>
                <img class="thumb-sm" src="../uploads/<?= clean($item['photo']) ?>" alt="">
              <?php else: ?>
                <span class="thumb-placeholder">📦</span>
              <?php endif; ?>
            </td>
            <td class="wrap"><a href="../item-details.php?id=<?= $item['id'] ?>" target="_blank" rel="noopener"><?= clean($item['item_name']) ?></a></td>
            <td><?= $item['report_type'] === 'lost' ? 'Lost' : 'Found' ?></td>
            <td><?= clean($item['full_name']) ?></td>
            <td>
              <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                <select class="status-select" name="status" aria-label="Item status" onchange="this.form.submit()">
                  <?php foreach (['Lost', 'Found', 'Claimed', 'Returned'] as $s): ?>
                    <option value="<?= $s ?>" <?= $item['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
            </td>
            <td><?= $item['is_verified'] ? '<span class="badge badge-approved">Verified</span>' : '<span class="badge badge-unverified">Pending</span>' ?></td>
            <td><?= timeAgo($item['created_at']) ?></td>
            <td class="actions-cell">
              <?php if (!$item['is_verified']): ?>
                <form method="POST"><input type="hidden" name="action" value="verify"><input type="hidden" name="item_id" value="<?= $item['id'] ?>"><button class="btn btn-sm btn-success">Verify</button></form>
              <?php else: ?>
                <form method="POST"><input type="hidden" name="action" value="unverify"><input type="hidden" name="item_id" value="<?= $item['id'] ?>"><button class="btn btn-sm btn-outline">Unverify</button></form>
              <?php endif; ?>
              <form method="POST" data-confirm="Delete this report permanently?">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                <button class="btn btn-sm btn-danger">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$items): ?>
          <tr><td colspan="8" class="text-center">No items found for this filter.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="table-foot">Showing <?= count($items) ?> report<?= count($items) === 1 ? '' : 's' ?></div>
</section>

<?php include '../includes/admin-footer.php'; ?>