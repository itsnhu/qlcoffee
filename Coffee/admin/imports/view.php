<?php



require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';


requireAdmin();


$import_id = $_GET['id'] ?? 0;

if (empty($import_id) || !is_numeric($import_id)) {
    setMessage('danger', 'Phiếu nhập không tồn tại.');
    header('Location: ' . BASE_URL . '/admin/imports/index.php');
    exit;
}


try {
    $sql = "SELECT i.id, i.import_code, i.total_amount, i.note, i.created_at,
                   s.name as supplier_name, s.phone as supplier_phone, s.address as supplier_address,
                   u.full_name as user_name
            FROM imports i
            LEFT JOIN suppliers s ON i.supplier_id = s.id
            LEFT JOIN users u ON i.user_id = u.id
            WHERE i.id = ?";

    $import = fetchOne($pdo, $sql, [$import_id]);

    if (!$import) {
        setMessage('danger', 'Phiếu nhập không tồn tại.');
        header('Location: ' . BASE_URL . '/admin/imports/index.php');
        exit;
    }

    
    $sql = "SELECT id.id, id.quantity, id.price, id.subtotal,
                   m.name as product_name, m.code as product_code, m.unit
            FROM import_details id
            LEFT JOIN products m ON id.product_id = m.id
            WHERE id.import_id = ?
            ORDER BY id.id ASC";

    $import_details = fetchAll($pdo, $sql, [$import_id]);

} catch (PDOException $e) {
    error_log("View Import Error: " . $e->getMessage());
    setMessage('danger', 'Có lỗi khi tải thông tin phiếu nhập.');
    header('Location: ' . BASE_URL . '/admin/imports/index.php');
    exit;
}

$pageTitle = 'Chi tiết phiếu nhập';
require_once dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<!-- Hiển thị thông báo -->
<?php
$message = getMessage();
if ($message):
?>
    <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show" role="alert">
        <i class="bi bi-<?php echo $message['type'] === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill'; ?> me-2"></i>
        <?php echo htmlspecialchars($message['text']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-2">
                    <i class="bi bi-file-earmark-text text-primary"></i>
                    Chi tiết phiếu nhập
                </h2>
                <p class="text-muted mb-0">
                    <span class="badge bg-primary"><?php echo htmlspecialchars($import['import_code']); ?></span>
                </p>
            </div>
            <div>
                <a href="<?php echo BASE_URL; ?>/admin/imports/index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>
                    Quay lại danh sách
                </a>
                <button onclick="window.print()" class="btn btn-info">
                    <i class="bi bi-printer me-2"></i>
                    In phiếu nhập
                </button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Thông tin phiếu nhập -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Thông tin phiếu nhập</strong>
            </div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <td width="35%" class="text-muted">
                            <i class="bi bi-hash"></i> Mã phiếu nhập:
                        </td>
                        <td>
                            <strong class="text-primary"><?php echo htmlspecialchars($import['import_code']); ?></strong>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">
                            <i class="bi bi-person"></i> Người nhập:
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($import['user_name']); ?></strong>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">
                            <i class="bi bi-calendar3"></i> Ngày nhập:
                        </td>
                        <td>
                            <?php echo date('d/m/Y H:i:s', strtotime($import['created_at'])); ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">
                            <i class="bi bi-cash-stack"></i> Tổng tiền:
                        </td>
                        <td>
                            <strong class="text-success fs-5">
                                <?php echo number_format($import['total_amount'], 0, ',', '.'); ?>đ
                            </strong>
                        </td>
                    </tr>
                    <?php if ($import['note']): ?>
                    <tr>
                        <td class="text-muted align-top">
                            <i class="bi bi-sticky"></i> Ghi chú:
                        </td>
                        <td>
                            <em><?php echo nl2br(htmlspecialchars($import['note'])); ?></em>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <!-- Thông tin nhà cung cấp -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-success text-white">
                <i class="bi bi-shop me-2"></i>
                <strong>Thông tin nhà cung cấp</strong>
            </div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <td width="35%" class="text-muted">
                            <i class="bi bi-building"></i> Tên NCC:
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($import['supplier_name']); ?></strong>
                        </td>
                    </tr>
                    <?php if ($import['supplier_phone']): ?>
                    <tr>
                        <td class="text-muted">
                            <i class="bi bi-telephone"></i> Số điện thoại:
                        </td>
                        <td>
                            <a href="tel:<?php echo htmlspecialchars($import['supplier_phone']); ?>">
                                <?php echo htmlspecialchars($import['supplier_phone']); ?>
                            </a>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($import['supplier_address']): ?>
                    <tr>
                        <td class="text-muted align-top">
                            <i class="bi bi-geo-alt"></i> Địa chỉ:
                        </td>
                        <td>
                            <?php echo nl2br(htmlspecialchars($import['supplier_address'])); ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Danh sách món -->
<div class="card shadow-sm">
    <div class="card-header bg-info text-white">
        <i class="bi bi-cup-hot-fill me-2"></i>
        <strong>Danh sách món nhập</strong>
        <span class="badge bg-light text-dark ms-2"><?php echo count($import_details); ?> món</span>
    </div>
    <div class="card-body">
        <?php if (empty($import_details)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="mt-3 text-muted">Không có món nào trong phiếu nhập này</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-info">
                        <tr>
                            <th width="5%" class="text-center">STT</th>
                            <th width="12%">Mã món</th>
                            <th width="30%">Tên món</th>
                            <th width="10%" class="text-center">Số lượng</th>
                            <th width="10%">Đơn vị</th>
                            <th width="15%" class="text-end">Giá nhập</th>
                            <th width="18%" class="text-end">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stt = 1;
                        foreach ($import_details as $detail):
                        ?>
                        <tr>
                            <td class="text-center"><?php echo $stt++; ?></td>
                            <td>
                                <strong class="text-primary">
                                    <?php echo htmlspecialchars($detail['product_code']); ?>
                                </strong>
                            </td>
                            <td><?php echo htmlspecialchars($detail['product_name']); ?></td>
                            <td class="text-center">
                                <span class="badge bg-info">
                                    <?php echo number_format($detail['quantity']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($detail['unit']); ?></td>
                            <td class="text-end">
                                <?php echo number_format($detail['price'], 0, ',', '.'); ?>đ
                            </td>
                            <td class="text-end">
                                <strong class="text-success">
                                    <?php echo number_format($detail['subtotal'], 0, ',', '.'); ?>đ
                                </strong>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="6" class="text-end">
                                <strong>Tổng cộng:</strong>
                            </td>
                            <td class="text-end">
                                <strong class="text-success fs-5">
                                    <?php echo number_format($import['total_amount'], 0, ',', '.'); ?>đ
                                </strong>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Print Styles -->
<style>
@media print {
    .navbar, .btn, .alert {
        display: none !important;
    }

    body {
        background: white !important;
    }

    .card {
        border: 1px solid #000 !important;
        box-shadow: none !important;
        page-break-inside: avoid;
    }

    .card-header {
        background-color: #f0f0f0 !important;
        color: #000 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    h2 {
        text-align: center;
        margin-bottom: 20px;
    }

    .table {
        font-size: 12px;
    }

    .badge {
        border: 1px solid #000;
    }
}
</style>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
