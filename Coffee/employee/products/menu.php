<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';

requireLogin();

// Fetch Categories to build the menu structure
$categories = fetchAll($pdo, "SELECT * FROM categories ORDER BY name ASC");
$allProducts = fetchAll($pdo, "SELECT p.*, c.name as category_name 
                               FROM products p 
                               JOIN categories c ON p.category_id = c.id 
                               WHERE p.is_available = 1 
                               ORDER BY c.name, p.name ASC");

// Group products by category name
$menuItems = [];
foreach ($allProducts as $p) {
    if (!isset($menuItems[$p['category_name']])) {
        $menuItems[$p['category_name']] = [];
    }
    $menuItems[$p['category_name']][] = $p;
}

$pageTitle = 'Thực đơn Coffee';
require_once dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<style>
    :root {
        --coffee-primary: #3b82f6; /* Blue for the top line as in screenshot */
        --coffee-bg: #fff;
        --card-border: #e2e8f0;
        --text-dark: #1e293b;
        --text-muted: #64748b;
    }

    .menu-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
        animation: fadeIn 0.8s ease-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .menu-title-header {
        border-bottom: 3px solid var(--coffee-primary);
        padding-bottom: 10px;
        margin-bottom: 40px;
        display: flex;
        align-items: baseline;
    }

    .menu-title-header h1 {
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--text-dark);
        margin: 0;
        margin-right: 15px;
    }

    .menu-title-header .divider {
        flex-grow: 1;
        border-bottom: 1px dotted #ccc;
        margin-bottom: 12px;
    }

    .search-section {
        max-width: 500px;
        margin: 0 auto 50px auto;
        position: relative;
    }

    .search-input {
        width: 100%;
        padding: 12px 20px 12px 20px;
        border-radius: 50px;
        border: 1px solid #ddd;
        background: #fdfdfd;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        font-size: 1rem;
        transition: all 0.3s ease;
        text-align: center;
    }

    .search-input:focus {
        outline: none;
        border-color: var(--coffee-primary);
        box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.1);
        background: #fff;
    }

    .search-icon {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: #8b5cf6; /* Purple search icon as in screenshot hint */
        font-size: 1.2rem;
    }

    .category-section {
        margin-bottom: 60px;
    }

    .category-title {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 30px;
        position: relative;
        padding-left: 15px;
    }

    .category-title::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: var(--coffee-primary);
        border-radius: 2px;
    }

    .menu-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 30px;
    }

    .menu-card {
        background: white;
        border: 1px solid var(--card-border);
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        cursor: pointer;
        display: flex;
        flex-direction: column;
    }

    .menu-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        border-color: var(--coffee-primary);
    }

    .card-img-wrapper {
        width: 100%;
        padding-top: 100%; /* Square aspect ratio like screenshot */
        position: relative;
        background-color: #f8fafc;
        overflow: hidden;
    }

    .card-img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.6s ease;
    }

    .menu-card:hover .card-img {
        transform: scale(1.1);
    }

    .card-body {
        padding: 20px;
        text-align: center;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .item-name {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 8px;
        line-height: 1.4;
    }

    .item-price {
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--text-muted);
    }

    .item-price .old-price {
        text-decoration: line-through;
        color: #cbd5e1;
        font-size: 0.85rem;
        margin-right: 4px;
    }

    .size-price-table {
        width: 100%;
        margin-top: 12px;
        border-top: 1px dashed #e2e8f0;
        padding-top: 10px;
    }

    .size-price-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 0.85rem;
        transition: background 0.2s;
    }

    .size-price-row:hover {
        background: #f1f5f9;
    }

    .size-price-row .size-label {
        font-weight: 700;
        color: #64748b;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .size-price-row .size-value {
        font-weight: 700;
        color: #1e293b;
    }

    .item-tag {
        position: absolute;
        top: 10px;
        left: 10px;
        background: rgba(255, 126, 0, 0.9); /* Orange new/hot tag hint */
        color: white;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.65rem;
        font-weight: 800;
        text-transform: uppercase;
        z-index: 10;
    }

    .no-results {
        text-align: center;
        padding: 100px 20px;
        color: var(--text-muted);
        display: none;
    }

    /* Size options badge style */
    .size-indicator {
        margin-top: 10px;
        display: flex;
        justify-content: center;
        gap: 5px;
    }
    .size-badge {
        font-size: 0.65rem;
        padding: 2px 6px;
        border: 1px solid #ddd;
        border-radius: 4px;
        color: #94a3b8;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .menu-title-header h1 { font-size: 1.8rem; }
        .menu-grid { grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; }
        .card-body { padding: 15px; }
        .item-name { font-size: 0.95rem; }
    }
