<?php



require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';


requireAdmin();


$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01'); 
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d'); 
$reportType = isset($_GET['report_type']) ? $_GET['report_type'] : 'daily'; 


if (strtotime($dateFrom) > strtotime($dateTo)) {
    $temp = $dateFrom;
    $dateFrom = $dateTo;
    $dateTo = $temp;
}


try {
    
    $totalRevenue = fetchOne($pdo, "
        SELECT COALESCE(SUM(total_amount), 0) as total
        FROM invoices
        WHERE DATE(created_at) BETWEEN ? AND ?
    ", [$dateFrom, $dateTo])['total'] ?? 0;

    
    $totalInvoices = fetchOne($pdo, "
        SELECT COUNT(*) as total
        FROM invoices
        WHERE DATE(created_at) BETWEEN ? AND ?
    ", [$dateFrom, $dateTo])['total'] ?? 0;

    
    $totalProductsSold = fetchOne($pdo, "
        SELECT COALESCE(SUM(id.quantity), 0) as total
        FROM invoice_details id
        JOIN invoices i ON id.invoice_id = i.id
        WHERE DATE(i.created_at) BETWEEN ? AND ?
    ", [$dateFrom, $dateTo])['total'] ?? 0;

    
    $avgInvoiceValue = $totalInvoices > 0 ? $totalRevenue / $totalInvoices : 0;

    
    if ($reportType === 'daily') {
        $chartData = fetchAll($pdo, "
            SELECT DATE(created_at) as period,
                   COUNT(*) as invoice_count,
                   COALESCE(SUM(total_amount), 0) as revenue
            FROM invoices
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY period ASC
        ", [$dateFrom, $dateTo]);
    } else {
        $chartData = fetchAll($pdo, "
            SELECT DATE_FORMAT(created_at, '%Y-%m') as period,
                   COUNT(*) as invoice_count,
                   COALESCE(SUM(total_amount), 0) as revenue
            FROM invoices
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY period ASC
        ", [$dateFrom, $dateTo]);
    }

    
    $topMedicines = fetchAll($pdo, "
        SELECT m.code, m.name, m.unit,
               SUM(id.quantity) as total_sold,
               SUM(id.subtotal) as revenue
        FROM invoice_details id
        JOIN medicines m ON id.medicine_id = m.id
        JOIN invoices i ON id.invoice_id = i.id
        WHERE DATE(i.created_at) BETWEEN ? AND ?
        GROUP BY m.id, m.code, m.name, m.unit
        ORDER BY total_sold DESC
        LIMIT 10
    ", [$dateFrom, $dateTo]);

    
    $revenueByEmployee = fetchAll($pdo, "
        SELECT u.full_name,
               COUNT(i.id) as invoice_count,
               COALESCE(SUM(i.total_amount), 0) as revenue
        FROM invoices i
        JOIN users u ON i.user_id = u.id
        WHERE DATE(i.created_at) BETWEEN ? AND ?
        GROUP BY u.id, u.full_name
        ORDER BY revenue DESC
    ", [$dateFrom, $dateTo]);

    
    $revenueByCategory = fetchAll($pdo, "
        SELECT c.name as category_name,
               SUM(id.quantity) as total_sold,
               COALESCE(SUM(id.subtotal), 0) as revenue
        FROM invoice_details id
        JOIN medicines m ON id.medicine_id = m.id
        LEFT JOIN categories c ON m.category_id = c.id
        JOIN invoices i ON id.invoice_id = i.id
        WHERE DATE(i.created_at) BETWEEN ? AND ?
        GROUP BY c.id, c.name
        ORDER BY revenue DESC
    ", [$dateFrom, $dateTo]);

} catch (PDOException $e) {
    error_log("Sales Report Error: " . $e->getMessage());
    setMessage('danger', 'Có lỗi xảy ra khi tải báo cáo doanh thu.');
    $totalRevenue = $totalInvoices = $totalProductsSold = $avgInvoiceValue = 0;
    $chartData = [];
    $topMedicines = [];
    $revenueByEmployee = [];
    $revenueByCategory = [];
}


$chartLabels = [];
$chartRevenue = [];
$chartInvoices = [];

foreach ($chartData as $data) {
    if ($reportType === 'daily') {
        $chartLabels[] = date('d/m', strtotime($data['period']));
    } else {
        $chartLabels[] = date('m/Y', strtotime($data['period'] . '-01'));
    }
    $chartRevenue[] = $data['revenue'];
    $chartInvoices[] = $data['invoice_count'];
}

$pageTitle = 'Báo cáo Doanh thu';
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- Additional CSS -->
<style>
@media print {
    .no-print {
        display: none !important;
    }
    .card {
        page-break-inside: avoid;
        box-shadow: none !important;
        border: 1px solid #dee2e6 !important;
    }
    .navbar, .btn {
        display: none !important;
    }
    canvas {
        max-height: 300px !important;
    }
}

.chart-container {
    position: relative;
    height: 400px;
    margin-bottom: 20px;
}
</style>

<!-- Hiển thị thông báo -->
<?php
$message = getMessage();
if ($message):
?>
    <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show no-print" role="alert">
        <i class="bi bi-info-circle-fill me-2"></i>
        <?php echo htmlspecialchars($message['text']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Page Header -->
<div class="row mb-4 no-print">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-0">
                    <i class="bi bi-graph-up-arrow"></i>
                    Báo cáo Doanh thu
                </h2>
                <p class="text-muted mb-0">Thống kê doanh thu và hiệu quả kinh doanh</p>
            </div>
            <div>
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="bi bi-printer"></i> In báo cáo
                </button>
                <a href="<?php echo BASE_URL; ?>/admin/reports/export.php?type=sales&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>" class="btn btn-success">
                    <i class="bi bi-file-earmark-excel"></i> Xuất Excel
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Print Header -->
<div class="d-none d-print-block text-center mb-4">
    <h3>BÁO CÁO DOANH THU</h3>
    <p class="mb-0">Từ ngày <?php echo date('d/m/Y', strtotime($dateFrom)); ?> đến <?php echo date('d/m/Y', strtotime($dateTo)); ?></p>
    <p class="mb-0">Ngày in: <?php echo date('d/m/Y H:i'); ?></p>
    <hr>
</div>

<!-- Filter Form -->
<div class="row mb-4 no-print">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Từ ngày</label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo $dateFrom; ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Đến ngày</label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo $dateTo; ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Loại báo cáo</label>
                        <select name="report_type" class="form-select">
                            <option value="daily" <?php echo $reportType === 'daily' ? 'selected' : ''; ?>>Theo ngày</option>
                            <option value="monthly" <?php echo $reportType === 'monthly' ? 'selected' : ''; ?>>Theo tháng</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Xem báo cáo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <!-- Tổng doanh thu -->
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 border-start border-success border-4 shadow-sm h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-uppercase text-success fw-bold small mb-1">Tổng doanh thu</div>
                        <div class="h4 mb-0 fw-bold text-success"><?php echo formatCurrency($totalRevenue); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-cash-stack fs-1 text-success opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tổng số hóa đơn -->
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 border-start border-primary border-4 shadow-sm h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-uppercase text-primary fw-bold small mb-1">Số hóa đơn</div>
                        <div class="h4 mb-0 fw-bold"><?php echo number_format($totalInvoices); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-receipt fs-1 text-primary opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sản phẩm đã bán -->
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 border-start border-info border-4 shadow-sm h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-uppercase text-info fw-bold small mb-1">Sản phẩm đã bán</div>
                        <div class="h4 mb-0 fw-bold"><?php echo number_format($totalProductsSold); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-box-seam fs-1 text-info opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Trung bình hóa đơn -->
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 border-start border-warning border-4 shadow-sm h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-uppercase text-warning fw-bold small mb-1">TB/Hóa đơn</div>
                        <div class="h5 mb-0 fw-bold"><?php echo formatCurrency($avgInvoiceValue); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-calculator fs-1 text-warning opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row mb-4">
    <!-- Biểu đồ doanh thu -->
    <div class="col-lg-8 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-bar-chart-line me-2"></i>
                <strong>Biểu đồ doanh thu <?php echo $reportType === 'daily' ? 'theo ngày' : 'theo tháng'; ?></strong>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Biểu đồ theo loại thuốc -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <i class="bi bi-pie-chart me-2"></i>
                <strong>Doanh thu theo loại thuốc</strong>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top thuốc bán chạy -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <i class="bi bi-trophy me-2"></i>
                <strong>Top 10 thuốc bán chạy</strong>
            </div>
            <div class="card-body p-0">
                <?php if (empty($topMedicines)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-inbox fs-1"></i>
                        <p class="mt-2">Chưa có dữ liệu</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Top</th>
                                    <th>Mã thuốc</th>
                                    <th>Tên thuốc</th>
                                    <th>Đơn vị</th>
                                    <th>Số lượng bán</th>
                                    <th>Doanh thu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $rank = 1;
                                foreach ($topMedicines as $medicine):
                                ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-success rounded-circle" style="width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center;">
                                                <?php echo $rank++; ?>
                                            </span>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($medicine['code']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($medicine['name']); ?></td>
                                        <td><?php echo htmlspecialchars($medicine['unit']); ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo number_format($medicine['total_sold']); ?></span>
                                        </td>
                                        <td class="text-success fw-bold">
                                            <?php echo formatCurrency($medicine['revenue']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Doanh thu theo nhân viên và loại thuốc -->
<div class="row mb-4">
    <!-- Doanh thu theo nhân viên -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-people me-2"></i>
                <strong>Doanh thu theo nhân viên</strong>
            </div>
            <div class="card-body p-0">
                <?php if (empty($revenueByEmployee)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-inbox fs-1"></i>
                        <p class="mt-2">Chưa có dữ liệu</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nhân viên</th>
                                    <th>Số HĐ</th>
                                    <th>Doanh thu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($revenueByEmployee as $employee): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($employee['full_name']); ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo number_format($employee['invoice_count']); ?></span>
                                        </td>
                                        <td class="text-success fw-bold">
                                            <?php echo formatCurrency($employee['revenue']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Doanh thu theo loại thuốc -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <i class="bi bi-tags me-2"></i>
                <strong>Doanh thu theo loại thuốc</strong>
            </div>
            <div class="card-body p-0">
                <?php if (empty($revenueByCategory)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-inbox fs-1"></i>
                        <p class="mt-2">Chưa có dữ liệu</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Loại thuốc</th>
                                    <th>SL bán</th>
                                    <th>Doanh thu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($revenueByCategory as $category): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category['category_name'] ?? 'Không xác định'); ?></td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo number_format($category['total_sold']); ?></span>
                                        </td>
                                        <td class="text-success fw-bold">
                                            <?php echo formatCurrency($category['revenue']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Scripts -->
<script>
// Dữ liệu biểu đồ từ PHP
const chartLabels = <?php echo json_encode($chartLabels); ?>;
const chartRevenue = <?php echo json_encode($chartRevenue); ?>;
const chartInvoices = <?php echo json_encode($chartInvoices); ?>;

// Biểu đồ doanh thu
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(revenueCtx, {
    type: 'bar',
    data: {
        labels: chartLabels,
        datasets: [
            {
                label: 'Doanh thu (VND)',
                data: chartRevenue,
                backgroundColor: 'rgba(25, 135, 84, 0.8)',
                borderColor: 'rgba(25, 135, 84, 1)',
                borderWidth: 1,
                yAxisID: 'y'
            },
            {
                label: 'Số hóa đơn',
                data: chartInvoices,
                type: 'line',
                borderColor: 'rgba(13, 110, 253, 1)',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                borderWidth: 2,
                fill: true,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        plugins: {
            legend: {
                display: true,
                position: 'top',
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        if (context.datasetIndex === 0) {
                            label += new Intl.NumberFormat('vi-VN').format(context.parsed.y) + ' ₫';
                        } else {
                            label += context.parsed.y;
                        }
                        return label;
                    }
                }
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                ticks: {
                    callback: function(value) {
                        return new Intl.NumberFormat('vi-VN').format(value) + ' ₫';
                    }
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                grid: {
                    drawOnChartArea: false,
                }
            }
        }
    }
});

// Biểu đồ theo loại thuốc (Pie chart)
<?php if (!empty($revenueByCategory)): ?>
const categoryLabels = <?php echo json_encode(array_column($revenueByCategory, 'category_name')); ?>;
const categoryData = <?php echo json_encode(array_column($revenueByCategory, 'revenue')); ?>;

const categoryCtx = document.getElementById('categoryChart').getContext('2d');
const categoryChart = new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
        labels: categoryLabels,
        datasets: [{
            data: categoryData,
            backgroundColor: [
                'rgba(255, 99, 132, 0.8)',
                'rgba(54, 162, 235, 0.8)',
                'rgba(255, 206, 86, 0.8)',
                'rgba(75, 192, 192, 0.8)',
                'rgba(153, 102, 255, 0.8)',
                'rgba(255, 159, 64, 0.8)',
                'rgba(199, 199, 199, 0.8)',
                'rgba(83, 102, 255, 0.8)',
                'rgba(255, 99, 255, 0.8)',
                'rgba(99, 255, 132, 0.8)'
            ],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'bottom'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        if (label) {
                            label += ': ';
                        }
                        label += new Intl.NumberFormat('vi-VN').format(context.parsed) + ' ₫';

                        // Tính phần trăm
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((context.parsed / total) * 100).toFixed(1);
                        label += ' (' + percentage + '%)';

                        return label;
                    }
                }
            }
        }
    }
});
<?php endif; ?>
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
