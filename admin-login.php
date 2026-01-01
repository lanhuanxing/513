<?php
// admin-login.php - 管理员专用登录页（简化版，避免参数绑定错误）
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . '/config.php';

// 启动会话
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 如果已经登录为管理员，重定向到仪表板
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

// 处理登录
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        try {
            $pdo = getDatabaseConnection();
            if ($pdo) {
                // 尝试从 admins 表查找
                $adminFound = false;
                $adminData = null;
                
                // 方法1：检查 admins 表
                try {
                    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = :username OR email = :username");
                    $stmt->execute([':username' => $username]);
                    $adminData = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($adminData && password_verify($password, $adminData['password'])) {
                        $adminFound = true;
                    }
                } catch (Exception $e) {
                    // admins 表可能不存在，继续尝试其他方法
                }
                
                // 方法2：如果 admins 表不存在或未找到，检查 users 表
                if (!$adminFound) {
                    try {
                        // 简化的查询：先找到用户，再检查是否为管理员
                        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username OR email = :username");
                        $stmt->execute([':username' => $username]);
                        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($userData && password_verify($password, $userData['password'])) {
                            // 检查是否是管理员
                            $isAdmin = false;
                            
                            // 检查各种可能的 admin 标志
                            if (isset($userData['is_admin']) && $userData['is_admin'] == 1) {
                                $isAdmin = true;
                            } elseif (isset($userData['role']) && $userData['role'] == 'admin') {
                                $isAdmin = true;
                            } elseif (isset($userData['user_role']) && $userData['user_role'] == 'admin') {
                                $isAdmin = true;
                            }
                            
                            if ($isAdmin) {
                                $adminFound = true;
                                $adminData = $userData;
                            }
                        }
                    } catch (Exception $e) {
                        // users 表查询失败
                    }
                }
                
                if ($adminFound && $adminData) {
                    // 管理员登录成功
                    session_regenerate_id(true);
                    
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['user_id'] = $adminData['id'];
                    $_SESSION['username'] = $adminData['username'];
                    $_SESSION['email'] = $adminData['email'];
                    $_SESSION['user_role'] = 'admin';
                    $_SESSION['is_admin'] = true;
                    $_SESSION['admin_id'] = $adminData['id'];
                    $_SESSION['login_time'] = time();
                    
                    // 尝试更新最后登录时间
                    try {
                        if (isset($adminData['password']) && isset($adminData['id'])) {
                            // 如果在 admins 表中
                            $updateStmt = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = :id");
                            $updateStmt->execute([':id' => $adminData['id']]);
                        } else {
                            // 如果在 users 表中
                            $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
                            $updateStmt->execute([':id' => $adminData['id']]);
                        }
                    } catch (Exception $e) {
                        // 更新失败，不影响登录
                    }
                    
                    header('Location: dashboard.php');
                    exit;
                } else {
                    // 演示模式：使用默认管理员凭据
                    if ($username === 'admin' && $password === 'admin123') {
                        $_SESSION['admin_logged_in'] = true;
                        $_SESSION['user_id'] = 1;
                        $_SESSION['username'] = 'admin';
                        $_SESSION['email'] = 'admin@techstore.com';
                        $_SESSION['user_role'] = 'admin';
                        $_SESSION['is_admin'] = true;
                        $_SESSION['admin_id'] = 1;
                        $_SESSION['login_time'] = time();
                        
                        header('Location: dashboard.php');
                        exit;
                    } else {
                        $error = 'Invalid username or password';
                    }
                }
            } else {
                // 演示模式
                if ($username === 'admin' && $password === 'admin123') {
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['user_id'] = 1;
                    $_SESSION['username'] = 'admin';
                    $_SESSION['email'] = 'admin@techstore.com';
                    $_SESSION['user_role'] = 'admin';
                    $_SESSION['is_admin'] = true;
                    $_SESSION['admin_id'] = 1;
                    $_SESSION['login_time'] = time();
                    
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = 'Database connection failed. Try admin/admin123';
                }
            }
        } catch (Exception $e) {
            $error = 'System error. Try admin/admin123';
        }
    }
}

