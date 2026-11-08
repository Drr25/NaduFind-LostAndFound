<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
requireStudent();

$errors = [];
$old = [
    'report_type' => 'lost',
    'item_name'   => '',
    'category'    => '',
    'description' => '',
    'location'    => '',
    'event_date'  => date('Y-m-d'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['report_type'] = ($_POST['report_type'] ?? '') === 'found' ? 'found' : 'lost';
    $old['item_name']   = trim($_POST['item_name'] ?? '');
    $old['category']    = trim($_POST['category'] ?? '');
    $old['description'] = trim($_POST['description'] ?? '');
    $old['location']    = trim($_POST['location'] ?? '');
    $old['event_date']  = trim($_POST['event_date'] ?? '');

    if ($old['item_name'] === '') $errors[] = 'Item name is required.';
    if ($old['category'] === '') $errors[] = 'Please choose a category.';
    if ($old['description'] === '') $errors[] = 'Please provide a description.';
    if ($old['location'] === '') $errors[] = 'Please provide a location.';
    if ($old['event_date'] === '') $errors[] = 'Please provide the date.';

    $photoName = null;
    if (!$errors) {
        try {
            $photoName = handlePhotoUpload('photo');
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (!$errors) {
        // Initial status mirrors the report type (Lost report -> "Lost", Found report -> "Found")
        $initialStatus = $old['report_type'] === 'found' ? 'Found' : 'Lost';

        $stmt = $pdo->prepare(
            'INSERT INTO items (user_id, report_type, item_name, category, description, location, event_date, photo, status, is_verified)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)'
        );
        $stmt->execute([
            currentUserId(), $old['report_type'], $old['item_name'], $old['category'],
            $old['description'], $old['location'], $old['event_date'], $photoName, $initialStatus,
        ]);
        $itemId = $pdo->lastInsertId();

        $log = $pdo->prepare('INSERT INTO item_status_log (item_id, old_status, new_status, changed_by) VALUES (?, NULL, ?, ?)');
        $log->execute([$itemId, $initialStatus, currentUserId()]);

        flash('success', 'Report submitted! An administrator will verify it shortly.');
        redirect('my-items.php');
    }
}

$pageTitle = 'Report an Item';
$activeNav = 'report';
include 'includes/pub_header.php';
?>

<div class="page-head">
  <h1>Report a Lost or Found Item</h1>
  <p>Fill in as much detail as possible &mdash; this helps others recognize the item.</p>
</div>

<?php foreach ($errors as $e): ?>
  <div class="alert alert-error" role="alert"><?= clean($e) ?></div>
<?php endforeach; ?>

<section class="pf-card pf-section">
  <div class="pf-section-head">
    <span class="pf-section-ico"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg></span>
    <div>
      <h3>Item Details</h3>
      <p>Tell us what was lost or found.</p>
    </div>
  </div>
  <div class="pf-section-body">
    <form method="POST" enctype="multipart/form-data" class="pf-form">
      <div class="field">
        <label>I am reporting a...</label>
        <div style="display:flex;gap:20px;flex-wrap:wrap;">
          <label style="font-weight:400;display:flex;align-items:center;gap:6px;">
            <input type="radio" name="report_type" value="lost" <?= $old['report_type'] === 'lost' ? 'checked' : '' ?>> Lost Item
          </label>
          <label style="font-weight:400;display:flex;align-items:center;gap:6px;">
            <input type="radio" name="report_type" value="found" <?= $old['report_type'] === 'found' ? 'checked' : '' ?>> Found Item
          </label>
        </div>
      </div>

      <div class="pf-row two">
        <div class="field">
          <label for="item_name">Item Name</label>
          <input type="text" id="item_name" name="item_name" value="<?= clean($old['item_name']) ?>" placeholder="e.g. Black Leather Wallet" required>
        </div>
        <div class="field">
          <label for="category">Category</label>
          <select id="category" name="category" required>
            <option value="">Select category</option>
            <?php foreach (itemCategories() as $cat): ?>
              <option value="<?= clean($cat) ?>" <?= $old['category'] === $cat ? 'selected' : '' ?>><?= clean($cat) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="field">
        <label for="description">Description</label>
        <textarea id="description" name="description" placeholder="Color, brand, distinguishing marks, contents, etc." required><?= clean($old['description']) ?></textarea>
      </div>

      <div class="pf-row two">
        <div class="field">
          <label for="location">Location (Lost / Found at)</label>
          <input type="text" id="location" name="location" value="<?= clean($old['location']) ?>" placeholder="e.g. Main Library, 2nd Floor" required>
        </div>
        <div class="field">
          <label for="event_date">Date</label>
          <input type="date" id="event_date" name="event_date" value="<?= clean($old['event_date']) ?>" max="<?= date('Y-m-d') ?>" required>
        </div>
      </div>

      <div class="field">
        <label for="photo">Photo (optional, max 5MB)</label>
        <input type="file" id="photo" name="photo" accept="image/*" data-preview="photoPreview">
        <img id="photoPreview" class="photo-preview" alt="Preview">
      </div>

      <div class="pf-actions">
        <button type="submit" class="btn btn-gold">Submit Report</button>
        <a class="btn btn-outline" href="index.php">Cancel</a>
      </div>
    </form>
  </div>
</section>

<?php include 'includes/pub_footer.php'; ?>