<?php
// navbar.php 文件最顶部
// 不要在这里调用 session_start()，因为已经在 config.php 中调用了
// 只需要检查会话状态

// 防止重复包含的检查
if (defined('NAVBAR_INCLUDED')) {
    return;
}
define('NAVBAR_INCLUDED', true);

// 如果config.php没有被包含，包含它
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/config.php';
}

// 检查会话是否已启动，如果没有，使用config.php中的函数
if (session_status() === PHP_SESSION_NONE && function_exists('startSessionIfNeeded')) {
    startSessionIfNeeded();
}

// 确保会话已启动
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 获取会话数据（如果存在）
$isLoggedIn = isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
$username = $_SESSION['username'] ?? '';
$userRole = $_SESSION['user_role'] ?? '';
$isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$userEmail = $_SESSION['email'] ?? '';

// 计算购物车商品总数
$cartTotalCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        if (isset($item['quantity'])) {
            $cartTotalCount += $item['quantity'];
        }
    }
}

// 从 config.php 继承函数，不重复定义
// 如果 config.php 已加载，会使用其中的函数
// 如果未加载，定义简单备用函数

// 检查用户是否登录
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        // 检查是否有用户会话，并且不是Guest用户
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
            if (isset($_SESSION['username']) && $_SESSION['username'] !== 'Guest') {
                return true;
            }
        }
        return false;
    }
}

// 检查是否为管理员
if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }
}

// 检查是否为普通用户（虽然删除了My Account菜单，但保留函数用于其他判断）
if (!function_exists('isUser')) {
    function isUser() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'user' && 
               isset($_SESSION['username']) && $_SESSION['username'] !== 'Guest' &&
               isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
    }
}

// 获取当前用户名和状态
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'guest';
$user_email = isset($_SESSION['email']) ? $_SESSION['email'] : '';

// 判断是否显示登出按钮
$show_logout = false;

// 检查是否应该显示登出按钮
if (isLoggedIn()) {
    // 如果 isLoggedIn() 返回 true，说明用户已登录
    $show_logout = true;
} elseif ($username !== 'Guest' && $username !== '') {
    // 如果用户名不是Guest且不为空，也认为是已登录
    $show_logout = true;
}

// 特殊处理：对于特定的邮箱账号，始终显示登出按钮
if ($user_email === '123456789@qq.com') {
    $show_logout = true;
}

