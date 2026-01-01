<?php
// view-order.php - 查看订单详情
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . '/config.php';

// 启动会话
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 检查用户是否登录
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
    header('Location: user-login.php');
    exit;
}

// 获取订单ID
$order_id = $_GET['id'] ?? 0;
if (!$order_id) {
    header('Location: order-history.php');
    exit;
}

// 获取数据库连接
$pdo = getDatabaseConnection();
if (!$pdo) {
    die("Database connection failed. Please check your config.php settings.");
}

// 获取订单详情
$order = null;
$order_items = [];
$user_id = $_SESSION['user_id'];

try {
    // 查询订单信息
    $order_sql = "SELECT o.*, u.username, u.email 
                  FROM orders o 
                  JOIN users u ON o.user_id = u.id 
                  WHERE o.id = :order_id AND o.user_id = :user_id";
    
    $order_stmt = $pdo->prepare($order_sql);
    $order_stmt->execute([
        ':order_id' => $order_id,
        ':user_id' => $user_id
    ]);
    
    $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        // 如果订单不存在或不属于当前用户
        header('Location: order-history.php?error=order_not_found');
        exit;
    }
    
    // 查询订单项目
    $items_sql = "SELECT oi.*, p.name as product_name, p.image_url, p.product_code, 
                         (oi.quantity * oi.unit_price) as subtotal
                  FROM order_items oi 
                  LEFT JOIN products p ON oi.product_id = p.id 
                  WHERE oi.order_id = :order_id";
    
    $items_stmt = $pdo->prepare($items_sql);
    $items_stmt->execute([':order_id' => $order_id]);
    $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 计算订单总金额
    $subtotal = 0;
    foreach ($order_items as $item) {
        $item_subtotal = $item['quantity'] * $item['unit_price'];
        $subtotal += $item_subtotal;
    }
    
    // 计算运费和税费（这里可以根据实际业务逻辑调整）
    $shipping = 10.00; // 固定运费
    $tax_rate = 0.08; // 8%税率
    $tax = $subtotal * $tax_rate;
    $total = $subtotal + $shipping + $tax;
    
} catch (Exception $e) {
    die("Error loading order details: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - TechStore</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            line-height: 1.6;
            color: #333;
        }
        
        .order-details-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 20px;
        }
        
        .back-link {
            margin-bottom: 1.5rem;
        }
        
        .back-link a {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: color 0.3s;
        }
        
        .back-link a:hover {
            color: #0056b3;
            text-decoration: underline;
        }
        
        .order-card {
            background: white;
            border-radius: 12px;
            padding: 2.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .order-header {
            margin-bottom: 2.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #eaeaea;
        }
        
        .order-header h1 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-size: 2rem;
        }
        
        .order-meta {
            display: flex;
            gap: 2rem;
            margin-top: 1rem;
            color: #666;
            flex-wrap: wrap;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .section-title {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eaeaea;
            font-size: 1.4rem;
        }
        
        .order-items {
            margin: 2.5rem 0;
        }
        
        .item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.2rem 0;
            border-bottom: 1px solid #eaeaea;
        }
        
        .item-row:last-child {
            border-bottom: none;
        }
        
        .item-info {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .item-image {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            background: #f8f9fa;
        }
        
        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .item-details h4 {
            margin-bottom: 5px;
            color: #2c3e50;
        }
        
        .item-meta {
            font-size: 0.9rem;
            color: #666;
            display: flex;
            gap: 1rem;
            margin-top: 5px;
        }
        
        .item-price {
            font-weight: 700;
            font-size: 1.2rem;
            color: #2c3e50;
            min-width: 120px;
            text-align: right;
        }
        
        .order-summary {
            background: #f8f9fa;
            padding: 1.8rem;
            border-radius: 10px;
            margin-top: 2.5rem;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.8rem;
            padding-bottom: 0.8rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        .summary-row:last-child {
            border-bottom: none;
        }
        
        .total-row {
            font-size: 1.3rem;
            font-weight: bold;
            padding-top: 1rem;
            margin-top: 1rem;
            border-top: 2px solid #007bff;
        }
        
        .customer-info {
            background: #e9f7fe;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .customer-info h3 {
            margin-bottom: 1rem;
            color: #0066cc;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .info-item {
            margin-bottom: 0.5rem;
        }
        
        .info-label {
            font-weight: 600;
            color: #495057;
            margin-right: 5px;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2.5rem;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            border: none;
            font-size: 1rem;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,123,255,0.3);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(108,117,125,0.3);
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(40,167,69,0.3);
        }
        
        @media (max-width: 768px) {
            .order-card {
                padding: 1.5rem;
            }
            
            .item-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .item-image {
                width: 100%;
                height: 200px;
            }
            
            .item-price {
                text-align: left;
                margin-top: 0.5rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="order-details-container">
        <div class="back-link">
            <a href="order-history.php">
                <i class="fas fa-arrow-left"></i> Back to Order History
            </a>
        </div>
        
        <div class="order-card">
            <div class="order-header">
                <h1>Order #<?php echo htmlspecialchars($order['order_number']); ?></h1>
                <div class="order-meta">
                    <div>
                        <i class="far fa-calendar"></i>
                        <strong>Placed on:</strong> <?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?>
                    </div>
                    <div>
                        <i class="fas fa-user"></i>
                        <strong>Customer:</strong> <?php echo htmlspecialchars($order['username']); ?>
                    </div>
                    <div>
                        <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                            <?php echo htmlspecialchars(ucfirst($order['status'])); ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="customer-info">
                <h3><i class="fas fa-user-circle"></i> Customer Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Name:</span>
                        <?php echo htmlspecialchars($order['username']); ?>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email:</span>
                        <?php echo htmlspecialchars($order['email']); ?>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Order ID:</span>
                        #<?php echo $order['id']; ?>
                    </div>
                    <?php if ($order['shipping_address']): ?>
                    <div class="info-item">
                        <span class="info-label">Shipping Address:</span>
                        <?php echo htmlspecialchars($order['shipping_address']); ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($order['payment_method']): ?>
                    <div class="info-item">
                        <span class="info-label">Payment Method:</span>
                        <?php echo htmlspecialchars($order['payment_method']); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="order-items">
                <h3 class="section-title"><i class="fas fa-shopping-cart"></i> Order Items</h3>
                
                <?php if (empty($order_items)): ?>
                    <p style="text-align: center; color: #666; padding: 2rem;">No items found in this order.</p>
                <?php else: ?>
                    <?php foreach ($order_items as $item): ?>
                        <div class="item-row">
                            <div class="item-info">
                                <div class="item-image">
                                    <img src="<?php echo htmlspecialchars($item['image_url'] ?: 'https://images.unsplash.com/photo-1498049794561-7780e7231661?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80'); ?>" 
                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                         onerror="this.src='https://images.unsplash.com/photo-1498049794561-7780e7231661?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80'">
                                </div>
                                <div class="item-details">
                                    <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                    <?php if ($item['product_code']): ?>
                                        <p style="color: #666; font-size: 0.9rem;">Code: <?php echo htmlspecialchars($item['product_code']); ?></p>
                                    <?php endif; ?>
                                    <div class="item-meta">
                                        <span>Quantity: <?php echo $item['quantity']; ?></span>
                                        <span>Unit Price: $<?php echo number_format($item['unit_price'], 2); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="item-price">
                                $<?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="order-summary">
                <h3 class="section-title"><i class="fas fa-file-invoice"></i> Order Summary</h3>
                <div class="summary-row">
                    <span>Subtotal (<?php echo count($order_items); ?> items):</span>
                    <span>$<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Shipping:</span>
                    <span>$<?php echo number_format($shipping, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Tax (8%):</span>
                    <span>$<?php echo number_format($tax, 2); ?></span>
                </div>
                <div class="summary-row total-row">
                    <span>Total:</span>
                    <span>$<?php echo number_format($total, 2); ?></span>
                </div>
                <?php if ($order['total_amount'] > 0): ?>
                <div class="summary-row" style="color: #666; font-size: 0.9rem;">
                    <span>Database Recorded Total:</span>
                    <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="action-buttons">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Print Invoice
                </button>
                <a href="order-history.php" class="btn btn-secondary">
                    <i class="fas fa-list"></i> View All Orders
                </a>
                <a href="products.php" class="btn btn-success">
                    <i class="fas fa-shopping-bag"></i> Continue Shopping
                </a>
            </div>
        </div>
    </div>

    <script>
        // 添加一些交互效果
        document.addEventListener('DOMContentLoaded', function() {
            // 打印按钮添加确认提示
            const printBtn = document.querySelector('button[onclick="window.print()"]');
            if (printBtn) {
                printBtn.addEventListener('click', function(e) {
                    if (!confirm('Are you ready to print the invoice?')) {
                        e.preventDefault();
                    }
                });
            }
            
            // 图片加载失败处理
            document.querySelectorAll('.item-image img').forEach(img => {
                img.onerror = function() {
                    this.src = 'https://images.unsplash.com/photo-1498049794561-7780e7231661?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80';
                };
            });
        });
    </script>
</body>
</html>