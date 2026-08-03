<?php

date_default_timezone_set('Asia/Ho_Chi_Minh');

error_reporting(E_ALL);
ini_set('display_errors', 1);

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Tự động phát hiện host và port
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];

// Lấy base path một cách chắc chắn hơn
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$currentDir = str_replace('\\', '/', __DIR__);
$projectRoot = str_replace('\\', '/', dirname($currentDir));
$baseDir = str_ireplace($docRoot, '', $projectRoot);

// Đảm bảo baseDir bắt đầu bằng / và không kết thúc bằng /
if ($baseDir !== '' && $baseDir[0] !== '/') $baseDir = '/' . $baseDir;
$baseDir = rtrim($baseDir, '/');

define('BASE_URL', $protocol . '://' . $host . $baseDir);

define('BASE_PATH', dirname(__DIR__));

define('UPLOAD_PATH', BASE_PATH . '/assets/images/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024);

define('ITEMS_PER_PAGE', 20);

define('CURRENCY', 'VND');

define('DATE_FORMAT', 'd/m/Y');
define('DATETIME_FORMAT', 'd/m/Y H:i:s');

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

function setMessage($type, $message) {
    $_SESSION['message'] = [
        'type' => $type,
        'text' => $message
    ];
}

function getMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        unset($_SESSION['message']);
        return $message;
    }
    return null;
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatCurrency($amount) {
    return number_format($amount, 0, ',', '.') . ' ₫';
}

function formatDate($date, $format = DATE_FORMAT) {
    if (empty($date)) return '';
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

function generateCode($prefix = '', $length = 8) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = $prefix;
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

function requireLogin() {
    if (!isLoggedIn()) {
        setMessage('warning', 'Vui lòng đăng nhập để tiếp tục.');
        redirect('/login.php');
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        setMessage('danger', 'Bạn không có quyền truy cập trang này.');
        redirect('/employee/orders/index.php');
    }
}
