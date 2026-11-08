<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    'SELECT items.*, users.full_name AS reporter_name
     FROM items JOIN users ON users.id = items.user_id
     WHERE items.id = ?'
);
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item || (!$item['is_verified'] && (!isLoggedIn() || ($item['user_id'] != currentUserId() && !isAdmin())))) {
    $pageTitle = 'Item Not Found';
    $activeNav = 'browse';
    include 'includes/pub_header.php';
    echo '<div class="empty-state"><div class="icon">🚫</div><p>Item not found or not yet verified. <a href="index.php">Back to browse</a></p></div>';
    include 'includes/pub_footer.php';
    exit;
}

$isOwner  = isLoggedIn() && $item['user_id'] == currentUserId();
$canClaim = isLoggedIn() && !isAdmin() && !$isOwner && $item['status'] === 'Found';

// Check if current user already has a pending/approved claim on this item
$alreadyClaimed = false;
if (isLoggedIn()) {
    $c = $pdo->prepare('SELECT id FROM claims WHERE item_id = ? AND claimer_id = ? AND status != "Rejected"');
    $c->execute([$id, currentUserId()]);
    $alreadyClaimed = (bool) $c->fetch();
}

$pageTitle = $item['item_name'];
$activeNav = 'browse';
include 'includes/pub_header.php';
?>

<a href="index.php" class="panel-link">&larr; Back to browse</a>

<div class="detail-wrap mt-16">
  <div class="detail-photo">
    <?php if ($item['photo']): ?>
      <img src="uploads/<?= clean($item['photo']) ?>" alt="<?= clean($item['item_name']) ?>">
    <?php else: ?>
      <div class="item-photo placeholder" style="aspect-ratio:4/3;"><span style="font-size:4rem;">📦</span></div>
    <?php endif; ?>
  </div>

  <div class="pf-card">
    <div class="pf-section-head">
      <div style="flex:1;min-width:0;">
        <h3 style="font-size:1.5rem;"><?= clean($item['item_name']) ?></h3>
        <p><?= $item['report_type'] === 'lost' ? 'Lost item report' : 'Found item report' ?></p>
      </div>
      <span class="<?= statusBadgeClass($item['status']) ?>"><?= clean($item['status']) ?></span>
    </div>

    <div class="pf-section-body">
      <?php if (!$item['is_verified']): ?>
        <div class="alert alert-error">This report is pending admin verification &mdash; only visible to you and admins.</div>
      <?php endif; ?>

      <p style="margin-top:0;"><?= nl2br(clean($item['description'])) ?></p>

      <div class="info-row"><span>Report Type</span><span><?= $item['report_type'] === 'lost' ? 'Lost Item' : 'Found Item' ?></span></div>
      <div class="info-row"><span>Category</span><span><?= clean($item['category']) ?></span></div>
      <div class="info-row"><span>Location</span><span><?= clean($item['location']) ?></span></div>
      <div class="info-row"><span>Date</span><span><?= date('M j, Y', strtotime($item['event_date'])) ?></span></div>
      <div class="info-row"><span>Reported By</span><span><?= clean($item['reporter_name']) ?></span></div>
      <div class="info-row"><span>Reported On</span><span><?= timeAgo($item['created_at']) ?></span></div>

      <?php if ($canClaim && !$alreadyClaimed): ?>
        <a href="claim-item.php?item_id=<?= $item['id'] ?>" class="btn btn-gold btn-block mt-16">Claim This Item</a>
      <?php elseif ($alreadyClaimed): ?>
        <div class="alert alert-success mt-16">You already submitted a claim for this item. Check <a href="my-claims.php">My Claims</a> for status.</div>
      <?php elseif (!isLoggedIn() && $item['status'] === 'Found'): ?>
        <a href="login.php" class="btn btn-gold btn-block mt-16">Log in to Claim This Item</a>
      <?php elseif ($isOwner): ?>
        <p class="mt-16" style="color:var(--text-soft);font-size:.9rem;">This is your report. Track its status under <a href="my-items.php">My Reports</a>.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include 'includes/pub_footer.php'; ?>