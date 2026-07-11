<?php



require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';


requireAdmin();

$supplierId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($supplierId <= 0) {
    setMessage('danger', 'ID nhà cung cấp không hợp lệ!');
    redirect('/admin/suppliers/index.php');
}

try {
    
    $sql = "SELECT * FROM suppliers WHERE id = ?";
    $supplier = fetchOne($pdo, $sql, [$supplierId]);

    if (!$supplier) {
        setMessage('danger', 'Không tìm thấy nhà cung cấp!');
        redirect('/admin/suppliers/index.php');
    }

    
    $sql = "SELECT COUNT(*) as count FROM products WHERE supplier_id = ?";
    $result = fetchOne($pdo, $sql, [$supplierId]);

    if ($result['count'] > 0) {
        setMessage('danger', 'Không thể xóa nhà cung cấp này vì có ' . $result['count'] . ' món đang sử dụng!');
        redirect('/admin/suppliers/index.php');
    }

    
    $sql = "DELETE FROM suppliers WHERE id = ?";
    executeQuery($pdo, $sql, [$supplierId]);

    setMessage('success', 'Xóa nhà cung cấp "' . htmlspecialchars($supplier['name']) . '" thành công!');
    redirect('/admin/suppliers/index.php');

} catch (PDOException $e) {
    error_log("Delete Supplier Error: " . $e->getMessage());
    setMessage('danger', 'Có lỗi khi xóa nhà cung cấp. Vui lòng thử lại.');
    redirect('/admin/suppliers/index.php');
}
