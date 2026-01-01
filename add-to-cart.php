<?php
// add-to-cart.php
session_start();

// 调试：检查会话状态
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 简单的用户登录检查（根据您的实际情况调整）
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
    // 如果用户未登录，重定向到登录页面
    $_SESSION['login_redirect'] = $_SERVER['HTTP_REFERER'] ?? 'products.php';
    header('Location: user-login.php');
    exit;
}

// 获取参数
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$return_url = isset($_GET['return_url']) ? urldecode($_GET['return_url']) : 'products.php';

// 验证产品ID
if ($product_id <= 0) {
    $_SESSION['error_message'] = 'Invalid product ID';
    header("Location: $return_url");
    exit;
}

// 初始化购物车
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// 检查产品是否已经在购物车中
if (isset($_SESSION['cart'][$product_id])) {
    // 如果已存在，增加数量
    $_SESSION['cart'][$product_id]['quantity']++;
} else {
    // 如果不存在，添加新产品
    $_SESSION['cart'][$product_id] = [
        'product_id' => $product_id,
        'quantity' => 1,
        'added_at' => date('Y-m-d H:i:s')
    ];
}

// 保存成功消息
$_SESSION['cart_success'] = 'Product added to cart successfully!';

// 调试：输出会话信息
// echo '<pre>';
// print_r($_SESSION);
// echo '</pre>';

// 重定向回产品页面
header("Location: $return_url?added=1");
exit;
?>