<?php
// ============================================
// config.php - TechStore Configuration File
// 修复版：安全会话管理和错误处理
// ============================================

// 开启错误报告（开发环境）
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// ============================================
// 会话管理函数 - 安全启动会话
// ============================================

/**
 * 安全地启动会话（防止"headers already sent"错误）
 * @return bool 是否成功启动会话
 */
function startSessionSafely() {
    // 检查会话是否已启动
    if (session_status() === PHP_SESSION_ACTIVE) {
        return true;
    }
    
    // 检查头部是否已发送
    if (headers_sent($filename, $linenum)) {
        // 如果是在开发环境，记录错误但不中断
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("Warning: Cannot start session - headers already sent in $filename on line $linenum");
        }
        return false;
    }
    
    // 安全地启动会话
    try {
        // 设置安全的会话参数
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        
        session_set_cookie_params([
            'lifetime' => 86400, // 24小时
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'] ?? '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        
        // 防止会话劫持
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', $secure ? 1 : 0);
        
        // 设置会话名称
        session_name('techstore_session_' . substr(md5(__DIR__), 0, 8));
        
        // 启动会话
        $result = @session_start(); // 使用@抑制可能的警告
        
        if ($result) {
            // 防止会话固定攻击
            if (!isset($_SESSION['created'])) {
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
            
            // 检查会话超时（30分钟）
            $timeout = 1800;
            if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
                // 会话超时，销毁并重新开始
                session_unset();
                session_destroy();
                session_start();
                $_SESSION['created'] = time();
            }
            
            // 更新最后活动时间
            $_SESSION['last_activity'] = time();
            
            return true;
        }
        
        return false;
        
    } catch (Exception $e) {
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("Session start error: " . $e->getMessage());
        }
        return false;
    }
}

/**
 * 启动会话（兼容性函数）
 * @return bool 是否成功启动会话
 */
function startSession() {
    return startSessionSafely();
}

/**
 * 检查并启动会话（如果需要）
 * @return bool 会话是否可用
 */
function ensureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        return startSessionSafely();
    }
    return true;
}

// ============================================
// 数据库配置 - 修复数据库主机名
// ============================================
// 根据之前的错误，数据库连接失败，请检查以下配置是否正确：
define('DB_HOST', 'sql103.infinityfree.com'); // 或者 'sql312.infinityfree.com'，根据实际
define('DB_NAME', 'if0_38912741_513final_db');
define('DB_USER', 'if0_38912741');
define('DB_PASS', 'L06MPSzobp79GbF'); // 请确保密码正确

// ============================================
// 数据库连接函数 - 修复版
// ============================================
function getDatabaseConnection() {
    static $pdo = null; // 使用静态变量避免重复连接
    
    if ($pdo !== null) {
        return $pdo;
    }
    
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false
        ]);
        return $pdo;
    } catch (PDOException $e) {
        // 在开发环境中显示错误，生产环境中记录到日志
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            // 显示详细的连接错误信息
            $error_msg = "Database connection failed: " . $e->getMessage();
            $error_msg .= "<br>Host: " . DB_HOST . ", Database: " . DB_NAME . ", User: " . DB_USER;
            
            // 直接输出错误信息以便调试
            echo "<div style='background:#f8d7da;color:#721c24;padding:15px;margin:10px;border-radius:5px;border-left:4px solid #dc3545;'>
                <h3>Database Connection Error</h3>
                <p><strong>Error:</strong> " . $e->getMessage() . "</p>
                <p><strong>Details:</strong> Host: " . DB_HOST . ", Database: " . DB_NAME . ", User: " . DB_USER . "</p>
                <p><strong>Possible issues:</strong><br>
                1. Database credentials incorrect<br>
                2. Database server is down<br>
                3. Hostname is wrong<br>
                4. Database doesn't exist</p>
            </div>";
            
            // 也记录到错误日志
            error_log($error_msg);
        } else {
            // 生产环境只记录到日志
            error_log("Database connection failed: " . $e->getMessage());
        }
        
        // 返回 null 而不是 false，以便检查
        return null;
    }
}

// ============================================
// 演示模式设置
// ============================================
function isDemoMode() {
    // 如果数据库连接失败，自动启用演示模式
    $pdo = getDatabaseConnection();
    return $pdo === null;
}

// ============================================
// 简单会话管理函数（修复版）
// ============================================
function setUserSession($user_id, $username, $email, $role = 'customer') {
    // 确保会话已启动
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // 清理旧的会话数据
    session_regenerate_id(true);
    
    // 设置基础信息
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['user_role'] = $role;
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    
    // 设置角色特定标志
    if ($role === 'admin') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_id'] = $user_id;
    } elseif ($role === 'customer') {
        $_SESSION['customer_logged_in'] = true;
    }
}

// ============================================
// 用户验证函数（简化版）
// ============================================
function isUserLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']) && 
           isset($_SESSION['username']) && 
           $_SESSION['username'] !== 'Guest';
}

