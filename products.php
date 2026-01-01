<?php
require_once 'config.php';

// 启动会话
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 检查用户是否登录
$isLoggedIn = false;
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0 && 
    isset($_SESSION['username']) && $_SESSION['username'] !== 'Guest') {
    $isLoggedIn = true;
}

// 获取用户信息
$username = $_SESSION['username'] ?? 'Guest';
$user_email = $_SESSION['email'] ?? '';

// 获取数据库连接
$pdo = getDatabaseConnection();
if (!$pdo) {
    die("Database connection failed. Please check your config.php settings.");
}

// 检查是否请求单个产品详情
$is_single_product_view = isset($_GET['product_id']) && !empty($_GET['product_id']);
$product_id = $is_single_product_view ? intval($_GET['product_id']) : null;

if ($is_single_product_view) {
    // ============================
    // 单个产品详情页面模式
    // ============================
    
    try {
        // 获取单个产品详情
        $sql = "SELECT * FROM products WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            header('Location: products.php');
            exit;
        }
        
        // 解析规格信息
        $specifications = [];
        if (!empty($product['specifications'])) {
            try {
                $specifications = json_decode($product['specifications'], true);
                if (!is_array($specifications)) {
                    $specifications = [];
                }
            } catch (Exception $e) {
                $specifications = [];
            }
        }
        
    } catch (Exception $e) {
        $error_message = "Error loading product: " . $e->getMessage();
        $product = null;
    }
} else {
    // ============================
    // 产品列表页面模式
    // ============================
    
    try {
        $sql = "SELECT * FROM products WHERE stock > 0 ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 获取所有分类和品牌用于筛选器
        $categories_stmt = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");
        $categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        $brands_stmt = $pdo->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != '' ORDER BY brand");
        $brands = $brands_stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
    } catch (Exception $e) {
        $products = [];
        $categories = [];
        $brands = [];
        $error_message = "Error loading products: " . $e->getMessage();
    }
    
    // 转换为JSON格式供JavaScript使用
    $products_json = json_encode($products);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_single_product_view ? htmlspecialchars($product['name']) . ' - TechStore' : 'Products - TechStore'; ?></title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #007bff;
            --accent: #28a745;
            --danger: #dc3545;
            --bg: #f8f9fa;
            --text: #212529;
            --muted: #6c757d;
            --radius: 12px;
            --shadow: 0 6px 20px rgba(0,0,0,0.08);
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            margin: 0;
            padding: 0;
        }
        
        main {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 2rem;
            margin-bottom: 2rem;
            color: var(--text);
            text-align: center;
        }
        
        /* 单个产品详情页样式 */
        .product-detail-container {
            background: white;
            border-radius: var(--radius);
            padding: 40px;
            box-shadow: var(--shadow);
            margin-bottom: 50px;
        }
        
        .product-detail-header {
            display: flex;
            gap: 40px;
            margin-bottom: 30px;
        }
        
        .product-detail-image {
            flex: 1;
            max-width: 400px;
            height: 400px;
            overflow: hidden;
            border-radius: var(--radius);
        }
        
        .product-detail-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: var(--radius);
        }
        
        .product-detail-info {
            flex: 2;
        }
        
        .product-code {
            font-size: 0.9rem;
            color: var(--muted);
            margin-bottom: 10px;
        }
        
        .product-detail-info h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: var(--text);
        }
        
        .product-brand {
            font-size: 1.1rem;
            color: var(--primary);
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .product-price {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin: 20px 0;
        }
        
        .stock-status {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .in-stock {
            background: #d4edda;
            color: #155724;
        }
        
        .out-of-stock {
            background: #f8d7da;
            color: #721c24;
        }
        
        .product-description {
            font-size: 1.1rem;
            line-height: 1.7;
            color: #495057;
            margin-bottom: 30px;
        }
        
        .specifications-table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
        }
        
        .specifications-table th,
        .specifications-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
            text-align: left;
        }
        
        .specifications-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: var(--text);
            width: 30%;
        }
        
        .specifications-table tr:hover {
            background: #f8f9fa;
        }
        
        .product-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 123, 255, 0.2);
        }
        
        .btn-accent {
            background: var(--accent);
            color: white;
        }
        
        .btn-accent:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(40, 167, 69, 0.2);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .btn:disabled {
            background: #ced4da;
            cursor: not-allowed;
            transform: none;
        }
        
        /* 列表页面样式（原有样式） */
        .filters {
            background: white;
            padding: 25px;
            border-radius: var(--radius);
            margin-bottom: 30px;
            box-shadow: var(--shadow);
        }
        
        .filter-group {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .filter-group label {
            font-weight: 600;
            color: var(--muted);
            min-width: 100px;
        }
        
        .filter-select {
            padding: 10px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            background: white;
            color: var(--text);
            min-width: 200px;
            font-size: 0.95rem;
        }
        
        .sort-options {
            display: flex;
            gap: 10px;
            margin-left: auto;
        }
        
        .sort-btn {
            padding: 8px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            background: white;
            color: var(--text);
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
        }
        
        .sort-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .price-range {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .price-input {
            width: 120px;
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
        }
        
        .action-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 600;
        }
        
        .apply-btn {
            background: var(--primary);
            color: white;
        }
        
        .apply-btn:hover {
            background: #0056b3;
        }
        
        .reset-btn {
            background: #6c757d;
            color: white;
        }
        
        .reset-btn:hover {
            background: #5a6268;
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .product-card {
            background: white;
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.1);
        }
        
        .product-image {
            width: 100%;
            height: 200px;
            overflow: hidden;
            border-radius: 8px;
            margin: 15px 0;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .product-card:hover .product-image img {
            transform: scale(1.05);
        }
        
        .price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin: 15px 0;
        }
        
        .add-to-cart {
            width: 100%;
            padding: 12px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: auto;
            text-decoration: none;
        }
        
        .add-to-cart:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .add-to-cart:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }
        
        .specs-tags {
            margin: 15px 0;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .spec-tag {
            background: #f8f9fa;
            padding: 4px 12px;
            border-radius: 10px;
            font-size: 0.85em;
            border: 1px solid #e9ecef;
        }
        
        .loading, .no-products {
            text-align: center;
            padding: 3rem;
            grid-column: 1 / -1;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px 20px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 20px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        @media (max-width: 768px) {
            .product-detail-header {
                flex-direction: column;
            }
            
            .product-detail-image {
                max-width: 100%;
                height: 300px;
            }
            
            .product-actions {
                flex-direction: column;
            }
            
            .filter-group {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .sort-options {
                margin-left: 0;
                width: 100%;
            }
            
            .product-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main>
        <?php if ($is_single_product_view): ?>
            <!-- 单个产品详情页面 -->
            <?php if ($product): ?>
                <div class="product-detail-container">
                    <!-- 面包屑导航 -->
                    <div style="margin-bottom: 20px;">
                        <a href="products.php" style="color: var(--muted); text-decoration: none;">
                            <i class="fas fa-arrow-left"></i> Back to Products
                        </a>
                    </div>
                    
                    <!-- 产品详情 -->
                    <div class="product-detail-header">
                        <div class="product-detail-image">
                            <img src="<?php echo htmlspecialchars($product['image_url'] ?: 'https://images.unsplash.com/photo-1498049794561-7780e7231661?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80'); ?>"
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 onerror="this.src='https://images.unsplash.com/photo-1498049794561-7780e7231661?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80'">
                        </div>
                        
                        <div class="product-detail-info">
                            <?php if (!empty($product['product_code'])): ?>
                                <div class="product-code">Product Code: <?php echo htmlspecialchars($product['product_code']); ?></div>
                            <?php endif; ?>
                            
                            <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                            
                            <?php if (!empty($product['brand'])): ?>
                                <div class="product-brand"><?php echo htmlspecialchars($product['brand']); ?></div>
                            <?php endif; ?>
                            
                            <div class="stock-status <?php echo ($product['stock'] > 0) ? 'in-stock' : 'out-of-stock'; ?>">
                                <?php echo ($product['stock'] > 0) ? '✓ In Stock' : '✗ Out of Stock'; ?>
                                <?php if ($product['stock'] > 0): ?>
                                    <span style="font-weight: normal;">(<?php echo $product['stock']; ?> available)</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
                            
                            <div class="product-description">
                                <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                            </div>
                            
                            <?php if (!empty($specifications)): ?>
                            <div class="specifications">
                                <h3 style="margin-bottom: 15px; font-size: 1.3rem;">Specifications</h3>
                                <table class="specifications-table">
                                    <?php foreach ($specifications as $key => $value): ?>
                                    <tr>
                                        <th><?php echo htmlspecialchars($key); ?></th>
                                        <td>
                                            <?php 
                                            if (is_array($value)) {
                                                echo htmlspecialchars(implode(', ', $value));
                                            } else {
                                                echo htmlspecialchars($value);
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </table>
                            </div>
                            <?php endif; ?>
                            
                            <div class="product-actions">
                                <?php if ($product['stock'] > 0): ?>
                                    <a href="add-to-cart.php?product_id=<?php echo $product['id']; ?>&return_url=products.php?product_id=<?php echo $product['id']; ?>" 
                                       class="btn btn-accent">
                                        <i class="fas fa-shopping-cart"></i> Add to Cart
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-secondary" disabled>
                                        <i class="fas fa-times"></i> Out of Stock
                                    </button>
                                <?php endif; ?>
                                
                                <a href="products.php" class="btn btn-primary">
                                    <i class="fas fa-list"></i> View All Products
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> Product not found.
                </div>
                <div style="text-align: center; margin-top: 30px;">
                    <a href="products.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Back to Products
                    </a>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <!-- 产品列表页面 -->
            <h2>All Products</h2>
            
            <?php if (isset($_GET['added']) && $_GET['added'] == '1'): ?>
                <div id="successMessage" class="success-message">
                    <i class="fas fa-check-circle"></i> Product added to cart successfully!
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <div class="filters">
                <div class="filter-group">
                    <label>Category:</label>
                    <select id="categoryFilter" class="filter-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>">
                                <?php echo htmlspecialchars($category); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <label>Brand:</label>
                    <select id="brandFilter" class="filter-select">
                        <option value="">All Brands</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?php echo htmlspecialchars($brand); ?>">
                                <?php echo htmlspecialchars($brand); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <div class="sort-options">
                        <button class="sort-btn active" data-sort="price_asc">Price: Low to High</button>
                        <button class="sort-btn" data-sort="price_desc">Price: High to Low</button>
                        <button class="sort-btn" data-sort="name_asc">Name: A-Z</button>
                    </div>
                </div>
                
                <div class="filter-group">
                    <label>Price Range:</label>
                    <div class="price-range">
                        <input type="number" id="minPrice" class="price-input" placeholder="Min" min="0" step="1">
                        <span>-</span>
                        <input type="number" id="maxPrice" class="price-input" placeholder="Max" min="0" step="1">
                        <button class="action-btn apply-btn" id="applyFilters">Apply Filters</button>
                        <button class="action-btn reset-btn" id="resetFilters">Reset</button>
                    </div>
                </div>
            </div>
            
            <div id="productContainer">
                <!-- 初始加载状态 -->
                <div class="loading">
                    <div class="loading-spinner"></div>
                    <p>Loading products from database...</p>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <footer style="text-align: center; padding: 2rem; color: var(--muted); margin-top: 3rem;">
        <p>&copy; 2024 TechStore. All rights reserved</p>
    </footer>

    <?php if (!$is_single_product_view): ?>
    <script>
        // 产品列表页面的JavaScript保持不变
        const DATABASE_PRODUCTS = <?php echo $products_json ?: '[]'; ?>;
        
        function convertDatabaseProduct(dbProduct) {
            let specifications = {};
            try {
                if (dbProduct.specifications) {
                    specifications = JSON.parse(dbProduct.specifications);
                }
            } catch (e) {
                if (dbProduct.specifications && typeof dbProduct.specifications === 'string') {
                    const specPairs = dbProduct.specifications.split(';');
                    specPairs.forEach(pair => {
                        const [key, value] = pair.split(':').map(s => s.trim());
                        if (key && value) {
                            specifications[key] = value;
                        }
                    });
                }
            }
            
            return {
                id: dbProduct.id,
                product_code: dbProduct.product_code || '',
                name: dbProduct.name,
                description: dbProduct.description || '',
                price: parseFloat(dbProduct.price) || 0,
                brand: dbProduct.brand || '',
                category: dbProduct.category || 'Uncategorized',
                image_url: dbProduct.image_url || 'https://images.unsplash.com/photo-1498049794561-7780e7231661?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
                stock_quantity: parseInt(dbProduct.stock) || 0,
                specifications: specifications
            };
        }

        class ProductManager {
            constructor() {
                this.products = DATABASE_PRODUCTS.map(convertDatabaseProduct);
                this.filteredProducts = [...this.products];
                this.currentSort = 'price_asc';
                this.currentFilters = {
                    category: '',
                    brand: '',
                    minPrice: 0,
                    maxPrice: Infinity
                };
                
                this.init();
            }
            
            init() {
                this.renderProducts();
                this.setupEventListeners();
                
                setTimeout(() => {
                    const successMessage = document.getElementById('successMessage');
                    if (successMessage) {
                        successMessage.style.opacity = '0';
                        successMessage.style.transition = 'opacity 0.5s';
                        setTimeout(() => successMessage.remove(), 500);
                    }
                }, 3000);
                
                console.log(`Loaded ${this.products.length} products from database`);
            }
            
            renderProducts() {
                const container = document.getElementById('productContainer');
                
                if (this.filteredProducts.length === 0) {
                    container.innerHTML = `
                        <div class="no-products">
                            <div style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.4;">📦</div>
                            <h3>No products found</h3>
                            <p>Try adjusting your filters or browse all categories.</p>
                            <button class="action-btn apply-btn" onclick="productManager.resetFilters()">Show All Products</button>
                        </div>
                    `;
                    return;
                }
                
                // 修改产品卡片，使用新的链接格式
                const productsHtml = this.filteredProducts.map(product => this.createProductCard(product)).join('');
                
                container.innerHTML = `
                    <div class="product-grid">
                        ${productsHtml}
                    </div>
                    <div style="text-align: center; margin-top: 2rem; color: var(--muted);">
                        Showing ${this.filteredProducts.length} of ${this.products.length} products
                    </div>
                `;
            }
            
            createProductCard(product) {
                const isInStock = product.stock_quantity > 0;
                const specs = product.specifications || {};
                
                let specsHtml = '';
                if (Object.keys(specs).length > 0) {
                    const specEntries = Object.entries(specs).slice(0, 3);
                    specsHtml = `
                        <div class="specs-tags">
                            ${specEntries.map(([key, value]) => `
                                <div class="spec-tag">
                                    <strong>${key}:</strong> ${Array.isArray(value) ? value.join('/') : value}
                                </div>
                            `).join('')}
                        </div>
                    `;
                }
                
                let imageUrl = product.image_url;
                if (!imageUrl || imageUrl.trim() === '') {
                    imageUrl = 'https://images.unsplash.com/photo-1498049794561-7780e7231661?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80';
                }
                
                return `
                    <div class="product-card">
                        ${product.product_code ? `<div class="product-code">${product.product_code}</div>` : ''}
                        <h3>${this.escapeHtml(product.name)}</h3>
                        <div class="product-image">
                            <img src="${imageUrl}" alt="${this.escapeHtml(product.name)}" 
                                 onerror="this.src='https://images.unsplash.com/photo-1498049794561-7780e7231661?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80'"
                                 loading="lazy">
                        </div>
                        <p>${this.truncateText(this.escapeHtml(product.description || ''), 100)}</p>
                        
                        ${specsHtml}
                        
                        <div style="margin: 10px 0; display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.9em; color: ${isInStock ? '#28a745' : '#dc3545'}; font-weight: 500;">
                                ${isInStock ? `✓ In Stock (${product.stock_quantity})` : '✗ Out of Stock'}
                            </span>
                            <span style="background: #e9ecef; padding: 4px 12px; border-radius: 15px; font-size: 0.85em;">
                                ${this.escapeHtml(product.category)}
                            </span>
                        </div>
                        
                        <p class="price">$${product.price.toFixed(2)}</p>
                        
                        <div style="display: flex; gap: 10px;">
                            ${isInStock ? 
                                `<a href="add-to-cart.php?product_id=${product.id}&return_url=products.php" class="add-to-cart" style="flex: 1;">
                                    <i class="fas fa-shopping-cart"></i> Add to Cart
                                </a>` :
                                `<button class="add-to-cart" style="background: #6c757d; cursor: not-allowed;" disabled>
                                    <i class="fas fa-times"></i> Out of Stock
                                </button>`
                            }
                            <a href="products.php?product_id=${product.id}" 
                               class="add-to-cart" 
                               style="flex: 1; background: var(--primary);">
                                <i class="fas fa-eye"></i> Details
                            </a>
                        </div>
                    </div>
                `;
            }
            
            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            
            truncateText(text, maxLength) {
                if (text.length <= maxLength) return text;
                return text.substring(0, maxLength) + '...';
            }
            
            applyFilters() {
                const category = document.getElementById('categoryFilter').value;
                const brand = document.getElementById('brandFilter').value;
                const minPrice = parseFloat(document.getElementById('minPrice').value) || 0;
                const maxPrice = parseFloat(document.getElementById('maxPrice').value) || Infinity;
                
                this.currentFilters = { category, brand, minPrice, maxPrice };
                
                this.filteredProducts = this.products.filter(product => {
                    const price = parseFloat(product.price);
                    const matchesCategory = !category || product.category.toLowerCase().includes(category.toLowerCase());
                    const matchesBrand = !brand || product.brand.toLowerCase().includes(brand.toLowerCase());
                    const matchesPrice = price >= minPrice && price <= maxPrice;
                    
                    return matchesCategory && matchesBrand && matchesPrice;
                });
                
                this.sortProducts();
                this.renderProducts();
            }
            
            sortProducts() {
                this.filteredProducts.sort((a, b) => {
                    switch(this.currentSort) {
                        case 'price_asc':
                            return a.price - b.price;
                        case 'price_desc':
                            return b.price - a.price;
                        case 'name_asc':
                            return a.name.localeCompare(b.name);
                        default:
                            return 0;
                    }
                });
            }
            
            setSort(sortType) {
                this.currentSort = sortType;
                
                document.querySelectorAll('.sort-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                event.target.classList.add('active');
                
                this.sortProducts();
                this.renderProducts();
            }
            
            resetFilters() {
                document.getElementById('categoryFilter').value = '';
                document.getElementById('brandFilter').value = '';
                document.getElementById('minPrice').value = '';
                document.getElementById('maxPrice').value = '';
                
                document.querySelectorAll('.sort-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                document.querySelectorAll('.sort-btn')[0].classList.add('active');
                
                this.currentSort = 'price_asc';
                this.currentFilters = {
                    category: '',
                    brand: '',
                    minPrice: 0,
                    maxPrice: Infinity
                };
                
                this.filteredProducts = [...this.products];
                this.sortProducts();
                this.renderProducts();
            }
            
            setupEventListeners() {
                document.getElementById('applyFilters').addEventListener('click', () => {
                    this.applyFilters();
                });
                
                document.getElementById('resetFilters').addEventListener('click', () => {
                    this.resetFilters();
                });
                
                document.querySelectorAll('.sort-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        this.setSort(e.target.dataset.sort);
                    });
                });
                
                document.getElementById('categoryFilter').addEventListener('change', () => {
                    this.applyFilters();
                });
                
                document.getElementById('brandFilter').addEventListener('change', () => {
                    this.applyFilters();
                });
                
                document.getElementById('minPrice').addEventListener('input', () => {
                    this.applyFilters();
                });
                
                document.getElementById('maxPrice').addEventListener('input', () => {
                    this.applyFilters();
                });
            }
        }
        
        let productManager;
        
        document.addEventListener('DOMContentLoaded', () => {
            productManager = new ProductManager();
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                productManager.resetFilters();
            }
            
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                document.getElementById('categoryFilter').focus();
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>