<?php
$pageTitle = 'Thực đơn';
require_once 'includes/customer_header.php';

// Get filter parameters
$search = sanitize($_GET['search'] ?? '');
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$sort = sanitize($_GET['sort'] ?? 'newest');
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Build query
$where = ["p.quantity > 0"]; // Only show available items
$params = [];

if ($search) {
    $where[] = "(p.name LIKE ? OR p.code LIKE ? OR p.description LIKE ?)";
    $searchTerm = "%{$search}%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

if ($categoryId > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $categoryId;
}

$whereClause = implode(' AND ', $where);

// Sort options
$orderBy = match($sort) {
    'price_asc' => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'name' => 'p.name ASC',
    // 'bestseller' => 'total_sold DESC', // Requires joining order_details which might be heavy or check table existence
    default => 'p.created_at DESC'
};

// Get total count
$countSql = "SELECT COUNT(*) FROM products p WHERE {$whereClause}";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$totalProducts = $stmt->fetchColumn();
$totalPages = ceil($totalProducts / $perPage);

// Get products
$sql = "
    SELECT p.*, c.name as category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE {$whereClause}
    ORDER BY {$orderBy}
    LIMIT {$perPage} OFFSET {$offset}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories for sidebar
$categories = fetchAll($pdo, "SELECT * FROM categories WHERE name LIKE '%Cà phê%' OR name LIKE '%Coffee%' ORDER BY name ASC");

// Get current category info
$currentCategory = null;
if ($categoryId > 0) {
    $currentCategory = fetchOne($pdo, "SELECT * FROM categories WHERE id = ?", [$categoryId]);
}

// Build query string for pagination
$queryParams = array_filter([
    'search' => $search,
    'category' => $categoryId ?: null,
    'sort' => $sort !== 'newest' ? $sort : null
]);
$queryString = http_build_query($queryParams);
?>

<div class="container py-5">
    <div class="row g-4">
        <!-- Sidebar Filters -->
        <div class="col-lg-3">
            <div class="sticky-top" style="top: 100px;">
                <!-- Filter Group -->
                <div class="bg-white p-4 rounded-4 shadow-sm mb-4 border border-light">
                    <h5 class="font-subheading fw-bold mb-4 text-center" style="color: var(--color-coffee-dark);">
                        <i class="bi bi-funnel me-2"></i>Bộ Lọc
                    </h5>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-uppercase text-muted">Danh mục</label>
                        <div class="d-flex flex-column gap-2">
                            <a href="menu.php<?php echo $search ? '?search=' . urlencode($search) : ''; ?>"
                               class="text-decoration-none px-3 py-2 rounded-3 <?php echo !$categoryId ? 'bg-brown text-white shadow-sm' : 'text-dark hover-bg-light'; ?>"
                               style="<?php echo !$categoryId ? 'background-color: var(--color-coffee-medium);' : ''; ?>">
                                <i class="bi bi-grid me-2"></i> Tất cả
                            </a>
                            <?php foreach ($categories as $cat): ?>
                                <a href="menu.php?category=<?php echo $cat['id']; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"
                                   class="text-decoration-none px-3 py-2 rounded-3 <?php echo $categoryId == $cat['id'] ? 'bg-brown text-white shadow-sm' : 'text-dark hover-bg-light'; ?>"
                                   style="<?php echo $categoryId == $cat['id'] ? 'background-color: var(--color-coffee-medium);' : ''; ?>">
                                   <i class="bi bi-cup-hot me-2"></i> <?php echo htmlspecialchars($cat['name']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Sort Group -->
                <div class="bg-white p-4 rounded-4 shadow-sm border border-light">
                     <h6 class="fw-bold mb-3 small text-uppercase text-muted">Sắp xếp theo</h6>
                     <select class="form-select border-2" onchange="window.location.href=this.value" style="border-color: var(--color-coffee-light);">
                        <option value="menu.php?<?php echo http_build_query(array_merge($queryParams, ['sort' => 'newest'])); ?>" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>✨ Mới nhất</option>
                        <option value="menu.php?<?php echo http_build_query(array_merge($queryParams, ['sort' => 'price_asc'])); ?>" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>💲 Giá tăng dần</option>
                        <option value="menu.php?<?php echo http_build_query(array_merge($queryParams, ['sort' => 'price_desc'])); ?>" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>💰 Giá giảm dần</option>
                        <option value="menu.php?<?php echo http_build_query(array_merge($queryParams, ['sort' => 'name'])); ?>" <?php echo $sort === 'name' ? 'selected' : ''; ?>>🔤 Tên A-Z</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="col-lg-9">
            <!-- Breadcrumb & Header -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 p-4 bg-white rounded-4 shadow-sm border-start border-5 border-coffee">
                <div>
                    <h2 class="font-heading fw-bold mb-1" style="color: var(--color-coffee-dark);">
                        <?php 
                        if ($search) echo 'Kết quả cho: "' . htmlspecialchars($search) . '"';
                        elseif ($currentCategory) echo htmlspecialchars($currentCategory['name']);
                        else echo 'Thực Đơn Của Chúng Tôi';
                        ?>
                    </h2>
                    <p class="text-muted mb-0 small">Hiển thị <?php echo count($products); ?> / <?php echo $totalProducts; ?> món ngon</p>
                </div>
                
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Trang chủ</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Thực đơn</li>
                    </ol>
                </nav>
            </div>

            <?php if (empty($products)): ?>
                <div class="text-center py-5 bg-white rounded-4 shadow-sm">
                    <div class="mb-3 opacity-25" style="font-size: 5rem;">☕</div>
                    <h4 class="font-heading fw-bold">Không tìm thấy món nào</h4>
                    <p class="text-muted">Hãy thử tìm kiếm với từ khóa khác nhé!</p>
                    <a href="menu.php" class="btn btn-premium-outline rounded-pill mt-3">Xem tất cả menu</a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($products as $product): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="product-card-premium h-100">
                                <div class="card-img-wrapper">
                                    <?php
                                    $menuImgSrc = 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?q=80&w=600&auto=format&fit=crop';
                                    if (!empty($product['image'])) {
                                        if (filter_var($product['image'], FILTER_VALIDATE_URL)) {
                                            $menuImgSrc = $product['image'];
                                        } else {
                                            $menuImgSrc = BASE_URL . '/assets/img/products/' . $product['image'];
                                        }
                                    }
                                    ?>
                                    <img src="<?= $menuImgSrc ?>"
                                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                                     <?php if ($product['quantity'] < 10): ?>
                                        <div class="position-absolute top-0 start-0 m-3">
                                            <span class="badge bg-danger rounded-pill shadow-sm">Sắp hết hàng</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-content d-flex flex-column h-100">
                                    <div class="mb-2 text-center">
                                        <span class="badge bg-light text-muted border"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                    </div>
                                    <h5 class="card-title-premium text-center mb-2">
                                        <a href="javascript:void(0)" onclick="showProductModal(<?php echo $product['id']; ?>)" class="text-decoration-none text-dark stretched-link">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </a>
                                    </h5>
                                    
                                    <div class="px-2 mb-3">
                                        <?php if (!empty($product['has_s'])): ?>
                                            <div class="d-flex justify-content-between align-items-center py-1" style="border-bottom: 1px dashed #f0f0f0;">
                                                <span class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.5px;">Size S</span>
                                                <span class="fw-bold" style="color: var(--color-coffee-medium, #6F4E37);"><?= number_format($product['price_s'], 0, ',', '.') ?>đ</span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($product['has_l'])): ?>
                                            <div class="d-flex justify-content-between align-items-center py-1" style="border-bottom: 1px dashed #f0f0f0;">
                                                <span class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.5px;">Size L</span>
                                                <span class="fw-bold" style="color: var(--color-coffee-medium, #6F4E37);"><?= number_format($product['price_l'], 0, ',', '.') ?>đ</span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($product['has_xl'])): ?>
                                            <div class="d-flex justify-content-between align-items-center py-1" style="border-bottom: 1px dashed #f0f0f0;">
                                                <span class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.5px;">Size XL</span>
                                                <span class="fw-bold" style="color: var(--color-coffee-medium, #6F4E37);"><?= number_format($product['price_xl'], 0, ',', '.') ?>đ</span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!$product['has_s'] && !$product['has_m'] && !$product['has_l'] && !$product['has_xl']): ?>
                                            <p class="text-muted small mb-0 text-center">
                                                <?php echo htmlspecialchars($product['description'] ?? ''); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mt-auto pt-3 border-top border-light d-flex justify-content-between align-items-center">
                                        <span class="price-tag mb-0"><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</span>
                                        <button onclick="addToCart(<?php echo $product['id']; ?>)" class="btn-add-cart shadow-sm position-relative" style="z-index: 2;">
                                            <i class="bi bi-plus-lg text-white"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-5">
                        <ul class="pagination justify-content-center gap-2">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link border-0 shadow-sm rounded-circle d-flex align-items-center justify-content-center text-dark" 
                                       href="menu.php?<?php echo http_build_query(array_merge($queryParams, ['page' => $page - 1])); ?>" style="width: 45px; height: 45px;">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item">
                                    <a class="page-link border-0 shadow-sm rounded-circle d-flex align-items-center justify-content-center fw-bold <?php echo $i == $page ? 'text-white' : 'text-dark'; ?>" 
                                       href="menu.php?<?php echo http_build_query(array_merge($queryParams, ['page' => $i])); ?>" 
                                       style="width: 45px; height: 45px; <?php echo $i == $page ? 'background: var(--color-coffee-medium);' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link border-0 shadow-sm rounded-circle d-flex align-items-center justify-content-center text-dark" 
                                       href="menu.php?<?php echo http_build_query(array_merge($queryParams, ['page' => $page + 1])); ?>" style="width: 45px; height: 45px;">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .text-truncate-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .hover-bg-light:hover {
        background-color: var(--color-beige);
    }
    .border-coffee {
        border-color: var(--color-coffee-medium) !important;
    }
</style>

<script>
function addToCart(productId, quantity = 1, size = 'M') {
    fetch('ajax/cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'add',
            product_id: productId,
            quantity: parseInt(quantity),
            size: size
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update badge
            const badge = document.querySelector('.cart-badge');
            const cartIcon = document.querySelector('.bi-bag');
            
            if (badge) {
                badge.textContent = data.cartCount;
                badge.classList.remove('d-none');
                
                // Add pop animation to the badge or icon
                badge.parentElement.classList.add('cart-anim');
                setTimeout(() => {
                    badge.parentElement.classList.remove('cart-anim');
                }, 400);
            }

            // Show Bootstrap Toast
            const toastEl = document.getElementById('cartToast');
            if (toastEl && typeof bootstrap !== 'undefined') {
                const toast = new bootstrap.Toast(toastEl);
                toast.show();
            }
        } else {
            if (data.redirect) {
                window.location.href = data.redirect;
            } else {
                alert(data.message || 'Có lỗi xảy ra');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}
</script>

<?php require_once 'includes/customer_footer.php'; ?>
