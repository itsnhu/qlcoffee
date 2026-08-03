<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';

requireAdmin();

try {
    // Fetch all tables with current active booking info if any
    $sql = "SELECT t.*, 
            b.customer_name as booking_customer, 
            b.booking_time as booking_time,
            b.status as booking_status
            FROM tables t
            LEFT JOIN bookings b ON t.id = b.table_id 
                AND b.status IN ('pending', 'confirmed', 'using')
                AND b.booking_date = CURRENT_DATE
            GROUP BY t.id
            ORDER BY CAST(SUBSTRING(t.name, 5) AS UNSIGNED) ASC, t.name ASC";
    $tables = fetchAll($pdo, $sql);
} catch (PDOException $e) {
    error_log("Table List Error: " . $e->getMessage());
    $tables = [];
    setMessage('danger', 'Có lỗi khi tải danh sách bàn.');
}

$pageTitle = 'Quản lý bàn';
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

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="fw-bold mb-0" style="color: #1e293b;">Sơ đồ bàn</h3>
            <a href="create.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
                <i class="bi bi-plus-lg me-2"></i> Thêm bàn mới
            </a>
        </div>
    </div>
</div>

<!-- Status Overview Section -->
<div class="row g-4 mb-4">
    <?php
    $total_count = count($tables);
    $free_count = 0;
    $busy_count = 0;
    $booked_count = 0;
    
    foreach ($tables as $t) {
        if ($t['status'] === 'free') $free_count++;
        elseif ($t['status'] === 'busy') $busy_count++;
        elseif ($t['status'] === 'booked') $booked_count++;
        else $busy_count++; // Mặc định là busy nếu không phải free/booked
    }
    ?>
    <div class="col-md-3">
        <div class="dash-card filter-card active" onclick="filterTables('all', this)">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="dash-card-icon bg-light-dark">
                    <i class="bi bi-grid-3x3-gap text-dark"></i>
                </div>
                <div class="small text-muted fw-bold">100%</div>
            </div>
            <div class="dash-card-title">Tổng số bàn</div>
            <div class="dash-card-value"><?= $total_count ?></div>
            <div class="small text-muted">Bàn hiện có</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="dash-card filter-card border-bottom border-danger border-4" onclick="filterTables('busy', this)">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="dash-card-icon bg-light-danger">
                    <i class="bi bi-person-fill text-danger"></i>
                </div>
                <div class="small text-danger fw-bold"><?= $total_count ? round(($busy_count/$total_count)*100) : 0 ?>%</div>
            </div>
            <div class="dash-card-title text-danger">Đang sử dụng</div>
            <div class="dash-card-value"><?= $busy_count ?></div>
            <div class="small text-muted">Bàn có khách</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="dash-card filter-card border-bottom border-info border-4" onclick="filterTables('booked', this)">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="dash-card-icon bg-light-info">
                    <i class="bi bi-calendar-check text-info"></i>
                </div>
                <div class="small text-info fw-bold"><?= $total_count ? round(($booked_count/$total_count)*100) : 0 ?>%</div>
            </div>
            <div class="dash-card-title text-info">Đã đặt</div>
            <div class="dash-card-value"><?= $booked_count ?></div>
            <div class="small text-muted">Lịch đặt hôm nay</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="dash-card filter-card border-bottom border-success border-4" onclick="filterTables('free', this)">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="dash-card-icon bg-light-success">
                    <i class="bi bi-check-circle text-success"></i>
                </div>
                <div class="small text-success fw-bold"><?= $total_count ? round(($free_count/$total_count)*100) : 0 ?>%</div>
            </div>
            <div class="dash-card-title text-success">Trống</div>
            <div class="dash-card-value"><?= $free_count ?></div>
            <div class="small text-muted">Có thể nhận khách</div>
        </div>
    </div>
</div>

