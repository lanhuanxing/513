<?php
// api/cart.php - 购物车API
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . '/../config.php';

// 检查是否已经有活动会话
$hasActiveSession = false;
if (function_exists('session_status')) {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $hasActiveSession = true;
    }
} else {
    // 对于旧版PHP
    if (isset($_SESSION)) {
        $hasActiveSession = true;
    }
}

// 只有在没有活动会话时才启动新会话
if (!$hasActiveSession) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// 设置JSON头
header('Content-Type: application/json');

// 获取购物车项目数量
if (isset($_GET['action']) && $_GET['action'] === 'count') {
    $cart_count = 0;
    
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $cart_count += $item['quantity'] ?? 1;
        }
    }
    
    echo json_encode([
        'success' => true,
        'count' => $cart_count
    ]);
    exit;
}

// 默认响应
echo json_encode([
    'success' => false,
    'message' => 'Invalid action'
]);
?>