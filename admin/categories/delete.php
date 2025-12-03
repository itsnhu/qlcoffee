<?php



require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';


requireAdmin();

$categoryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($categoryId <= 0) {
    setMessage('danger', 'ID loại thuốc không hợp lệ!');
    redirect('/admin/categories/index.php');
}

try {
    
    $sql = "SELECT * FROM categories WHERE id = ?";
    $category = fetchOne($pdo, $sql, [$categoryId]);

    if (!$category) {
        setMessage('danger', 'Không tìm thấy loại thuốc!');
        redirect('/admin/categories/index.php');
    }

    
    $sql = "SELECT COUNT(*) as count FROM medicines WHERE category_id = ?";
    $result = fetchOne($pdo, $sql, [$categoryId]);

    if ($result['count'] > 0) {
        setMessage('danger', 'Không thể xóa loại thuốc này vì có ' . $result['count'] . ' thuốc đang sử dụng!');
        redirect('/admin/categories/index.php');
    }

    
    $sql = "DELETE FROM categories WHERE id = ?";
    executeQuery($pdo, $sql, [$categoryId]);

    setMessage('success', 'Xóa loại thuốc "' . htmlspecialchars($category['name']) . '" thành công!');
    redirect('/admin/categories/index.php');

} catch (PDOException $e) {
    error_log("Delete Category Error: " . $e->getMessage());
    setMessage('danger', 'Có lỗi khi xóa loại thuốc. Vui lòng thử lại.');
    redirect('/admin/categories/index.php');
}
