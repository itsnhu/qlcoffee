<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';

requireEmployee();

// Tự động kiểm tra và nâng cấp DB
try {
    $checkCols = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('payment_status', $checkCols)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN payment_status ENUM('pending', 'paid') DEFAULT 'pending' AFTER status");
    }
    if (!in_array('payment_method', $checkCols)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN payment_method ENUM('cash', 'transfer', 'cod') DEFAULT 'cod' AFTER payment_status");
    }
    // Đảm bảo có trạng thái cancelled
    $pdo->exec("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'preparing', 'served', 'completed', 'cancelled') DEFAULT 'pending'");
} catch (Exception $e) { }

// Xử lý Thanh Toán
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_order'])) {
    $order_id = (int)$_POST['order_id'];
    $method = $_POST['payment_method']; // cash hoặc transfer
    
    try {
        $sql = "UPDATE orders SET payment_status = 'paid', payment_method = ?, status = 'completed' WHERE id = ?";
        $pdo->prepare($sql)->execute([$method, $order_id]);
        
        // Giải phóng bàn nếu có
        $order = fetchOne($pdo, "SELECT table_id FROM orders WHERE id = ?", [$order_id]);
        if ($order && $order['table_id']) {
            $pdo->prepare("UPDATE tables SET status = 'free' WHERE id = ?")->execute([$order['table_id']]);
        }
        
        setMessage('success', 'Đã thanh toán hóa đơn thành công!');
    } catch (Exception $e) {
        setMessage('danger', 'Lỗi thanh toán: ' . $e->getMessage());
    }
    header("Location: index.php");
    exit;
}

// Xử lý Lưu Đơn Hàng Nhanh
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_save'])) {
    $table_id = $_POST['table_id'] ?: null;
    $product_list_raw = trim($_POST['product_list'] ?? '');
    $total_amount = (float)($_POST['total_amount'] ?? 0);
    
    if (!empty($product_list_raw)) {
        try {
            $pdo->beginTransaction();
            $customer_name = !empty($_POST['customer_name']) ? $_POST['customer_name'] : 'Khách tại quầy';
            $customer_phone = $_POST['customer_phone'] ?? '';
            
            // Get the next sequence number
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
            $total_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            $next_num = $total_orders + 1;
            
            // Format: ORD-XXX (e.g. ORD-001)
            do {
                $order_code = 'ORD-' . sprintf('%03d', $next_num);
                $exists = fetchOne($pdo, "SELECT id FROM orders WHERE order_code = ?", [$order_code]);
                if ($exists) $next_num++;
            } while ($exists);
            
            $sql = "INSERT INTO orders (order_code, user_id, table_id, customer_name, customer_phone, total_amount, status, payment_status, note, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, 'pending', 'pending', ?, NOW())";
            $pdo->prepare($sql)->execute([$order_code, $_SESSION['user_id'], $table_id, $customer_name, $customer_phone, $total_amount, $product_list_raw]);
            if ($table_id) $pdo->prepare("UPDATE tables SET status = 'busy' WHERE id = ?")->execute([$table_id]);
            $pdo->commit();
            setMessage('success', "Đã tạo hóa đơn $order_code cho $customer_name!");
        } catch (Exception $e) { $pdo->rollBack(); setMessage('danger', 'Lỗi: ' . $e->getMessage()); }
    }
    header("Location: index.php");
    exit;
}

