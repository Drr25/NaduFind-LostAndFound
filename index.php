<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

/* ---------- filters (GET) ---------- */
$keyword = trim($_GET['keyword'] ?? '');
$type    = $_GET['report_type'] ?? '';
$cat     = $_GET['category'] ?? '';
$status  = $_GET['status'] ?? '';

$where  = ['is_verified = 1'];
$params = [];

if ($keyword !== '') {
    $where[] = '(item_name LIKE ? OR description LIKE ? OR location LIKE ?)';
    $like = '%' . $keyword . '%';
    array_push($params, $like, $like, $like);
}
if (in_array($type, ['lost', 'found'], true)) {
    $where[] = 'report_type = ?';
    $params[] = $type;
}
if ($cat !== '') {
    $where[] = 'category = ?';
    $params[] = $cat;
}
if (in_array($status, ['Lost', 'Found', 'Claimed', 'Returned'], true)) {
    $where[] = 'status = ?';
    $params[] = $status;
}

$stmt = $pdo->prepare('SELECT * FROM items WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC LIMIT 60');
$stmt->execute($params);
$items = $stmt->fetchAll();

$isFiltered = ($keyword !== '' || $type !== '' || $cat !== '' || $status !== '');

$pageTitle = 'Browse Lost & Found Items';
$topTitle  = 'Browse Items';
$activeNav = 'browse';
include 'includes/pub_header.php';
?>

  <!-- ===== Hero ===== -->
  <section class="hero">
    <div class="hero-inner">
      <h1>Lost something? Someone may have found it.</h1>
      <p>Search verified reports from the <?= clean($pfSite) ?> community and get reunited with your belongings.</p>
      <form class="hero-search" id="heroSearchForm" method="GET" action="index.php" role="search">
        <input type="text" id="heroKeyword" name="keyword" value="<?= clean($keyword) ?>" placeholder="Search item name, description, or location..." aria-label="Search items">
        <button type="submit" class="btn btn-gold">Search</button>
      </form>
    </div>
  </section>

  <!-- ===== Filters ===== -->
  <form class="filter-bar" id="filterForm" method="GET" action="index.php">
    <div class="field">
      <label for="keyword">Keyword</label>
      <input type="text" id="keyword" name="keyword" value="<?= clean($keyword) ?>" placeholder="e.g. wallet, backpack">
    </div>
    <div class="field">
      <label for="report_type">Type</label>
      <select id="report_type" name="report_type">
        <option value="">All</option>
        <option value="lost"  <?= $type === 'lost'  ? 'selected' : '' ?>>Lost</option>
        <option value="found" <?= $type === 'found' ? 'selected' : '' ?>>Found</option>
      </select>
    </div>
    <div class="field">
      <label for="category">Category</label>
      <select id="category" name="category">
        <option value="">All Categories</option>
        <?php foreach (itemCategories() as $catOpt): ?>
          <option value="<?= clean($catOpt) ?>" <?= $cat === $catOpt ? 'selected' : '' ?>><?= clean($catOpt) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="status">Status</label>
      <select id="status" name="status">
        <option value="">All Statuses</option>
        <?php foreach (['Lost', 'Found', 'Claimed', 'Returned'] as $s): ?>
          <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn btn-gold">Filter</button>
  </form>

  <p class="results-count" id="resultsCount">
    <?= count($items) ?> item<?= count($items) === 1 ? '' : 's' ?> found
    <?php if ($isFiltered): ?> &mdash; <a href="index.php">clear filters</a><?php endif; ?>
  </p>

  <!-- ===== Results ===== -->
  <div class="item-grid" id="itemResults">
    <?php foreach ($items as $item): ?>
      <a class="item-card" href="item-details.php?id=<?= $item['id'] ?>">
        <?php if ($item['photo']): ?>
          <img class="item-photo" src="uploads/<?= clean($item['photo']) ?>" alt="<?= clean($item['item_name']) ?>" loading="lazy">
        <?php else: ?>
          <div class="item-photo placeholder">📦</div>
        <?php endif; ?>
        <div class="item-body">
          <div class="flex-between">
            <h3><?= clean($item['item_name']) ?></h3>
            <span class="<?= statusBadgeClass($item['status']) ?>"><?= clean($item['status']) ?></span>
          </div>
          <div class="item-meta"><?= clean($item['category']) ?> &middot; <?= clean($item['location']) ?></div>
          <div class="item-desc"><?= clean($item['description']) ?></div>
        </div>
      </a>
    <?php endforeach; ?>

    <?php if (!$items): ?>
      <div class="empty-state">
        <div class="icon">🔍</div>
        <?php if ($isFiltered): ?>
          <p>No items match your search. <a href="index.php">Clear filters</a> and try again.</p>
        <?php else: ?>
          <p>No items reported yet. Be the first to <a href="report-item.php">report one</a>!</p>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

<?php include 'includes/pub_footer.php'; ?>