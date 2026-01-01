<?php
// dashboard.php - 精简版管理员面板
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// 包含配置文件
require_once __DIR__ . '/config.php';

// 检查管理员权限
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}

// 获取数据库连接
$pdo = getDatabaseConnection();
if (!$pdo) {
    die("Database connection failed. Please check your config.php settings.");
}

// 定义当前页面
$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// 处理登出
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header('Location: admin-login.php');
    exit;
}

// 处理消息
$success_message = '';
$error_message = '';
if (isset($_SESSION['success'])) {
    $success_message = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error_message = $_SESSION['error'];
    unset($_SESSION['error']);
}

// 仪表板页面 - 获取统计数据
if ($current_page == 'dashboard') {
    try {
        // 产品总数
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
        $total_products = $stmt->fetch()['total'] ?? 0;
        
        // 订单统计
        $order_stmt = $pdo->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
        $order_stats = $order_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $total_orders = 0;
        $pending_orders = 0;
        foreach ($order_stats as $stat) {
            $total_orders += $stat['count'];
            if ($stat['status'] === 'pending') {
                $pending_orders = $stat['count'];
            }
        }
        
        // 用户总数
        $user_stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
        $total_users = $user_stmt->fetch()['total'] ?? 0;
        
        // 总收入
        $revenue_stmt = $pdo->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed'");
        $total_revenue = $revenue_stmt->fetch()['total'] ?? 0;
        
        // 最近产品
        $products_stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 5");
        $recent_products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        $total_products = $total_orders = $pending_orders = $total_users = $total_revenue = 0;
        $recent_products = [];
    }
}

// 产品管理功能
if ($current_page == 'products') {
    // 处理删除产品
    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
        try {
            $delete_id = intval($_GET['delete']);
            
            // 先获取产品图片路径
            $stmt = $pdo->prepare("SELECT image_url FROM products WHERE id = ?");
            $stmt->execute([$delete_id]);
            $product = $stmt->fetch();
            
            // 删除产品图片文件
            if ($product && !empty($product['image_url']) && file_exists($product['image_url'])) {
                @unlink($product['image_url']);
            }
            
            // 删除产品记录
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$delete_id]);
            
            $_SESSION['success'] = 'Product deleted successfully!';
            header('Location: dashboard.php?page=products');
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = 'Failed to delete product: ' . $e->getMessage();
            header('Location: dashboard.php?page=products');
            exit;
        }
    }
    
    // 处理添加/编辑产品
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['product_action'])) {
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $name = trim($_POST['name']);
        $price = floatval($_POST['price']);
        $category = trim($_POST['category']);
        $stock = intval($_POST['stock']);
        $description = trim($_POST['description']);
        
        // 生成唯一的产品代码
        if ($product_id > 0) {
            // 编辑时：检查是否有传product_code，没有就从数据库获取
            if (!empty($_POST['product_code'])) {
                $product_code = trim($_POST['product_code']);
            } else {
                // 从数据库获取现有的产品代码
                $stmt = $pdo->prepare("SELECT product_code FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $existing = $stmt->fetch();
                $product_code = $existing['product_code'] ?? '';
                
                // 如果没有产品代码，生成一个
                if (empty($product_code)) {
                    $product_code = 'PROD-' . date('YmdHis') . '-' . mt_rand(1000, 9999);
                }
            }
        } else {
            // 添加时：总是生成新的唯一产品代码
            $product_code = 'PROD-' . date('YmdHis') . '-' . mt_rand(1000, 9999);
            
            // 确保代码唯一，如果重复就重新生成
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE product_code = ?");
            $stmt->execute([$product_code]);
            while ($stmt->fetchColumn() > 0) {
                $product_code = 'PROD-' . date('YmdHis') . '-' . mt_rand(1000, 9999);
                $stmt->execute([$product_code]);
            }
        }
        
        // 处理图片上传
        $image_url = '';
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
            $upload_dir = 'uploads/products/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['product_image']['name']);
            $target_file = $upload_dir . $file_name;
            
            // 验证文件类型
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $file_ext = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            
            if (in_array($file_ext, $allowed_types)) {
                if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file)) {
                    $image_url = $target_file;
                    
                    // 如果编辑产品，删除旧的图片
                    if ($product_id > 0 && !empty($_POST['old_image_url'])) {
                        $old_image = $_POST['old_image_url'];
                        if (file_exists($old_image) && $old_image != $target_file) {
                            @unlink($old_image);
                        }
                    }
                }
            }
        } elseif (!empty($_POST['old_image_url'])) {
            // 如果没有上传新图片，保留旧图片
            $image_url = $_POST['old_image_url'];
        }
        
        try {
            if ($product_id > 0) {
                // 更新产品
                if (!empty($image_url)) {
                    $sql = "UPDATE products SET name=?, price=?, category=?, stock=?, description=?, image_url=?, product_code=? WHERE id=?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$name, $price, $category, $stock, $description, $image_url, $product_code, $product_id]);
                } else {
                    $sql = "UPDATE products SET name=?, price=?, category=?, stock=?, description=?, product_code=? WHERE id=?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$name, $price, $category, $stock, $description, $product_code, $product_id]);
                }
                $_SESSION['success'] = 'Product updated successfully!';
            } else {
                // 添加新产品
                $sql = "INSERT INTO products (name, price, category, stock, description, image_url, product_code, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$name, $price, $category, $stock, $description, $image_url, $product_code]);
                $_SESSION['success'] = 'Product added successfully!';
            }
            
            header('Location: dashboard.php?page=products');
            exit;
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Failed to save product: ' . $e->getMessage();
            header('Location: dashboard.php?page=products');
            exit;
        }
    }
    
    // 检查是否有编辑请求
    $editing_product = null;
    if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
        try {
            $edit_id = intval($_GET['edit']);
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$edit_id]);
            $editing_product = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $_SESSION['error'] = 'Failed to load product for editing: ' . $e->getMessage();
        }
    }
    
    // 获取所有产品
    try {
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        
        $sql = "SELECT * FROM products";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " WHERE name LIKE ? OR description LIKE ?";
            $search_term = "%$search%";
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $all_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        $all_products = [];
    }
}

