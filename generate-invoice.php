<?php
// generate-invoice.php
session_start();

// 检查用户是否登录
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
    }
}

if (!isLoggedIn()) {
    header('Location: user-login.php');
    exit;
}

// 检查是否有订单号
if (!isset($_GET['order']) || empty($_GET['order'])) {
    die('Error: No order specified.');
}

$order_number = $_GET['order'];

// 从会话中获取订单数据
if (!isset($_SESSION['orders']) || !isset($_SESSION['orders'][$order_number])) {
    // 尝试从上次订单获取
    if (isset($_SESSION['last_order']) && $_SESSION['last_order'] === $order_number && isset($_SESSION['orders'][$order_number])) {
        $order = $_SESSION['orders'][$order_number];
    } else {
        die('Error: Order not found. Order number: ' . htmlspecialchars($order_number));
    }
} else {
    $order = $_SESSION['orders'][$order_number];
}

// 验证订单数据完整性
if (!isset($order['items']) || !is_array($order['items'])) {
    die('Error: Invalid order data.');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - TechStore</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: white;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 30px;
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
        }
        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .invoice-logo {
            font-size: 20px;
            font-weight: bold;
            color: #007bff;
        }
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .info-box h3 {
            margin-top: 0;
            color: #333;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .items-table th, .items-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .items-table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        .total-section {
            text-align: right;
            margin-top: 30px;
        }
        .grand-total {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #333;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        .print-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin: 20px 0;
        }
        .print-btn:hover {
            background: #0056b3;
        }
        .back-link {
            color: #007bff;
            text-decoration: none;
            margin-top: 10px;
            display: inline-block;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <div class="invoice-logo">TechStore</div>
            <div class="invoice-title">INVOICE</div>
        </div>
        
        <div class="info-section">
            <div class="info-box">
                <h3>From:</h3>
                <p><strong>TechStore Inc.</strong></p>
                <p>123 Tech Street</p>
                <p>San Francisco, CA 94107</p>
                <p>Email: billing@techstore.com</p>
                <p>Phone: (555) 123-4567</p>
            </div>
            
            <div class="info-box">
                <h3>To:</h3>
                <p><strong><?php echo htmlspecialchars($order['username']); ?></strong></p>
                <p>Email: <?php echo htmlspecialchars($order['email']); ?></p>
                <p>Order Date: <?php echo date('F j, Y', strtotime($order['created_at'])); ?></p>
                <p>Invoice Date: <?php echo date('F j, Y'); ?></p>
            </div>
        </div>
        
        <div class="invoice-details">
            <h3>Invoice Details</h3>
            <p><strong>Invoice Number:</strong> <?php echo htmlspecialchars($order['order_number']); ?></p>
            <p><strong>Status:</strong> <span style="color: #28a745;"><?php echo htmlspecialchars($order['status']); ?></span></p>
        </div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Description</th>
                    <th>Unit Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order['items'] as $index => $item): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td>$<?php echo number_format($item['subtotal'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="total-section">
            <div style="margin-bottom: 10px;">
                <span>Subtotal:</span>
                <span>$<?php echo number_format($order['total'], 2); ?></span>
            </div>
            <div style="margin-bottom: 10px;">
                <span>Tax (10%):</span>
                <span>$<?php echo number_format($order['total'] * 0.1, 2); ?></span>
            </div>
            <div class="grand-total">
                <span>Grand Total:</span>
                <span>$<?php echo number_format($order['total'] * 1.1, 2); ?></span>
            </div>
        </div>
        
        <div class="footer">
            <p>Thank you for your business!</p>
            <p>TechStore Inc. | 123 Tech Street, San Francisco, CA 94107</p>
            <p>Phone: (555) 123-4567 | Email: info@techstore.com</p>
            <p>Terms: Payment due within 30 days</p>
        </div>
        
        <center>
            <button class="print-btn" onclick="window.print()">
                <i class="fas fa-print"></i> Print Invoice
            </button>
            <br>
            <a href="order-history.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Order History
            </a>
        </center>
    </div>
</body>
</html>