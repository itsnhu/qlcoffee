<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';

requireEmployee();

// Fetch products
$sql = "SELECT id, name, code, price, sizes, status_label, image FROM products ORDER BY name ASC";
$products = fetchAll($pdo, $sql);

$pageTitle = 'Danh sách sản phẩm';
$additionalCSS = '
<style>
    .product-list-card { 
        background: #fff; border-radius: 10px; padding: 20px; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
        border: 1px solid #f0f0f0;
        width: 100%;
    }
    .table-prod th { font-size: 0.85rem; color: #6c757d; font-weight: 600; border-bottom: 2px solid #f0f0f0; }
    .table-prod td { vertical-align: middle; padding: 12px; }
    .table-prod tr:hover { background: #f8f9fa; }
    
    .status-pill { padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
    .status-selling { background: #e6f8f3; color: #20c997; }
    .status-empty { background: #fff3cd; color: #ffc107; }
    .status-stopped { background: #fceceb; color: #dc3545; }

    /* Product Avatar Styles */
    .product-avatar-container {
        width: 48px;
        height: 48px;
        background: #eef2fd;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border: 1px solid #e9efff;
    }
    .product-avatar {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .prod-name {
        color: #1a1a1a;
        font-weight: 700;
        font-size: 0.95rem;
    }
    .prod-code {
        color: #8b91a7;
        font-size: 0.75rem;
        font-weight: 500;
        text-transform: uppercase;
    }
    .table-prod thead th {
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        padding-bottom: 15px;
    }
</style>
';

require_once dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<div class="main-content">
    <div class="product-list-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0" style="color:#2a3166;">Danh sách sản phẩm</h5>
            <span class="badge bg-light text-dark border rounded-pill px-3 py-2">
                Tổng cộng: <?= count($products) ?> sản phẩm
            </span>
        </div>
        
        <div class="table-responsive">
            <table class="table table-prod table-borderless">
                <thead>
                    <tr>
                        <th style="width: 80px;">HÌNH ẢNH</th>
                        <th>TÊN MÓN</th>
                        <th>SIZE</th>
                        <th>GIÁ (M)</th>
                        <th>TRẠNG THÁI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): 
                        $sizesData = !empty($p['sizes']) ? json_decode($p['sizes'], true) : [];
                        if (!is_array($sizesData)) $sizesData = [];
                        $sizesList = implode(', ', array_keys($sizesData));
                        if(empty($sizesList)) $sizesList = 'Mặc định';
                        
                        $priceM = $sizesData['M'] ?? $p['price'];
                        
                        $statusClass = 'status-selling';
                        if ($p['status_label'] == 'Đã hết') $statusClass = 'status-empty';
                        if ($p['status_label'] == 'Ngừng bán') $statusClass = 'status-stopped';
                    ?>
                    <tr>
                        <td>
                            <div class="product-avatar-container">
                                <?php if (!empty($p['image'])): ?>
                                    <img src="<?= htmlspecialchars($p['image']) ?>" class="product-avatar">
                                <?php else: ?>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#8da2fb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8h1a4 4 0 0 1 0 8h-1"></path><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path><line x1="6" y1="1" x2="6" y2="4"></line><line x1="10" y1="1" x2="10" y2="4"></line><line x1="14" y1="1" x2="14" y2="4"></line></svg>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="prod-name"><?= htmlspecialchars($p['name']) ?></div>
                            <div class="prod-code"><?= htmlspecialchars($p['code'] ?? 'CODE') ?></div>
                        </td>
                        <td class="small text-muted"><?= $sizesList ?></td>
                        <td class="fw-bold"><?= number_format($priceM, 0, ',', '.') ?>đ</td>
                        <td><span class="status-pill <?= $statusClass ?>"><?= htmlspecialchars($p['status_label']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