// 获取当前页面用于高亮
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="logo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #007bff;
            --accent: #28a745;
            --danger: #dc3545;
            --muted: #6c757d;
            --radius: 12px;
            --shadow: 0 6px 20px rgba(0,0,0,.08);
            --transition: all 0.3s ease;
        }
        
        /* 导航栏样式 */
        .navbar {
            background: white;
            box-shadow: var(--shadow);
            padding: 0.8rem 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
            font-family: 'Inter', sans-serif;
        }
        
        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        /* 左侧：Logo和网站名称 */
        .nav-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logo {
            font-family: 'Poppins', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .logo-icon {
            background: var(--primary);
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        /* 中间：主导航菜单 */
        .nav-center {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .nav-link {
            color: #495057;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: var(--radius);
            transition: var(--transition);
            position: relative;
            white-space: nowrap;
        }
        
        .nav-link:hover {
            color: var(--primary);
            background: rgba(0, 123, 255, 0.05);
        }
        
        .nav-link.active {
            color: var(--primary);
            background: rgba(0, 123, 255, 0.1);
        }
        
        .dropdown {
            position: relative;
        }
        
        .dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            white-space: nowrap;
        }
        
        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            box-shadow: var(--shadow);
            border-radius: var(--radius);
            padding: 0.5rem 0;
            min-width: 200px;
            display: none;
            z-index: 1001;
        }
        
        .dropdown:hover .dropdown-menu {
            display: block;
        }
        
        .dropdown-item {
            display: block;
            padding: 0.75rem 1.5rem;
            color: #495057;
            text-decoration: none;
            transition: var(--transition);
        }
        
        .dropdown-item:hover {
            background: rgba(0, 123, 255, 0.05);
            color: var(--primary);
        }
        
        .dropdown-item.external {
            color: #28a745;
        }
        
        .dropdown-item.external:hover {
            background: rgba(40, 167, 69, 0.05);
            color: #218838;
        }
        
        /* 管理员特定样式 */
        .admin-badge {
            background: #28a745;
            color: white;
            font-size: 0.7rem;
            padding: 0.2rem 0.6rem;
            border-radius: 10px;
            margin-left: 0.5rem;
            vertical-align: middle;
        }
        
        .admin-link {
            color: #28a745;
            font-weight: 600;
        }
        
        .admin-link:hover {
            color: #218838;
        }
        
        /* 右侧：用户信息和购物车 */
        .nav-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            background: #f8f9fa;
            border-radius: var(--radius);
            min-width: 180px;
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .user-avatar.admin {
            background: #28a745;
        }
        
        .user-details {
            flex: 1;
            min-width: 0;
        }
        
        .username {
            font-weight: 500;
            color: #495057;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .user-role {
            font-size: 0.8rem;
            color: var(--muted);
            display: block;
            margin-top: 2px;
        }
        
        .cart-container {
            position: relative;
        }
        
        .cart-icon {
            position: relative;
            display: block;
            font-size: 1.2rem;
            color: #495057;
            text-decoration: none;
            padding: 0.5rem;
        }
        
        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--accent);
            color: white;
            font-size: 0.7rem;
            min-width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        /* 登出按钮样式 */
        .logout-btn {
            background: var(--danger);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: var(--radius);
            cursor: pointer;
            font-weight: 500;
            font-size: 0.9rem;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }
        
        .logout-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
        }
        
        .login-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: var(--radius);
            cursor: pointer;
            font-weight: 500;
            font-size: 0.9rem;
            transition: var(--transition);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .login-btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
        }
        
        /* 移动端响应式 */
        @media (max-width: 1200px) {
            .nav-center {
                gap: 0.75rem;
            }
            
            .nav-link {
                padding: 0.5rem 0.75rem;
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 1024px) {
            .nav-center {
                display: none;
            }
            
            .mobile-menu-btn {
                display: block;
                background: none;
                border: none;
                font-size: 1.5rem;
                cursor: pointer;
                color: #495057;
            }
            
            .user-info {
                min-width: auto;
            }
            
            .user-details {
                display: none;
            }
        }
        
        @media (min-width: 1025px) {
            .mobile-menu-btn {
                display: none;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <!-- 左侧：Logo -->
            <div class="nav-left">
                <a href="index.php" class="logo">
                    <div class="logo-icon">T</div>
                    <span>TechStore</span>
                </a>
            </div>
            
            <!-- 移动端菜单按钮 -->
            <button class="mobile-menu-btn" onclick="toggleMobileMenu()">☰</button>
            
            <!-- 中间：导航菜单 -->
            <div class="nav-center" id="navMenu">
                <a href="index.php" class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i> Home
                </a>
                
                <a href="products.php" class="nav-link <?= $current_page == 'products.php' ? 'active' : '' ?>">
                    <i class="fas fa-shopping-bag"></i> Products
                </a>
                
                <!-- 新增的页面链接 -->
                <a href="order-history.php" class="nav-link <?= $current_page == 'order-history.php' ? 'active' : '' ?>">
                    <i class="fas fa-history"></i> Order History
                </a>
                
                <a href="forum.php" class="nav-link <?= $current_page == 'forum.php' ? 'active' : '' ?>">
                    <i class="fas fa-comments"></i> Forum
                </a>
                
                <!-- 关于和职业下拉菜单 -->
                <div class="dropdown">
                    <div class="nav-link dropdown-toggle">
                        <i class="fas fa-info-circle"></i> About & Careers
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="dropdown-menu">
                        <a href="about.php" class="dropdown-item">
                            <i class="fas fa-building"></i> About Us
                        </a>
                        <a href="careers.php" class="dropdown-item">
                            <i class="fas fa-briefcase"></i> Careers
                        </a>
                    </div>
                </div>
                
                <!-- 外部链接：客户和联系 -->
                <div class="dropdown">
                    <div class="nav-link dropdown-toggle">
                        <i class="fas fa-external-link-alt"></i> External Links
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="dropdown-menu">
                        <a href="https://bgg.kesug.com/Merlin2/6-2/" 
                           target="_blank" 
                           class="dropdown-item external"
                           title="View customer contact list">
                            <i class="fas fa-users"></i> Customer
                        </a>
                        <a href="https://bgg.kesug.com/Merlin2/26-2/" 
                           target="_blank" 
                           class="dropdown-item external"
                           title="Submit feedback or contact us">
                            <i class="fas fa-envelope"></i> Contact
                        </a>
                    </div>
                </div>
                
                <!-- 管理员菜单 -->
                <?php if (isAdmin()): ?>
                <div class="dropdown">
                    <div class="nav-link dropdown-toggle admin-link">
                        <i class="fas fa-crown"></i> Admin Panel
                        <span class="admin-badge">ADMIN</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="dropdown-menu">
                        <a href="dashboard.php" class="dropdown-item">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <a href="dashboard.php?page=products" class="dropdown-item">
                            <i class="fas fa-box"></i> Manage Products
                        </a>
                        <a href="dashboard.php?page=orders" class="dropdown-item">
                            <i class="fas fa-shopping-cart"></i> Manage Orders
                        </a>
                        <a href="dashboard.php?page=users" class="dropdown-item">
                            <i class="fas fa-users"></i> Manage Users
                        </a>
                        <a href="dashboard.php?page=settings" class="dropdown-item">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="admin-login.php" class="dropdown-item">
                            <i class="fas fa-user-cog"></i> Admin Login
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- 右侧：购物车、用户信息和登出按钮 -->
            <div class="nav-right">
                <!-- 购物车 -->
                <div class="cart-container">
                    <a href="cart.php" class="cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if ($cartTotalCount > 0): ?>
                            <span class="cart-count" id="cartCount"><?php echo $cartTotalCount; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                
                <!-- 用户信息 -->
                <div class="user-info">
                    <div class="user-avatar <?= isAdmin() ? 'admin' : '' ?>">
                        <?= strtoupper(substr($username, 0, 1)) ?>
                    </div>
                    <div class="user-details">
                        <div class="username"><?= htmlspecialchars($username) ?></div>
                        <span class="user-role">
                            <?php if (isAdmin()): ?>
                                <span style="color: #28a745;">Administrator</span>
                            <?php elseif (isUser()): ?>
                                <span style="color: var(--primary);">Customer</span>
                            <?php else: ?>
                                <span>Guest</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
                
                <!-- 登出/登录按钮 -->
                <?php if ($show_logout): ?>
                    <!-- 已登录用户：显示登出按钮 -->
                    <button onclick="logout()" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                <?php else: ?>
                    <!-- 未登录用户：显示登录按钮 -->
                    <a href="user-login.php" class="login-btn">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- 移动端菜单样式 -->
    <style>
        .mobile-menu {
            display: none;
            background: white;
            box-shadow: var(--shadow);
            padding: 1rem;
            position: fixed;
            top: 70px;
            left: 0;
            right: 0;
            z-index: 999;
            max-height: calc(100vh - 70px);
            overflow-y: auto;
        }
        
        .mobile-menu.active {
            display: block;
        }
        
        .mobile-nav-link {
            display: flex;
            align-items: center;
            padding: 1rem;
            color: #495057;
            text-decoration: none;
            border-bottom: 1px solid #e9ecef;
            font-weight: 500;
            gap: 0.75rem;
        }
        
        .mobile-nav-link:hover {
            background: #f8f9fa;
            color: var(--primary);
        }
        
        .mobile-nav-link.external {
            color: #28a745;
        }
        
        .mobile-nav-link.external:hover {
            color: #218838;
        }
        
        .mobile-dropdown {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .mobile-dropdown-title {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }
        
        .mobile-dropdown-content {
            padding-left: 1rem;
            display: none;
        }
        
        .mobile-dropdown.active .mobile-dropdown-content {
            display: block;
        }
        
        .mobile-user-info {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: var(--radius);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .mobile-logout-btn {
            display: block;
            width: 100%;
            padding: 1rem;
            background: var(--danger);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            text-align: center;
            margin-top: 1rem;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .mobile-login-btn {
            display: block;
            width: 100%;
            padding: 1rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            text-align: center;
            margin-top: 1rem;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
    </style>

    <!-- 移动端菜单 -->
    <div class="mobile-menu" id="mobileMenu">
        <!-- 用户信息（移动端） -->
        <div class="mobile-user-info">
            <div class="user-avatar <?= isAdmin() ? 'admin' : '' ?>">
                <?= strtoupper(substr($username, 0, 1)) ?>
            </div>
            <div>
                <div style="font-weight: 600; color: #495057;"><?= htmlspecialchars($username) ?></div>
                <div style="font-size: 0.85rem; color: var(--muted);">
                    <?php if (isAdmin()): ?>
                        <span style="color: #28a745;">Administrator</span>
                    <?php elseif (isUser()): ?>
                        <span style="color: var(--primary);">Customer</span>
                    <?php else: ?>
                        <span>Guest</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <a href="index.php" class="mobile-nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>">
            <i class="fas fa-home"></i> Home
        </a>
        
        <a href="products.php" class="mobile-nav-link <?= $current_page == 'products.php' ? 'active' : '' ?>">
            <i class="fas fa-shopping-bag"></i> Products
        </a>
        
        <!-- 新增的移动端页面链接 -->
        <a href="order-history.php" class="mobile-nav-link <?= $current_page == 'order-history.php' ? 'active' : '' ?>">
            <i class="fas fa-history"></i> Order History
        </a>
        
        <a href="forum.php" class="mobile-nav-link <?= $current_page == 'forum.php' ? 'active' : '' ?>">
            <i class="fas fa-comments"></i> Forum
        </a>
        
        <!-- 关于和职业的移动端下拉菜单 -->
        <div class="mobile-dropdown" id="aboutMobileMenu">
            <div class="mobile-dropdown-title" onclick="toggleMobileDropdown('aboutMobileMenu')">
                <span><i class="fas fa-info-circle"></i> About & Careers</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="mobile-dropdown-content">
                <a href="about.php" class="mobile-nav-link" style="padding-left: 2rem;">
                    <i class="fas fa-building"></i> About Us
                </a>
                <a href="careers.php" class="mobile-nav-link" style="padding-left: 2rem;">
                    <i class="fas fa-briefcase"></i> Careers
                </a>
            </div>
        </div>
        
        <!-- 外部链接的移动端下拉菜单 -->
        <div class="mobile-dropdown" id="externalMobileMenu">
            <div class="mobile-dropdown-title" onclick="toggleMobileDropdown('externalMobileMenu')">
                <span><i class="fas fa-external-link-alt"></i> External Links</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="mobile-dropdown-content">
                <a href="http://bgg.kesug.com/Merlin/contact-list/" 
                   target="_blank"
                   class="mobile-nav-link external" 
                   style="padding-left: 2rem;">
                    <i class="fas fa-users"></i> Customer
                </a>
                <a href="http://bgg.kesug.com/Merlin/56-2/" 
                   target="_blank"
                   class="mobile-nav-link external" 
                   style="padding-left: 2rem;">
                    <i class="fas fa-envelope"></i> Contact
                </a>
            </div>
        </div>
        
        <!-- 管理员菜单（移动端） -->
        <?php if (isAdmin()): ?>
        <div class="mobile-dropdown" id="adminMobileMenu">
            <div class="mobile-dropdown-title" onclick="toggleMobileDropdown('adminMobileMenu')">
                <span>
                    <i class="fas fa-crown"></i> Admin Panel
                    <span style="background: #28a745; color: white; font-size: 0.7rem; padding: 0.2rem 0.6rem; border-radius: 10px; margin-left: 0.5rem;">ADMIN</span>
                </span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="mobile-dropdown-content">
                <a href="dashboard.php" class="mobile-nav-link" style="padding-left: 2rem;">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="dashboard.php?page=products" class="mobile-nav-link" style="padding-left: 2rem;">
                    <i class="fas fa-box"></i> Manage Products
                </a>
                <a href="dashboard.php?page=orders" class="mobile-nav-link" style="padding-left: 2rem;">
                    <i class="fas fa-shopping-cart"></i> Manage Orders
                </a>
                <a href="dashboard.php?page=users" class="mobile-nav-link" style="padding-left: 2rem;">
                    <i class="fas fa-users"></i> Manage Users
                </a>
                <a href="dashboard.php?page=settings" class="mobile-nav-link" style="padding-left: 2rem;">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <a href="admin-login.php" class="mobile-nav-link" style="padding-left: 2rem;">
                    <i class="fas fa-user-cog"></i> Admin Login
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <a href="cart.php" class="mobile-nav-link <?= $current_page == 'cart.php' ? 'active' : '' ?>">
            <i class="fas fa-shopping-cart"></i> Shopping Cart
            <?php if ($cartTotalCount > 0): ?>
                <span class="cart-count" id="mobileCartCount" style="position: static; margin-left: auto; display: inline-flex;"><?php echo $cartTotalCount; ?></span>
            <?php endif; ?>
        </a>
        
        <!-- 登出/登录按钮（移动端） -->
        <?php if ($show_logout): ?>
            <button onclick="logout()" class="mobile-logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        <?php else: ?>
            <!-- 修改这里：指向 user-login.php -->
            <a href="user-login.php" class="mobile-login-btn">
                <i class="fas fa-sign-in-alt"></i> Login / Register
            </a>
        <?php endif; ?>
    </div>

    <!-- 脚本 -->
    <script>
        // 切换移动端菜单
        function toggleMobileMenu() {
            const mobileMenu = document.getElementById('mobileMenu');
            mobileMenu.classList.toggle('active');
        }
        
        // 切换移动端下拉菜单
        function toggleMobileDropdown(id) {
            const dropdown = document.getElementById(id);
            dropdown.classList.toggle('active');
        }
        
        // 登出函数
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                // 直接跳转到登出页面
                window.location.href = 'logout.php';
            }
        }
        
        // 页面加载完成后执行
        document.addEventListener('DOMContentLoaded', function() {
            // 为当前页面链接添加active类
            const currentPage = '<?= $current_page ?>';
            const navLinks = document.querySelectorAll('.nav-link, .mobile-nav-link');
            
            navLinks.forEach(link => {
                const href = link.getAttribute('href');
                if (href && href.includes(currentPage) && !href.includes('#')) {
                    link.classList.add('active');
                }
            });
            
            // 关闭移动端菜单当点击外部
            document.addEventListener('click', function(event) {
                const mobileMenu = document.getElementById('mobileMenu');
                const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
                
                if (mobileMenu && mobileMenuBtn && 
                    !mobileMenu.contains(event.target) && 
                    !mobileMenuBtn.contains(event.target) &&
                    mobileMenu.classList.contains('active')) {
                    mobileMenu.classList.remove('active');
                }
            });
        });
        
        // 函数：更新购物车数量显示
        function updateCartCountDisplay() {
            const cartCountElements = document.querySelectorAll('.cart-count');
            const cartTotal = <?php echo $cartTotalCount; ?>;
            
            cartCountElements.forEach(element => {
                if (cartTotal > 0) {
                    element.textContent = cartTotal;
                    element.style.display = 'flex';
                } else {
                    element.style.display = 'none';
                }
            });
        }
        
        // 页面加载时更新购物车数量显示
        updateCartCountDisplay();
    </script>
</body>
</html>