<?php
function isLoggedIn() {
    return !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
}

function currentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        flash('error', 'Please log in to continue.');
        redirect('login.php');
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        flash('error', 'Admin access only.');
        redirect('login.php');
    }
}


function requireStudent() {
    requireLogin();
    if (isAdmin() && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        flash('error', 'Admin accounts can browse the public site but cannot submit reports or claims.');
        redirect('admin/admin_dashboard.php');
    }
}