// Xử lý Xong/Sẵn sàng/Hủy
if (isset($_GET['action']) && isset($_GET['order_id'])) {
    $order_id = (int)$_GET['order_id'];
    $action = $_GET['action'];
    $newStatus = '';
    if ($action === 'start_processing') $newStatus = 'preparing';
    elseif ($action === 'mark_ready') $newStatus = 'served';
    
    if ($newStatus) {
        try {
            $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$newStatus, $order_id]);
            setMessage('success', 'Đã cập nhật trạng thái!');
        } catch (PDOException $e) { setMessage('danger', 'Lỗi: ' . $e->getMessage()); }
    } elseif ($action === 'cancel_order') {
        try {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?")->execute([$order_id]);
            $order = fetchOne($pdo, "SELECT table_id FROM orders WHERE id = ?", [$order_id]);
            if ($order && $order['table_id']) {
                $pdo->prepare("UPDATE tables SET status = 'free' WHERE id = ?")->execute([$order['table_id']]);
            }
            $pdo->commit();
            setMessage('success', 'Đã hủy hóa đơn!');
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            setMessage('danger', 'Lỗi hủy hóa đơn: ' . $e->getMessage());
        }
    }
    header("Location: index.php" . (isset($_GET['status']) ? '?status=' . $_GET['status'] : ''));
    exit;
}

$current_filter = $_GET['status'] ?? 'all';

try {
    // Thống kê
    $raw_stats = fetchAll($pdo, "SELECT status, payment_status, COUNT(*) as count FROM orders GROUP BY status, payment_status");
    $stats = ['total'=>0, 'pending'=>0, 'preparing'=>0, 'served'=>0, 'completed'=>0];
    foreach ($raw_stats as $rs) {
        if ($rs['payment_status'] === 'paid') $stats['completed'] += $rs['count'];
        elseif (isset($stats[$rs['status']])) $stats[$rs['status']] += $rs['count'];
        $stats['total'] += $rs['count'];
    }

    // Danh sách
    $sql = "SELECT o.*, t.name as table_name,
            (SELECT GROUP_CONCAT(p.name SEPARATOR ', ') 
             FROM order_details od 
             JOIN products p ON od.product_id = p.id 
             WHERE od.order_id = o.id) as product_list
            FROM orders o
            LEFT JOIN tables t ON o.table_id = t.id";
    $params = [];
    if ($current_filter === 'completed') {
        $sql .= " WHERE o.payment_status = 'paid'";
    } elseif ($current_filter !== 'all') {
        $sql .= " WHERE o.status = ? AND o.payment_status = 'pending'";
        $params[] = $current_filter;
    }
    $sql .= " ORDER BY o.id DESC LIMIT 50";
    $allOrders = fetchAll($pdo, $sql, $params);
    $tables = fetchAll($pdo, "SELECT * FROM tables ORDER BY name");
    $allProducts = fetchAll($pdo, "SELECT id, name, price FROM products WHERE is_available = 1 ORDER BY name ASC");
} catch (PDOException $e) { $allOrders = []; $tables = []; $allProducts = []; }

$pageTitle = 'Đơn hàng';
require_once dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<!-- Đưa CSS lên ngoài body để dễ kiểm soát -->
<style>
.main-content-pos { padding: 0.5rem 1.5rem; }
.bg-teal { background: #14b8a6; }
.rounded-10 { border-radius: 10px; }
.rounded-15 { border-radius: 15px; }
.rounded-20 { border-radius: 20px; }
.rounded-25 { border-radius: 25px; }

.stat-card-pos {
    background: white;
    border-radius: 20px;
    padding: 1.25rem;
    border: 1px solid #f1f5f9;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    height: 100%;
}

.stat-card-pos::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    width: 4px;
    height: 100%;
    background: var(--accent-color);
}

.stat-card-pos.active {
    background: var(--accent-color);
    color: white;
    transform: translateY(-5px);
    box-shadow: 0 10px 20px -5px rgba(0,0,0,0.1);
}

.stat-card-pos.active .stat-value,
.stat-card-pos.active .stat-label,
.stat-card-pos.active .stat-icon i {
    color: white !important;
}