function isAdmin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['admin_logged_in']) && 
           $_SESSION['admin_logged_in'] === true;
}

function isCustomer() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['customer_logged_in']) && 
           $_SESSION['customer_logged_in'] === true;
}

// ============================================
// 调试模式
// ============================================
define('DEBUG_MODE', true); // 设为 false 在生产环境

// ============================================
// 网站基本配置
// ============================================
define('SITE_NAME', 'TechStore');
define('SITE_URL', 'https://bgg.kesug.com');
define('SITE_PATH', '/513/final/'); // 根据您的实际路径修改
define('ADMIN_EMAIL', 'admin@techstore.com');

// ============================================
// 路径函数
// ============================================
function site_url($path = '') {
    $base = SITE_URL . SITE_PATH;
    if ($path) {
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
    return rtrim($base, '/');
}

function base_path($path = '') {
    $base = __DIR__;
    if ($path) {
        return $base . '/' . ltrim($path, '/');
    }
    return $base;
}

// ============================================
// 错误处理函数 - 修复版
// ============================================
function handleError($errno, $errstr, $errfile, $errline) {
    // 忽略某些类型的错误
    if ($errno === E_WARNING && strpos($errstr, 'session_start') !== false) {
        // 忽略session_start相关的警告
        return true;
    }
    
    if (DEBUG_MODE) {
        $error_type = [
            E_ERROR => 'Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Strict Notice',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated'
        ];
        
        $type = $error_type[$errno] ?? 'Unknown Error';
        
        echo "<div style='background:#f8d7da;color:#721c24;padding:15px;margin:10px;border-radius:5px;border-left:4px solid #dc3545;'>
            <strong>$type [$errno]:</strong> " . htmlspecialchars($errstr) . "<br>
            <small style='color:#6c757d;'>File: $errfile, Line: $errline</small>
        </div>";
    } else {
        // 生产环境记录到日志
        error_log("[$errno] $errstr in $errfile on line $errline");
    }
    
    // 不要让脚本停止，除非是致命错误
    if ($errno === E_ERROR || $errno === E_USER_ERROR) {
        if (!DEBUG_MODE) {
            // 生产环境显示用户友好的错误页面
            http_response_code(500);
            echo "<h1>500 Internal Server Error</h1>";
            echo "<p>Sorry, something went wrong. Please try again later.</p>";
        }
        exit(1);
    }
    
    return true;
}

function handleException($exception) {
    if (DEBUG_MODE) {
        echo "<div style='background:#f8d7da;color:#721c24;padding:15px;margin:10px;border-radius:5px;border-left:4px solid #dc3545;'>
            <strong>Uncaught Exception:</strong> " . htmlspecialchars($exception->getMessage()) . "<br>
            <small style='color:#6c757d;'>File: " . $exception->getFile() . ", Line: " . $exception->getLine() . "</small>
            <pre style='background:#f8f9fa;padding:10px;margin-top:10px;border-radius:3px;font-size:12px;max-height:200px;overflow:auto;'>" . 
            htmlspecialchars($exception->getTraceAsString()) . "</pre>
        </div>";
    } else {
        error_log("Uncaught Exception: " . $exception->getMessage() . 
                 " in " . $exception->getFile() . " on line " . $exception->getLine());
        
        http_response_code(500);
        echo "<h1>500 Internal Server Error</h1>";
        echo "<p>Sorry, something went wrong. Please try again later.</p>";
    }
    exit(1);
}

// 设置错误和异常处理
set_error_handler('handleError');
set_exception_handler('handleException');

// ============================================
// 实用函数（修复版）
// ============================================
function redirect($url, $permanent = false) {
    if (headers_sent()) {
        // 如果头部已发送，使用JavaScript重定向
        echo "<script>window.location.href = '" . htmlspecialchars($url) . "';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=" . htmlspecialchars($url) . "'></noscript>";
    } else {
        // 使用HTTP头重定向
        header('Location: ' . $url, true, $permanent ? 301 : 302);
    }
    exit;
}

function sanitizeInput($input, $allow_html = false) {
    if (is_array($input)) {
        return array_map(function($item) use ($allow_html) {
            return sanitizeInput($item, $allow_html);
        }, $input);
    }
    
    if (!is_string($input)) {
        return $input;
    }
    
    $input = trim($input);
    
    if ($allow_html) {
        // 允许安全的HTML
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    } else {
        // 去除所有HTML标签
        $input = strip_tags($input);
    }
    
    return $input;
}

function jsonResponse($data, $success = true, $message = '', $http_code = 200) {
    if (!headers_sent()) {
        http_response_code($http_code);
        header('Content-Type: application/json; charset=utf-8');
    }
    
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => time()
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

function validate_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ============================================
// 文件上传配置
// ============================================
define('MAX_FILE_SIZE', 20 * 1024 * 1024); // 20MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_DOC_TYPES', ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);

function validateUploadedFile($file, $allowed_types = null) {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File too large'];
    }
    
    $allowed = $allowed_types ?: array_merge(ALLOWED_IMAGE_TYPES, ALLOWED_DOC_TYPES);
    
    if (!function_exists('finfo_open')) {
        // 如果没有 fileinfo 扩展，使用简单检查
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx'];
        
        if (!in_array($extension, $allowed_extensions)) {
            return ['success' => false, 'message' => 'File type not allowed'];
        }
        
        return ['success' => true, 'mime' => 'unknown'];
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime, $allowed)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }
    
    return ['success' => true, 'mime' => $mime];
}

