<?php
// diagnose-cart.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>购物车诊断工具</h1>";

// 方法1：使用与 add-to-cart.php 相同的方式
session_start();
echo "<h2>当前会话状态：</h2>";
echo "<p>会话ID: <strong>" . session_id() . "</strong></p>";
echo "<p>会话名称: <strong>" . session_name() . "</strong></p>";

echo "<h2>购物车数据：</h2>";
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    echo "<p>购物车项目数: <strong>" . count($_SESSION['cart']) . "</strong></p>";
    echo "<pre>";
    print_r($_SESSION['cart']);
    echo "</pre>";
    
    // 计算总数量
    $total_quantity = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total_quantity += $item['quantity'];
    }
    echo "<p>购物车商品总数量: <strong>" . $total_quantity . "</strong></p>";
} else {
    echo "<p style='color: red;'>购物车为空或未设置</p>";
    echo "<p>所有会话数据：</p>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
}

echo "<h2>Cookie信息：</h2>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

echo "<h2>测试操作：</h2>";
echo '<p><a href="add-to-cart.php?product_id=1&return_url=diagnose-cart.php">添加商品1到购物车</a></p>';
echo '<p><a href="add-to-cart.php?product_id=2&return_url=diagnose-cart.php">添加商品2到购物车</a></p>';
echo '<p><a href="cart.php">查看购物车页面</a></p>';
echo '<p><a href="diagnose-cart.php?clear=1">清除会话（谨慎操作）</a></p>';

// 清除会话选项
if (isset($_GET['clear'])) {
    session_destroy();
    echo "<script>alert('会话已清除'); window.location.href='diagnose-cart.php';</script>";
}
?>