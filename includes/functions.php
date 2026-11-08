<?php
/**
 * Shared helper functions
 */

function clean($value) {
    return htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function redirect($path) {
    header("Location: {$path}");
    exit;
}

function flash($key, $message = null) {
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return;
    }
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function statusBadgeClass($status) {
    return match ($status) {
        'Lost'     => 'badge badge-lost',
        'Found'    => 'badge badge-found',
        'Claimed'  => 'badge badge-claimed',
        'Returned' => 'badge badge-returned',
        default    => 'badge',
    };
}

/**
 * Handle a single photo upload. Returns the stored filename on success,
 * null if no file was sent, or throws an Exception on validation error.
 */
function handlePhotoUpload($fileField = 'photo') {
    if (empty($_FILES[$fileField]) || $_FILES[$fileField]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$fileField];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('There was a problem uploading the photo.');
    }

    if ($file['size'] > MAX_UPLOAD_BYTES) {
        throw new Exception('Photo is too large. Maximum size is 5MB.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, ALLOWED_IMAGE_TYPES, true)) {
        throw new Exception('Only JPG, PNG, GIF, or WEBP images are allowed.');
    }

    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
        default      => 'jpg',
    };

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $newName = 'item_' . uniqid('', true) . '.' . $ext;
    $destination = UPLOAD_DIR . $newName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception('Failed to save uploaded photo.');
    }

    return $newName;
}

function timeAgo($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 2592000) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', strtotime($datetime));
}

function itemCategories() {
    return [
        'Electronics', 'Documents / ID', 'Bags', 'Wallet / Purse',
        'Keys', 'Jewelry / Accessories', 'Clothing', 'Books / School Supplies',
        'Pets', 'Others',
    ];
}
