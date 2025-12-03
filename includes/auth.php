<?php

if (!defined('BASE_URL')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

if (!function_exists('requireEmployee')) {
    function requireEmployee() {
        requireLogin();
    }
}

if (!function_exists('checkSessionTimeout')) {
    function checkSessionTimeout($timeout = 7200) {
        if (isLoggedIn()) {
            $lastActivity = $_SESSION['last_activity'] ?? $_SESSION['login_time'] ?? time();
            if ((time() - $lastActivity) > $timeout) {
                session_destroy();
                setMessage('warning', 'Phiên đăng nhập đã hết hạn.');
                redirect('/login.php');
            }
            $_SESSION['last_activity'] = time();
        }
    }
}

if (!function_exists('getCurrentUser')) {
    function getCurrentUser() {
        if (!isLoggedIn()) return null;
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'full_name' => $_SESSION['full_name'] ?? null,
            'role' => $_SESSION['role'] ?? null
        ];
    }
}

if (!function_exists('hasPermission')) {
    function hasPermission($resource, $action) {
        if (!isLoggedIn()) return false;
        if ($_SESSION['role'] === 'admin') return true;
        $perms = [
            'medicine' => ['view'],
            'invoice' => ['view', 'create'],
        ];
        return isset($perms[$resource]) && in_array($action, $perms[$resource]);
    }
}

?>