<!-- Table Grid Section -->
<div class="row g-4">
    <?php if (empty($tables)): ?>
        <div class="col-12">
            <div class="dash-card text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="mt-2 text-muted">Chưa có bàn nào trong sơ đồ.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($tables as $table): 
            $card_class = 'bg-white';
            $status_text = 'Trống';
            $status_icon = 'bi-check-circle';
            $btn_class = 'btn-light-success';
            
            if ($table['status'] === 'busy' || ($table['status'] !== 'free' && $table['status'] !== 'booked')) {
                $card_class = 'bg-light-danger-subtle border-danger border-opacity-25';
                $status_text = 'Đang sử dụng';
                $status_icon = 'bi-person-fill';
                $btn_class = 'btn-light-danger';
            } elseif ($table['status'] === 'booked') {
                $card_class = 'bg-light-info-subtle border-info border-opacity-25';
                $status_text = 'Đã đặt';
                $status_icon = 'bi-calendar-check';
                $btn_class = 'btn-light-info';
            } else {
                $card_class = 'bg-light-success-subtle border-success border-opacity-25';
            }
        ?>
        <div class="col-xl-3 col-lg-4 col-md-6 table-item" data-status="<?= $table['status'] === 'free' ? 'free' : ($table['status'] === 'booked' ? 'booked' : 'busy') ?>">
            <div class="dash-card <?= $card_class ?> h-100 position-relative transition-hover">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="fw-bold mb-0"><?= htmlspecialchars($table['name']) ?></h5>
                        <div class="small text-muted mt-1">
                            <i class="bi bi-people me-1"></i> <?= $table['capacity'] ?> chỗ ngồi
                        </div>
                    </div>
                    <span class="badge <?= $btn_class ?> border px-2 py-1 small">
                        <i class="bi <?= $status_icon ?> me-1"></i> <?= $status_text ?>
                    </span>
                </div>
                
                <div class="mb-4 mt-auto">
                    <?php if ($table['status'] === 'busy'): ?>
                        <div class="p-2 bg-white bg-opacity-50 rounded-3 small border border-danger border-opacity-10 mb-2">
                            <div class="text-danger fw-bold mb-1">Đang sử dụng</div>
                            <?php if ($table['booking_customer']): ?>
                                <div class="text-muted x-small"><?= htmlspecialchars($table['booking_customer']) ?></div>
                            <?php else: ?>
                                <div class="text-muted italic x-small">Khách vãng lai</div>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($table['status'] === 'booked'): ?>
                        <div class="p-2 bg-white bg-opacity-50 rounded-3 small border border-info border-opacity-10 mb-2">
                            <div class="text-info fw-bold mb-1">
                                <i class="bi bi-clock me-1"></i><?= $table['booking_time'] ? date('H:i', strtotime($table['booking_time'])) : '--:--' ?>
                            </div>
                            <div class="text-muted x-small text-truncate" title="<?= htmlspecialchars($table['booking_customer'] ?? 'Khách') ?>">
                                <i class="bi bi-person me-1"></i><?= htmlspecialchars($table['booking_customer'] ?? 'Khách lẻ') ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="p-4"></div> <!-- Spacer for empty table -->
                    <?php endif; ?>
                </div>

                <div class="d-flex gap-1 justify-content-end mt-3 pt-3 border-top border-dark border-opacity-10">
                    <a href="<?= BASE_URL ?>/admin/tables/edit.php?id=<?= $table['id'] ?>" class="btn btn-sm btn-icon btn-white rounded-circle shadow-sm" title="Sửa">
                        <i class="bi bi-pencil-square text-primary"></i>
                    </a>
                    <a href="<?= BASE_URL ?>/admin/tables/delete.php?id=<?= $table['id'] ?>" class="btn btn-sm btn-icon btn-white rounded-circle shadow-sm" title="Xóa" onclick="return confirm('Xóa bàn này?');">
                        <i class="bi bi-trash text-danger"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<style>
    .bg-light-success-subtle { background-color: #f0fdf4 !important; }
    .bg-light-danger-subtle { background-color: #fef2f2 !important; }
    .bg-light-info-subtle { background-color: #f0f9ff !important; }
    
    .bg-light-success { background-color: rgba(34, 197, 94, 0.1) !important; color: #16a34a !important; }
    .bg-light-danger { background-color: rgba(239, 68, 68, 0.1) !important; color: #dc2626 !important; }
    .bg-light-info { background-color: rgba(59, 130, 246, 0.1) !important; color: #2563eb !important; }
    .bg-light-dark { background-color: rgba(30, 41, 59, 0.1) !important; color: #1e293b !important; }

    .btn-light-success { background: white; color: #16a34a; border-color: #bbf7d0 !important; }
    .btn-light-danger { background: white; color: #dc2626; border-color: #fecaca !important; }
    .btn-light-info { background: white; color: #2563eb; border-color: #bfdbfe !important; }

    .btn-white { background: white; border: 1px solid #e2e8f0; }
    .btn-white:hover { background: #f8fafc; }

    .transition-hover { transition: all 0.3s ease; }
    .transition-hover:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05) !important; }
    
    .italic { font-style: italic; }
    .x-small { font-size: 0.7rem; }

    .filter-card { cursor: pointer; transition: all 0.3s ease; opacity: 0.7; }
    .filter-card:hover { opacity: 1; transform: scale(1.02); }
    .filter-card.active { opacity: 1; box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.25); transform: translateY(-3px); }
</style>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
function filterTables(status, element) {
    // Update active filter UI
    document.querySelectorAll('.filter-card').forEach(card => card.classList.remove('active'));
    element.classList.add('active');

    // Filter items
    const tables = document.querySelectorAll('.table-item');
    tables.forEach(table => {
        if (status === 'all' || table.getAttribute('data-status') === status) {
            table.style.display = 'block';
            $(table).hide().fadeIn(300); // Animation
        } else {
            table.style.display = 'none';
        }
    });
}
</script>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
