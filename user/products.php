<?php
$pageTitle = 'Sản phẩm';
require_once 'includes/header.php';

// Get filter parameters
$search = sanitize($_GET['search'] ?? '');
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$sort = sanitize($_GET['sort'] ?? 'newest');
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Build query
$where = ["m.quantity > 0", "m.expiry_date > CURDATE()"];
$params = [];

if ($search) {
    $where[] = "(m.name LIKE ? OR m.code LIKE ? OR m.description LIKE ?)";
    $searchTerm = "%{$search}%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

if ($categoryId > 0) {
    $where[] = "m.category_id = ?";
    $params[] = $categoryId;
}

$whereClause = implode(' AND ', $where);

// Sort options
$orderBy = match($sort) {
    'price_asc' => 'm.price ASC',
    'price_desc' => 'm.price DESC',
    'name' => 'm.name ASC',
    'bestseller' => 'total_sold DESC',
    default => 'm.created_at DESC'
};

// Get total count
$countSql = "SELECT COUNT(*) FROM medicines m WHERE {$whereClause}";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$totalProducts = $stmt->fetchColumn();
$totalPages = ceil($totalProducts / $perPage);

// Get products
$sql = "
    SELECT m.*, c.name as category_name, COALESCE(SUM(od.quantity), 0) as total_sold
    FROM medicines m
    JOIN categories c ON m.category_id = c.id
    LEFT JOIN order_details od ON m.id = od.medicine_id
    WHERE {$whereClause}
    GROUP BY m.id
    ORDER BY {$orderBy}
    LIMIT {$perPage} OFFSET {$offset}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get current category
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

<div class="container py-4">
    <div class="row">
        <!-- Sidebar Filters -->
        <div class="col-lg-3 mb-4">
            <div class="card border-0 shadow-sm" style="border-radius: var(--radius-lg);">
                <div class="card-header bg-gradient" style="background: linear-gradient(135deg, var(--primary-500), var(--primary-600)); border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
                    <h6 class="mb-0 text-white"><i class="bi bi-funnel me-2"></i>Bộ lọc</h6>
                </div>
                <div class="card-body">
                    <!-- Categories -->
                    <h6 class="mb-3" style="font-family: var(--font-display); font-weight: 600;">
                        <i class="bi bi-grid-3x3-gap text-primary me-2"></i>Danh mục
                    </h6>
                    <div class="list-group list-group-flush mb-4">
                        <a href="<?= BASE_URL ?>/user/products.php<?= $search ? '?search=' . urlencode($search) : '' ?>"
                           class="list-group-item list-group-item-action border-0 rounded <?= !$categoryId ? 'active bg-primary' : '' ?>" style="font-size: 0.9rem;">
                            <i class="bi bi-collection me-2"></i>Tất cả sản phẩm
                        </a>
                        <?php foreach ($categories as $cat): ?>
                            <a href="<?= BASE_URL ?>/user/products.php?category=<?= $cat['id'] ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                               class="list-group-item list-group-item-action border-0 rounded <?= $categoryId == $cat['id'] ? 'active bg-primary' : '' ?>" style="font-size: 0.9rem;">
                                <?= htmlspecialchars($cat['name']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <!-- Sort -->
                    <h6 class="mb-3" style="font-family: var(--font-display); font-weight: 600;">
                        <i class="bi bi-sort-down text-primary me-2"></i>Sắp xếp
                    </h6>
                    <select class="form-select" onchange="window.location.href=this.value" style="border-radius: var(--radius);">
                        <option value="<?= BASE_URL ?>/user/products.php?<?= http_build_query(array_merge($queryParams, ['sort' => 'newest'])) ?>" <?= $sort === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                        <option value="<?= BASE_URL ?>/user/products.php?<?= http_build_query(array_merge($queryParams, ['sort' => 'price_asc'])) ?>" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Giá thấp - cao</option>
                        <option value="<?= BASE_URL ?>/user/products.php?<?= http_build_query(array_merge($queryParams, ['sort' => 'price_desc'])) ?>" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Giá cao - thấp</option>
                        <option value="<?= BASE_URL ?>/user/products.php?<?= http_build_query(array_merge($queryParams, ['sort' => 'bestseller'])) ?>" <?= $sort === 'bestseller' ? 'selected' : '' ?>>Bán chạy nhất</option>
                        <option value="<?= BASE_URL ?>/user/products.php?<?= http_build_query(array_merge($queryParams, ['sort' => 'name'])) ?>" <?= $sort === 'name' ? 'selected' : '' ?>>Tên A-Z</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="col-lg-9">
            <!-- Breadcrumb & Results Info -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0" style="font-size: 0.9rem;">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/user/" class="text-decoration-none"><i class="bi bi-house me-1"></i>Trang chủ</a></li>
                        <li class="breadcrumb-item active">
                            <?php if ($search): ?>
                                Tìm kiếm: "<?= htmlspecialchars($search) ?>"
                            <?php elseif ($currentCategory): ?>
                                <?= htmlspecialchars($currentCategory['name']) ?>
                            <?php else: ?>
                                Tất cả sản phẩm
                            <?php endif; ?>
                        </li>
                    </ol>
                </nav>
                <span class="badge bg-primary-subtle text-primary px-3 py-2">
                    <i class="bi bi-box-seam me-1"></i><?= $totalProducts ?> sản phẩm
                </span>
            </div>

            <?php if ($search): ?>
                <div class="alert alert-primary border-0" style="background: var(--primary-50); border-radius: var(--radius-lg);">
                    <i class="bi bi-search me-2"></i>Kết quả tìm kiếm cho: <strong>"<?= htmlspecialchars($search) ?>"</strong>
                    <a href="<?= BASE_URL ?>/user/products.php" class="ms-2 text-danger">[Xóa tìm kiếm]</a>
                </div>
            <?php endif; ?>

            <?php if (empty($products)): ?>
                <div class="text-center py-5">
                    <div class="bg-light rounded-4 d-inline-block p-4 mb-3">
                        <i class="bi bi-search text-muted" style="font-size: 4rem;"></i>
                    </div>
                    <h5 class="mt-3" style="font-family: var(--font-display);">Không tìm thấy sản phẩm</h5>
                    <p class="text-muted">Vui lòng thử tìm kiếm với từ khóa khác</p>
                    <a href="<?= BASE_URL ?>/user/products.php" class="btn btn-primary">
                        <i class="bi bi-grid me-2"></i>Xem tất cả sản phẩm
                    </a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($products as $product): ?>
                        <div class="col-6 col-md-4">
                            <div class="product-card">
                                <a href="<?= BASE_URL ?>/user/product-detail.php?id=<?= $product['id'] ?>">
                                    <div class="card-img-top position-relative">
                                        <i class="bi bi-capsule-pill" style="font-size: 4rem; color: var(--primary-400);"></i>
                                        <?php if ($product['quantity'] <= 10): ?>
                                            <span class="position-absolute top-0 start-0 m-2">
                                                <span class="badge bg-warning text-dark rounded-pill">
                                                    <i class="bi bi-exclamation-triangle me-1"></i>Sắp hết
                                                </span>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($product['total_sold'] > 10): ?>
                                            <span class="position-absolute bottom-0 end-0 m-2">
                                                <span class="badge bg-dark bg-opacity-75 rounded-pill">
                                                    <i class="bi bi-bag-check me-1"></i><?= $product['total_sold'] ?> đã bán
                                                </span>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                                <div class="card-body">
                                    <span class="badge bg-primary-subtle text-primary mb-2"><?= htmlspecialchars($product['category_name']) ?></span>
                                    <h6 class="card-title mb-1" style="font-family: var(--font-display); font-weight: 600; line-height: 1.3;">
                                        <a href="<?= BASE_URL ?>/user/product-detail.php?id=<?= $product['id'] ?>" class="text-decoration-none text-dark">
                                            <?= htmlspecialchars($product['name']) ?>
                                        </a>
                                    </h6>
                                    <p class="small text-muted mb-2"><?= htmlspecialchars($product['unit']) ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="product-price"><?= formatCurrency($product['price']) ?></span>
                                        <button onclick="addToCart(<?= $product['id'] ?>)" class="btn btn-add-cart">
                                            <i class="bi bi-cart-plus"></i>
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
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= BASE_URL ?>/user/products.php?<?= http_build_query(array_merge($queryParams, ['page' => $page - 1])) ?>" style="border-radius: var(--radius);">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= BASE_URL ?>/user/products.php?<?= http_build_query(array_merge($queryParams, ['page' => $i])) ?>" style="border-radius: var(--radius);"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= BASE_URL ?>/user/products.php?<?= http_build_query(array_merge($queryParams, ['page' => $page + 1])) ?>" style="border-radius: var(--radius);">
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

<?php require_once 'includes/footer.php'; ?>
