<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';

requireEmployee();

$tableId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($tableId <= 0) {
    setMessage('danger', 'ID bàn không hợp lệ!');
    redirect('/employee/tables/index.php');
}

try {
    $sql = "SELECT * FROM tables WHERE id = ?";
    $table = fetchOne($pdo, $sql, [$tableId]);

    if (!$table) {
        setMessage('danger', 'Không tìm thấy bàn!');
        redirect('/employee/tables/index.php');
    }

    // Không cho xóa bàn đang có khách
    if ($table['status'] === 'busy') {
        setMessage('danger', 'Không thể xóa bàn đang có khách!');
        redirect('/employee/tables/index.php');
    }

    $sql = "DELETE FROM tables WHERE id = ?";
    executeQuery($pdo, $sql, [$tableId]);

    setMessage('success', 'Xóa bàn "' . htmlspecialchars($table['name']) . '" thành công!');
    redirect('/employee/tables/index.php');

} catch (PDOException $e) {
    error_log("Delete Table Error: " . $e->getMessage());
    setMessage('danger', 'Có lỗi khi xóa bàn. Vui lòng thử lại.');
    redirect('/employee/tables/index.php');
}
