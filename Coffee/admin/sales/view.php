<?php



require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';


requireEmployee();


$invoice_id = $_GET['id'] ?? 0;

if (!is_numeric($invoice_id) || $invoice_id <= 0) {
    setMessage('danger', 'ID đơn hàng không hợp lệ.');
    header('Location: ' . BASE_URL . '/admin/sales/index.php');
    exit;
}

try {
    
    $sql = "SELECT i.*, u.full_name as staff_name, u.username as staff_username
            FROM orders i
            LEFT JOIN users u ON i.user_id = u.id
            WHERE i.id = ?";

    
    if ($_SESSION['role'] === 'employee') {
        $sql .= " AND i.user_id = ?";
        $invoice = fetchOne($pdo, $sql, [$invoice_id, $_SESSION['user_id']]);
    } else {
        $invoice = fetchOne($pdo, $sql, [$invoice_id]);
    }

    if (!$invoice) {
        setMessage('danger', 'Không tìm thấy đơn hàng hoặc bạn không có quyền truy cập.');
        header('Location: ' . BASE_URL . '/admin/sales/index.php');
        exit;
    }

    
    $sql = "SELECT id.*, m.name as product_name, m.code as product_code, m.unit
            FROM order_details id
            LEFT JOIN products m ON id.product_id = m.id
            WHERE id.order_id = ?
            ORDER BY id.id ASC";

    $details = fetchAll($pdo, $sql, [$invoice_id]);

} catch (PDOException $e) {
    error_log("Load Invoice Error: " . $e->getMessage());
    setMessage('danger', 'Có lỗi khi tải thông tin hóa đơn.');
    header('Location: ' . BASE_URL . '/admin/sales/index.php');
    exit;
}

