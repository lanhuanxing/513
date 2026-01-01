<?php
// ============================================
// index.php - TechStore 主页（修复版）
// 使用实际产品图片，从数据库读取产品
// ============================================

// 错误报告（仅开发时使用）
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . '/config.php';

// 启动会话
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 检查是否有登出消息
if (isset($_SESSION['logout_message'])) {
    $logout_message = $_SESSION['logout_message'];
    unset($_SESSION['logout_message']);
}

// 如果没有登录，设置为访客
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 0;
    $_SESSION['username'] = 'Guest';
    $_SESSION['user_role'] = 'guest';
    $_SESSION['logged_in'] = false;
}

// 检查用户登录状态
$isLoggedIn = isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
$username = $_SESSION['username'] ?? 'Guest';
$userRole = $_SESSION['user_role'] ?? 'guest';
$isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// 设置页面变量
$page_title = "TechStore - Your Technology Partner";
$meta_description = "Welcome to TechStore! Find the latest technology products, gadgets, and accessories at the best prices.";
$current_year = date('Y');

// 获取数据库连接
$pdo = getDatabaseConnection();
if (!$pdo) {
    die("Database connection failed. Please check your config.php settings.");
}

// 从数据库获取特色产品
try {
    // 获取前8个有库存的产品作为特色产品
    $sql = "SELECT * FROM products WHERE stock > 0 ORDER BY created_at DESC LIMIT 8";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 获取统计数据
    $products_stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE stock > 0");
    $total_products = $products_stmt->fetch()['total'] ?? 0;
    
    $users_stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $total_customers = $users_stmt->fetch()['total'] ?? 0;
    
    $orders_stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE status = 'completed'");
    $total_orders = $orders_stmt->fetch()['total'] ?? 0;
    
    // 如果数据库中没有足够的产品，使用默认数据
    if (empty($featured_products)) {
        $featured_products = getDefaultFeaturedProducts();
    }
    
} catch (Exception $e) {
    // 如果数据库查询失败，使用默认数据
    $featured_products = getDefaultFeaturedProducts();
    $total_products = 152;
    $total_customers = 1245;
    $total_orders = 5678;
    $error_message = "Error loading products: " . $e->getMessage();
}

// 默认产品数据函数
function getDefaultFeaturedProducts() {
    return [
        [
            'id' => 1,
            'product_code' => 'ELEC-001',
            'name' => 'iPhone 15 Pro',
            'description' => 'The most advanced iPhone ever with A17 Pro chip, titanium design, and professional camera system.',
            'price' => 999.00,
            'brand' => 'Apple',
            'category' => 'Smartphones',
            'image_url' => 'https://images.unsplash.com/photo-1695048133142-1a20484d2569?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
            'stock' => 10,
            'rating' => 4.8,
            'specifications' => json_encode([
                'Processor' => 'A17 Pro',
                'Display' => '6.1-inch Super Retina XDR',
                'Storage' => '128GB/256GB/512GB/1TB'
            ])
        ],
        [
            'id' => 2,
            'product_code' => 'ELEC-002',
            'name' => 'Galaxy S24 Ultra',
            'description' => 'Epic in every way with advanced AI features, professional-grade camera, and titanium frame.',
            'price' => 1199.00,
            'brand' => 'Samsung',
            'category' => 'Smartphones',
            'image_url' => 'https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
            'stock' => 8,
            'rating' => 4.7,
            'specifications' => json_encode([
                'Processor' => 'Snapdragon 8 Gen 3',
                'Display' => '6.8-inch Dynamic AMOLED 2X',
                'Storage' => '256GB/512GB/1TB'
            ])
        ],
        [
            'id' => 3,
            'product_code' => 'ELEC-003',
            'name' => 'MacBook Air M2',
            'description' => 'Lightning fast performance with M2 chip, stunning Retina display, and all-day battery life.',
            'price' => 1299.00,
            'brand' => 'Apple',
            'category' => 'Laptops',
            'image_url' => 'https://images.unsplash.com/photo-1541807084-5c52b6b3adef?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
            'stock' => 5,
            'rating' => 4.9,
            'specifications' => json_encode([
                'Processor' => 'Apple M2',
                'Display' => '13.6-inch Liquid Retina',
                'RAM' => '8GB/16GB/24GB'
            ])
        ],
        [
            'id' => 4,
            'product_code' => 'ELEC-004',
            'name' => 'PS5 Console',
            'description' => 'Play Has No Limits. Experience lightning-fast loading with an ultra-high speed SSD.',
            'price' => 499.00,
            'brand' => 'Sony',
            'category' => 'Gaming',
            'image_url' => 'https://images.unsplash.com/photo-1606144042614-b2417e99c4e3?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
            'stock' => 15,
            'rating' => 4.9,
            'specifications' => json_encode([
                'Storage' => '825GB SSD',
                'Resolution' => '4K',
                'Frame Rate' => 'Up to 120fps'
            ])
        ]
    ];
}

