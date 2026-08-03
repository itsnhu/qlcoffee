<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/customer_header.php';

$orderCode = $_GET['code'] ?? '';

// Basic validation: Check if order exists (optional)
// For simplicity, just show the success message.
?>

<div class="container py-5 text-center">
    <div class="card border-0 shadow-sm rounded-4 p-5 d-inline-block" style="max-width: 600px;">
        <div class="mb-4">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
        </div>
        <h2 class="fw-bold mb-3">Đặt Hàng Thành Công!</h2>
        <p class="text-muted mb-4">
            Cảm ơn bạn đã đặt món tại Coffee Manager.<br>
            Mã đơn hàng của bạn là: <strong class="text-primary"><?= htmlspecialchars($orderCode) ?></strong>
        </p>
        <div class="d-flex justify-content-center gap-3">
            <a href="menu.php" class="btn btn-primary rounded-pill px-4">Tiếp tục đặt món</a>
            <a href="booking.php" class="btn btn-brown rounded-pill px-4"><i class="bi bi-calendar-plus me-2"></i>Đặt bàn ngay</a>
            <a href="user/orders.php" class="btn btn-outline-primary rounded-pill px-4">Xem đơn hàng</a>
        </div>
    </div>
</div>

<?php require_once 'includes/customer_footer.php'; ?>