.stat-card-pos.active .stat-icon {
    background: rgba(255,255,255,0.2) !important;
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-icon i { font-size: 1.1rem; color: var(--accent-color); }
.stat-value { font-size: 1.25rem; font-weight: 800; color: #1e293b; line-height: 1.2; }
.stat-label { font-size: 0.7rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }

.premium-order-card {
    background: white !important;
    border-radius: 24px !important;
    border: 1px solid #f1f5f9 !important;
    box-shadow: 0 2px 15px rgba(0,0,0,0.03) !important;
    transition: all 0.3s ease !important;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.premium-order-card:hover {
    transform: translateY(-5px) !important;
    box-shadow: 0 12px 30px rgba(0,0,0,0.08) !important;
}

.order-paid { border-top: 5px solid #10b981 !important; }

.order-code { 
    font-size: 1.1rem; 
    font-weight: 800; 
    color: #1e293b;
    word-break: break-all;
    flex: 1;
    margin-right: 10px;
}
.order-price { 
    font-size: 1.1rem; 
    font-weight: 700; 
    color: #14b8a6;
    white-space: nowrap;
}

.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 100px;
    font-size: 0.7rem;
    font-weight: 700;
}

.status-pending { background: #fffbeb; color: #d97706; border: 1px solid #fef3c7; }
.status-preparing { background: #ecfdf5; color: #059669; border: 1px solid #d1fae5; }
.status-ready { background: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; }
.status-paid { background: #f0fdf4; color: #15803d; border: 1px solid #dcfce7; }
.status-cancelled { background: #fef2f2; color: #991b1b; border: 1px solid #fee2e2; }

.product-list-preview {
    background: #f8faf9;
    padding: 1rem;
    border-radius: 15px;
    font-size: 0.85rem;
    color: #475569;
    min-height: 80px;
}

.btn-brown { background: #5d4037; color: white; border: none; }
.btn-brown:hover { background: #4e342e; color: white; }

.btn-light-pos { background: #f1f5f9; border: none; color: #475569; font-weight: 600; }
.btn-warning-pos { background: #f59e0b; border: none; }
.btn-danger-pos { background: #ef4444; border: none; }
.btn-success-pos { background: #10b981; border: none; }
.btn-secondary-pos { background: #475569; border: none; }
</style>

<div class="main-content-pos">
    <div class="d-flex justify-content-end mb-4">
        <button type="button" class="btn btn-brown rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#quickOrderModal">
            <i class="bi bi-plus-lg me-2"></i> Thêm mới
        </button>
    </div>

    <!-- Quick Stats Bar -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-5 g-3 mb-5">
        <?php 
        $statItems = [
            ['id' => 'all', 'label' => 'Tất cả đơn', 'count' => $stats['total'], 'bg' => '#6366f1', 'icon' => 'bi-receipt'],
            ['id' => 'pending', 'label' => 'Chờ xử lý', 'count' => $stats['pending'], 'bg' => '#f59e0b', 'icon' => 'bi-clock-history'],
            ['id' => 'preparing', 'label' => 'Đang pha chế', 'count' => $stats['preparing'], 'bg' => '#10b981', 'icon' => 'bi-fire'],
            ['id' => 'served', 'label' => 'Sẵn sàng', 'count' => $stats['served'], 'bg' => '#3b82f6', 'icon' => 'bi-check2-all'],
            ['id' => 'completed', 'label' => 'Đã hoàn thành', 'count' => $stats['completed'], 'bg' => '#64748b', 'icon' => 'bi-archive-fill'],
        ];
        foreach ($statItems as $item): 
            $isActive = $current_filter === $item['id'];
        ?>
        <div class="col">
            <a href="?status=<?= $item['id'] ?>" class="text-decoration-none">
                <div class="stat-card-pos <?= $isActive ? 'active' : '' ?>" style="--accent-color: <?= $item['bg'] ?>">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="stat-icon">
                            <i class="bi <?= $item['icon'] ?>"></i>
                        </div>
                        <div class="text-end">
                            <div class="stat-value"><?= number_format($item['count']) ?></div>
                            <div class="stat-label"><?= $item['label'] ?></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Orders Grid -->
    <div class="row g-4">
        <?php foreach ($allOrders as $order): 
            $is_paid = $order['payment_status'] === 'paid';
            $status_class = ''; $status_text = '';
            if ($is_paid) { $status_class = 'status-paid'; $status_text = 'Đã thanh toán'; }
            else {
                switch($order['status']) {
                    case 'pending': $status_class = 'status-pending'; $status_text = 'Đang chờ'; break;
                    case 'preparing': $status_class = 'status-preparing'; $status_text = 'Đang pha chế'; break;
                    case 'served': $status_class = 'status-ready'; $status_text = 'Sẵn sàng - Chờ thu tiền'; break;
                    case 'cancelled': $status_class = 'status-cancelled'; $status_text = 'Đã hủy'; break;
                }
            }
        ?>
        <div class="col-sm-6 col-lg-4 col-xl-3">
            <div class="premium-order-card <?= $is_paid ? 'order-paid' : '' ?>">
                <div class="order-header p-4 pb-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
                        <h5 class="order-code mb-0">#<?= htmlspecialchars($order['order_code']) ?></h5>
                        <div class="order-price"><?= number_format($order['total_amount']) ?>đ</div>
                    </div>
                    <span class="status-badge <?= $status_class ?> mb-3"><?= $status_text ?></span>
                    
                    <div class="order-meta mb-3 small">
                        <span class="me-3"><i class="bi bi-geo-alt me-1"></i> <?= $order['table_name'] ?: 'Mang về' ?></span>
                        <span><i class="bi bi-clock me-1"></i> <?= date('H:i', strtotime($order['created_at'])) ?></span>
                        <div class="mt-1"><i class="bi bi-person me-1 text-primary"></i> <strong><?= htmlspecialchars($order['customer_name'] ?? 'Khách lẻ') ?></strong></div>
                    </div>
                </div>

                <div class="order-body p-4 pt-2">
                    <div class="product-list-preview mb-4">
                        <?= !empty($order['product_list']) ? htmlspecialchars($order['product_list']) : htmlspecialchars($order['note'] ?? 'Hóa đơn mới...') ?>
                    </div>

                    <div class="order-actions mt-auto">
                        <a href="<?= BASE_URL ?>/admin/sales/view.php?id=<?= $order['id'] ?>" class="btn btn-light-pos w-100 py-2 mb-2 rounded-10 small fw-bold">
                            <i class="bi bi-eye me-2"></i> Chi tiết
                        </a>

                        <?php if (!$is_paid && $order['status'] !== 'cancelled'): ?>
                            <?php if ($order['status'] === 'pending'): ?>
                                <a href="?action=start_processing&order_id=<?= $order['id'] ?>" class="btn btn-warning-pos w-100 py-2 fw-bold rounded-10 text-dark mb-2">Chế biến</a>
                                <a href="?action=cancel_order&order_id=<?= $order['id'] ?>" class="btn btn-outline-danger w-100 py-2 fw-bold rounded-10 mb-2 small" onclick="return confirm('Bạn có chắc muốn hủy hóa đơn này?')">Hủy hóa đơn</a>
                            <?php elseif ($order['status'] === 'preparing'): ?>
                                <a href="?action=mark_ready&order_id=<?= $order['id'] ?>" class="btn btn-danger-pos w-100 py-2 fw-bold rounded-10 text-dark mb-2">Sẵn sàng</a>
                            <?php endif; ?>
                            <button type="button" class="btn btn-success-pos w-100 py-3 fw-bold rounded-10 text-dark mt-2" data-bs-toggle="modal" data-bs-target="#payModal<?= $order['id'] ?>">
                                <i class="bi bi-cash-coin me-2"></i> Thanh toán
                            </button>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>/admin/sales/print.php?id=<?= $order['id'] ?>" target="_blank" class="btn btn-secondary-pos w-100 py-3 fw-bold rounded-10 text-dark">
                                <i class="bi bi-printer me-2"></i> In Hóa Đơn
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Modal Thanh Toán -->
            <div class="modal fade" id="payModal<?= $order['id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-20">
                        <div class="modal-header border-0 p-4 pb-0">
                            <h5 class="modal-title fw-bold">Thanh toán #<?= htmlspecialchars($order['order_code']) ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <div class="modal-body p-4 text-center">
                                <div class="display-6 fw-bold text-success mb-3"><?= number_format($order['total_amount']) ?>đ</div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold d-block mb-3">Chọn hình thức</label>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <input type="radio" class="btn-check" name="payment_method" value="cash" id="cash<?= $order['id'] ?>" checked>
                                            <label class="btn btn-outline-primary w-100 py-3 rounded-15" for="cash<?= $order['id'] ?>">Tiền mặt</label>
                                        </div>
                                        <div class="col-6">
                                            <input type="radio" class="btn-check" name="payment_method" value="transfer" id="transfer<?= $order['id'] ?>">
                                            <label class="btn btn-outline-primary w-100 py-3 rounded-15" for="transfer<?= $order['id'] ?>">Chuyển khoản</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer border-0 p-4 pt-0">
                                <button type="submit" name="pay_order" class="btn btn-success w-100 py-3 rounded-pill fw-bold">Xác nhận thu tiền</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal Thêm Đơn Hàng Nhanh -->
<div class="modal fade" id="quickOrderModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-25 overflow-hidden">
            <div class="modal-header bg-teal text-white p-4">
                <h5 class="modal-title fw-bold">Tạo Hóa Đơn Mới</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase mb-2">Tên khách</label>
                        <input type="text" name="customer_name" class="form-control border-0 px-3 py-3" style="background: #f1f5f9; border-radius: 12px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase mb-2">Số điện thoại</label>
                        <input type="tel" name="customer_phone" class="form-control border-0 px-3 py-3" style="background: #f1f5f9; border-radius: 12px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase mb-2">Số bàn</label>
                        <select name="table_id" class="form-select border-0 px-3 py-3" style="background: #f1f5f9; border-radius: 12px;">
                            <option value="">Mang về</option>
                            <?php foreach ($tables as $t): ?><option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase mb-2">Chọn món nhanh</label>
                        <select id="quickProductSelect" class="form-select border-0 px-3 py-3 mb-2" style="background: #f1f5f9; border-radius: 12px;">
                            <option value="">-- Chọn món để thêm --</option>
                            <?php foreach ($allProducts as $p): ?>
                                <option value="<?= htmlspecialchars($p['name']) ?>" data-price="<?= $p['price'] ?>">
                                    <?= htmlspecialchars($p['name']) ?> - <?= number_format($p['price']) ?>đ
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <textarea name="product_list" id="quickOrderProductList" class="form-control border-0 px-3 py-3" rows="3" style="background: #f1f5f9; border-radius: 12px;" placeholder="Danh sách món đã chọn..."></textarea>
                        <div class="text-end mt-1">
                            <button type="button" class="btn btn-sm text-danger fw-bold" onclick="document.getElementById('quickOrderProductList').value=''; document.getElementById('quickOrderTotal').value=0;">Xóa hết</button>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold small text-uppercase mb-2">Tổng tiền (đ)</label>
                        <input type="number" name="total_amount" id="quickOrderTotal" class="form-control border-0 px-3 py-3 fw-bold text-primary" style="background: #f1f5f9; border-radius: 12px;" value="0">
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" name="quick_save" class="btn btn-dark w-100 py-3 rounded-pill fw-bold">Lưu đơn hàng</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('quickProductSelect').addEventListener('change', function() {
    const dish = this.value;
    const price = parseFloat(this.selectedOptions[0].getAttribute('data-price') || 0);
    const list = document.getElementById('quickOrderProductList');
    const totalInput = document.getElementById('quickOrderTotal');
    
    if (dish && this.selectedIndex > 0) {
        if (list.value.trim().length > 0) {
            list.value += ', ' + dish;
        } else {
            list.value = dish;
        }
        let currentTotal = parseFloat(totalInput.value || 0);
        totalInput.value = currentTotal + price;
        this.value = '';
    }
});
</script>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
