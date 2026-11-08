<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
requireLogin();

$userId = currentUserId();
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

$errors = [];
$old = ['full_name' => $user['full_name'], 'phone' => $user['phone'] ?? ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update_info';

    if ($action === 'update_info') {
        $old['full_name'] = trim($_POST['full_name'] ?? '');
        $old['phone']     = trim($_POST['phone'] ?? '');

        if ($old['full_name'] === '') {
            $errors[] = 'Full name is required.';
        }

        if (!$errors) {
            $pdo->prepare('UPDATE users SET full_name = ?, phone = ? WHERE id = ?')
                ->execute([$old['full_name'], $old['phone'] ?: null, $userId]);
            $_SESSION['full_name'] = $old['full_name'];
            flash('success', 'Profile updated successfully.');
            redirect('profile.php');
        }
    } elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_new_password'] ?? '';

        if (!password_verify($current, $user['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        }

        if (!$errors) {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $userId]);
            flash('success', 'Password changed successfully.');
            redirect('profile.php');
        }
    }
}

/* quick stats for the identity card */
$st = $pdo->prepare('SELECT COUNT(*) FROM items WHERE user_id = ?');
$st->execute([$userId]);
$reportCount = (int) $st->fetchColumn();
$st = $pdo->prepare('SELECT COUNT(*) FROM claims WHERE claimer_id = ?');
$st->execute([$userId]);
$claimCount = (int) $st->fetchColumn();

$isAdmin = $user['role'] === 'admin';

$pageTitle = 'My Profile';
$activeNav = 'profile';
include 'includes/pub_header.php';

$eyeSvg = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>';
?>

<div class="page-head">
  <h1>My Profile</h1>
  <p>Manage your account information and password.</p>
</div>

<?php foreach ($errors as $e): ?>
  <div class="alert alert-error" role="alert"><?= clean($e) ?></div>
<?php endforeach; ?>

<div class="pf-grid">

  <!-- ===== Left: identity card ===== -->
  <aside class="pf-side">
    <div class="pf-card">
      <div class="pf-cover"></div>
      <div class="pf-id">
        <div class="pf-avatar"><?= clean(strtoupper(substr($user['full_name'], 0, 1))) ?></div>
        <h2 class="pf-name"><?= clean($user['full_name']) ?></h2>
        <p class="pf-email"><?= clean($user['email']) ?></p>
        <span class="badge <?= $isAdmin ? 'badge-approved' : 'badge-unverified' ?>"><?= ucfirst(clean($user['role'])) ?></span>
      </div>
      <ul class="pf-facts">
        <li><span>Member since</span><strong><?= date('M j, Y', strtotime($user['created_at'])) ?></strong></li>
        <li><span>Reports filed</span><strong><?= $reportCount ?></strong></li>
        <li><span>Claims made</span><strong><?= $claimCount ?></strong></li>
        <li><span>Phone</span><strong><?= $user['phone'] ? clean($user['phone']) : '&mdash;' ?></strong></li>
      </ul>
      <?php if ($isAdmin): ?>
        <div class="pf-side-actions">
          <a class="btn btn-accent btn-block" href="admin/admin_dashboard.php">Back to Dashboard</a>
        </div>
      <?php endif; ?>
    </div>
  </aside>

  <!-- ===== Right: forms ===== -->
  <div>
    <section class="pf-card pf-section">
      <div class="pf-section-head">
        <span class="pf-section-ico"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/></svg></span>
        <div>
          <h3>Account Information</h3>
          <p>Update your name and contact number.</p>
        </div>
      </div>
      <div class="pf-section-body">
        <form method="POST" class="pf-form">
          <input type="hidden" name="action" value="update_info">
          <div class="pf-row two">
            <div class="field">
              <label for="full_name">Full Name</label>
              <input type="text" id="full_name" name="full_name" value="<?= clean($old['full_name']) ?>" autocomplete="name" required>
            </div>
            <div class="field">
              <label for="email">Email Address</label>
              <input type="email" id="email" value="<?= clean($user['email']) ?>" disabled>
              <small>Email can't be changed.</small>
            </div>
          </div>
          <div class="field">
            <label for="phone">Phone Number</label>
            <input type="tel" id="phone" name="phone" value="<?= clean($old['phone']) ?>" autocomplete="tel" placeholder="e.g. 0917 123 4567">
          </div>
          <div class="pf-actions">
            <button type="submit" class="btn btn-gold">Save Changes</button>
          </div>
        </form>
      </div>
    </section>

    <section class="pf-card pf-section">
      <div class="pf-section-head">
        <span class="pf-section-ico"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg></span>
        <div>
          <h3>Change Password</h3>
          <p>Use at least 6 characters.</p>
        </div>
      </div>
      <div class="pf-section-body">
        <form method="POST" class="pf-form">
          <input type="hidden" name="action" value="change_password">
          <div class="field">
            <label for="current_password">Current Password</label>
            <div class="pf-pass">
              <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
              <button type="button" class="pf-toggle" data-target="current_password" aria-label="Show or hide password"><?= $eyeSvg ?></button>
            </div>
          </div>
          <div class="pf-row two">
            <div class="field">
              <label for="new_password">New Password</label>
              <div class="pf-pass">
                <input type="password" id="new_password" name="new_password" minlength="6" autocomplete="new-password" required>
                <button type="button" class="pf-toggle" data-target="new_password" aria-label="Show or hide password"><?= $eyeSvg ?></button>
              </div>
            </div>
            <div class="field">
              <label for="confirm_new_password">Confirm New Password</label>
              <div class="pf-pass">
                <input type="password" id="confirm_new_password" name="confirm_new_password" minlength="6" autocomplete="new-password" required>
                <button type="button" class="pf-toggle" data-target="confirm_new_password" aria-label="Show or hide password"><?= $eyeSvg ?></button>
              </div>
            </div>
          </div>
          <div class="pf-actions">
            <button type="submit" class="btn btn-accent">Update Password</button>
          </div>
        </form>
      </div>
    </section>
  </div>
</div>

<script>
document.querySelectorAll('.pf-toggle').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var input = document.getElementById(btn.dataset.target);
    input.type = input.type === 'password' ? 'text' : 'password';
  });
});
</script>

<?php include 'includes/pub_footer.php'; ?>