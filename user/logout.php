<?php
require_once dirname(__DIR__) . '/config/config.php';

// Clear customer session data
unset($_SESSION['customer_id']);
unset($_SESSION['customer_email']);
unset($_SESSION['customer_name']);
unset($_SESSION['customer_login_time']);

setMessage('success', 'Đã đăng xuất thành công');
header('Location: ' . BASE_URL . '/user/');
exit;
?>
