<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';

requireEmployee();

$order_id = $_GET['id'] ?? 0;
try {
    $sql = "SELECT o.*, t.name as table_name, u.full_name as staff_name
            FROM orders o
            LEFT JOIN tables t ON o.table_id = t.id
            LEFT JOIN users u ON o.user_id = u.id
            WHERE o.id = ?";
    $order = fetchOne($pdo, $sql, [$order_id]);

    if (!$order) die("Không tìm thấy đơn hàng.");

    $sql = "SELECT od.*, p.name as product_name
            FROM order_details od
            JOIN products p ON od.product_id = p.id
            WHERE od.order_id = ?";
    $details = fetchAll($pdo, $sql, [$order_id]);
    
    // Nếu không có details thì ghép text từ product_list (đơn hàng nhanh)
    $items_text = [];
    if (!empty($details)) {
        foreach ($details as $d) {
            $items_text[] = $d['product_name'] . " (Size " . $d['size'] . ") x" . $d['quantity'];
        }
    }

} catch (Exception $e) { die($e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>In Hóa Đơn - #<?= $order['order_code'] ?></title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; line-height: 1.6; color: #333; max-width: 400px; margin: 0 auto; padding: 20px; }
        .text-center { text-align: center; }
        .header h1 { margin: 0; font-size: 1.5rem; text-transform: uppercase; }
        .header p { margin: 5px 0; font-size: 0.9rem; }
        .divider { border-top: 1px dashed #333; margin: 15px 0; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 0.95rem; }
        .info-label { font-weight: bold; }
        .section-title { font-weight: bold; text-transform: uppercase; margin-top: 15px; display: block; }
        .items-list { margin: 10px 0; padding-left: 0; list-style: none; }
        .footer { margin-top: 30px; font-style: italic; font-size: 0.9rem; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; width: 100%; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">IN HÓA ĐƠN</button>
        <button onclick="window.close()" style="padding: 10px 20px; cursor: pointer;">ĐÓNG</button>
    </div>

    <div class="header text-center">
        <h1>TNT Coffee</h1>
        <p>Địa chỉ: TP. Cao Lãnh, Đồng Tháp</p>
        <p>SĐT: 1900 123 456</p>
    </div>

    <div class="divider"></div>

    <div class="info-row">
        <span class="info-label">Mã đơn:</span>
        <span>#<?= $order['order_code'] ?></span>
    </div>
    <div class="info-row">
        <span class="info-label">Bàn phục vụ:</span>
        <span><?= $order['table_name'] ?: 'Mang về' ?></span>
    </div>
    <div class="info-row">
        <span class="info-label">Thời gian:</span>
        <span><?= date('H:i:s d/m/Y', strtotime($order['created_at'])) ?></span>
    </div>

    <div class="divider"></div>

    <span class="section-title">CÁC MÓN ĐÃ GỌI:</span>
    <ul class="items-list">
        <?php if (!empty($items_text)): ?>
            <?php foreach ($items_text as $item): ?>
                <li>- <?= htmlspecialchars($item) ?></li>
            <?php endforeach; ?>
        <?php else: ?>
            <li>- <?= htmlspecialchars($order['customer_name']) ?> đã đặt món riêng</li>
        <?php endif; ?>
    </ul>

    <div class="divider"></div>

    <div class="info-row" style="font-size: 1.1rem;">
        <span class="info-label">Tổng hóa đơn:</span>
        <span style="font-weight: bold;"><?= number_format($order['total_amount']) ?> đ</span>
    </div>
    <div class="info-row">
        <span class="info-label">Hình thức TT:</span>
        <span><?= ($order['payment_method'] === 'transfer') ? 'Chuyển khoản' : 'Tiền mặt' ?></span>
    </div>

    <div class="divider"></div>

    <div class="footer text-center">
        Cảm ơn quý khách và hẹn gặp lại!
    </div>

    <script>
        // Tự động mở hộp thoại in sau 500ms
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
