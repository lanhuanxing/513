<?php
// logout.php - 修复版
session_start();

// 记录登出前的用户信息（用于消息显示）
$was_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$username = $_SESSION['username'] ?? '';

// 完全销毁会话
session_unset();
session_destroy();

// 重新开始新会话（用于显示消息）
session_start();

// 设置登出消息
$_SESSION['logout_message'] = 'You have been successfully logged out' . 
    ($username ? ", $username" : '') . '!';

// 根据用户类型重定向
if ($was_admin) {
    // 如果是管理员，重定向到管理员登录页
    header('Location: admin-login.php');
} else {
    // 如果是普通用户，重定向到首页
    header('Location: index.php');
}
exit;
?>