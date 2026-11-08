<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
requireAdmin();

$allowedFilters = ['pending', 'approved', 'rejected', 'all'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $claimId = (int) ($_POST['claim_id'] ?? 0);

    $stmt = $pdo->prepare('SELECT * FROM claims WHERE id = ?');
    $stmt->execute([$claimId]);
    $claim = $stmt->fetch();

    if ($claim) {
        if ($action === 'approve') {
            $pdo->prepare('UPDATE claims SET status = "Approved" WHERE id = ?')->execute([$claimId]);
            // Reject other pending claims on the same item
            $pdo->prepare('UPDATE claims SET status = "Rejected", admin_notes = "Another claim was approved" WHERE item_id = ? AND id != ? AND status = "Pending"')
                ->execute([$claim['item_id'], $claimId]);
            // Move item status to Claimed
            $old = $pdo->prepare('SELECT status FROM items WHERE id = ?');
            $old->execute([$claim['item_id']]);
            $oldStatus = $old->fetchColumn();
            $pdo->prepare('UPDATE items SET status = "Claimed" WHERE id = ?')->execute([$claim['item_id']]);
            $pdo->prepare('INSERT INTO item_status_log (item_id, old_status, new_status, changed_by) VALUES (?, ?, "Claimed", ?)')
                ->execute([$claim['item_id'], $oldStatus, currentUserId()]);

            flash('success', 'Claim approved. Item marked as Claimed.');
        } elseif ($action === 'reject') {
            $pdo->prepare('UPDATE claims SET status = "Rejected", admin_notes = ? WHERE id = ?')
                ->execute([trim($_POST['admin_notes'] ?? ''), $claimId]);
            flash('success', 'Claim rejected.');
        } elseif ($action === 'mark_returned') {
            $old = $pdo->prepare('SELECT status FROM items WHERE id = ?');
            $old->execute([$claim['item_id']]);
            $oldStatus = $old->fetchColumn();
            $pdo->prepare('UPDATE items SET status = "Returned" WHERE id = ?')->execute([$claim['item_id']]);
            $pdo->prepare('INSERT INTO item_status_log (item_id, old_status, new_status, changed_by) VALUES (?, ?, "Returned", ?)')
                ->execute([$claim['item_id'], $oldStatus, currentUserId()]);
            flash('success', 'Item marked as Returned to owner.');
        }
    }

    redirect('manage-claims.php' . (isset($_GET['filter']) ? '?filter=' . urlencode($_GET['filter']) : ''));
}

$filter = $_GET['filter'] ?? 'pending';
if (!in_array($filter, $allowedFilters, true)) $filter = 'pending';

$counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
foreach ($pdo->query('SELECT LOWER(status) s, COUNT(*) c FROM claims GROUP BY status') as $r) {
    if (isset($counts[$r['s']])) $counts[$r['s']] = (int) $r['c'];
}
$counts['all'] = array_sum($counts);

$sql = "SELECT claims.*, items.item_name, items.photo, items.status AS item_status, users.full_name AS claimer_name, users.email AS claimer_email
        FROM claims
        JOIN items ON items.id = claims.item_id
        JOIN users ON users.id = claims.claimer_id";
if ($filter !== 'all') {
    $sql .= ' WHERE claims.status = ' . $pdo->quote(ucfirst($filter));
}
$sql .= ' ORDER BY claims.created_at DESC';
$claims = $pdo->query($sql)->fetchAll();

$pageTitle = 'Manage Claims';
$activeNav = 'claims';
include '../includes/admin-header.php';
?>

<div class="page-head">
  <h1>Manage Claims</h1>
  <p>Review claims submitted by users for found items.</p>
</div>

<div class="manage-bar">
  <div class="pill-tabs">
    <a href="?filter=pending" class="<?= $filter === 'pending' ? 'active' : '' ?>">Pending <span class="count"><?= $counts['pending'] ?></span></a>
    <a href="?filter=approved" class="<?= $filter === 'approved' ? 'active' : '' ?>">Approved <span class="count"><?= $counts['approved'] ?></span></a>
    <a href="?filter=rejected" class="<?= $filter === 'rejected' ? 'active' : '' ?>">Rejected <span class="count"><?= $counts['rejected'] ?></span></a>
    <a href="?filter=all" class="<?= $filter === 'all' ? 'active' : '' ?>">All <span class="count"><?= $counts['all'] ?></span></a>
  </div>
</div>

<section class="panel">
  <div class="table-wrap admin-table">
    <table>
      <thead>
        <tr><th>Item</th><th>Claimant</th><th>Proof</th><th>Contact</th><th>Status</th><th>Item Status</th><th>Submitted</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($claims as $c): ?>
          <tr>
            <td class="wrap"><a href="../item-details.php?id=<?= $c['item_id'] ?>" target="_blank" rel="noopener"><?= clean($c['item_name']) ?></a></td>
            <td class="wrap"><?= clean($c['claimer_name']) ?><small class="cell-sub"><?= clean($c['claimer_email']) ?></small></td>
            <td class="wrap proof-cell"><?= clean($c['proof_description']) ?></td>
            <td class="wrap"><?= clean($c['contact_info']) ?></td>
            <td><span class="badge badge-<?= strtolower($c['status']) ?>"><?= clean($c['status']) ?></span></td>
            <td><span class="<?= statusBadgeClass($c['item_status']) ?>"><?= clean($c['item_status']) ?></span></td>
            <td><?= timeAgo($c['created_at']) ?></td>
            <td class="actions-cell">
              <?php if ($c['status'] === 'Pending'): ?>
                <form method="POST" data-confirm="Approve this claim?"><input type="hidden" name="action" value="approve"><input type="hidden" name="claim_id" value="<?= $c['id'] ?>"><button class="btn btn-sm btn-success">Approve</button></form>
                <form method="POST" data-confirm="Reject this claim?"><input type="hidden" name="action" value="reject"><input type="hidden" name="claim_id" value="<?= $c['id'] ?>"><button class="btn btn-sm btn-danger">Reject</button></form>
              <?php elseif ($c['status'] === 'Approved' && $c['item_status'] !== 'Returned'): ?>
                <form method="POST" data-confirm="Mark item as returned to owner?"><input type="hidden" name="action" value="mark_returned"><input type="hidden" name="claim_id" value="<?= $c['id'] ?>"><button class="btn btn-sm btn-outline">Mark Returned</button></form>
              <?php else: ?>
                &mdash;
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$claims): ?>
          <tr><td colspan="8" class="text-center">No claims found for this filter.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="table-foot">Showing <?= count($claims) ?> claim<?= count($claims) === 1 ? '' : 's' ?></div>
</section>

<?php include '../includes/admin-footer.php'; ?>