// 设置页面标题
$page_title = "Admin Login - TechStore";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
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
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        /* 管理员登录页面特定样式 */
        .admin-login-section {
            padding: 80px 0;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(44, 62, 80, 0.05) 0%, rgba(52, 73, 94, 0.05) 100%);
        }
        
        .login-box {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
            transition: var(--transition);
        }
        
        .login-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.12);
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--accent) 0%, #20c997 100%);
            color: white;
            text-align: center;
            padding: 40px 20px;
        }
        
        .login-header h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 2.2rem;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .login-header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .login-content {
            padding: 40px 30px;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: var(--radius);
            margin-bottom: 25px;
            border-left: 4px solid var(--danger);
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e5e9;
            border-radius: var(--radius);
            font-size: 16px;
            font-family: 'Inter', sans-serif;
            transition: var(--transition);
        }
        
        input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
        }
        
        button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--accent) 0%, #20c997 100%);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-size: 16px;
            cursor: pointer;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        button:hover {
            background: linear-gradient(135deg, #218838 0%, #1ba87e 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(40, 167, 69, 0.3);
        }
        
        .demo-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: var(--radius);
            margin-top: 25px;
            font-size: 0.95rem;
            color: var(--muted);
            line-height: 1.6;
            text-align: center;
        }
        
        .demo-info strong {
            color: var(--text);
            display: block;
            margin-bottom: 8px;
        }
        
        .user-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #e9ecef;
        }
        
        .user-link a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            margin: 0 15px;
        }
        
        .user-link a:hover {
            color: #218838;
            text-decoration: underline;
        }
        
        /* 响应式设计 */
        @media (max-width: 768px) {
            .admin-login-section {
                padding: 60px 20px;
            }
            
            .login-header h1 {
                font-size: 1.8rem;
            }
            
            .login-content {
                padding: 30px 20px;
            }
        }
        
        @media (max-width: 480px) {
            .login-header {
                padding: 30px 20px;
            }
            
            .login-header h1 {
                font-size: 1.6rem;
            }
            
            .login-header p {
                font-size: 1rem;
            }
            
            .user-link a {
                display: block;
                margin: 10px 0;
            }
        }
        
        /* 页面内容区域样式 */
        main {
            flex: 1;
        }
    </style>
</head>
<body>
    <!-- 包含导航栏 -->
    <?php 
    $navbar_file = __DIR__ . '/navbar.php';
    if (file_exists($navbar_file)) {
        include $navbar_file;
    } else {
        echo '<nav style="background:#2c3e50;color:white;padding:1rem;">
                <div class="container">
                    <a href="index.php" style="color:white;text-decoration:none;font-size:1.5rem;font-weight:bold;">
                        <i class="fas fa-store"></i> TechStore
                    </a>
                </div>
              </nav>';
    }
    ?>
    
    <main>
        <!-- 管理员登录区域 -->
        <section class="admin-login-section">
            <div class="container">
                <div class="login-box">
                    <div class="login-header">
                        <h1>Admin Login</h1>
                        <p>Administrator Access Only</p>
                    </div>
                    <div class="login-content">
                        <?php if ($error): ?>
                            <div class="error">
                                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="form-group">
                                <input type="text" name="username" placeholder="Username" required value="admin">
                            </div>
                            <div class="form-group">
                                <input type="password" name="password" placeholder="Password" required value="admin123">
                            </div>
                            <button type="submit">
                                <i class="fas fa-user-cog"></i> Login as Admin
                            </button>
                        </form>
                        <div class="demo-info">
                            <strong>Demo Admin Credentials:</strong><br>
                            Username: admin<br>
                            Password: admin123
                        </div>
                        <div class="user-link">
                            <a href="user-login.php">User Login</a> | 
                            <a href="index.php">Back to Store</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    
    <!-- 包含页脚 -->
    <?php 
    $footer_file = __DIR__ . '/footer.php';
    if (file_exists($footer_file)) {
        include $footer_file;
    } else {
        echo '<footer style="background: var(--text); color: white; padding: 50px 0; text-align: center;">
                <div class="container">
                    <p style="font-size: 1.2rem; margin-bottom: 1rem;">
                        <i class="fas fa-store" style="margin-right: 10px;"></i>TechStore
                    </p>
                    <p>&copy; ' . date('Y') . ' TechStore. All rights reserved.</p>
                    <p style="margin-top: 0.5rem; opacity: 0.8; font-size: 0.9rem;">
                        <i class="fas fa-map-marker-alt" style="margin-right: 8px;"></i>
                        123 Tech Street, Silicon Valley, CA 94000
                    </p>
                </div>
            </footer>';
    }
    ?>
    
    <script>
        // 聚焦到用户名输入框
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('input[name="username"]').focus();
        });
    </script>
</body>
</html>