</style>

<div class="menu-container">
    <div class="menu-title-header">
        <h1>Bảng giá thực đơn</h1>
        <div class="divider"></div>
    </div>

    <div class="search-section">
        <input type="text" id="menuSearch" class="search-input" placeholder="Bạn cần tìm gì ?">
        <i class="bi bi-search search-icon"></i>
    </div>

    <div id="noResults" class="no-results">
        <i class="bi bi-emoji-frown display-4 d-block mb-3"></i>
        <h4>Rất tiếc, không tìm thấy món nào phù hợp!</h4>
    </div>

    <?php foreach ($menuItems as $catName => $products): ?>
        <div class="category-section" data-category="<?= htmlspecialchars($catName) ?>">
            <h2 class="category-title"><?= htmlspecialchars($catName) ?></h2>
            <div class="menu-grid">
                <?php foreach ($products as $item): 
                    $sizes = json_decode($item['sizes'], true) ?: [];
                    $priceStr = number_format($item['price'], 0, ',', '.') . ' VNĐ';
                    if (count($sizes) > 1) {
                        // If has sizes, show range or base
                        $minPrice = min($sizes);
                        $maxPrice = max($sizes);
                        if ($minPrice != $maxPrice) {
                            $priceStr = number_format($minPrice, 0, ',', '.') . ' - ' . number_format($maxPrice, 0, ',', '.') . ' VNĐ';
                        }
                    }
                ?>
                    <div class="menu-card" data-name="<?= strtolower(htmlspecialchars($item['name'])) ?>">
                        <?php if (rand(0, 5) > 4): // Randomly add a 'NEW' tag for demo ?>
                            <div class="item-tag">Mới</div>
                        <?php endif; ?>
                        <div class="card-img-wrapper">
                            <?php 
                            $imgSrc = 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?q=80&w=1000&auto=format&fit=crop';
                            if (!empty($item['image'])) {
                                if (filter_var($item['image'], FILTER_VALIDATE_URL)) {
                                    $imgSrc = $item['image'];
                                } else {
                                    $imgSrc = BASE_URL . "/assets/img/products/" . $item['image'];
                                }
                            }
                            ?>
                            <img src="<?= $imgSrc ?>" class="card-img" alt="<?= htmlspecialchars($item['name']) ?>">
                        </div>
                        <div class="card-body">
                            <h3 class="item-name"><?= htmlspecialchars($item['name']) ?></h3>
                            <?php if (!empty($sizes) && count($sizes) > 0): ?>
                                <div class="size-price-table">
                                    <?php foreach ($sizes as $sz => $szPrice): ?>
                                        <div class="size-price-row">
                                            <span class="size-label">Size <?= $sz ?></span>
                                            <span class="size-value"><?= number_format($szPrice, 0, ',', '.') ?>đ</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="item-price">Giá: <?= number_format($item['price'], 0, ',', '.') ?> VNĐ</div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('menuSearch');
    const cards = document.querySelectorAll('.menu-card');
    const sections = document.querySelectorAll('.category-section');
    const noResults = document.getElementById('noResults');

    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        let totalVisible = 0;

        sections.forEach(section => {
            const sectionCards = section.querySelectorAll('.menu-card');
            let sectionVisible = 0;

            sectionCards.forEach(card => {
                const name = card.dataset.name;
                if (name.includes(query)) {
                    card.style.display = 'flex';
                    sectionVisible++;
                    totalVisible++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Hide section title if no cards visible in it
            section.style.display = sectionVisible > 0 ? 'block' : 'none';
        });

        noResults.style.display = totalVisible === 0 ? 'block' : 'none';
    });
});
</script>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
