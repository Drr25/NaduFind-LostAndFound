<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
requireStudent();

$itemId = (int) ($_GET['item_id'] ?? $_POST['item_id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM items WHERE id = ? AND is_verified = 1');
$stmt->execute([$itemId]);
$item = $stmt->fetch();

if (!$item || $item['status'] !== 'Found') {
    flash('error', 'This item is not available for claiming.');
    redirect('index.php');
}

if ($item['user_id'] == currentUserId()) {
    flash('error', 'You cannot claim your own reported item.');
    redirect('item-details.php?id=' . $itemId);
}

$errors = [];
$old = ['proof_description' => '', 'contact_info' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['proof_description'] = trim($_POST['proof_description'] ?? '');
    $old['contact_info']      = trim($_POST['contact_info'] ?? '');

    if (strlen($old['proof_description']) < 10) {
        $errors[] = 'Please provide more detail proving this item belongs to you (at least 10 characters).';
    }
    if ($old['contact_info'] === '') {
        $errors[] = 'Please provide a way to contact you.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO claims (item_id, claimer_id, proof_description, contact_info, status)
             VALUES (?, ?, ?, ?, "Pending")'
        );
        $stmt->execute([$itemId, currentUserId(), $old['proof_description'], $old['contact_info']]);

        flash('success', 'Claim submitted! An administrator will review it soon.');
        redirect('my-claims.php');
    }
}

$pageTitle = 'Claim Item';
$activeNav = 'claims';
include 'includes/pub_header.php';
?>

<div class="page-head">
  <h1>Claim: <?= clean($item['item_name']) ?></h1>
  <p>Provide details that prove this item belongs to you. Admins will verify before approving.</p>
</div>

<?php foreach ($errors as $e): ?>
  <div class="alert alert-error" role="alert"><?= clean($e) ?></div>
<?php endforeach; ?>

<section class="pf-card pf-section">
  <div class="pf-section-head">
    <span class="pf-section-ico"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l8 3v6c0 4.5-3.2 8-8 9-4.8-1-8-4.5-8-9V6l8-3z"/><path d="M9 12l2 2 4-4"/></svg></span>
    <div>
      <h3>Proof of Ownership</h3>
      <p>Only the real owner should know these details.</p>
    </div>
  </div>
  <div class="pf-section-body">
    <form method="POST" class="pf-form">
      <input type="hidden" name="item_id" value="<?= $itemId ?>">
      <div class="field">
        <label for="proof_description">Proof of Ownership</label>
        <textarea id="proof_description" name="proof_description" placeholder="Describe unique details only the owner would know (contents, serial number, scratches, etc.)" required><?= clean($old['proof_description']) ?></textarea>
      </div>
      <div class="field">
        <label for="contact_info">Contact Info</label>
        <input type="text" id="contact_info" name="contact_info" value="<?= clean($old['contact_info']) ?>" placeholder="Phone number or alternate email" required>
      </div>
      <div class="pf-actions">
        <button type="submit" class="btn btn-gold">Submit Claim</button>
        <a class="btn btn-outline" href="item-details.php?id=<?= $itemId ?>">Cancel</a>
      </div>
    </form>
  </div>
</section>

<?php include 'includes/pub_footer.php'; ?>