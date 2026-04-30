<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isStaff() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'staff';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . BASE_URL . '/staff/index.php');
        exit;
    }
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function alert($message, $type = 'success') {
    return '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">
        ' . $message . '
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
}

function formatCurrency($amount) {
    return CURRENCY . number_format($amount, 2);
}

function formatDate($date) {
    return date('d M Y', strtotime($date));
}

function formatDateTime($date) {
    return date('d M Y h:i A', strtotime($date));
}

function getCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function convertDate($date) {
    if (empty($date)) return '';
    $parts = explode('-', $date);
    if (count($parts) === 3) {
        if (strlen($parts[0]) === 2 && strlen($parts[2]) === 4) {
            return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }
    }
    return $date;
}

function displayDate($date) {
    if (empty($date)) return '';
    $parts = explode('-', $date);
    if (count($parts) === 3 && strlen($parts[0]) === 4) {
        return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
    }
    return $date;
}
