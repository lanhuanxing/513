<?php
// checkout.php - 动态结账页面
require_once __DIR__ . '/config.php';

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

if (!$isLoggedIn) {
    header('Location: user-login.php');
    exit;
}

// 检查购物车是否为空
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php?error=empty_cart');
    exit;
}

// 获取数据库连接
$pdo = getDatabaseConnection();
if (!$pdo) {
    die("Database connection failed. Please check your config.php settings.");
}

// 获取用户信息
$user_id = $_SESSION['user_id'] ?? 0;
$username = $_SESSION['username'] ?? 'Guest';
$email = $_SESSION['email'] ?? '';

// 获取产品数据从数据库
$products = [];
try {
    $sql = "SELECT * FROM products ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $productsResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 转换为以ID为键的数组
    foreach ($productsResult as $product) {
        $products[$product['id']] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => floatval($product['price']),
            'image_url' => $product['image_url'] ?: 'https://images.unsplash.com/photo-1498049794561-7780e7231661?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
            'stock' => intval($product['stock']), // 注意：你的数据库使用 stock 字段，不是 stock_quantity
            'stock_quantity' => intval($product['stock']), // 为了兼容性
            'product_code' => $product['product_code'] ?? '',
            'description' => $product['description'] ?? '',
            'brand' => $product['brand'] ?? '',
            'category' => $product['category'] ?? '',
            'specifications' => $product['specifications'] ?? ''
        ];
    }
} catch (Exception $e) {
    die("Error loading products: " . $e->getMessage());
}

// 计算总金额和订单项目，同时检查库存
$total = 0;
$order_items = [];
$items_count = 0;
$hasOutOfStock = false;
$insufficientStockItems = [];

foreach ($_SESSION['cart'] as $product_id => $item) {
    if (isset($products[$product_id])) {
        $product = $products[$product_id];
        
        // 检查库存 - 使用 stock 字段
        if ($product['stock'] < $item['quantity']) {
            $hasOutOfStock = true;
            $insufficientStockItems[] = [
                'name' => $product['name'],
                'requested' => $item['quantity'],
                'available' => $product['stock']
            ];
            
            // 如果库存不足，调整数量到最大可用数量
            $availableQty = min($item['quantity'], $product['stock']);
        } else {
            $availableQty = $item['quantity'];
        }
        
        $subtotal = $product['price'] * $availableQty;
        $total += $subtotal;
        $items_count += $availableQty;
        
        $order_items[] = [
            'product_id' => $product_id,
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $availableQty,
            'original_quantity' => $item['quantity'], // 保存原始请求数量
            'available_quantity' => $product['stock'],
            'subtotal' => $subtotal,
            'has_stock_issue' => $product['stock'] < $item['quantity']
        ];
    } else {
        // 如果产品不存在，跳过
        unset($_SESSION['cart'][$product_id]);
    }
}

// 如果有库存不足的商品，显示错误并重定向回购物车
if ($hasOutOfStock) {
    $_SESSION['checkout_error'] = "Some items in your cart have insufficient stock.";
    $_SESSION['insufficient_stock_items'] = $insufficientStockItems;
    header('Location: cart.php?error=insufficient_stock');
    exit;
}

// 生成订单号 - 使用20位长度以匹配数据库
$order_number = 'ORD-' . date('YmdHis') . '-' . rand(100, 999);
// 确保长度不超过20个字符
if (strlen($order_number) > 20) {
    $order_number = substr($order_number, 0, 20);
}

