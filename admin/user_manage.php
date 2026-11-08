<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = (int) ($_POST['user_id'] ?? 0);

    if ($userId === (int) currentUserId() && in_array($action, ['deactivate', 'make_user'], true)) {
        flash('error', "You can't modify your own admin/active status.");
        redirect('user_manage.php');
    }

    if ($action === 'make_admin') {
        $pdo->prepare('UPDATE users SET role = "admin" WHERE id = ?')->execute([$userId]);
        flash('success', 'User promoted to admin.');
    } elseif ($action === 'make_user') {
        $pdo->prepare('UPDATE users SET role = "user" WHERE id = ?')->execute([$userId]);
        flash('success', 'Admin demoted to regular user.');
    } elseif ($action === 'deactivate') {
        $pdo->prepare('UPDATE users SET is_active = 0 WHERE id = ?')->execute([$userId]);
        flash('success', 'User account deactivated.');
    } elseif ($action === 'activate') {
        $pdo->prepare('UPDATE users SET is_active = 1 WHERE id = ?')->execute([$userId]);
        flash('success', 'User account activated.');
    }

    redirect('user_manage.php');
}

$users = $pdo->query(
    "SELECT users.*,
     (SELECT COUNT(*) FROM items WHERE items.user_id = users.id) AS item_count,
     (SELECT COUNT(*) FROM claims WHERE claims.claimer_id = users.id) AS claim_count
     FROM users ORDER BY created_at DESC"
)->fetchAll();

$pageTitle = 'Manage Users';
$activeNav = 'users';
include '../includes/admin-header.php';
?>

<div class="page-head">
  <h1>Manage Users</h1>
  <p>View registered accounts, manage roles and access.</p>
</div>

<section class="panel">
  <div class="table-wrap admin-table">
    <table>
      <thead>
        <tr><th>User</th><th>Role</th><th>Reports</th><th>Claims</th><th>Status</th><th>Joined</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td class="wrap">
              <div class="user-cell">
                <span class="avatar"><?= clean(strtoupper(substr($u['full_name'], 0, 1))) ?></span>
                <span><strong><?= clean($u['full_name']) ?></strong><small class="cell-sub"><?= clean($u['email']) ?></small></span>
              </div>
            </td>
            <td><span class="badge <?= $u['role'] === 'admin' ? 'badge-approved' : 'badge-unverified' ?>"><?= ucfirst($u['role']) ?></span></td>
            <td><?= (int) $u['item_count'] ?></td>
            <td><?= (int) $u['claim_count'] ?></td>
            <td><?= $u['is_active'] ? '<span class="badge badge-approved">Active</span>' : '<span class="badge badge-rejected">Deactivated</span>' ?></td>
            <td><?= timeAgo($u['created_at']) ?></td>
            <td class="actions-cell">
              <?php if ((int) $u['id'] !== (int) currentUserId()): ?>
                <?php if ($u['role'] === 'user'): ?>
                  <form method="POST" data-confirm="Promote this user to admin?"><input type="hidden" name="action" value="make_admin"><input type="hidden" name="user_id" value="<?= $u['id'] ?>"><button class="btn btn-sm btn-outline">Make Admin</button></form>
                <?php else: ?>
                  <form method="POST" data-confirm="Demote this admin to regular user?"><input type="hidden" name="action" value="make_user"><input type="hidden" name="user_id" value="<?= $u['id'] ?>"><button class="btn btn-sm btn-outline">Demote</button></form>
                <?php endif; ?>
                <?php if ($u['is_active']): ?>
                  <form method="POST" data-confirm="Deactivate this account?"><input type="hidden" name="action" value="deactivate"><input type="hidden" name="user_id" value="<?= $u['id'] ?>"><button class="btn btn-sm btn-danger">Deactivate</button></form>
                <?php else: ?>
                  <form method="POST"><input type="hidden" name="action" value="activate"><input type="hidden" name="user_id" value="<?= $u['id'] ?>"><button class="btn btn-sm btn-success">Activate</button></form>
                <?php endif; ?>
              <?php else: ?>
                <span class="you-tag">(You)</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="table-foot"><?= count($users) ?> account<?= count($users) === 1 ? '' : 's' ?></div>
</section>

<?php include '../includes/admin-footer.php'; ?>