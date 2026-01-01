<?php
// order-history.php - 用户订单历史
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . '/config.php';

// 启动会话
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 检查用户是否登录
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || 
    !isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
    $_SESSION['login_redirect'] = 'order-history.php';
    header('Location: user-login.php');
    exit;
}

// 获取用户ID
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Customer';

// 获取数据库连接
$pdo = getDatabaseConnection();

// 获取用户订单
$orders = [];
if ($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM orders 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC
            LIMIT 20
        ");
        $stmt->execute([':user_id' => $user_id]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = "Error loading orders: " . $e->getMessage();
    }
} else {
    // 演示模式 - 显示示例订单
    $orders = [
        [
            'id' => 1001,
            'order_number' => 'ORD-2024-1001',
            'total_amount' => 129.99,
            'status' => 'completed',
            'created_at' => '2024-01-15 14:30:00'
        ],
        [
            'id' => 1002,
            'order_number' => 'ORD-2024-1002',
            'total_amount' => 89.50,
            'status' => 'processing',
            'created_at' => '2024-01-14 10:15:00'
        ],
        [
            'id' => 1003,
            'order_number' => 'ORD-2024-1003',
            'total_amount' => 249.99,
            'status' => 'shipped',
            'created_at' => '2024-01-10 16:45:00'
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - TechStore</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f5f5f5;
        }
        
        .order-history-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
        }
        
        .page-header {
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .page-header h1 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .welcome-message {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        
        .orders-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .table-header {
            background: #f8f9fa;
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f8f9fa;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
        }
        
        td {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        tr:hover {
            background-color: #f8f9fa;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-processing {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-shipped {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #28a745;
            color: white;
        }
        
        .btn-primary:hover {
            background: #218838;
        }
        
        .btn-view {
            background: #17a2b8;
            color: white;
        }
        
        .btn-view:hover {
            background: #138496;
        }
        
        .btn-track {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-track:hover {
            background: #e0a800;
        }
        
        @media (max-width: 768px) {
            .order-history-container {
                padding: 0 10px;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
            
            .btn {
                display: block;
                margin-bottom: 5px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- 只在body中包含一次导航栏 -->
    <?php include 'navbar.php'; ?>
    
    <div class="order-history-container">
        <div class="page-header">
            <h1>Order History</h1>
            <p>View and track your past orders</p>
        </div>
        
        <div class="welcome-message">
            <h2>Welcome back, <?php echo htmlspecialchars($username); ?>!</h2>
            <p>Here's your order history. You can view details and track your orders.</p>
        </div>
        
        <div class="orders-table">
            <div class="table-header">
                <h3 style="margin: 0;">Your Orders (<?php echo count($orders); ?>)</h3>
            </div>
            
            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>No Orders Yet</h3>
                    <p>You haven't placed any orders yet.</p>
                    <a href="products.php" class="btn btn-primary">Start Shopping</a>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($order['order_number'] ?? 'ORD-' . $order['id']); ?></strong>
                                </td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($order['created_at'])); ?><br>
                                    <small style="color: #6c757d;"><?php echo date('g:i A', strtotime($order['created_at'])); ?></small>
                                </td>
                                <td>
                                    <strong>$<?php echo number_format($order['total_amount'] ?? 0, 2); ?></strong>
                                </td>
                                <td>
                                    <?php
                                    $status = $order['status'] ?? 'pending';
                                    $status_class = 'status-' . $status;
                                    ?>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                        <a href="view-order.php?id=<?php echo $order['id']; ?>" class="btn btn-view">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <?php if (($order['status'] ?? '') === 'shipped'): ?>
                                            <a href="track-order.php?id=<?php echo $order['id']; ?>" class="btn btn-track">
                                                <i class="fas fa-truck"></i> Track
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div style="padding: 1.5rem; text-align: center; border-top: 1px solid #e9ecef;">
                    <p>Showing <?php echo count($orders); ?> orders</p>
                    <div style="margin-top: 1rem;">
                        <a href="products.php" class="btn btn-primary">Continue Shopping</a>
                        <a href="index.php" class="btn" style="background: #6c757d; color: white; margin-left: 10px;">Back to Home</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (isset($error)): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 5px; margin-top: 1rem;">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // 页面加载完成后执行
        document.addEventListener('DOMContentLoaded', function() {
            // 如果有错误消息，3秒后自动消失
            const errorMessage = document.querySelector('[style*="background: #f8d7da"]');
            if (errorMessage) {
                setTimeout(function() {
                    errorMessage.style.opacity = '0';
                    errorMessage.style.transition = 'opacity 0.5s';
                    setTimeout(function() {
                        errorMessage.remove();
                    }, 500);
                }, 3000);
            }
            
            // 确认订单操作
            const actionButtons = document.querySelectorAll('.btn');
            actionButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (this.classList.contains('btn-track')) {
                        if (!confirm('Track this order?')) {
                            e.preventDefault();
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>