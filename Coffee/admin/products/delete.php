<?php



require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';


requireAdmin();


$id = $_GET['id'] ?? 0;

if (!$id || !is_numeric($id)) {
    setMessage('danger', 'ID món không hợp lệ.');
    header('Location: ' . BASE_URL . '/admin/products/index.php');
    exit;
}


try {
    $product = fetchOne($pdo, "SELECT * FROM products WHERE id = ?", [$id]);

    if (!$product) {
        setMessage('danger', 'Không tìm thấy món.');
        header('Location: ' . BASE_URL . '/admin/products/index.php');
        exit;
    }

} catch (PDOException $e) {
    error_log("Load Product Error: " . $e->getMessage());
    setMessage('danger', 'Có lỗi khi tải dữ liệu.');
    header('Location: ' . BASE_URL . '/admin/products/index.php');
    exit;
}


$hasInvoices = false;
$hasImports = false;
$invoiceCount = 0;
$importCount = 0;

try {
    
    $invoiceCheck = fetchOne($pdo, "SELECT COUNT(*) as count FROM order_details WHERE product_id = ?", [$id]);
    $invoiceCount = $invoiceCheck['count'] ?? 0;
    $hasInvoices = $invoiceCount > 0;

    
    $importCheck = fetchOne($pdo, "SELECT COUNT(*) as count FROM import_details WHERE product_id = ?", [$id]);
    $importCount = $importCheck['count'] ?? 0;
    $hasImports = $importCount > 0;

} catch (PDOException $e) {
    error_log("Check Relations Error: " . $e->getMessage());
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {

    
    if ($hasInvoices || $hasImports) {
        setMessage('danger', 'Không thể xóa món vì đã có trong đơn hàng hoặc phiếu nhập. Vui lòng vô hiệu hóa thay vì xóa.');
        header('Location: ' . BASE_URL . '/admin/products/index.php');
        exit;
    }

    
    try {
        $sql = "DELETE FROM products WHERE id = ?";
        executeQuery($pdo, $sql, [$id]);

        setMessage('success', 'Xóa món thành công!');
        header('Location: ' . BASE_URL . '/admin/products/index.php');
        exit;

    } catch (PDOException $e) {
        error_log("Delete Product Error: " . $e->getMessage());
        setMessage('danger', 'Có lỗi khi xóa món. Vui lòng thử lại.');
        header('Location: ' . BASE_URL . '/admin/products/index.php');
        exit;
    }
}

$pageTitle = 'Xóa món';
require_once dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-2">
                    <i class="bi bi-trash text-danger"></i>
                    Xóa món
                </h2>
                <p class="text-muted mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Xác nhận xóa món khỏi hệ thống
                </p>
            </div>
            <div>
                <a href="<?php echo BASE_URL; ?>/admin/products/index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>
                    Quay lại danh sách
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Delete -->
<div class="row">
    <div class="col-lg-8 col-md-10 mx-auto">
        <div class="card shadow-sm">
            <div class="card-header bg-danger text-white">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Xác nhận xóa món</strong>
            </div>
            <div class="card-body">
                <!-- Thông tin món -->
                <div class="alert alert-warning">
                    <h5 class="alert-heading">
                        <i class="bi bi-info-circle me-2"></i>
                        Thông tin món sẽ bị xóa:
                    </h5>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2">
                                <strong>Tên món:</strong><br>
                                <?php echo htmlspecialchars($product['name']); ?>
                            </p>
                            <p class="mb-2">
                                <strong>Mã món:</strong><br>
                                <?php echo htmlspecialchars($product['code']); ?>
                            </p>
                            <p class="mb-2">
                                <strong>Giá bán:</strong><br>
                                <?php echo number_format($product['price'], 0, ',', '.'); ?>đ
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2">
                                <strong>Số lượng tồn:</strong><br>
                                <?php echo number_format($product['quantity']); ?> <?php echo htmlspecialchars($product['unit'] ?? 'Ly'); ?>
                            </p>
                            <p class="mb-2">
                                <strong>Hạn sử dụng:</strong><br>
                                <?php echo $product['expiry_date'] ? date('d/m/Y', strtotime($product['expiry_date'])) : 'Không có'; ?>
                            </p>
                            <p class="mb-2">
                                <strong>Ngày tạo:</strong><br>
                                <?php echo date('d/m/Y H:i', strtotime($product['created_at'])); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Kiểm tra ràng buộc -->
                <?php if ($hasInvoices || $hasImports): ?>
                    <div class="alert alert-danger">
                        <h5 class="alert-heading">
                            <i class="bi bi-x-circle-fill me-2"></i>
                            Không thể xóa món này!
                        </h5>
                        <hr>
                        <p class="mb-2">Món này đã được sử dụng trong hệ thống:</p>
                        <ul class="mb-0">
                            <?php if ($hasInvoices): ?>
                                <li>
                                    <strong><?php echo $invoiceCount; ?></strong> đơn hàng (bán hàng)
                                </li>
                            <?php endif; ?>
                            <?php if ($hasImports): ?>
                                <li>
                                    <strong><?php echo $importCount; ?></strong> phiếu nhập hàng
                                </li>
                            <?php endif; ?>
                        </ul>
                        <hr class="my-3">
                        <p class="mb-0">
                            <i class="bi bi-lightbulb me-2"></i>
                            <strong>Gợi ý:</strong> Bạn nên đặt số lượng về 0 hoặc đánh dấu ngừng kinh doanh thay vì xóa.
                        </p>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="<?php echo BASE_URL; ?>/admin/products/index.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-2"></i>
                            Quay lại
                        </a>
                        <a href="<?php echo BASE_URL; ?>/admin/products/edit.php?id=<?php echo $id; ?>" class="btn btn-primary">
                            <i class="bi bi-pencil me-2"></i>
                            Chỉnh sửa thay thế
                        </a>
                    </div>

                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Món này chưa được sử dụng trong bất kỳ đơn hàng hoặc phiếu nhập nào. Bạn có thể xóa an toàn.
                    </div>

                    <div class="alert alert-danger">
                        <h5 class="alert-heading">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            Cảnh báo!
                        </h5>
                        <p class="mb-0">
                            Hành động này <strong>không thể hoàn tác</strong>. Tất cả thông tin về món sẽ bị xóa vĩnh viễn khỏi hệ thống.
                        </p>
                    </div>

                    <form method="POST" action="" id="deleteForm">
                        <div class="form-check mb-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="confirmCheck"
                                   required>
                            <label class="form-check-label" for="confirmCheck">
                                Tôi hiểu và muốn xóa món này vĩnh viễn
                            </label>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="<?php echo BASE_URL; ?>/admin/products/index.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle me-2"></i>
                                Hủy bỏ
                            </a>
                            <button type="submit" name="confirm_delete" class="btn btn-danger" id="deleteBtn" disabled>
                                <i class="bi bi-trash me-2"></i>
                                Xác nhận xóa
                            </button>
                        </div>
                    </form>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<script>
// Enable/disable delete button based on checkbox
document.getElementById('confirmCheck')?.addEventListener('change', function() {
    document.getElementById('deleteBtn').disabled = !this.checked;
});

// Confirm before submit
document.getElementById('deleteForm')?.addEventListener('submit', function(e) {
    if (!confirm('BẠN CÓ CHẮC CHẮN MUỐN XÓA MÓN NÀY?\n\nHành động này không thể hoàn tác!')) {
        e.preventDefault();
        return false;
    }
});
</script>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