$pageTitle = 'Chi tiết hóa đơn ' . $invoice['order_code'];
$additionalCSS = '
<style>
    .order-view-container { background: #f8fafc; padding: 1.25rem; border-radius: 12px; }
    
    .sleek-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        margin-bottom: 1rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        overflow: hidden;
        border-left: 4px solid transparent;
        transition: all 0.3s ease;
    }
    
    .card-purple { border-left-color: #8b5cf6; }
    .card-blue { border-left-color: #3b82f6; }
    .card-green { border-left-color: #10b981; }
    
    .card-head { padding: 0.75rem 1rem; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; }
    .card-title { margin: 0; font-size: 0.8rem; font-weight: 800; color: #1e293b; text-transform: uppercase; letter-spacing: 0.05em; display: flex; align-items: center; gap: 0.5rem; }
    
    .data-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); padding: 1rem; gap: 1rem; }
    .data-item { display: flex; flex-direction: column; }
    .data-label { font-size: 0.65rem; font-weight: 700; color: #94a3b8; margin-bottom: 0.2rem; text-transform: uppercase; }
    .data-value { font-size: 0.9rem; font-weight: 600; color: #334155; }
    
    .item-table thead th { background: #f8fafc; font-size: 0.65rem; font-weight: 800; color: #475569; text-transform: uppercase; padding: 0.75rem 1rem; border: none; }
    .item-table td { padding: 0.75rem 1rem; vertical-align: middle; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; }
    
    .price-pill { background: #f1f5f9; padding: 0.25rem 0.6rem; border-radius: 9999px; font-weight: 700; font-size: 0.75rem; }
    .total-pill { background: #ecfdf5; color: #059669; padding: 0.25rem 0.6rem; border-radius: 9999px; font-weight: 800; font-size: 0.85rem; }
    
    .payment-summary {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 0.85rem 1rem;
        margin-top: 1rem;
        max-width: 300px;
        margin-left: auto;
    }
    .summary-title { font-size: 0.65rem; font-weight: 700; color: #94a3b8; margin-bottom: 0.75rem; text-transform: uppercase; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.4rem; }
    
    .btn-action-sleek {
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.75rem;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }
    .btn-back-sleek { color: #64748b; background: white; border: 1px solid #e2e8f0; }
    .btn-print-sleek { color: white; background: #0f172a; border: none; }
</style>
';

require_once dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<div class="order-view-container mt-4">
    <!-- Header -->
    <div class="d-flex justify-content-end align-items-center mb-4 no-print">
        <div class="d-flex gap-3">
            <a href="<?= BASE_URL ?>/admin/sales/index.php" class="btn-action-sleek btn-back-sleek">
                <i class="bi bi-arrow-left"></i> QUAY LẠI
            </a>
            <a href="<?= BASE_URL ?>/admin/sales/print.php?id=<?= $invoice['id'] ?>" target="_blank" class="btn-action-sleek btn-print-sleek">
                <i class="bi bi-printer"></i> IN HÓA ĐƠN
            </a>
        </div>
    </div>

    <!-- 1. Transaction Info -->
    <div class="sleek-card card-purple">
        <div class="card-head">
            <h5 class="card-title"><i class="bi bi-lightning-charge-fill text-purple"></i> CHI TIẾT HÓA ĐƠN</h5>
            <?php
            $statusLabels = [
                'pending' => ['Chờ xử lý', 'warning'],
                'confirmed' => ['Đã xác nhận', 'info'],
                'preparing' => ['Đang pha chế', 'primary'],
                'served' => ['Đã phục vụ', 'info'],
                'shipping' => ['Đang giao', 'primary'],
                'completed' => ['Hoàn thành', 'success'],
                'paid' => ['Đã thanh toán', 'success'],
                'cancelled' => ['Đã hủy', 'danger'],
            ];
            $status = $invoice['status'] ?? 'pending';
            $label = $statusLabels[$status] ?? ['Không xác định', 'secondary'];
            ?>
            <span class="badge bg-<?= $label[1] ?>-subtle text-<?= $label[1] ?> rounded-pill px-3"><?= $label[0] ?></span>
        </div>
        <div class="data-row">
            <div class="data-item">
                <span class="data-label">Mã hóa đơn</span>
                <span class="data-value text-primary"><?= htmlspecialchars($invoice['order_code']) ?></span>
            </div>
            <div class="data-item">
                <span class="data-label">Ngày thanh toán</span>
                <span class="data-value"><?= date('d/m/Y • H:i', strtotime($invoice['created_at'])) ?></span>
            </div>
            <div class="data-item">
                <span class="data-label">Nhân viên phụ trách</span>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <div class="user-profile-avatar" style="width: 32px; height: 32px; font-size: 0.8rem; flex-shrink: 0;">
                        <?= strtoupper(substr($invoice['staff_name'] ?? 'Q', 0, 1)) ?>
                    </div>
                    <div class="data-value">
                        <?= htmlspecialchars($invoice['staff_name'] ?? 'Khách tự đặt (Online)') ?>
                        <?php if(!empty($invoice['staff_username'])): ?>
                            <small class="text-muted fw-normal d-block" style="font-size: 0.7rem;">@<?= htmlspecialchars($invoice['staff_username']) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Participant Info -->
    <div class="sleek-card card-blue">
        <div class="card-head">
            <h5 class="card-title"><i class="bi bi-person-badge-fill text-blue"></i> THÔNG TIN KHÁCH HÀNG</h5>
        </div>
        <div class="data-row">
            <div class="data-item">
                <span class="data-label">Họ và tên</span>
                <span class="data-value"><?= !empty($invoice['customer_name']) ? htmlspecialchars($invoice['customer_name']) : 'Khách lẻ' ?></span>
            </div>
            <div class="data-item">
                <span class="data-label">Số điện thoại</span>
                <span class="data-value"><?= !empty($invoice['customer_phone']) ? htmlspecialchars($invoice['customer_phone']) : '—' ?></span>
            </div>
        </div>
    </div>

    <!-- 3. Line Items -->
    <div class="sleek-card card-green">
        <div class="card-head">
            <h5 class="card-title"><i class="bi bi-bag-check-fill text-green"></i> DANH SÁCH MÓN</h5>
        </div>
        <div class="table-responsive">
            <table class="table item-table mb-0">
                <thead>
                    <tr>
                        <th width="80">STT</th>
                        <th>Tên món & Mã món</th>
                        <th class="text-center">ĐVT</th>
                        <th class="text-center">SL</th>
                        <th class="text-end">Đơn giá</th>
                        <th class="text-end">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $stt = 1;
                    foreach ($details as $item): ?>
                    <tr>
                        <td class="text-muted fw-bold">#<?= str_pad($stt++, 2, '0', STR_PAD_LEFT) ?></td>
                        <td>
                            <div class="fw-bold fs-6 mb-1"><?= htmlspecialchars($item['product_name']) ?></div>
                            <code class="text-danger small"><?= htmlspecialchars($item['product_code']) ?></code>
                        </td>
                        <td class="text-center text-muted"><?= htmlspecialchars($item['unit'] ?? 'Ly') ?></td>
                        <td class="text-center fw-black fs-6"><?= number_format($item['quantity']) ?></td>
                        <td class="text-end"><span class="price-pill"><?= number_format($item['price']) ?>đ</span></td>
                        <td class="text-end"><span class="total-pill"><?= number_format($item['subtotal']) ?>đ</span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="payment-summary shadow-sm border-green">
        <div class="summary-title">TỔNG KẾT THANH TOÁN</div>
        <div class="d-flex justify-content-between align-items-end mb-2">
            <div class="text-muted fw-bold small" style="font-size: 0.75rem;">Tổng số lượng món</div>
            <div class="fw-black mb-0 h6"><?= number_format(array_sum(array_column($details, 'quantity'))) ?> món</div>
        </div>
        <div class="d-flex justify-content-between align-items-end pt-2 border-top border-1 border-dashed">
            <div class="text-slate-900 fw-black small mb-0" style="font-size: 0.8rem;">Tổng tiền thanh toán</div>
            <div class="h4 fw-black mb-0 text-success"><?= number_format($invoice['total_amount']) ?>đ</div>
        </div>
    </div>
</div>

<style>
    .text-purple { color: #8b5cf6 !important; }
    .bg-purple-subtle { background-color: #f5f3ff !important; }
    .text-blue { color: #3b82f6 !important; }
    .text-green { color: #10b981 !important; }
    .fw-black { font-weight: 900 !important; }
</style>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
