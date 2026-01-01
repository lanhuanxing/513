<?php
// cart-actions.php

// 在文件最开始调用 session_start()，并且只调用一次
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 设置响应头为 JSON
header('Content-Type: application/json');

// 检查用户是否登录
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

// 检查购物车是否存在
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// 获取操作类型
$action = isset($_POST['action']) ? trim($_POST['action']) : '';

// 验证 action
if (empty($action)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No action specified']);
    exit;
}

// 处理不同的操作
$response = ['success' => false, 'message' => 'Unknown error'];

try {
    switch ($action) {
        case 'update_quantity':
            $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
            $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
            
            if ($product_id <= 0) {
                $response = ['success' => false, 'message' => 'Invalid product ID'];
                break;
            }
            
            // 确保数量至少为1
            if ($quantity < 1) {
                $quantity = 1;
            }
            
            // 更新购物车中的商品数量
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['quantity'] = $quantity;
                $response = [
                    'success' => true, 
                    'message' => 'Quantity updated',
                    'quantity' => $quantity
                ];
            } else {
                // 如果商品不在购物车中，添加到购物车
                $_SESSION['cart'][$product_id] = [
                    'product_id' => $product_id,
                    'quantity' => $quantity,
                    'added_at' => date('Y-m-d H:i:s')
                ];
                $response = [
                    'success' => true, 
                    'message' => 'Item added to cart',
                    'quantity' => $quantity
                ];
            }
            break;
            
        case 'remove_item':
            $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
            
            if ($product_id <= 0) {
                $response = ['success' => false, 'message' => 'Invalid product ID'];
                break;
            }
            
            if (isset($_SESSION['cart'][$product_id])) {
                unset($_SESSION['cart'][$product_id]);
                $response = ['success' => true, 'message' => 'Item removed from cart'];
            } else {
                $response = ['success' => false, 'message' => 'Item not found in cart'];
            }
            break;
            
        case 'clear_cart':
            $_SESSION['cart'] = [];
            $response = ['success' => true, 'message' => 'Cart cleared'];
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Invalid action'];
            break;
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Server error: ' . $e->getMessage()];
}

// 输出 JSON 响应
echo json_encode($response);
exit;
?>