// 解析规格信息
function parseSpecifications($specs) {
    if (empty($specs)) return [];
    
    try {
        // 尝试解析JSON
        $parsed = json_decode($specs, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
            return $parsed;
        }
    } catch (Exception $e) {
        // 如果不是JSON，尝试其他格式
    }
    
    // 默认返回空数组
    return [];
}

// 生成随机评分（用于演示，实际应从数据库获取）
function getRandomRating() {
    $ratings = [4.5, 4.6, 4.7, 4.8, 4.9];
    return $ratings[array_rand($ratings)];
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta name="keywords" content="technology, electronics, smartphones, laptops, gadgets, tech store">
    <meta name="author" content="TechStore">
    
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    
    <!-- CSS Stylesheets -->
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- 页面特定样式 -->
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
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        /* 英雄区域样式 */
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0.7)), 
                        url('https://images.unsplash.com/photo-1516383740770-fbcc5ccbece0?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 120px 0;
            text-align: center;
            margin-bottom: 60px;
            border-radius: 0 0 var(--radius) var(--radius);
        }
        
        .hero-section h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 3.5rem;
            margin-bottom: 20px;
            font-weight: 700;
        }
        
        .hero-section p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            opacity: 0.9;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: var(--accent);
            color: white;
            text-decoration: none;
            border-radius: var(--radius);
            font-weight: 600;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .btn:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
        }
        
        .btn-lg {
            padding: 15px 40px;
            font-size: 1.1rem;
        }
        
        /* 特性区域样式 */
        .features-section {
            padding: 60px 0;
            background: white;
            border-radius: var(--radius);
            margin: 0 auto 60px;
            max-width: 1200px;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .section-title h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 2.5rem;
            color: var(--text);
            margin-bottom: 15px;
        }
        
        .section-title p {
            color: var(--muted);
            max-width: 600px;
            margin: 0 auto;
            font-size: 1.1rem;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
        }
        
        .feature-card {
            text-align: center;
            padding: 40px 30px;
            border-radius: var(--radius);
            background: white;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary) 0%, #6c8eff 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            color: white;
            font-size: 32px;
        }
        
        .feature-card h3 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.4rem;
            margin-bottom: 15px;
            color: var(--text);
        }
        
        /* 产品区域样式 - 与products.php一致 */
        .products-section {
            padding: 60px 0;
            background: var(--bg);
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
        
        .product-card {
            background: white;
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.12);
        }
        
        .product-code {
            font-size: 0.85rem;
            color: var(--muted);
            margin-bottom: 8px;
        }
        
        .product-card h3 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.35rem;
            margin: 10px 0;
            color: var(--text);
        }
        
        .product-image {
            width: 100%;
            height: 220px;
            overflow: hidden;
            border-radius: 10px;
            margin: 15px 0;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .product-card:hover .product-image img {
            transform: scale(1.05);
        }
        
        .product-card p {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.6;
            margin: 10px 0;
            flex-grow: 1;
        }
        
        .product-specs {
            margin: 15px 0;
            font-size: 0.9em;
            color: #666;
        }
        
        .product-specs strong {
            display: block;
            margin-bottom: 8px;
            color: var(--text);
        }
        
        .specs-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }
        
        .spec-tag {
            background: #f8f9fa;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.85em;
            color: var(--text);
            border: 1px solid #e9ecef;
        }
        
        .product-info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 15px 0;
        }
        
        .stock-status {
            font-size: 0.9em;
            font-weight: 500;
        }
        
        .in-stock {
            color: var(--accent);
        }
        
        .out-of-stock {
            color: var(--danger);
        }
        
        .category-badge {
            background: #e9ecef;
            padding: 6px 15px;
            border-radius: 15px;
            font-size: 0.85em;
            color: var(--text);
        }
        
        .price {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--primary);
            margin: 15px 0 20px;
        }
        
        .product-rating {
            color: #ffc107;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 10px;
        }
        
        .rating-count {
            color: var(--muted);
            font-size: 0.9em;
        }
        
        .product-actions {
            display: flex;
            gap: 12px;
            margin-top: auto;
        }
        
        .btn-add-to-cart {
            flex: 1;
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
            text-decoration: none;
        }
        
        .btn-add-to-cart:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .btn-add-to-cart:disabled,
        .btn-add-to-cart[disabled] {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-add-to-cart:disabled:hover,
        .btn-add-to-cart[disabled]:hover {
            background: #6c757d;
            transform: none;
        }
        
        .btn-view-details {
            padding: 12px 20px;
            background: var(--primary);
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
            text-decoration: none;
        }
        
        .btn-view-details:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }
        
        /* 错误消息 */
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
        
        /* 统计区域样式 */
        .stats-section {
            background: linear-gradient(135deg, var(--text) 0%, #343a40 100%);
            color: white;
            padding: 80px 0;
            margin: 60px 0;
            border-radius: var(--radius);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 40px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 15px;
            color: var(--accent);
            font-family: 'Poppins', sans-serif;
        }
        
        .stat-label {
            font-size: 1.2rem;
            opacity: 0.9;
            font-weight: 500;
        }
        
        /* 欢迎消息样式 */
        .welcome-message {
            background: linear-gradient(135deg, var(--primary) 0%, #6c8eff 100%);
            color: white;
            padding: 25px;
            border-radius: var(--radius);
            margin-bottom: 30px;
            text-align: center;
            box-shadow: var(--shadow);
            animation: fadeIn 0.6s ease;
        }
        
        .welcome-message h3 {
            margin-bottom: 10px;
            font-size: 1.8rem;
            font-family: 'Poppins', sans-serif;
        }
        
        .welcome-message a {
            color: white;
            text-decoration: underline;
            font-weight: 600;
        }
        
        /* 新闻通讯订阅 */
        .newsletter-section {
            padding: 80px 0;
            background: white;
            border-radius: var(--radius);
        }
        
        .newsletter-form {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .form-group {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .form-group input {
            flex: 1;
            padding: 15px 20px;
            border: 2px solid #e1e5e9;
            border-radius: var(--radius);
            font-size: 1rem;
            transition: var(--transition);
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }
        
        /* 响应式设计 */
        @media (max-width: 768px) {
            .hero-section {
                padding: 80px 0;
            }
            
            .hero-section h1 {
                font-size: 2.5rem;
            }
            
            .hero-section p {
                font-size: 1rem;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }
            
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .product-actions {
                flex-direction: column;
            }
            
            .stat-number {
                font-size: 2.8rem;
            }
            
            .form-group {
                flex-direction: column;
            }
        }
        
        @media (max-width: 480px) {
            .hero-section h1 {
                font-size: 2rem;
            }
            
            .hero-section {
                padding: 60px 20px;
            }
            
            .section-title h2 {
                font-size: 1.8rem;
            }
            
            .feature-card {
                padding: 30px 20px;
            }
            
            .product-card {
                padding: 20px;
            }
        }
        
        /* 动画 */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    </style>
</head>
<body>
    <!-- 包含导航栏 -->
    <?php 
    $navbar_file = __DIR__ . '/navbar.php';
    if (file_exists($navbar_file)) {
        include $navbar_file;
    } else {
        echo '<nav style="background:#2c3e50;color:white;padding:1rem;">
                <div class="container">
                    <a href="index.php" style="color:white;text-decoration:none;font-size:1.5rem;font-weight:bold;">
                        <i class="fas fa-store"></i> TechStore
                    </a>
                </div>
              </nav>';
    }
    ?>
    
    <!-- 错误消息 -->
    <?php if (isset($error_message)): ?>
    <div class="container">
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- 欢迎消息（如果用户已登录） -->
    <?php if ($isLoggedIn && $username !== 'Guest'): ?>
    <div class="container">
        <div class="welcome-message">
            <h3>Welcome back, <?php echo htmlspecialchars($username); ?>! 👋</h3>
            <p>You are logged in as <?php echo htmlspecialchars($userRole); ?>.</p>
            <?php if ($isAdmin): ?>
                <p><a href="dashboard.php?page=dashboard">Go to Admin Dashboard</a></p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- 英雄区域 -->
    <section class="hero-section">
        <div class="container">
            <h1>Discover the Future of Technology</h1>
            <p>Find the latest gadgets, electronics, and tech accessories at unbeatable prices. 
               From smartphones to laptops, we have everything you need to stay ahead.</p>
            <a href="#products" class="btn btn-lg">
                <i class="fas fa-shopping-cart"></i> Shop Now
            </a>
        </div>
    </section>
    
    <!-- 特性区域 -->
    <section class="features-section">
        <div class="container">
            <div class="section-title">
                <h2>Why Choose TechStore?</h2>
                <p>We offer the best shopping experience with our premium services</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <h3>Free Shipping</h3>
                    <p>Free delivery on all orders over $50. Fast and reliable shipping worldwide.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Secure Payment</h3>
                    <p>100% secure payment processing. Your financial information is protected.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>24/7 Support</h3>
                    <p>Round-the-clock customer support. We're always here to help you.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-undo"></i>
                    </div>
                    <h3>30-Day Returns</h3>
                    <p>Not satisfied? Return any product within 30 days for a full refund.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- 产品区域 -->
    <section class="products-section" id="products">
        <div class="container">
            <div class="section-title">
                <h2>Featured Products</h2>
                <p>Check out our most popular products this month</p>
            </div>
            
            <div class="products-grid">
                <?php foreach ($featured_products as $product): 
                    // 解析规格信息
                    $specifications = parseSpecifications($product['specifications'] ?? '');
                    
                    // 获取评分（如果数据库中没有，使用随机评分）
                    $rating = isset($product['rating']) ? $product['rating'] : getRandomRating();
                    
                    // 处理图片URL
                    $image_url = $product['image_url'] ?? 'https://images.unsplash.com/photo-1498049794561-7780e7231661?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80';
                ?>
                <div class="product-card">
                    <?php if (!empty($product['product_code'])): ?>
                        <div class="product-code"><?php echo htmlspecialchars($product['product_code']); ?></div>
                    <?php endif; ?>
                    
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    
                    <div class="product-image">
                        <img src="<?php echo htmlspecialchars($image_url); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             onerror="this.src='https://images.unsplash.com/photo-1498049794561-7780e7231661?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80'"
                             loading="lazy">
                    </div>
                    
                    <p><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 100)); ?><?php echo strlen($product['description'] ?? '') > 100 ? '...' : ''; ?></p>
                    
                    <?php if (!empty($specifications)): ?>
                    <div class="product-specs">
                        <strong>Key Specs:</strong>
                        <div class="specs-tags">
                            <?php 
                            $count = 0;
                            foreach ($specifications as $key => $value):
                                if ($count < 3): // 只显示前3个规格
                            ?>
                                <div class="spec-tag">
                                    <strong><?php echo htmlspecialchars($key); ?>:</strong> 
                                    <?php echo htmlspecialchars($value); ?>
                                </div>
                            <?php 
                                endif;
                                $count++;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="product-info-row">
                        <span class="stock-status <?php echo ($product['stock'] ?? 0) > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                            <?php if (($product['stock'] ?? 0) > 0): ?>
                                ✓ In Stock (<?php echo $product['stock']; ?>)
                            <?php else: ?>
                                ✗ Out of Stock
                            <?php endif; ?>
                        </span>
                        <span class="category-badge">
                            <?php echo htmlspecialchars($product['category'] ?? 'Uncategorized'); ?>
                        </span>
                    </div>
                    
                    <div class="product-rating">
                        <?php 
                        $full_stars = floor($rating);
                        $has_half_star = ($rating - $full_stars) >= 0.5;
                        $empty_stars = 5 - $full_stars - ($has_half_star ? 1 : 0);
                        
                        // 满星
                        for ($i = 0; $i < $full_stars; $i++) {
                            echo '<i class="fas fa-star"></i>';
                        }
                        
                        // 半星
                        if ($has_half_star) {
                            echo '<i class="fas fa-star-half-alt"></i>';
                        }
                        
                        // 空星
                        for ($i = 0; $i < $empty_stars; $i++) {
                            echo '<i class="far fa-star"></i>';
                        }
                        ?>
                        <span class="rating-count">(<?php echo $rating; ?>)</span>
                    </div>
                    
                    <div class="price">$<?php echo number_format($product['price'] ?? 0, 2); ?></div>
                    
                    <div class="product-actions">
                        <?php if (($product['stock'] ?? 0) > 0): ?>
                            <a href="add-to-cart.php?product_id=<?php echo $product['id']; ?>&return_url=index.php" 
                               class="btn-add-to-cart">
                                <i class="fas fa-shopping-cart"></i> Add to Cart
                            </a>
                        <?php else: ?>
                            <button class="btn-add-to-cart" disabled style="background: #6c757d; cursor: not-allowed;">
                                <i class="fas fa-times"></i> Out of Stock
                            </button>
                        <?php endif; ?>
                        <a href="products.php?product_id=<?php echo $product['id']; ?>" class="btn-view-details">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div style="text-align: center; margin-top: 50px;">
                <a href="products.php" class="btn btn-lg">
                    <i class="fas fa-th-list"></i> View All Products
                </a>
            </div>
        </div>
    </section>
    
    <!-- 统计区域 -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($total_products); ?></div>
                    <div class="stat-label">Products Available</div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($total_customers); ?></div>
                    <div class="stat-label">Happy Customers</div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($total_orders); ?></div>
                    <div class="stat-label">Orders Delivered</div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">Customer Support</div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- 新闻通讯订阅 -->
    <section class="newsletter-section">
        <div class="container">
            <div class="section-title">
                <h2>Stay Updated</h2>
                <p>Subscribe to our newsletter for the latest deals and tech news</p>
            </div>
            
            <form id="newsletter-form" class="newsletter-form">
                <div class="form-group">
                    <input type="email" 
                           placeholder="Enter your email address" 
                           required>
                    <button type="submit" class="btn">Subscribe</button>
                </div>
                <p style="color: var(--muted); font-size: 0.9rem; text-align: center;">
                    <i class="fas fa-lock"></i> We respect your privacy. Unsubscribe at any time.
                </p>
            </form>
        </div>
    </section>
    
    <!-- 包含页脚 -->
    <?php 
    $footer_file = __DIR__ . '/footer.php';
    if (file_exists($footer_file)) {
        include $footer_file;
    } else {
        echo '<footer style="background: var(--text); color: white; padding: 50px 0; text-align: center;">
            <div class="container">
                <p style="font-size: 1.2rem; margin-bottom: 1rem;">
                    <i class="fas fa-store" style="margin-right: 10px;"></i>TechStore
                </p>
                <p>&copy; ' . $current_year . ' TechStore. All rights reserved.</p>
                <p style="margin-top: 0.5rem; opacity: 0.8; font-size: 0.9rem;">
                    <i class="fas fa-map-marker-alt" style="margin-right: 8px;"></i>
                    123 Tech Street, Silicon Valley, CA 94000
                </p>
            </div>
        </footer>';
    }
    ?>
    
    <!-- JavaScript -->
    <script>
        // 页面加载完成后执行
        document.addEventListener('DOMContentLoaded', function() {
            
            // 1. 新闻通讯表单处理
            const newsletterForm = document.getElementById('newsletter-form');
            if (newsletterForm) {
                newsletterForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const email = this.querySelector('input[type="email"]').value;
                    
                    if (validateEmail(email)) {
                        showMessage(`Thank you for subscribing with ${email}!`, 'success');
                        this.reset();
                    } else {
                        showMessage('Please enter a valid email address', 'error');
                    }
                });
            }
            
            // 2. 平滑滚动
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    if (targetId !== '#') {
                        const targetElement = document.querySelector(targetId);
                        if (targetElement) {
                            targetElement.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }
                    }
                });
            });
            
            // 3. 添加返回顶部按钮
            addBackToTopButton();
            
            // 4. 产品卡片悬停效果增强
            document.querySelectorAll('.product-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
            
            // 初始化页面动画
            initializeAnimations();
            
            // 5. 显示数据库产品数量
            const productCards = document.querySelectorAll('.product-card');
            console.log(`Displaying ${productCards.length} featured products from database`);
        });
        
        // 显示消息函数
        function showMessage(message, type = 'info') {
            // 移除现有的消息
            const existingMessages = document.querySelectorAll('.message-toast');
            existingMessages.forEach(msg => msg.remove());
            
            // 创建新消息
            const messageDiv = document.createElement('div');
            messageDiv.className = `message-toast message-${type}`;
            
            // 图标映射
            const icons = {
                'success': 'fa-check-circle',
                'error': 'fa-exclamation-circle',
                'warning': 'fa-exclamation-triangle',
                'info': 'fa-info-circle'
            };
            
            // 颜色映射
            const colors = {
                'success': '#28a745',
                'error': '#dc3545',
                'warning': '#ffc107',
                'info': '#17a2b8'
            };
            
            messageDiv.innerHTML = `
                <i class="fas ${icons[type]}" style="margin-right:12px;font-size:1.2em;"></i>
                <span>${message}</span>
            `;
            
            // 样式
            messageDiv.style.cssText = `
                position: fixed;
                top: 25px;
                right: 25px;
                padding: 18px 30px;
                background: ${colors[type]};
                color: white;
                border-radius: 12px;
                z-index: 10000;
                animation: slideIn 0.3s ease;
                box-shadow: 0 10px 25px rgba(0,0,0,0.2);
                font-weight: 600;
                display: flex;
                align-items: center;
                max-width: 400px;
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255,255,255,0.1);
            `;
            
            document.body.appendChild(messageDiv);
            
            // 3秒后自动移除
            setTimeout(() => {
                messageDiv.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => {
                    if (messageDiv.parentNode) {
                        messageDiv.parentNode.removeChild(messageDiv);
                    }
                }, 300);
            }, 3000);
        }
        
        // 邮箱验证
        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
        
        // 添加返回顶部按钮
        function addBackToTopButton() {
            const button = document.createElement('button');
            button.innerHTML = '<i class="fas fa-arrow-up"></i>';
            button.setAttribute('aria-label', 'Back to top');
            button.style.cssText = `
                position: fixed;
                bottom: 40px;
                right: 40px;
                width: 60px;
                height: 60px;
                background: var(--primary);
                color: white;
                border: none;
                border-radius: 50%;
                cursor: pointer;
                display: none;
                z-index: 1000;
                font-size: 22px;
                box-shadow: 0 8px 25px rgba(0,123,255,0.3);
                transition: all 0.3s ease;
                opacity: 0.9;
            `;
            
            button.addEventListener('mouseover', function() {
                this.style.background = '#0056b3';
                this.style.transform = 'scale(1.1)';
                this.style.opacity = '1';
            });
            
            button.addEventListener('mouseout', function() {
                this.style.background = 'var(--primary)';
                this.style.transform = 'scale(1)';
                this.style.opacity = '0.9';
            });
            
            button.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
            
            document.body.appendChild(button);
            
            // 监听滚动事件
            window.addEventListener('scroll', function() {
                if (window.pageYOffset > 300) {
                    button.style.display = 'flex';
                    button.style.alignItems = 'center';
                    button.style.justifyContent = 'center';
                } else {
                    button.style.display = 'none';
                }
            });
        }
        
        // 初始化页面动画
        function initializeAnimations() {
            // 添加滚动动画
            const observerOptions = {
                root: null,
                rootMargin: '0px',
                threshold: 0.1
            };
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.animationPlayState = 'running';
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);
            
            // 观察需要动画的元素
            document.querySelectorAll('.feature-card, .product-card, .stat-item').forEach(el => {
                el.style.animation = 'fadeIn 0.6s ease forwards';
                el.style.animationPlayState = 'paused';
                observer.observe(el);
            });
        }
        
        // 添加CSS动画样式
        if (!document.querySelector('#animation-styles')) {
            const style = document.createElement('style');
            style.id = 'animation-styles';
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOut {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
                @keyframes fadeIn {
                    from { opacity: 0; transform: translateY(30px); }
                    to { opacity: 1; transform: translateY(0); }
                }
            `;
            document.head.appendChild(style);
        }
    </script>
</body>
</html>



View Details