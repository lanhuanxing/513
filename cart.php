<?php
// cart.php
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

if (!$isLoggedIn) {
    header('Location: user-login.php');
    exit;
}

// 获取用户信息
$username = $_SESSION['username'] ?? 'Guest';
$user_id = $_SESSION['user_id'] ?? 0;

// 获取数据库连接
$pdo = getDatabaseConnection();
if (!$pdo) {
    die("Database connection failed. Please check your config.php settings.");
}

// 获取所有产品数据
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
            'stock_quantity' => intval($product['stock']),
            'product_code' => $product['product_code'] ?? '',
            'description' => $product['description'] ?? '',
            'brand' => $product['brand'] ?? '',
            'category' => $product['category'] ?? '',
            'specifications' => $product['specifications'] ?? ''
        ];
    }
} catch (Exception $e) {
    $error_message = "Error loading products: " . $e->getMessage();
    $products = [];
}

// 获取购物车数据
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$total = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - TechStore</title>
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
        
        h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 2.5rem;
            color: var(--text);
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .cart-container {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-bottom: 3rem;
        }
        
        .cart-items {
            margin-bottom: 2rem;
        }
        
        .cart-item {
            display: flex;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
            transition: var(--transition);
        }
        
        .cart-item:hover {
            background: #f8f9fa;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .item-image {
            width: 120px;
            height: 120px;
            border-radius: 8px;
            overflow: hidden;
            margin-right: 1.5rem;
            flex-shrink: 0;
        }
        
        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .cart-item:hover .item-image img {
            transform: scale(1.05);
        }
        
        .item-details {
            flex-grow: 1;
            min-width: 0;
        }
        
        .item-name {
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: var(--text);
            font-family: 'Poppins', sans-serif;
        }
        
        .item-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            color: var(--muted);
        }
        
        .item-price {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        .stock-info {
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .in-stock {
            color: var(--accent);
            font-weight: 600;
        }
        
        .out-of-stock {
            color: var(--danger);
            font-weight: 600;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .quantity-btn {
            width: 36px;
            height: 36px;
            border: 2px solid #dee2e6;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            font-weight: 600;
        }
        
        .quantity-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-2px);
        }
        
        .quantity-input {
            width: 60px;
            text-align: center;
            border: 2px solid #dee2e6;
            border-radius: 6px;
            padding: 0.5rem;
            font-weight: 600;
            transition: var(--transition);
        }
        
        .quantity-input:focus {
            border-color: var(--primary);
            outline: none;
        }
        
        .remove-btn {
            background: var(--danger);
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }
        
        .remove-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
        }
        
        .cart-summary {
            background: #f8f9fa;
            border-radius: var(--radius);
            padding: 2rem;
            margin-top: 2rem;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #dee2e6;
            font-size: 1.1rem;
        }
        
        .summary-row.total {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text);
            border-bottom: 3px solid var(--primary);
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .checkout-btn {
            background: var(--accent);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: var(--radius);
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
            margin-top: 1rem;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        
        .checkout-btn:hover {
            background: #218838;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(40, 167, 69, 0.3);
        }
        
        .empty-cart {
            text-align: center;
            padding: 4rem 2rem;
        }
        
        .empty-cart-icon {
            font-size: 5rem;
            color: #dee2e6;
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }
        
        .empty-cart h2 {
            font-size: 2rem;
            color: var(--text);
            margin-bottom: 1rem;
        }
        
        .empty-cart p {
            color: var(--muted);
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 1rem 1.5rem;
            border-radius: var(--radius);
            margin-bottom: 2rem;
            border-left: 4px solid var(--accent);
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem 1.5rem;
            border-radius: var(--radius);
            margin-bottom: 2rem;
            border-left: 4px solid var(--danger);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            justify-content: center;
        }
        
        .btn {
            padding: 0.75rem 2rem;
            border-radius: var(--radius);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        }
        
        .btn-secondary {
            background: var(--muted);
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }
        
        /* 禁用状态样式 */
        .checkout-btn:disabled {
            background: var(--muted);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .checkout-btn:disabled:hover {
            background: var(--muted);
            transform: none;
            box-shadow: none;
        }
        
        /* 响应式设计 */
        @media (max-width: 768px) {
            .cart-item {
                flex-direction: column;
                align-items: flex-start;
                padding: 1rem;
            }
            
            .item-image {
                width: 100%;
                height: 200px;
                margin-right: 0;
                margin-bottom: 1rem;
            }
            
            .item-details {
                width: 100%;
            }
            
            .quantity-controls {
                justify-content: flex-start;
            }
            
            .remove-btn {
                width: 100%;
                justify-content: center;
                margin-top: 1rem;
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
    
    <main>
        <h1><i class="fas fa-shopping-cart"></i> Shopping Cart</h1>
        
        <?php if (isset($_SESSION['cart_success'])): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($_SESSION['cart_success']); ?>
                <?php unset($_SESSION['cart_success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <div class="cart-container">
            <?php if (empty($cart)): ?>
                <div class="empty-cart">
                    <div class="empty-cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h2>Your cart is empty</h2>
                    <p>Add some products to your cart to get started!</p>
                    <div class="action-buttons">
                        <a href="products.php" class="btn btn-primary">
                            <i class="fas fa-shopping-bag"></i> Continue Shopping
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="cart-items">
                    <?php 
                    $total = 0;
                    $hasOutOfStock = false;
                    ?>
                    
                    <?php foreach ($cart as $product_id => $item): ?>
                        <?php if (isset($products[$product_id])): ?>
                            <?php 
                            $product = $products[$product_id];
                            $subtotal = $product['price'] * $item['quantity'];
                            $total += $subtotal;
                            
                            // 检查库存
                            $isInStock = $product['stock_quantity'] > 0;
                            $availableQty = min($item['quantity'], $product['stock_quantity']);
                            
                            if (!$isInStock) {
                                $hasOutOfStock = true;
                            }
                            ?>
                            
                            <div class="cart-item" id="item-<?php echo $product_id; ?>">
                                <div class="item-image">
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         onerror="this.src='https://images.unsplash.com/photo-1498049794561-7780e7231661?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80'">
                                </div>
                                
                                <div class="item-details">
                                    <div class="item-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                    
                                    <div class="item-meta">
                                        <?php if ($product['product_code']): ?>
                                            <span>Code: <?php echo htmlspecialchars($product['product_code']); ?></span>
                                        <?php endif; ?>
                                        <?php if ($product['brand']): ?>
                                            <span>Brand: <?php echo htmlspecialchars($product['brand']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="stock-info <?php echo $isInStock ? 'in-stock' : 'out-of-stock'; ?>">
                                        <?php if ($isInStock): ?>
                                            <i class="fas fa-check-circle"></i> In Stock (<?php echo $product['stock_quantity']; ?> available)
                                        <?php else: ?>
                                            <i class="fas fa-times-circle"></i> Out of Stock
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="item-price">$<?php echo number_format($product['price'], 2); ?></div>
                                    
                                    <div class="quantity-controls">
                                        <button class="quantity-btn" 
                                                onclick="updateQuantity(<?php echo $product_id; ?>, -1)"
                                                <?php echo ($item['quantity'] <= 1) ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : ''; ?>>
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="number" 
                                               class="quantity-input" 
                                               value="<?php echo $availableQty; ?>" 
                                               min="1" 
                                               max="<?php echo $product['stock_quantity']; ?>"
                                               onchange="updateQuantity(<?php echo $product_id; ?>, 0, this.value)"
                                               <?php echo !$isInStock ? 'disabled' : ''; ?>>
                                        <button class="quantity-btn" 
                                                onclick="updateQuantity(<?php echo $product_id; ?>, 1)"
                                                <?php echo ($item['quantity'] >= $product['stock_quantity']) ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : ''; ?>>
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div style="text-align: right; min-width: 180px; margin-left: auto;">
                                    <div style="font-weight: 700; font-size: 1.4rem; color: var(--text); margin-bottom: 1rem;">
                                        $<?php echo number_format($subtotal, 2); ?>
                                    </div>
                                    <button class="remove-btn" onclick="removeItem(<?php echo $product_id; ?>)">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- 处理产品已从数据库删除的情况 -->
                            <div class="cart-item" style="opacity: 0.7;">
                                <div class="item-details">
                                    <div class="item-name" style="color: var(--muted);">
                                        <i class="fas fa-exclamation-triangle"></i> Product Unavailable
                                    </div>
                                    <div style="color: var(--danger); margin-bottom: 1rem;">
                                        This product has been removed from the store.
                                    </div>
                                    <button class="remove-btn" onclick="removeItem(<?php echo $product_id; ?>)">
                                        <i class="fas fa-trash"></i> Remove Item
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                
                <div class="cart-summary">
                    <div class="summary-row">
                        <span>Subtotal (<?php echo count($cart); ?> items)</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span>Calculated at checkout</span>
                    </div>
                    <div class="summary-row">
                        <span>Tax</span>
                        <span>Will be applied</span>
                    </div>
                    <div class="summary-row total">
                        <span>Estimated Total</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                    
                    <?php if ($hasOutOfStock): ?>
                        <div class="error-message" style="margin-bottom: 1rem;">
                            <i class="fas fa-exclamation-circle"></i>
                            Some items in your cart are out of stock. Please remove them or update quantities to proceed.
                        </div>
                    <?php endif; ?>
                    
                    <a href="checkout.php" class="checkout-btn" <?php echo $hasOutOfStock ? 'style="opacity:0.5;cursor:not-allowed;pointer-events:none;"' : ''; ?>>
                        <i class="fas fa-lock"></i> Proceed to Checkout
                    </a>
                    
                    <div class="action-buttons">
                        <a href="products.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Continue Shopping
                        </a>
                        <button class="btn btn-danger" onclick="clearCart()">
                            <i class="fas fa-trash"></i> Clear Cart
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function updateQuantity(productId, change, newValue = null) {
            let formData = new FormData();
            formData.append('action', 'update_quantity');
            formData.append('product_id', productId);
            
            if (newValue !== null) {
                formData.append('quantity', parseInt(newValue));
            } else {
                let currentInput = document.querySelector(`#item-${productId} .quantity-input`);
                let currentValue = parseInt(currentInput.value);
                let newQuantity = currentValue + change;
                
                // 获取库存限制
                let maxValue = parseInt(currentInput.max) || 999;
                if (newQuantity < 1) newQuantity = 1;
                if (newQuantity > maxValue) newQuantity = maxValue;
                
                formData.append('quantity', newQuantity);
            }
            
            fetch('cart-actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error updating quantity');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Network error occurred');
            });
        }
        
        function removeItem(productId) {
            if (confirm('Are you sure you want to remove this item from your cart?')) {
                let formData = new FormData();
                formData.append('action', 'remove_item');
                formData.append('product_id', productId);
                
                fetch('cart-actions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Error removing item');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Network error occurred');
                });
            }
        }
        
        function clearCart() {
            if (confirm('Are you sure you want to clear your entire cart? This action cannot be undone.')) {
                let formData = new FormData();
                formData.append('action', 'clear_cart');
                
                fetch('cart-actions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Error clearing cart');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Network error occurred');
                });
            }
        }
        
        // 自动隐藏成功消息
        setTimeout(() => {
            const successMessage = document.querySelector('.success-message');
            if (successMessage) {
                successMessage.style.opacity = '0';
                successMessage.style.transition = 'opacity 0.5s';
                setTimeout(() => successMessage.remove(), 500);
            }
        }, 5000);
    </script>
</body>
</html>