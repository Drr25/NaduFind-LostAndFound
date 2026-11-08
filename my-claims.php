<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
requireStudent();

$stmt = $pdo->prepare(
    'SELECT claims.*, items.item_name, items.photo, items.status AS item_status
     FROM claims JOIN items ON items.id = claims.item_id
     WHERE claims.claimer_id = ?
     ORDER BY claims.created_at DESC'
);
$stmt->execute([currentUserId()]);
$claims = $stmt->fetchAll();

$pageTitle = 'My Claims';
$activeNav = 'claims';
include 'includes/pub_header.php';
?>

<div class="page-head">
  <h1>My Claims</h1>
  <p>Track the status of claims you've submitted for found items.</p>
</div>

<?php if (!$claims): ?>
  <div class="panel">
    <div class="panel-empty">
      <div class="icon" style="font-size:2.6rem;">📋</div>
      <p>You haven't submitted any claims yet.</p>
      <a href="index.php" class="btn btn-gold mt-16">Browse Found Items</a>
    </div>
  </div>
<?php else: ?>
  <section class="panel">
    <div class="panel-head">
      <h2 class="panel-title">Submitted Claims</h2>
      <span class="cell-sub"><?= count($claims) ?> claim<?= count($claims) === 1 ? '' : 's' ?></span>
    </div>
    <div class="table-wrap admin-table">
      <table>
        <thead>
          <tr><th>Photo</th><th>Item</th><th>Claim Status</th><th>Item Status</th><th>Submitted</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($claims as $c): ?>
            <tr>
              <td>
                <?php if ($c['photo']): ?>
                  <img class="thumb-sm" src="uploads/<?= clean($c['photo']) ?>" alt="">
                <?php else: ?>
                  <span class="thumb-placeholder">📦</span>
                <?php endif; ?>
              </td>
              <td class="wrap"><strong><?= clean($c['item_name']) ?></strong></td>
              <td><span class="badge badge-<?= strtolower($c['status']) ?>"><?= clean($c['status']) ?></span></td>
              <td><span class="<?= statusBadgeClass($c['item_status']) ?>"><?= clean($c['item_status']) ?></span></td>
              <td><?= timeAgo($c['created_at']) ?></td>
              <td><a href="item-details.php?id=<?= $c['item_id'] ?>" class="btn btn-sm btn-outline">View Item</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
<?php endif; ?>

<?php include 'includes/pub_footer.php'; ?>