<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
requireStudent();

$stmt = $pdo->prepare('SELECT * FROM items WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([currentUserId()]);
$items = $stmt->fetchAll();

$pageTitle = 'My Reports';
$activeNav = 'items';
include 'includes/pub_header.php';
?>

<div class="page-head">
  <h1>My Reports</h1>
  <p>Track the status of items you've reported as lost or found.</p>
</div>

<?php if (!$items): ?>
  <div class="panel">
    <div class="panel-empty">
      <div class="icon" style="font-size:2.6rem;">🗂️</div>
      <p>You haven't reported any items yet.</p>
      <a href="report-item.php" class="btn btn-gold mt-16">Report an Item</a>
    </div>
  </div>
<?php else: ?>
  <section class="panel">
    <div class="panel-head">
      <h2 class="panel-title">Your Reports</h2>
      <a class="btn btn-gold btn-sm" href="report-item.php">+ New Report</a>
    </div>
    <div class="table-wrap admin-table">
      <table>
        <thead>
          <tr>
            <th>Photo</th><th>Item</th><th>Type</th><th>Category</th>
            <th>Status</th><th>Verified</th><th>Reported</th><th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item): ?>
            <tr>
              <td>
                <?php if ($item['photo']): ?>
                  <img class="thumb-sm" src="uploads/<?= clean($item['photo']) ?>" alt="">
                <?php else: ?>
                  <span class="thumb-placeholder">📦</span>
                <?php endif; ?>
              </td>
              <td class="wrap"><strong><?= clean($item['item_name']) ?></strong></td>
              <td><?= $item['report_type'] === 'lost' ? 'Lost' : 'Found' ?></td>
              <td><?= clean($item['category']) ?></td>
              <td><span class="<?= statusBadgeClass($item['status']) ?>"><?= clean($item['status']) ?></span></td>
              <td><?= $item['is_verified'] ? '<span class="badge badge-approved">Verified</span>' : '<span class="badge badge-unverified">Pending</span>' ?></td>
              <td><?= timeAgo($item['created_at']) ?></td>
              <td><a href="item-details.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline">View</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
<?php endif; ?>

<?php include 'includes/pub_footer.php'; ?>