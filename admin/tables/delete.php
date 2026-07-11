<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';

requireEmployee();

$tableId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($tableId <= 0) {
    setMessage('danger', 'ID bàn không hợp lệ!');
    $return_url = ($_SESSION['role'] === 'admin') ? '/admin/tables/index.php' : '/employee/tables/index.php';
    redirect($return_url);
}

try {
    $sql = "SELECT * FROM tables WHERE id = ?";
    $table = fetchOne($pdo, $sql, [$tableId]);

    if (!$table) {
        setMessage('danger', 'Không tìm thấy bàn!');
        $return_url = ($_SESSION['role'] === 'admin') ? '/admin/tables/index.php' : '/employee/tables/index.php';
        redirect($return_url);
    }

    // Check if table has orders (even unrelated to current status, better safe)
    // Actually, maybe we only care about active orders? Or just any reference.
    // If we delete a table, historical orders with that table_id might be affected (if we didn't use SET NULL).
    // I set ON DELETE SET NULL in SQL, so it's safe to delete.
    // But maybe warn if it's currently occupied.
    
    if ($table['status'] === 'busy') {
        setMessage('danger', 'Không thể xóa bàn đang có khách!');
        $return_url = ($_SESSION['role'] === 'admin') ? '/admin/tables/index.php' : '/employee/tables/index.php';
        redirect($return_url);
    }

    $sql = "DELETE FROM tables WHERE id = ?";
    executeQuery($pdo, $sql, [$tableId]);

    setMessage('success', 'Xóa bàn "' . htmlspecialchars($table['name']) . '" thành công!');
    $return_url = ($_SESSION['role'] === 'admin') ? '/admin/tables/index.php' : '/employee/tables/index.php';
    redirect($return_url);

} catch (PDOException $e) {
    error_log("Delete Table Error: " . $e->getMessage());
    setMessage('danger', 'Có lỗi khi xóa bàn. Vui lòng thử lại.');
    $return_url = ($_SESSION['role'] === 'admin') ? '/admin/tables/index.php' : '/employee/tables/index.php';
    redirect($return_url);
}
