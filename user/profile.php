<?php
ob_start();
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

if (!isset($_SESSION['customer_id'])) {
    $_SESSION['redirect_after_login'] = BASE_URL . '/user/profile.php';
    setMessage('warning', 'Vui lòng đăng nhập');
    header('Location: ' . BASE_URL . '/login.php?type=customer');
    exit;
}

$customerId = $_SESSION['customer_id'];
$customer   = fetchOne($pdo, "SELECT * FROM customers WHERE id = ?", [$customerId]);
$errors     = [];
$ajaxResp   = null;

/* ── AJAX handler ── */
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $fullName = sanitize($_POST['full_name'] ?? '');
        $phone    = sanitize($_POST['phone']     ?? '');
        $address  = sanitize($_POST['address']   ?? '');
        if (empty($fullName)) {
            echo json_encode(['ok' => false, 'msg' => 'Vui lòng nhập họ tên']);
            exit;
        }
        try { executeQuery($pdo, "UPDATE customers SET full_name=?, phone=?, address=? WHERE id=?", [$fullName, $phone, $address, $customerId]); }
        catch (Exception $e) { executeQuery($pdo, "UPDATE customers SET full_name=?, phone=? WHERE id=?", [$fullName, $phone, $customerId]); }
        $_SESSION['customer_name'] = $fullName;
        echo json_encode(['ok' => true, 'msg' => 'Cập nhật thành công!']);
        exit;
    }

    if ($action === 'change_password') {
        $cust = fetchOne($pdo, "SELECT password FROM customers WHERE id = ?", [$customerId]);
        $curr = $_POST['current_password'] ?? '';
        $new  = $_POST['new_password']     ?? '';
        $conf = $_POST['confirm_password'] ?? '';
        if (!password_verify($curr, $cust['password']))
            { echo json_encode(['ok' => false, 'msg' => 'Mật khẩu hiện tại không đúng']); exit; }
        if (strlen($new) < 6)
            { echo json_encode(['ok' => false, 'msg' => 'Mật khẩu mới phải ít nhất 6 ký tự']); exit; }
        if ($new !== $conf)
            { echo json_encode(['ok' => false, 'msg' => 'Mật khẩu xác nhận không khớp']); exit; }
        executeQuery($pdo, "UPDATE customers SET password=? WHERE id=?", [password_hash($new, PASSWORD_DEFAULT), $customerId]);
        echo json_encode(['ok' => true, 'msg' => 'Đổi mật khẩu thành công!']);
        exit;
    }

    echo json_encode(['ok' => false, 'msg' => 'Hành động không hợp lệ']);
    exit;
}

