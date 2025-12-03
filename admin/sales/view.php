<?php



require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';


requireEmployee();


$invoice_id = $_GET['id'] ?? 0;

if (!is_numeric($invoice_id) || $invoice_id <= 0) {
    setMessage('danger', 'ID hóa đơn không hợp lệ.');
    header('Location: ' . BASE_URL . '/admin/sales/index.php');
    exit;
}

try {
    
    $sql = "SELECT i.*, u.full_name as staff_name, u.username as staff_username
            FROM invoices i
            LEFT JOIN users u ON i.user_id = u.id
            WHERE i.id = ?";

    
    if ($_SESSION['role'] === 'employee') {
        $sql .= " AND i.user_id = ?";
        $invoice = fetchOne($pdo, $sql, [$invoice_id, $_SESSION['user_id']]);
    } else {
        $invoice = fetchOne($pdo, $sql, [$invoice_id]);
    }

    if (!$invoice) {
        setMessage('danger', 'Không tìm thấy hóa đơn hoặc bạn không có quyền truy cập.');
        header('Location: ' . BASE_URL . '/admin/sales/index.php');
        exit;
    }

    
    $sql = "SELECT id.*, m.name as medicine_name, m.code as medicine_code, m.unit
            FROM invoice_details id
            LEFT JOIN medicines m ON id.medicine_id = m.id
            WHERE id.invoice_id = ?
            ORDER BY id.id ASC";

    $details = fetchAll($pdo, $sql, [$invoice_id]);

} catch (PDOException $e) {
    error_log("Load Invoice Error: " . $e->getMessage());
    setMessage('danger', 'Có lỗi khi tải thông tin hóa đơn.');
    header('Location: ' . BASE_URL . '/admin/sales/index.php');
    exit;
}

$pageTitle = 'Chi tiết hóa đơn ' . $invoice['invoice_code'];
$additionalCSS = '
<style>
    .invoice-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 10px;
        margin-bottom: 20px;
    }
    .info-label {
        font-weight: 600;
        color: #6c757d;
    }
    .info-value {
        font-size: 1.1rem;
    }
    @media print {
        .no-print {
            display: none;
        }
    }
</style>
';

require_once dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<!-- Page Header -->
<div class="row mb-4 no-print">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-2">
                    <i class="bi bi-receipt-cutoff text-primary"></i>
                    Chi tiết hóa đơn
                </h2>
                <p class="text-muted mb-0">
                    Mã hóa đơn: <strong class="text-primary"><?php echo htmlspecialchars($invoice['invoice_code']); ?></strong>
                </p>
            </div>
            <div>
                <a href="<?php echo BASE_URL; ?>/admin/sales/index.php" class="btn btn-outline-secondary me-2">
                    <i class="bi bi-arrow-left me-2"></i>
                    Quay lại
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/sales/print.php?id=<?php echo $invoice['id']; ?>"
                   class="btn btn-primary"
                   target="_blank">
                    <i class="bi bi-printer me-2"></i>
                    In hóa đơn
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Invoice Header -->
<div class="invoice-header shadow-sm">
    <div class="row">
        <div class="col-md-6">
            <h3 class="mb-3">
                <i class="bi bi-receipt"></i>
                HÓA ĐƠN BÁN HÀNG
            </h3>
            <p class="mb-2">
                <strong>Mã hóa đơn:</strong> <?php echo htmlspecialchars($invoice['invoice_code']); ?>
            </p>
            <p class="mb-0">
                <strong>Ngày tạo:</strong>
                <?php echo date('d/m/Y H:i:s', strtotime($invoice['created_at'])); ?>
            </p>
        </div>
        <div class="col-md-6 text-md-end">
            <p class="mb-2">
                <strong>Nhân viên:</strong> <?php echo htmlspecialchars($invoice['staff_name']); ?>
            </p>
            <p class="mb-0">
                <strong>Username:</strong> <?php echo htmlspecialchars($invoice['staff_username']); ?>
            </p>
        </div>
    </div>
</div>

<!-- Customer Information -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-info text-white">
        <i class="bi bi-person-circle me-2"></i>
        <strong>Thông tin khách hàng</strong>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p class="mb-2">
                    <span class="info-label">Tên khách hàng:</span>
                    <span class="info-value ms-2">
                        <?php echo !empty($invoice['customer_name']) ? htmlspecialchars($invoice['customer_name']) : '<em class="text-muted">Khách lẻ</em>'; ?>
                    </span>
                </p>
            </div>
            <div class="col-md-6">
                <p class="mb-2">
                    <span class="info-label">Số điện thoại:</span>
                    <span class="info-value ms-2">
                        <?php echo !empty($invoice['customer_phone']) ? htmlspecialchars($invoice['customer_phone']) : '<em class="text-muted">-</em>'; ?>
                    </span>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Invoice Details -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-success text-white">
        <i class="bi bi-list-ul me-2"></i>
        <strong>Chi tiết hóa đơn</strong>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th width="5%">STT</th>
                        <th width="15%">Mã thuốc</th>
                        <th width="35%">Tên thuốc</th>
                        <th width="10%" class="text-center">ĐVT</th>
                        <th width="10%" class="text-end">Số lượng</th>
                        <th width="12%" class="text-end">Đơn giá</th>
                        <th width="13%" class="text-end">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($details)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">
                                Không có chi tiết
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php
                        $stt = 1;
                        foreach ($details as $detail):
                        ?>
                        <tr>
                            <td><?php echo $stt++; ?></td>
                            <td>
                                <code><?php echo htmlspecialchars($detail['medicine_code']); ?></code>
                            </td>
                            <td><?php echo htmlspecialchars($detail['medicine_name']); ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($detail['unit']); ?></td>
                            <td class="text-end">
                                <strong><?php echo number_format($detail['quantity']); ?></strong>
                            </td>
                            <td class="text-end"><?php echo number_format($detail['price']); ?>đ</td>
                            <td class="text-end">
                                <strong class="text-success">
                                    <?php echo number_format($detail['subtotal']); ?>đ
                                </strong>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="6" class="text-end">
                            <strong>TỔNG CỘNG:</strong>
                        </td>
                        <td class="text-end">
                            <h5 class="mb-0 text-success">
                                <strong><?php echo number_format($invoice['total_amount']); ?>đ</strong>
                            </h5>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Summary Card -->
<div class="row">
    <div class="col-md-6 offset-md-6">
        <div class="card shadow-sm border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Tổng số lượng:</h5>
                    <h5 class="mb-0 text-primary">
                        <?php
                        $total_qty = array_sum(array_column($details, 'quantity'));
                        echo number_format($total_qty);
                        ?>
                    </h5>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Tổng tiền thanh toán:</h4>
                    <h3 class="mb-0 text-success">
                        <strong><?php echo number_format($invoice['total_amount']); ?>đ</strong>
                    </h3>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Actions (No Print) -->
<div class="row mt-4 no-print">
    <div class="col-12">
        <div class="d-flex justify-content-end gap-2">
            <a href="<?php echo BASE_URL; ?>/admin/sales/index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-2"></i>
                Quay lại danh sách
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/sales/print.php?id=<?php echo $invoice['id']; ?>"
               class="btn btn-primary"
               target="_blank">
                <i class="bi bi-printer me-2"></i>
                In hóa đơn
            </a>
        </div>
    </div>
</div>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