// 订单管理功能
if ($current_page == 'orders') {
    // 更新订单状态
    if (isset($_GET['update_status']) && isset($_GET['order_id']) && isset($_GET['new_status'])) {
        try {
            $order_id = intval($_GET['order_id']);
            $new_status = $_GET['new_status'];
            
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $order_id]);
            
            $_SESSION['success'] = 'Order status updated successfully!';
            header('Location: dashboard.php?page=orders');
            exit;
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Failed to update order status: ' . $e->getMessage();
            header('Location: dashboard.php?page=orders');
            exit;
        }
    }
    
    // 获取所有订单
    try {
        $sql = "SELECT * FROM orders ORDER BY created_at DESC";
        $stmt = $pdo->query($sql);
        $all_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        $all_orders = [];
    }
}

// 用户管理功能
if ($current_page == 'users') {
    // 获取所有用户
    try {
        $sql = "SELECT * FROM users ORDER BY created_at DESC";
        $stmt = $pdo->query($sql);
        $all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        $all_users = [];
    }
}

// 用户信息
$username = $_SESSION['username'] ?? 'Admin';
$email = $_SESSION['email'] ?? 'admin@techstore.com';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TechStore</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; }
        
        .admin-header {
            background: #2c3e50;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .user-info { display: flex; align-items: center; gap: 15px; }
        .logout-btn { background: #dc3545; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; }
        
        .admin-container { display: flex; min-height: 100vh; }
        
        .sidebar {
            width: 200px;
            background: white;
            border-right: 1px solid #ddd;
            padding: 1rem 0;
        }
        
        .sidebar ul { list-style: none; }
        .sidebar a {
            display: block;
            padding: 12px 20px;
            color: #555;
            text-decoration: none;
            border-left: 4px solid transparent;
        }
        .sidebar a:hover, .sidebar a.active {
            background: #f8f9fa;
            color: #28a745;
            border-left-color: #28a745;
        }
        
        .main-content { flex: 1; padding: 2rem; }
        
        .alert {
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        table {
            width: 100%;
            background: white;
            border-collapse: collapse;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th { background: #f8f9fa; }
        tr:hover { background: #f8f9fa; }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            margin: 2px;
        }
        .btn-danger { background: #dc3545; }
        .btn-info { background: #17a2b8; }
        .btn-warning { background: #ffc107; color: #000; }
        
        .form-group { margin-bottom: 15px; }
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .admin-container { flex-direction: column; }
            .sidebar { width: 100%; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1><i class="fas fa-crown"></i> TechStore Admin</h1>
        <div class="user-info">
            <span><?php echo htmlspecialchars($username); ?></span>
            <a href="?action=logout" class="logout-btn" onclick="return confirm('Logout?')">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
    
    <div class="admin-container">
        <nav class="sidebar">
            <ul>
                <li><a href="?page=dashboard" class="<?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a></li>
                <li><a href="?page=products" class="<?php echo $current_page == 'products' ? 'active' : ''; ?>">
                    <i class="fas fa-box"></i> Products
                </a></li>
                <li><a href="?page=orders" class="<?php echo $current_page == 'orders' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i> Orders
                </a></li>
                <li><a href="?page=users" class="<?php echo $current_page == 'users' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> Users
                </a></li>
            </ul>
        </nav>
        
        <main class="main-content">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($current_page == 'dashboard'): ?>
                <h2>Dashboard Overview</h2>
                <p>Welcome back, <?php echo htmlspecialchars($username); ?>!</p>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?php echo $total_products; ?></h3>
                        <p>Total Products</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $total_orders; ?></h3>
                        <p>Total Orders</p>
                        <small><?php echo $pending_orders; ?> pending</small>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $total_users; ?></h3>
                        <p>Registered Users</p>
                    </div>
                    <div class="stat-card">
                        <h3>$<?php echo number_format($total_revenue, 2); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
                
                <h3>Recent Products</h3>
                <?php if (!empty($recent_products)): ?>
                    <table>
                        <tr><th>Name</th><th>Price</th><th>Stock</th><th>Added</th></tr>
                        <?php foreach ($recent_products as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td>$<?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo $product['stock']; ?></td>
                            <td><?php echo date('Y-m-d', strtotime($product['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <p>No products found.</p>
                <?php endif; ?>
                
            <?php elseif ($current_page == 'products'): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2>Product Management</h2>
                    <button onclick="showProductModal()" class="btn">
                        <i class="fas fa-plus"></i> Add Product
                    </button>
                </div>
                
                <form method="GET" style="margin-bottom: 20px; display: flex; gap: 10px;">
                    <input type="hidden" name="page" value="products">
                    <input type="text" name="search" placeholder="Search products..." 
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                           class="form-control" style="flex: 1;">
                    <button type="submit" class="btn">Search</button>
                    <a href="?page=products" class="btn">Reset</a>
                </form>
                
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Category</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                    <?php if (empty($all_products)): ?>
                        <tr><td colspan="7" style="text-align: center;">No products found</td></tr>
                    <?php else: ?>
                        <?php foreach ($all_products as $product): ?>
                        <tr>
                            <td><?php echo $product['id']; ?></td>
                            <td><small><?php echo htmlspecialchars($product['product_code'] ?? 'N/A'); ?></small></td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td>$<?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                            <td><?php echo $product['stock']; ?></td>
                            <td>
                                <button onclick="editProduct(<?php echo $product['id']; ?>)" class="btn btn-info">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?page=products&delete=<?php echo $product['id']; ?>" 
                                   class="btn btn-danger"
                                   onclick="return confirm('Delete this product?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </table>
                
            <?php elseif ($current_page == 'orders'): ?>
                <h2>Order Management</h2>
                
                <table>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                    <?php if (empty($all_orders)): ?>
                        <tr><td colspan="6" style="text-align: center;">No orders found</td></tr>
                    <?php else: ?>
                        <?php foreach ($all_orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td>User #<?php echo $order['user_id']; ?></td>
                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <select onchange="updateOrderStatus(<?php echo $order['id']; ?>, this.value)" style="padding: 5px; border-radius: 4px;">
                                    <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </td>
                            <td><?php echo date('Y-m-d', strtotime($order['created_at'])); ?></td>
                            <td>
                                <button onclick="viewOrder(<?php echo $order['id']; ?>)" class="btn btn-info">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </table>
                
            <?php elseif ($current_page == 'users'): ?>
                <h2>User Management</h2>
                
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                    <?php if (empty($all_users)): ?>
                        <tr><td colspan="5" style="text-align: center;">No users found</td></tr>
                    <?php else: ?>
                        <?php foreach ($all_users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                            <td>
                                <button onclick="viewUser(<?php echo $user['id']; ?>)" class="btn btn-info">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </table>
            <?php endif; ?>
        </main>
    </div>
    
    <!-- 产品模态框 -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add Product</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" action="?page=products" enctype="multipart/form-data">
                <input type="hidden" name="product_action" value="save">
                <input type="hidden" id="product_id" name="product_id" value="0">
                <input type="hidden" id="old_image_url" name="old_image_url" value="">
                
                <div class="form-group">
                    <label>Product Name *</label>
                    <input type="text" id="product_name" name="name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Product Code</label>
                    <input type="text" id="product_code" name="product_code" class="form-control" 
                           placeholder="Auto-generated if empty">
                    <small style="color: #666;">Leave empty for auto-generated code</small>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Price ($) *</label>
                        <input type="number" id="product_price" name="price" step="0.01" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Stock *</label>
                        <input type="number" id="product_stock" name="stock" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Category *</label>
                    <input type="text" id="product_category" name="category" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea id="product_description" name="description" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Product Image</label>
                    <input type="file" name="product_image" accept="image/*" class="form-control">
                    <div id="currentImage" style="margin-top: 10px;"></div>
                </div>
                
                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button type="submit" class="btn">Save Product</button>
                    <button type="button" onclick="closeModal()" class="btn btn-danger">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showProductModal() {
            document.getElementById('modalTitle').textContent = 'Add Product';
            document.getElementById('product_id').value = '0';
            document.getElementById('product_name').value = '';
            document.getElementById('product_code').value = '';
            document.getElementById('product_price').value = '';
            document.getElementById('product_category').value = '';
            document.getElementById('product_stock').value = '10';
            document.getElementById('product_description').value = '';
            document.getElementById('old_image_url').value = '';
            document.getElementById('currentImage').innerHTML = '';
            document.getElementById('productModal').style.display = 'flex';
        }
        
        function editProduct(productId) {
            // 通过URL参数打开编辑模式
            window.location.href = '?page=products&edit=' + productId;
        }
        
        function closeModal() {
            document.getElementById('productModal').style.display = 'none';
        }
        
        function updateOrderStatus(orderId, status) {
            if (confirm('Update order status to ' + status + '?')) {
                window.location.href = '?page=orders&update_status=1&order_id=' + orderId + '&new_status=' + status;
            }
        }
        
        function viewOrder(orderId) {
            alert('View order details for #' + orderId + '\n\nThis feature would show detailed order information.');
        }
        
        function viewUser(userId) {
            alert('View user details for #' + userId + '\n\nThis feature would show user profile information.');
        }
        
        // 关闭模态框当点击外部
        document.getElementById('productModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
        
        // 页面加载时，如果正在编辑产品，自动打开模态框
        <?php if ($editing_product): ?>
        window.addEventListener('load', function() {
            document.getElementById('modalTitle').textContent = 'Edit Product';
            document.getElementById('product_id').value = '<?php echo $editing_product['id']; ?>';
            document.getElementById('product_name').value = '<?php echo addslashes($editing_product['name']); ?>';
            document.getElementById('product_code').value = '<?php echo addslashes($editing_product['product_code'] ?? ''); ?>';
            document.getElementById('product_price').value = '<?php echo $editing_product['price']; ?>';
            document.getElementById('product_category').value = '<?php echo addslashes($editing_product['category']); ?>';
            document.getElementById('product_stock').value = '<?php echo $editing_product['stock']; ?>';
            document.getElementById('product_description').value = '<?php echo addslashes($editing_product['description'] ?? ''); ?>';
            document.getElementById('old_image_url').value = '<?php echo addslashes($editing_product['image_url'] ?? ''); ?>';
            
            // 显示当前图片
            <?php if (!empty($editing_product['image_url'])): ?>
            document.getElementById('currentImage').innerHTML = 
                '<p>Current Image:</p>' +
                '<img src="<?php echo $editing_product['image_url']; ?>" style="max-width: 100px; max-height: 100px; border-radius: 5px;">';
            <?php endif; ?>
            
            document.getElementById('productModal').style.display = 'flex';
        });
        <?php endif; ?>
        
        // 自动隐藏消息
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0.5';
                setTimeout(() => alert.style.display = 'none', 500);
            });
        }, 5000);
    </script>
</body>
</html>