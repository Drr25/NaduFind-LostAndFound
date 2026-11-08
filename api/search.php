<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

$keyword    = trim($_GET['keyword'] ?? '');
$reportType = trim($_GET['report_type'] ?? '');
$category   = trim($_GET['category'] ?? '');
$status     = trim($_GET['status'] ?? '');

$sql = "SELECT id, item_name, category, description, location, status, report_type, photo
        FROM items
        WHERE is_verified = 1";
$params = [];

if ($keyword !== '') {
    $sql .= " AND (item_name LIKE ? OR description LIKE ? OR location LIKE ?)";
    $like = '%' . $keyword . '%';
    array_push($params, $like, $like, $like);
}

if ($reportType !== '' && in_array($reportType, ['lost', 'found'], true)) {
    $sql .= " AND report_type = ?";
    $params[] = $reportType;
}

if ($category !== '') {
    $sql .= " AND category = ?";
    $params[] = $category;
}

if ($status !== '' && in_array($status, ['Lost', 'Found', 'Claimed', 'Returned'], true)) {
    $sql .= " AND status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY created_at DESC LIMIT 60";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

echo json_encode(['items' => $items]);