// ============================================
// 密码安全函数
// ============================================
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function generateRandomString($length = 32) {
    try {
        return bin2hex(random_bytes($length / 2));
    } catch (Exception $e) {
        // 如果 random_bytes 失败，使用备选方法
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }
}

// ============================================
// 日志函数（简化版）
// ============================================
function logActivity($action, $user_id = null, $details = null) {
    try {
        $pdo = getDatabaseConnection();
        if (!$pdo) {
            return false;
        }
        
        $user_id = $user_id ?? ($_SESSION['user_id'] ?? null);
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // 检查表是否存在
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent, created_at)
            VALUES (:user_id, :action, :details, :ip_address, :user_agent, NOW())
        ");
        
        $stmt->execute([
            ':user_id' => $user_id,
            ':action' => $action,
            ':details' => $details,
            ':ip_address' => $ip_address,
            ':user_agent' => $user_agent
        ]);
        
        return true;
        
    } catch (Exception $e) {
        if (DEBUG_MODE) {
            error_log("Activity log error: " . $e->getMessage());
        }
        return false;
    }
}

// ============================================
// 初始化检查（修复版）
// ============================================
function initializeSystem() {
    // 设置时区
    date_default_timezone_set('Asia/Shanghai');
    
    // 设置字符编码
    mb_internal_encoding('UTF-8');
    
    // 检查必要的扩展
    $required_extensions = ['pdo', 'pdo_mysql', 'session'];
    $missing_extensions = [];
    
    foreach ($required_extensions as $ext) {
        if (!extension_loaded($ext)) {
            $missing_extensions[] = $ext;
        }
    }
    
    if (!empty($missing_extensions)) {
        if (DEBUG_MODE) {
            die("<div style='background:#f8d7da;color:#721c24;padding:15px;margin:10px;border-radius:5px;'>
                <h3>Missing PHP Extensions</h3>
                <p>Please enable the following PHP extensions:</p>
                <ul>" . implode('', array_map(function($ext) {
                    return "<li>$ext</li>";
                }, $missing_extensions)) . "</ul>
            </div>");
        } else {
            error_log("Missing required PHP extensions: " . implode(', ', $missing_extensions));
        }
    }
}

// 执行初始化
initializeSystem();

// ============================================
// 自动加载类（如果需要）
// ============================================
spl_autoload_register(function ($class_name) {
    $directories = [
        __DIR__ . '/classes/',
        __DIR__ . '/models/',
        __DIR__ . '/controllers/'
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $class_name . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
    
    // 如果找不到文件，记录错误但不中断
    if (DEBUG_MODE) {
        error_log("Class not found: $class_name");
    }
});

// ============================================
// 创建必要的数据库表（如果不存在）- 新增函数
// ============================================
function createDatabaseTablesIfNeeded() {
    try {
        $pdo = getDatabaseConnection();
        if (!$pdo) {
            return false;
        }
        
        // 创建 users 表
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL,
            INDEX idx_username (username),
            INDEX idx_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // 创建 admins 表
        $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL,
            INDEX idx_username (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // 添加默认管理员（如果不存在）
        $checkAdmin = $pdo->prepare("SELECT id FROM admins WHERE username = 'admin'");
        $checkAdmin->execute();
        
        if (!$checkAdmin->fetch()) {
            $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $insertAdmin = $pdo->prepare("INSERT INTO admins (username, email, password) VALUES ('admin', 'admin@techstore.com', ?)");
            $insertAdmin->execute([$hashedPassword]);
        }
        
        // 添加默认用户（如果不存在）
        $checkUser = $pdo->prepare("SELECT id FROM users WHERE username = 'user'");
        $checkUser->execute();
        
        if (!$checkUser->fetch()) {
            $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
            $insertUser = $pdo->prepare("INSERT INTO users (username, email, password) VALUES ('user', 'user@techstore.com', ?)");
            $insertUser->execute([$hashedPassword]);
        }
        
        return true;
        
    } catch (Exception $e) {
        if (DEBUG_MODE) {
            error_log("Table creation error: " . $e->getMessage());
        }
        return false;
    }
}

// 自动创建表（仅在需要时）
if (DEBUG_MODE && php_sapi_name() !== 'cli') {
    // 只在网页访问时创建，避免命令行执行时创建
    createDatabaseTablesIfNeeded();
}

// ============================================
// 结束标记
// ============================================
?>