/* ── Stats ── */
$orderStats = fetchOne($pdo, "
    SELECT COUNT(*) as total_orders,
        COALESCE(SUM(CASE WHEN status IN ('completed','paid','served') THEN total_amount ELSE 0 END),0) as total_spent,
        COALESCE(SUM(CASE WHEN status IN ('pending','preparing','confirmed','shipping') THEN 1 ELSE 0 END),0) as pending_orders,
        COALESCE(SUM(CASE WHEN status IN ('completed','paid','served') THEN 1 ELSE 0 END),0) as completed_orders
    FROM orders WHERE customer_id = ?
", [$customerId]);

/* ── Orders ── */
$orders = fetchAll($pdo, "
    SELECT o.*, COUNT(od.id) as item_count
    FROM orders o
    LEFT JOIN order_details od ON o.id = od.order_id
    WHERE o.customer_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
", [$customerId]);

$statusMap = [
    'pending'   => ['text' => 'Chờ xác nhận',  'cls' => 'st-pending'],
    'confirmed' => ['text' => 'Đã xác nhận',   'cls' => 'st-confirmed'],
    'preparing' => ['text' => 'Đang pha chế',  'cls' => 'st-preparing'],
    'shipping'  => ['text' => 'Đang giao',     'cls' => 'st-shipping'],
    'completed' => ['text' => 'Hoàn thành',    'cls' => 'st-completed'],
    'paid'      => ['text' => 'Đã thanh toán', 'cls' => 'st-completed'],
    'served'    => ['text' => 'Đã phục vụ',    'cls' => 'st-completed'],
    'cancelled' => ['text' => 'Đã hủy',        'cls' => 'st-cancelled'],
];

$joinDate = !empty($customer['created_at']) ? date('d/m/Y', strtotime($customer['created_at'])) : date('d/m/Y');

$pageTitle = 'Tài khoản của tôi';
require_once dirname(__DIR__) . '/includes/customer_header.php';
?>

<style>
/* ══════════════════════════════════════
   BASE
══════════════════════════════════════ */
:root { --coffee:#6F4E37; --coffee-dk:#5a3e2b; --gold:#ECB176; --bg:#eef0f5; }
body  { background:var(--bg); font-family:'Inter',sans-serif; }
.pf-page { padding:36px 0 70px; }

/* ══════════════════════════════════════
   SIDEBAR
══════════════════════════════════════ */
/* Sidebar */
.sidebar-card {
    background:#fff;
    border-radius:24px;
    padding:32px 20px;
    box-shadow:0 10px 40px rgba(0,0,0,0.04);
    position:sticky;
    top:100px;
    border:1px solid rgba(0,0,0,0.03);
}
.avatar-circle {
    width:95px; height:95px;
    border:4px solid var(--gold);
    border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    background:#fdf8f3;
    margin:0 auto 15px;
    box-shadow: 0 5px 15px rgba(236,177,118,0.2);
}
.avatar-circle i { font-size:2.8rem; color:var(--coffee); opacity:.7; }
.pf-name  { font-weight:800; font-size:1.1rem; color:#2d3436; margin-bottom:4px; text-align:center; }
.pf-email { font-size:.82rem; color:#a0a0a0; margin-bottom:12px; text-align:center; word-break:break-all; }
.points-badge { 
    background:#FFB300; 
    color:#fff; 
    font-size:.75rem; 
    font-weight:700; 
    padding:6px 16px; 
    border-radius:100px; 
    display:flex; 
    align-items:center; 
    gap:6px; 
    box-shadow: 0 4px 10px rgba(255,179,0,0.3);
    width: fit-content;
    margin: 0 auto;
}

.profile-nav { display: flex; flex-direction: column; gap: 4px; }
.profile-nav .nav-link {
    display:flex; align-items:center; gap:14px;
    padding:13px 18px;
    border-radius:14px;
    color:#555;
    font-weight:600;
    font-size:.92rem;
    text-decoration:none;
    transition:all .3s ease;
    cursor:pointer;
}
.profile-nav .nav-link i { font-size:1.1rem; color:#999; flex-shrink:0; transition:all .3s ease; }
.profile-nav .nav-link:hover { background:#f8f9fa; color:var(--coffee); }
.profile-nav .nav-link:hover i { color:var(--coffee); }
.profile-nav .nav-link.active { background:var(--coffee); color:#fff; box-shadow: 0 4px 15px rgba(111,78,55,0.25); }
.profile-nav .nav-link.active i { color:#fff; }
.profile-nav .nav-link.text-danger:hover { background:#fff0f0; }

/* ══════════════════════════════════════
   STAT CARDS
══════════════════════════════════════ */
.pf-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:24px; }
.pf-stat {
    background:#fff; border-radius:20px; padding:20px 12px;
    text-align:center; border:1.5px solid #ececec;
    box-shadow:0 4px 15px rgba(0,0,0,.04);
    transition:all .3s ease;
    cursor:pointer;
    position:relative;
    overflow:hidden;
}
.pf-stat:hover { 
    transform:translateY(-5px);
    box-shadow:0 8px 25px rgba(0,0,0,.08);
    border-color:var(--gold);
}
.pf-stat i.pf-stat-icon {
    font-size:1.2rem;
    margin-bottom:8px;
    display:block;
    opacity:0.8;
}
.pf-stat-val { font-size:1.6rem; font-weight:800; display:block; line-height:1.2; }
.pf-stat-lbl { font-size:.7rem; font-weight:700; color:#888; text-transform:uppercase; letter-spacing:.05em; display:block; margin-top:4px; }

.pf-stat.c-total   { border-left:4px solid var(--coffee); }
.pf-stat.c-done    { border-left:4px solid #27ae60; }
.pf-stat.c-pending { border-left:4px solid #f39c12; }
.pf-stat.c-spent   { border-left:4px solid #d4a017; }

.pf-stat.c-total .pf-stat-val   { color:var(--coffee); }
.pf-stat.c-done .pf-stat-val    { color:#27ae60; }
.pf-stat.c-pending .pf-stat-val { color:#f39c12; }
.pf-stat.c-spent .pf-stat-val   { color:#d4a017; }

.pf-stat.c-total i   { color:var(--coffee); }
.pf-stat.c-done i    { color:#27ae60; }
.pf-stat.c-pending i { color:#f39c12; }
.pf-stat.c-spent i   { color:#d4a017; }

/* ══════════════════════════════════════
   MAIN CARDS (tabs)
══════════════════════════════════════ */
.pf-card {
    background:#fff;
    border-radius:22px;
    padding:42px 46px;
    box-shadow:0 2px 20px rgba(0,0,0,.05);
    min-height:380px;
    animation:fadeInUp .25s ease;
}
@keyframes fadeInUp { from{opacity:0;transform:translateY(12px)} to{opacity:1;transform:translateY(0)} }

.pf-card-title { text-align:center; font-weight:700; font-size:1.3rem; color:#2d2d2d; margin-bottom:6px; }
.pf-card-accent { width:36px; height:4px; background:var(--coffee); border-radius:3px; margin:0 auto 32px; }

/* Form */
.pf-field label { display:block; font-weight:700; font-size:.85rem; color:#444; margin-bottom:8px; }
.pf-input-wrap { position:relative; margin-bottom:20px; }
.pf-input-wrap i.pf-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#bbb; font-size:1rem; pointer-events:none; }
.pf-input-wrap.ta-wrap i.pf-icon { top:14px; transform:none; }
.pf-input {
    width:100%; background:#fafafa; border:1.5px solid #eee;
    border-radius:12px; height:48px;
    padding:0 14px 0 40px; font-size:.93rem; color:#333;
    transition:border .2s, box-shadow .2s; font-family:inherit;
}
.pf-input:focus { outline:none; border-color:var(--gold); box-shadow:0 0 0 3px rgba(236,177,118,.15); background:#fff; }
.pf-input[readonly] { background:#f4f4f4; color:#999; cursor:not-allowed; }
textarea.pf-input { height:110px; padding-top:12px; resize:vertical; }
.pf-hint { font-size:.75rem; color:#aaa; margin-top:-12px; margin-bottom:16px; margin-left:2px; }
.pf-btn {
    background:var(--coffee); color:#fff; border:none;
    padding:13px 36px; border-radius:12px;
    font-weight:700; font-size:.95rem; cursor:pointer;
    transition:background .2s, transform .1s;
    display:inline-flex; align-items:center; gap:8px; margin-top:8px;
}
.pf-btn:hover  { background:var(--coffee-dk); transform:translateY(-1px); }
.pf-btn:active { transform:translateY(0); }

/* Alert toast */
.pf-toast {
    display:none;
    padding:12px 18px;
    border-radius:12px;
    font-size:.88rem; font-weight:600;
    margin-bottom:22px;
    animation:fadeInUp .2s ease;
}
.pf-toast.ok  { background:#eafaf1; color:#27ae60; border:1px solid #a9dfbf; }
.pf-toast.err { background:#fdf0ee; color:#c0392b; border:1px solid #f5b7b1; }

/* ══════════════════════════════════════
   ORDERS TAB
══════════════════════════════════════ */
.ord-empty { text-align:center; padding:60px 20px; color:#bbb; }
.ord-empty i { font-size:3rem; display:block; margin-bottom:14px; }
.ord-empty p { font-size:.95rem; }

.ord-list { margin-top:8px; }
.ord-item {
    display:flex; align-items:center; justify-content:space-between;
    gap:16px;
    border:1.5px solid #f0f0f0;
    border-radius:16px;
    padding:18px 22px;
    margin-bottom:12px;
    transition:border-color .2s, box-shadow .2s;
    background:#fff;
}
.ord-item:hover { border-color:#e8ddd6; box-shadow:0 4px 16px rgba(111,78,55,.07); }
.ord-code { font-weight:800; font-size:1rem; color:#222; }
.ord-date { font-size:.78rem; color:#aaa; margin-top:2px; }
.ord-items-count { font-size:.8rem; color:#999; margin-top:3px; }
.ord-amount { font-size:1.15rem; font-weight:800; color:var(--coffee); white-space:nowrap; }

/* Status badges */
.ord-badge {
    display:inline-flex; align-items:center; gap:5px;
    font-size:.75rem; font-weight:700;
    padding:5px 12px; border-radius:20px;
    white-space:nowrap;
}
.st-pending   { background:#fff8e6; color:#f39c12; }
.st-confirmed { background:#e8f4fd; color:#2980b9; }
.st-preparing { background:#f0ebff; color:#8e44ad; }
.st-shipping  { background:#e8f8f5; color:#1abc9c; }
.st-completed { background:#eafaf1; color:#27ae60; }
.st-cancelled { background:#fdf0ee; color:#c0392b; }

.ord-detail-btn {
    font-size:.8rem; color:var(--coffee);
    text-decoration:none; font-weight:700;
    padding:6px 12px; border-radius:8px;
    border:1.5px solid var(--gold);
    white-space:nowrap;
    transition:background .2s;
}
.ord-detail-btn:hover { background:#fdf3e7; color:var(--coffee); }

/* Responsive */
@media(max-width:767px) {
    .pf-card { padding:28px 20px; }
    .pf-stats { grid-template-columns:repeat(2,1fr); }
    .ord-item { flex-wrap:wrap; }
}
</style>

<div class="pf-page">
    <div class="container">
        <div class="row g-4 align-items-start">

            <!-- ══ SIDEBAR ══ -->
            <div class="col-lg-3">
                <div class="sidebar-card">
                    <div class="text-center mb-4">
                        <div class="avatar-circle"><i class="bi bi-person-fill"></i></div>
                        <div class="pf-name"><?= htmlspecialchars($customer['full_name']) ?></div>
                        <div class="pf-email"><?= htmlspecialchars($customer['email']) ?></div>
                        <div class="points-badge">
                            <i class="bi bi-star-fill"></i>
                            <span><?= intval($customer['points'] ?? 0) ?> điểm tích lũy</span>
                        </div>
                    </div>

                    <nav class="profile-nav" id="sideNav">
                        <a data-tab="tab-info" class="nav-link active">
                            <i class="bi bi-person-badge-fill"></i><span>Thông tin cá nhân</span>
                        </a>
                        <a data-tab="tab-password" class="nav-link">
                            <i class="bi bi-shield-lock"></i><span>Bảo mật &amp; Mật khẩu</span>
                        </a>
                        <a data-tab="tab-orders" class="nav-link">
                            <i class="bi bi-receipt"></i><span>Lịch sử đơn hàng</span>
                        </a>
                        <a href="<?= BASE_URL ?>/cart.php" class="nav-link">
                            <i class="bi bi-cart3"></i><span>Giỏ hàng của tôi</span>
                        </a>
                        <hr class="my-3 opacity-10">
                        <a href="<?= BASE_URL ?>/logout.php" class="nav-link text-danger mt-3">
                            <i class="bi bi-box-arrow-right"></i><span>Đăng xuất</span>
                        </a>
                    </nav>
                </div>
            </div>

            <!-- ══ CONTENT ══ -->
            <div class="col-lg-9">

                <!-- Stat cards -->
                <div class="pf-stats">
                    <div class="pf-stat c-total" onclick="showTab('tab-orders')">
                        <i class="bi bi-box-seam pf-stat-icon"></i>
                        <span class="pf-stat-val"><?= intval($orderStats['total_orders']) ?></span>
                        <span class="pf-stat-lbl">Tổng đơn</span>
                    </div>
                    <div class="pf-stat c-done" onclick="showTab('tab-orders')">
                        <i class="bi bi-check2-circle pf-stat-icon"></i>
                        <span class="pf-stat-val"><?= intval($orderStats['completed_orders']) ?></span>
                        <span class="pf-stat-lbl">Hoàn thành</span>
                    </div>
                    <div class="pf-stat c-pending" onclick="showTab('tab-orders')">
                        <i class="bi bi-clock-history pf-stat-icon"></i>
                        <span class="pf-stat-val"><?= intval($orderStats['pending_orders']) ?></span>
                        <span class="pf-stat-lbl">Đang đợi</span>
                    </div>
                    <div class="pf-stat c-spent">
                        <i class="bi bi-wallet2 pf-stat-icon"></i>
                        <span class="pf-stat-val"><?= number_format($orderStats['total_spent'], 0, ',', '.') ?>đ</span>
                        <span class="pf-stat-lbl">Đã chi</span>
                    </div>
                </div>

                <!-- ────────────────────────────
                     TAB 1: Thông tin cá nhân
                ──────────────────────────── -->
                <div id="tab-info" class="tab-section pf-card">
                    <div class="pf-card-title">Hồ sơ cá nhân</div>
                    <div class="pf-card-accent"></div>

                    <div id="toast-info" class="pf-toast"></div>

                    <form id="formProfile">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="row g-0">
                            <div class="col-md-6 pe-md-3">
                                <div class="pf-field">
                                    <label>Họ và tên</label>
                                    <div class="pf-input-wrap">
                                        <i class="bi bi-person pf-icon"></i>
                                        <input type="text" name="full_name" class="pf-input"
                                               value="<?= htmlspecialchars($customer['full_name']) ?>"
                                               placeholder="Nhập họ và tên">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 ps-md-3">
                                <div class="pf-field">
                                    <label>Địa chỉ Email</label>
                                    <div class="pf-input-wrap">
                                        <i class="bi bi-envelope pf-icon"></i>
                                        <input type="email" class="pf-input" readonly
                                               value="<?= htmlspecialchars($customer['email']) ?>">
                                    </div>
                                    <p class="pf-hint"><i class="bi bi-info-circle me-1"></i>Email đăng nhập không thể thay đổi</p>
                                </div>
                            </div>
                            <div class="col-md-6 pe-md-3">
                                <div class="pf-field">
                                    <label>Số điện thoại</label>
                                    <div class="pf-input-wrap">
                                        <i class="bi bi-telephone pf-icon"></i>
                                        <input type="text" name="phone" class="pf-input"
                                               value="<?= htmlspecialchars($customer['phone'] ?? '') ?>"
                                               placeholder="Nhập số điện thoại">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 ps-md-3">
                                <div class="pf-field">
                                    <label>Ngày tham gia</label>
                                    <div class="pf-input-wrap">
                                        <i class="bi bi-calendar3 pf-icon"></i>
                                        <input type="text" class="pf-input" readonly value="<?= $joinDate ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="pf-field">
                                    <label>Địa chỉ</label>
                                    <div class="pf-input-wrap ta-wrap">
                                        <i class="bi bi-geo-alt pf-icon"></i>
                                        <textarea name="address" class="pf-input"
                                                  placeholder="Nhập địa chỉ"><?= htmlspecialchars($customer['address'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="pf-btn" id="btnSaveProfile">
                            <span class="btn-txt">Lưu thay đổi</span>
                            <i class="bi bi-arrow-right btn-ico"></i>
                            <span class="btn-spin d-none"><span class="spinner-border spinner-border-sm"></span></span>
                        </button>
                    </form>
                </div>

                <!-- ────────────────────────────
                     TAB 2: Bảo mật & Mật khẩu
                ──────────────────────────── -->
                <div id="tab-password" class="tab-section pf-card" style="display:none;">
                    <div class="pf-card-title">Bảo mật &amp; Mật khẩu</div>
                    <div class="pf-card-accent"></div>
                    <p style="color:#999;font-size:.88rem;margin-bottom:28px;">Sử dụng mật khẩu mạnh ít nhất 6 ký tự để bảo vệ tài khoản.</p>

                    <div id="toast-pw" class="pf-toast"></div>

                    <form id="formPassword" style="max-width:480px;">
                        <input type="hidden" name="action" value="change_password">
                        <div class="pf-field">
                            <label>Mật khẩu hiện tại</label>
                            <div class="pf-input-wrap">
                                <i class="bi bi-lock pf-icon"></i>
                                <input type="password" name="current_password" class="pf-input" placeholder="••••••••">
                            </div>
                        </div>
                        <div class="pf-field">
                            <label>Mật khẩu mới</label>
                            <div class="pf-input-wrap">
                                <i class="bi bi-lock-fill pf-icon"></i>
                                <input type="password" name="new_password" class="pf-input" placeholder="••••••••">
                            </div>
                        </div>
                        <div class="pf-field">
                            <label>Xác nhận mật khẩu mới</label>
                            <div class="pf-input-wrap">
                                <i class="bi bi-shield-lock pf-icon"></i>
                                <input type="password" name="confirm_password" class="pf-input" placeholder="••••••••">
                            </div>
                        </div>
                        <button type="submit" class="pf-btn">
                            <span class="btn-txt">Cập nhật mật khẩu</span>
                            <i class="bi bi-arrow-right btn-ico"></i>
                            <span class="btn-spin d-none"><span class="spinner-border spinner-border-sm"></span></span>
                        </button>
                    </form>
                </div>

                <!-- ────────────────────────────
                     TAB 3: Lịch sử đơn hàng
                ──────────────────────────── -->
                <div id="tab-orders" class="tab-section pf-card" style="display:none;">
                    <div class="pf-card-title">Lịch sử đơn hàng</div>
                    <div class="pf-card-accent"></div>

                    <?php if (empty($orders)): ?>
                        <div class="ord-empty">
                            <i class="bi bi-bag-x"></i>
                            <p>Bạn chưa có đơn hàng nào.</p>
                            <a href="<?= BASE_URL ?>/menu.php" class="pf-btn" style="margin-top:12px;display:inline-flex;">
                                <i class="bi bi-cup-hot-fill"></i> Đặt ngay
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="ord-list">
                            <?php foreach ($orders as $ord):
                                $st = $statusMap[$ord['status']] ?? ['text' => ucfirst($ord['status']), 'cls' => 'st-pending'];
                            ?>
                            <div class="ord-item">
                                <div>
                                    <div class="ord-code">#<?= htmlspecialchars($ord['order_code'] ?? $ord['id']) ?></div>
                                    <div class="ord-date"><i class="bi bi-clock me-1"></i><?= date('d/m/Y H:i', strtotime($ord['created_at'])) ?></div>
                                    <div class="ord-items-count"><i class="bi bi-basket me-1"></i><?= intval($ord['item_count']) ?> sản phẩm</div>
                                </div>
                                <div class="text-center">
                                    <span class="ord-badge <?= $st['cls'] ?>"><?= $st['text'] ?></span>
                                </div>
                                <div class="text-end">
                                    <div class="ord-amount"><?= number_format($ord['total_amount'], 0, ',', '.') ?>đ</div>
                                    <?php if (!empty($ord['id'])): ?>
                                    <a href="<?= BASE_URL ?>/user/order-detail.php?id=<?= $ord['id'] ?>"
                                       class="ord-detail-btn mt-2 d-inline-block">Chi tiết</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div><!-- /col-lg-9 -->
        </div><!-- /row -->
    </div><!-- /container -->
</div><!-- /pf-page -->

<script>
(function () {
    'use strict';

    /* ── Tab switching ── */
    const nav   = document.getElementById('sideNav');
    const links = nav.querySelectorAll('[data-tab]');
    const tabs  = document.querySelectorAll('.tab-section');

    window.showTab = function(id) {
        tabs.forEach(t  => { t.style.display = (t.id === id) ? 'block' : 'none'; });
        links.forEach(l => { l.classList.toggle('active', l.getAttribute('data-tab') === id); });
        history.replaceState(null, '', '#' + id.replace('tab-', ''));
    }

    links.forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            showTab(link.getAttribute('data-tab'));
        });
    });

    // Hash on load
    const h = location.hash.replace('#','');
    const validTabs = {'info':'tab-info','password':'tab-password','orders':'tab-orders'};
    showTab(validTabs[h] || 'tab-info');

    /* ── Toast helper ── */
    function showToast(el, msg, isOk) {
        el.textContent = '';
        const ico = document.createElement('i');
        ico.className = 'bi bi-' + (isOk ? 'check-circle-fill' : 'exclamation-circle-fill') + ' me-2';
        el.prepend(ico);
        el.append(msg);
        el.className = 'pf-toast ' + (isOk ? 'ok' : 'err');
        el.style.display = 'block';
        clearTimeout(el._t);
        if (isOk) el._t = setTimeout(() => { el.style.display='none'; }, 4000);
    }

    /* ── AJAX form submit ── */
    function ajaxForm(formEl, toastEl) {
        formEl.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn  = formEl.querySelector('.pf-btn');
            const txt  = btn.querySelector('.btn-txt');
            const ico  = btn.querySelector('.btn-ico');
            const spin = btn.querySelector('.btn-spin');

            btn.disabled = true;
            txt.textContent = 'Đang xử lý...';
            ico.classList.add('d-none');
            spin.classList.remove('d-none');

            try {
                const fd = new FormData(formEl);
                const res = await fetch(location.href, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd
                });
                const json = await res.json();
                showToast(toastEl, json.msg, json.ok);
                if (json.ok && formEl.id === 'formPassword') formEl.reset();
            } catch(err) {
                showToast(toastEl, 'Có lỗi xảy ra. Vui lòng thử lại.', false);
            } finally {
                btn.disabled = false;
                txt.textContent = btn.id === 'btnSaveProfile' ? 'Lưu thay đổi' : 'Cập nhật mật khẩu';
                ico.classList.remove('d-none');
                spin.classList.add('d-none');
            }
        });
    }

    ajaxForm(document.getElementById('formProfile'),  document.getElementById('toast-info'));
    ajaxForm(document.getElementById('formPassword'), document.getElementById('toast-pw'));
    // Check hash on load to switch to correct tab
    const hash = window.location.hash.replace('#', '');
    if (hash && document.getElementById('tab-' + hash)) {
        showTab('tab-' + hash);
    }

})();
</script>
<?php require_once dirname(__DIR__) . '/includes/customer_footer.php'; ?>
