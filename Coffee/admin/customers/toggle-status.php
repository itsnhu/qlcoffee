<?php

require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';

requireAdmin();

$id = $_GET['id'] ?? 0;

if (!$id || !is_numeric($id)) {
    setMessage('danger', 'ID khách hàng không hợp lệ.');
    header('Location: ' . BASE_URL . '/admin/customers/index.php');
    exit;
}

try {
    // Get customer
    $customer = fetchOne($pdo, "SELECT id, full_name, is_active FROM customers WHERE id = ?", [$id]);

    if (!$customer) {
        setMessage('danger', 'Không tìm thấy khách hàng.');
        header('Location: ' . BASE_URL . '/admin/customers/index.php');
        exit;
    }

    // Toggle status
    $newStatus = $customer['is_active'] ? 0 : 1;
    executeQuery($pdo, "UPDATE customers SET is_active = ? WHERE id = ?", [$newStatus, $id]);

    $statusText = $newStatus ? 'kích hoạt' : 'vô hiệu hóa';
    setMessage('success', "Đã {$statusText} tài khoản khách hàng: " . $customer['full_name']);

} catch (PDOException $e) {
    error_log("Toggle Customer Status Error: " . $e->getMessage());
    setMessage('danger', 'Có lỗi khi thay đổi trạng thái tài khoản.');
}

header('Location: ' . BASE_URL . '/admin/customers/index.php');
exit;
