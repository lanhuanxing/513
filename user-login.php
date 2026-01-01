<?php
// user-login.php - 修复参数绑定错误版
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . '/config.php';

// 启动会话
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 如果已经登录，重定向到首页
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
    isset($_SESSION['username']) && $_SESSION['username'] !== 'Guest') {
    header('Location: index.php');
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
            
            // 演示模式：直接使用预定义账户
            if ($pdo === null) {
                // 演示模式 - 使用硬编码账户
                $demo_users = [
                    'user' => [
                        'id' => 2,
                        'username' => 'user',
                        'email' => 'user@techstore.com',
                        'password' => 'password123', // 明文用于演示
                        'is_admin' => false
                    ],
                    'admin' => [
                        'id' => 1,
                        'username' => 'admin',
                        'email' => 'admin@techstore.com',
                        'password' => 'admin123',
                        'is_admin' => true
                    ]
                ];
                
                // 检查用户名（不区分大小写）
                $username_lower = strtolower($username);
                $found_user = null;
                
                foreach ($demo_users as $key => $user) {
                    if (strtolower($key) === $username_lower || 
                        strtolower($user['email']) === $username_lower) {
                        $found_user = $user;
                        break;
                    }
                }
                
                if ($found_user && $password === $found_user['password']) {
                    $user = $found_user;
                    
                    // 登录成功
                    session_regenerate_id(true);
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['logged_in'] = true;
                    $_SESSION['login_time'] = time();
                    
                    if ($user['is_admin']) {
                        $_SESSION['user_role'] = 'admin';
                        $_SESSION['admin_logged_in'] = true;
                        $_SESSION['is_admin'] = true;
                        $_SESSION['admin_id'] = $user['id'];
                        
                        // 如果是管理员，仍然可以登录，但提示访问管理员面板
                        $_SESSION['login_message'] = 'Admin logged in. Visit admin panel for full features.';
                        header('Location: index.php');
                    } else {
                        $_SESSION['user_role'] = 'user';
                        $_SESSION['customer_logged_in'] = true;
                        header('Location: index.php');
                    }
                    exit;
                } else {
                    $error = 'Invalid username or password';
                }
                
            } else {
                // 数据库模式 - 修复参数绑定问题
                
                // 方法1：首先检查普通用户表（使用正确的参数绑定）
                try {
                    // 使用单个参数名，但执行两次查询
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :input OR email = :input");
                    $stmt->execute([':input' => $username]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    // 如果表不存在，跳到演示模式
                    $user = null;
                }
                
                if ($user) {
                    // 验证密码
                    if (password_verify($password, $user['password'])) {
                        // 普通用户登录成功
                        session_regenerate_id(true);
                        
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['user_role'] = 'user';
                        $_SESSION['logged_in'] = true;
                        $_SESSION['customer_logged_in'] = true;
                        $_SESSION['login_time'] = time();
                        
                        // 更新最后登录时间
                        try {
                            $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
                            $updateStmt->execute([':id' => $user['id']]);
                        } catch (Exception $e) {
                            // 更新失败不影响登录
                        }
                        
                        header('Location: index.php');
                        exit;
                    } else {
                        $error = 'Invalid username or password';
                    }
                } else {
                    // 方法2：检查管理员表
                    try {
                        $adminStmt = $pdo->prepare("SELECT * FROM admins WHERE username = :input OR email = :input");
                        $adminStmt->execute([':input' => $username]);
                        $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
                    } catch (Exception $e) {
                        $admin = null;
                    }
                    
                    if ($admin) {
                        // 验证密码
                        if (password_verify($password, $admin['password'])) {
                            // 管理员登录成功
                            session_regenerate_id(true);
                            
                            $_SESSION['user_id'] = $admin['id'];
                            $_SESSION['username'] = $admin['username'];
                            $_SESSION['email'] = $admin['email'];
                            $_SESSION['user_role'] = 'admin';
                            $_SESSION['logged_in'] = true;
                            $_SESSION['admin_logged_in'] = true;
                            $_SESSION['is_admin'] = true;
                            $_SESSION['admin_id'] = $admin['id'];
                            $_SESSION['login_time'] = time();
                            
                            $_SESSION['login_message'] = 'Admin account detected. For full admin features, use the admin login page.';
                            
                            // 更新最后登录时间
                            try {
                                $updateStmt = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = :id");
                                $updateStmt->execute([':id' => $admin['id']]);
                            } catch (Exception $e) {
                                // 更新失败不影响登录
                            }
                            
                            header('Location: index.php');
                            exit;
                        } else {
                            $error = 'Invalid username or password';
                        }
                    } else {
                        // 方法3：演示模式回退
                        $demo_users = [
                            'user' => [
                                'id' => 2,
                                'username' => 'user',
                                'email' => 'user@techstore.com',
                                'password' => 'password123',
                                'is_admin' => false
                            ],
                            'admin' => [
                                'id' => 1,
                                'username' => 'admin',
                                'email' => 'admin@techstore.com',
                                'password' => 'admin123',
                                'is_admin' => true
                            ]
                        ];
                        
                        $username_lower = strtolower($username);
                        $found_user = null;
                        
                        foreach ($demo_users as $key => $demo_user) {
                            if (strtolower($key) === $username_lower || 
                                strtolower($demo_user['email']) === $username_lower) {
                                $found_user = $demo_user;
                                break;
                            }
                        }
                        
                        if ($found_user && $password === $found_user['password']) {
                            $user = $found_user;
                            
                            // 登录成功
                            session_regenerate_id(true);
                            
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['logged_in'] = true;
                            $_SESSION['login_time'] = time();
                            
                            if ($user['is_admin']) {
                                $_SESSION['user_role'] = 'admin';
                                $_SESSION['admin_logged_in'] = true;
                                $_SESSION['is_admin'] = true;
                                $_SESSION['admin_id'] = $user['id'];
                                
                                $_SESSION['login_message'] = 'Admin logged in. Visit admin panel for full features.';
                            } else {
                                $_SESSION['user_role'] = 'user';
                                $_SESSION['customer_logged_in'] = true;
                            }
                            
                            header('Location: index.php');
                            exit;
                        } else {
                            $error = 'Invalid username or password';
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $error = 'Login error: ' . $e->getMessage();
            // 详细错误信息用于调试
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                $error .= '<br>Details: ' . $e->getFile() . ' on line ' . $e->getLine();
            }
        }
    }
}

// 设置页面标题
$page_title = "User Login - TechStore";
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
        
        /* 登录页面特定样式 */
        .login-section {
            padding: 80px 0;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
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
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: var(--radius);
            margin-bottom: 25px;
            border-left: 4px solid var(--accent);
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
        }
        
        button:hover {
            background: linear-gradient(135deg, #218838 0%, #1ba87e 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(40, 167, 69, 0.3);
        }
        
        .links {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #e9ecef;
        }
        
        .links a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .links a:hover {
            color: #218838;
            text-decoration: underline;
        }
        
        .demo-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: var(--radius);
            margin-top: 25px;
            font-size: 0.95rem;
            color: var(--muted);
            line-height: 1.6;
        }
        
        .demo-info strong {
            color: var(--text);
            display: block;
            margin-bottom: 8px;
        }
        
        /* 响应式设计 */
        @media (max-width: 768px) {
            .login-section {
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
        }
        
        /* 页面内容区域样式，避免页脚覆盖 */
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
        <!-- 用户登录区域 -->
        <section class="login-section">
            <div class="container">
                <div class="login-box">
                    <div class="login-header">
                        <h1>User Login</h1>
                        <p>Customer Login - TechStore</p>
                    </div>
                    <div class="login-content">
                        <?php if (isset($_SESSION['login_message'])): ?>
                            <div class="success">
                                <i class="fas fa-check-circle"></i> 
                                <?php echo htmlspecialchars($_SESSION['login_message']); unset($_SESSION['login_message']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="error">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="form-group">
                                <input type="text" name="username" placeholder="Username or Email" required value="user">
                            </div>
                            <div class="form-group">
                                <input type="password" name="password" placeholder="Password" required value="password123">
                            </div>
                            <button type="submit">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </button>
                        </form>
                        
                        <div class="demo-info">
                            <strong>Demo User Credentials:</strong><br>
                            Username: user<br>
                            Password: password123<br><br>
                            <strong>Note:</strong> Admin accounts can also login here, but for full admin features use the admin login page.
                        </div>
                        
                        <div class="links">
                            <p>
                                New user? <a href="register.php">Create Account</a><br>
                                <a href="admin-login.php">Admin Login (Full Features)</a> | 
                                <a href="index.php">Back to Store</a>
                            </p>
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
        // 自动提交表单（如果URL中有demo参数）
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('demo')) {
                // 自动提交表单
                document.querySelector('form').submit();
            }
            
            // 聚焦到用户名输入框
            document.querySelector('input[name="username"]').focus();
        });
    </script>
</body>
</html>