try {
    // 开始事务
    $pdo->beginTransaction();
    
    // 1. 创建订单主记录 - 根据你的数据库结构
    $order_sql = "INSERT INTO orders (order_number, user_id, status, total_amount, shipping_address, payment_method) 
                  VALUES (:order_number, :user_id, :status, :total_amount, :shipping_address, :payment_method)";
    
    $order_stmt = $pdo->prepare($order_sql);
    $order_stmt->execute([
        ':order_number' => $order_number,
        ':user_id' => $user_id,
        ':status' => 'completed', // 使用 'completed' 而不是 'pending'
        ':total_amount' => $total,
        ':shipping_address' => NULL, // 根据你的数据库结构，可以为NULL
        ':payment_method' => 'Online Payment' // 添加默认支付方式
    ]);
    
    $order_id = $pdo->lastInsertId();
    
    // 2. 创建订单项目记录并更新库存
    foreach ($order_items as $item) {
        // 插入订单项目 - 根据你的 order_items 表结构
        $item_sql = "INSERT INTO order_items (order_id, product_id, quantity, unit_price) 
                     VALUES (:order_id, :product_id, :quantity, :unit_price)";
        $item_stmt = $pdo->prepare($item_sql);
        $item_stmt->execute([
            ':order_id' => $order_id,
            ':product_id' => $item['product_id'],
            ':quantity' => $item['quantity'],
            ':unit_price' => $item['price']
        ]);
        
        // 更新产品库存 - 注意你的数据库使用 stock 字段
        $update_stock_sql = "UPDATE products SET stock = stock - :quantity WHERE id = :product_id";
        $update_stmt = $pdo->prepare($update_stock_sql);
        $update_stmt->execute([
            ':quantity' => $item['quantity'],
            ':product_id' => $item['product_id']
        ]);
    }
    
    // 提交事务
    $pdo->commit();
    
    // 创建订单记录用于会话
    $order = [
        'order_id' => $order_id,
        'order_number' => $order_number,
        'user_id' => $user_id,
        'username' => $username,
        'email' => $email,
        'total' => $total,
        'total_amount' => $total, // 兼容字段
        'items' => $order_items,
        'items_count' => $items_count,
        'created_at' => date('Y-m-d H:i:s'),
        'status' => 'completed'
    ];
    
    // 保存订单到会话
    if (!isset($_SESSION['orders'])) {
        $_SESSION['orders'] = [];
    }
    
    $_SESSION['orders'][$order_number] = $order;
    $_SESSION['last_order'] = $order_number;
    
    // 记录成功日志
    error_log("Order created successfully: " . $order_number . " by user: " . $username);
    
} catch (Exception $e) {
    // 回滚事务
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // 记录错误
    error_log("Order creation failed: " . $e->getMessage());
    error_log("SQL: " . (isset($order_sql) ? $order_sql : 'N/A'));
    
    // 显示错误信息
    die("Error creating order: " . $e->getMessage() . ". Please try again or contact support.");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - TechStore</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .checkout-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 3rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
            text-align: center;
        }
        .success-icon {
            font-size: 5rem;
            color: #28a745;
            margin-bottom: 1.5rem;
        }
        .order-number {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            font-family: monospace;
            font-size: 1.2rem;
            margin: 1.5rem 0;
        }
        .order-details {
            text-align: left;
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 2rem 0;
        }
        .order-details h3 {
            margin-top: 0;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .order-total {
            font-size: 1.3rem;
            font-weight: 700;
            color: #212529;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid #007bff;
        }
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        .customer-info {
            background: #e9f7fe;
            padding: 1rem;
            border-radius: 8px;
            margin: 1.5rem 0;
            text-align: left;
        }
        .customer-info h4 {
            margin-top: 0;
            color: #007bff;
        }
        .order-id {
            background: #fff3cd;
            padding: 0.5rem;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="checkout-container">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h1>Order Confirmed!</h1>
        <p>Thank you for your purchase. Your order has been received and is being processed.</p>
        
        <div class="order-number">
            Order Number: <strong><?php echo htmlspecialchars($order_number); ?></strong>
        </div>
        
        <div class="customer-info">
            <h4><i class="fas fa-user"></i> Customer Information</h4>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($username); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
            <p><strong>Order Date:</strong> <?php echo date('F j, Y, g:i a'); ?></p>
            <p><strong>Order ID:</strong> <span class="order-id"><?php echo $order_id; ?></span></p>
            <p><strong>Status:</strong> <span style="color: #28a745; font-weight: 600;">Completed</span></p>
        </div>
        
        <div class="order-details">
            <h3>Order Summary</h3>
            
            <?php foreach ($order_items as $item): ?>
                <div class="order-item">
                    <div>
                        <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                        <div style="font-size: 0.9rem; color: #6c757d;">
                            Quantity: <?php echo $item['quantity']; ?> × $<?php echo number_format($item['price'], 2); ?>
                            <?php if ($item['has_stock_issue']): ?>
                                <br><span style="color: #dc3545; font-weight: 600;">
                                    <i class="fas fa-exclamation-triangle"></i> Adjusted from <?php echo $item['original_quantity']; ?> due to stock limits
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="font-weight: 600;">
                        $<?php echo number_format($item['subtotal'], 2); ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="order-total">
                <span>Order Total:</span>
                <span>$<?php echo number_format($total, 2); ?></span>
            </div>
        </div>
        
        <p><i class="fas fa-envelope"></i> A confirmation email will be sent to you shortly.</p>
        
        <div class="action-buttons">
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Back to Home
            </a>
            <a href="products.php" class="btn btn-success">
                <i class="fas fa-shopping-bag"></i> Continue Shopping
            </a>
            <a href="generate-invoice.php?order=<?php echo urlencode($order_number); ?>" class="btn btn-secondary" target="_blank">
                <i class="fas fa-file-invoice"></i> Download Invoice
            </a>
            <a href="order-history.php" class="btn" style="background: #17a2b8; color: white;">
                <i class="fas fa-eye"></i> View Order History
            </a>
        </div>
    </div>

    <script>
        // 页面加载后清空购物车
        document.addEventListener('DOMContentLoaded', function() {
            // 延迟执行，确保页面显示后再清空购物车
            setTimeout(function() {
                // 清空购物车
                fetch('cart-actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=clear_cart'
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Cart cleared after checkout:', data);
                    // 更新导航栏购物车数量
                    if (window.updateCartCountDisplay) {
                        updateCartCountDisplay();
                    }
                })
                .catch(error => {
                    console.error('Error clearing cart:', error);
                });
            }, 1000); // 1秒后执行
        });
    </script>
</body>
</html>
<?php
// 页面输出完成后，清空购物车
// 这确保即使用户禁用了JavaScript，购物车也会被清空
$_SESSION['cart'] = [];
?>