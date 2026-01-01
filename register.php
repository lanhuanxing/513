<?php
// register.php - 用户注册页面
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . '/config.php';

// 启动会话
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 如果已经登录，重定向到首页
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0 && 
    isset($_SESSION['username']) && $_SESSION['username'] !== 'Guest') {
    header('Location: index.php');
    exit;
}

// 获取数据库连接
$pdo = getDatabaseConnection();

// 处理注册
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // 验证输入
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        try {
            if (!$pdo) {
                throw new Exception('Database connection failed');
            }
            
            // 检查用户名是否已存在
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
            $checkStmt->execute([':username' => $username, ':email' => $email]);
            $existingUser = $checkStmt->fetch();
            
            if ($existingUser) {
                $error = 'Username or email already exists';
            } else {
                // 创建新用户
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $insertStmt = $pdo->prepare("INSERT INTO users (username, email, password, created_at) VALUES (:username, :email, :password, NOW())");
                
                if ($insertStmt->execute([
                    ':username' => $username,
                    ':email' => $email,
                    ':password' => $hashedPassword
                ])) {
                    $success = 'Registration successful! You can now login.';
                    
                    // 自动登录
                    $userId = $pdo->lastInsertId();
                    session_regenerate_id(true);
                    
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;
                    $_SESSION['user_role'] = 'user';
                    $_SESSION['logged_in'] = true;
                    $_SESSION['login_time'] = time();
                    
                    // 延迟重定向，让用户看到成功消息
                    header('Refresh: 3; URL=index.php');
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        } catch (Exception $e) {
            $error = 'Registration error: ' . $e->getMessage();
        }
    }
}

// 设置页面标题
$page_title = "Register - TechStore";
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
        
        /* 注册页面特定样式 */
        .register-section {
            padding: 80px 0;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        }
        
        .register-box {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
            transition: var(--transition);
        }
        
        .register-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.12);
        }
        
        .register-header {
            background: linear-gradient(135deg, var(--accent) 0%, #20c997 100%);
            color: white;
            text-align: center;
            padding: 40px 20px;
        }
        
        .register-header h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 2.2rem;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .register-header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .register-content {
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
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text);
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
        
        .btn-primary {
            background: linear-gradient(135deg, var(--accent) 0%, #20c997 100%);
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #218838 0%, #1ba87e 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(40, 167, 69, 0.3);
        }
        
        .btn-wordpress {
            background: linear-gradient(135deg, #0073aa 0%, #00a0d2 100%);
            color: white;
        }
        
        .btn-wordpress:hover {
            background: linear-gradient(135deg, #005a87 0%, #0085ba 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 115, 170, 0.3);
        }
        
        .wordpress-icon {
            background: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #0073aa;
            font-size: 16px;
            font-family: 'Poppins', sans-serif;
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 30px 0;
            color: var(--muted);
            font-size: 14px;
        }
        
        .divider::before,
        .divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .divider span {
            padding: 0 15px;
            background: white;
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
        
        .password-strength {
            font-size: 0.85rem;
            margin-top: 8px;
            color: var(--muted);
        }
        
        .password-strength.good {
            color: var(--accent);
        }
        
        .password-strength.fair {
            color: #fd7e14;
        }
        
        .password-strength.weak {
            color: var(--danger);
        }
        
        .wordpress-note {
            font-size: 0.9rem;
            color: var(--muted);
            text-align: center;
            margin-top: 15px;
            line-height: 1.5;
            font-style: italic;
        }
        
        /* 响应式设计 */
        @media (max-width: 768px) {
            .register-section {
                padding: 60px 20px;
            }
            
            .register-header h1 {
                font-size: 1.8rem;
            }
            
            .register-content {
                padding: 30px 20px;
            }
        }
        
        @media (max-width: 480px) {
            .register-header {
                padding: 30px 20px;
            }
            
            .register-header h1 {
                font-size: 1.6rem;
            }
            
            .register-header p {
                font-size: 1rem;
            }
            
            .divider span {
                font-size: 12px;
                padding: 0 10px;
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
        <!-- 用户注册区域 -->
        <section class="register-section">
            <div class="container">
                <div class="register-box">
                    <div class="register-header">
                        <h1>Create Account</h1>
                        <p>Join TechStore today</p>
                    </div>
                    <div class="register-content">
                        <?php if ($error): ?>
                            <div class="error">
                                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="success">
                                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                            </div>
                            <p style="text-align: center; color: var(--muted);">Redirecting to homepage in 3 seconds...</p>
                        <?php else: ?>
                            <!-- WordPress 注册按钮 -->
                            <button class="btn-wordpress" onclick="window.open('http://bgg.kesug.com/Merlin2/11-2/', '_blank')">
                                <div class="wordpress-icon">W</div>
                                Register with WordPress
                            </button>
                            
                            <div class="divider">
                                <span>or register with email</span>
                            </div>
                            
                            <form method="POST" id="register-form">
                                <div class="form-group">
                                    <label for="username">Username *</label>
                                    <input type="text" id="username" name="username" required 
                                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email *</label>
                                    <input type="email" id="email" name="email" required 
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="password">Password *</label>
                                    <input type="password" id="password" name="password" required>
                                    <div class="password-strength" id="password-strength-text">At least 6 characters</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password">Confirm Password *</label>
                                    <input type="password" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-user-plus"></i> Create Account
                                </button>
                            </form>
                            
                            <p class="wordpress-note">
                                Note: Registering with WordPress will open a new tab where you can create your WordPress account.
                            </p>
                        <?php endif; ?>
                        
                        <div class="links">
                            <p>
                                Already have an account? <a href="user-login.php">Login</a><br>
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
        // 密码匹配验证
        document.getElementById('register-form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters!');
                return false;
            }
            
            return true;
        });
        
        // 实时密码强度检查
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthText = document.getElementById('password-strength-text');
            
            if (password.length === 0) {
                strengthText.textContent = 'At least 6 characters';
                strengthText.className = 'password-strength';
            } else if (password.length < 6) {
                strengthText.textContent = 'Too short (minimum 6 characters)';
                strengthText.className = 'password-strength weak';
            } else if (password.length < 8) {
                strengthText.textContent = 'Fair';
                strengthText.className = 'password-strength fair';
            } else {
                // 检查密码复杂性
                const hasUpperCase = /[A-Z]/.test(password);
                const hasLowerCase = /[a-z]/.test(password);
                const hasNumbers = /\d/.test(password);
                const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
                
                const complexityScore = [hasUpperCase, hasLowerCase, hasNumbers, hasSpecial].filter(Boolean).length;
                
                if (complexityScore >= 3) {
                    strengthText.textContent = 'Strong';
                } else if (complexityScore >= 2) {
                    strengthText.textContent = 'Good';
                } else {
                    strengthText.textContent = 'Fair';
                }
                strengthText.className = 'password-strength good';
            }
        });
        
        // 用户名可用性检查（简化版）
        document.getElementById('username').addEventListener('blur', function() {
            const username = this.value.trim();
            if (username.length >= 3) {
                console.log('Checking username:', username);
            }
        });
        
        // 确认密码实时检查
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            const confirmField = this;
            
            if (confirmPassword.length > 0) {
                if (password !== confirmPassword) {
                    confirmField.style.borderColor = '#dc3545';
                } else {
                    confirmField.style.borderColor = '#28a745';
                }
            } else {
                confirmField.style.borderColor = '#e1e5e9';
            }
        });
        
        // 聚焦到第一个输入框
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>