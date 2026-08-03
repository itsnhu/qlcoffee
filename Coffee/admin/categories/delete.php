<?php



require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';


requireAdmin();

$categoryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($categoryId <= 0) {
    setMessage('danger', 'ID danh mục không hợp lệ!');
    redirect('/admin/categories/index.php');
}

try {
    
    $sql = "SELECT * FROM categories WHERE id = ?";
    $category = fetchOne($pdo, $sql, [$categoryId]);

    if (!$category) {
        setMessage('danger', 'Không tìm thấy danh mục!');
        redirect('/admin/categories/index.php');
    }

    
    $sql = "SELECT COUNT(*) as count FROM products WHERE category_id = ?";
    $result = fetchOne($pdo, $sql, [$categoryId]);

    if ($result['count'] > 0) {
        setMessage('danger', 'Không thể xóa danh mục này vì có ' . $result['count'] . ' món đang sử dụng!');
        redirect('/admin/categories/index.php');
    }

    
    $sql = "DELETE FROM categories WHERE id = ?";
    executeQuery($pdo, $sql, [$categoryId]);

    setMessage('success', 'Xóa danh mục "' . htmlspecialchars($category['name']) . '" thành công!');
    redirect('/admin/categories/index.php');

} catch (PDOException $e) {
    error_log("Delete Category Error: " . $e->getMessage());
    setMessage('danger', 'Có lỗi khi xóa danh mục. Vui lòng thử lại.');
    redirect('/admin/categories/index.php');
}
