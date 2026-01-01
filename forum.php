<?php
// forum.php - TechStore Community Forum (修复版)
session_start();
require_once 'config.php';

// 获取数据库连接
$pdo = getDatabaseConnection();
if (!$pdo) {
    die("<div class='error'>Database connection failed. Please check your config.php file.</div>");
}

// 检查并创建论坛表（如果不存在）
function createForumTablesIfNeeded($pdo) {
    $tables = [
        'forum_posts' => "CREATE TABLE IF NOT EXISTS forum_posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            user_id INT NOT NULL,
            author_name VARCHAR(100) NOT NULL,
            views INT DEFAULT 0,
            likes INT DEFAULT 0,
            replies_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'forum_replies' => "CREATE TABLE IF NOT EXISTS forum_replies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            user_id INT NOT NULL,
            author_name VARCHAR(100) NOT NULL,
            content TEXT NOT NULL,
            likes INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_post_id (post_id),
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'forum_tags' => "CREATE TABLE IF NOT EXISTS forum_tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            description VARCHAR(200),
            usage_count INT DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'post_tags' => "CREATE TABLE IF NOT EXISTS post_tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            tag_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_post_tag (post_id, tag_id),
            INDEX idx_post_id (post_id),
            INDEX idx_tag_id (tag_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];
    
    foreach ($tables as $table => $sql) {
        try {
            $pdo->exec($sql);
            
            // 如果是标签表，插入默认标签
            if ($table === 'forum_tags') {
                $defaultTags = [
                    ['Hardware', 'Tech Hardware Discussions'],
                    ['Software', 'Software & Apps'],
                    ['Reviews', 'Product Reviews'],
                    ['Troubleshooting', 'Technical Support'],
                    ['Deals', 'Deals & Discounts'],
                    ['News', 'Tech News'],
                    ['Gaming', 'Gaming Hardware & Software'],
                    ['Programming', 'Coding & Development'],
                    ['Security', 'Cybersecurity & Privacy'],
                    ['Mobile', 'Mobile Devices & Apps']
                ];
                
                foreach ($defaultTags as $tag) {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO forum_tags (name, description) VALUES (?, ?)");
                    $stmt->execute([$tag[0], $tag[1]]);
                }
            }
        } catch (PDOException $e) {
            error_log("Table creation error for $table: " . $e->getMessage());
        }
    }
}

// 创建表（如果不存在）
createForumTablesIfNeeded($pdo);

// 获取论坛统计数据
function getForumStats($pdo) {
    $stats = [
        'today_posts' => 0,
        'total_replies' => 0,
        'active_topics' => 0,
        'total_members' => 0
    ];
    
    try {
        // 获取今日帖子数
        $today = date('Y-m-d');
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM forum_posts WHERE DATE(created_at) = ?");
        $stmt->execute([$today]);
        $stats['today_posts'] = $stmt->fetchColumn() ?: 0;
        
        // 获取总回复数
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM forum_replies");
        $stmt->execute();
        $stats['total_replies'] = $stmt->fetchColumn() ?: 0;
        
        // 获取活跃主题数（最近7天有活动的主题）
        $weekAgo = date('Y-m-d', strtotime('-7 days'));
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT id) 
            FROM forum_posts 
            WHERE created_at >= ? 
            OR id IN (
                SELECT DISTINCT post_id 
                FROM forum_replies 
                WHERE created_at >= ?
            )
        ");
        $stmt->execute([$weekAgo, $weekAgo]);
        $stats['active_topics'] = $stmt->fetchColumn() ?: 0;
        
        // 获取总会员数
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
        $stmt->execute();
        $stats['total_members'] = $stmt->fetchColumn() ?: 0;
        
    } catch (PDOException $e) {
        error_log("Forum stats error: " . $e->getMessage());
    }
    
    return $stats;
}

// 获取热门标签
function getPopularTags($pdo) {
    $tags = [];
    try {
        $stmt = $pdo->query("
            SELECT t.name, t.description, COUNT(pt.tag_id) as count 
            FROM forum_tags t 
            LEFT JOIN post_tags pt ON t.id = pt.tag_id 
            GROUP BY t.id 
            ORDER BY count DESC, t.name ASC 
            LIMIT 12
        ");
        $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 如果没有标签，返回默认值
        if (empty($tags)) {
            $defaultTags = [
                ['name' => 'Hardware', 'description' => 'Tech Hardware Discussions', 'count' => 24],
                ['name' => 'Software', 'description' => 'Software & Apps', 'count' => 18],
                ['name' => 'Reviews', 'description' => 'Product Reviews', 'count' => 15],
                ['name' => 'Troubleshooting', 'description' => 'Technical Support', 'count' => 12],
                ['name' => 'Deals', 'description' => 'Deals & Discounts', 'count' => 9],
                ['name' => 'News', 'description' => 'Tech News', 'count' => 7]
            ];
            return $defaultTags;
        }
    } catch (PDOException $e) {
        error_log("Popular tags error: " . $e->getMessage());
    }
    
    return $tags;
}

// 获取最新讨论
function getLatestDiscussions($pdo) {
    $discussions = [];
    try {
        $stmt = $pdo->query("
            SELECT p.*, u.username as author_username
            FROM forum_posts p
            LEFT JOIN users u ON p.user_id = u.id
            ORDER BY p.created_at DESC
            LIMIT 10
        ");
        
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($posts as $post) {
            // 获取帖子的标签
            $tagStmt = $pdo->prepare("
                SELECT t.name 
                FROM forum_tags t
                JOIN post_tags pt ON t.id = pt.tag_id
                WHERE pt.post_id = ?
            ");
            $tagStmt->execute([$post['id']]);
            $tags = $tagStmt->fetchAll(PDO::FETCH_COLUMN);
            
            // 计算最后活动时间
            $lastActivity = strtotime($post['updated_at']);
            $now = time();
            $diff = $now - $lastActivity;
            
            if ($diff < 3600) {
                $lastActivityText = floor($diff / 60) . ' minutes ago';
            } elseif ($diff < 86400) {
                $lastActivityText = floor($diff / 3600) . ' hours ago';
            } else {
                $lastActivityText = floor($diff / 86400) . ' days ago';
            }
            
            // 获取作者头像首字母
            $authorName = $post['author_name'] ?? $post['author_username'] ?? 'Anonymous';
            $authorAvatar = strtoupper(substr($authorName, 0, 2));
            
            $discussions[] = [
                'id' => $post['id'],
                'title' => htmlspecialchars($post['title']),
                'author' => $authorName,
                'author_avatar' => $authorAvatar,
                'replies' => $post['replies_count'] ?? 0,
                'likes' => $post['likes'] ?? 0,
                'views' => $post['views'] ?? 0,
                'last_activity' => $lastActivityText,
                'tags' => $tags ?: ['General']
            ];
        }
        
        // 如果没有帖子，返回示例数据
        if (empty($discussions)) {
            return [
                [
                    'id' => 1,
                    'title' => 'Welcome to TechStore Forum!',
                    'author' => 'Admin',
                    'author_avatar' => 'AD',
                    'replies' => 0,
                    'likes' => 0,
                    'views' => 0,
                    'last_activity' => 'Just now',
                    'tags' => ['Welcome']
                ]
            ];
        }
        
    } catch (PDOException $e) {
        error_log("Latest discussions error: " . $e->getMessage());
        
        // 发生错误时返回示例数据
        return [
            [
                'id' => 1,
                'title' => 'Best Laptop for Programming in 2024?',
                'author' => 'AlexChen',
                'author_avatar' => 'AC',
                'replies' => 15,
                'likes' => 42,
                'views' => 320,
                'last_activity' => '2 hours ago',
                'tags' => ['Hardware', 'Software']
            ]
        ];
    }
    
    return $discussions;
}

// 处理新帖子提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_post') {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $tags = $_POST['tags'] ?? [];
        
        if (!empty($title) && !empty($content)) {
            try {
                // 获取当前用户信息
                $user_id = $_SESSION['user_id'] ?? 0;
                $author_name = $_SESSION['username'] ?? 'Anonymous';
                
                if ($user_id === 0) {
                    // 如果没有登录，显示错误信息
                    $_SESSION['forum_error'] = 'Please login to create a discussion.';
                    header('Location: login.php');
                    exit;
                }
                
                // 保存帖子到数据库
                $stmt = $pdo->prepare("
                    INSERT INTO forum_posts (title, content, user_id, author_name, created_at) 
                    VALUES (?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    htmlspecialchars($title), 
                    htmlspecialchars($content), 
                    $user_id, 
                    $author_name
                ]);
                
                $post_id = $pdo->lastInsertId();
                
                // 保存标签
                if (!empty($tags) && is_array($tags)) {
                    foreach ($tags as $tagName) {
                        $tagName = trim(htmlspecialchars($tagName));
                        if (!empty($tagName)) {
                            // 获取或创建标签
                            $tagStmt = $pdo->prepare("
                                INSERT IGNORE INTO forum_tags (name) VALUES (?)
                            ");
                            $tagStmt->execute([$tagName]);
                            
                            // 获取标签ID
                            $tagIdStmt = $pdo->prepare("SELECT id FROM forum_tags WHERE name = ?");
                            $tagIdStmt->execute([$tagName]);
                            $tag_id = $tagIdStmt->fetchColumn();
                            
                            if ($tag_id) {
                                // 关联帖子与标签
                                $linkStmt = $pdo->prepare("
                                    INSERT IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)
                                ");
                                $linkStmt->execute([$post_id, $tag_id]);
                            }
                        }
                    }
                }
                
                $_SESSION['forum_message'] = 'New discussion created successfully!';
                $_SESSION['new_post_id'] = $post_id;
                
                // 重定向到新帖子页面
                header('Location: forum.php');
                exit;
                
            } catch (PDOException $e) {
                error_log("Create post error: " . $e->getMessage());
                $_SESSION['forum_error'] = 'Failed to create discussion. Please try again.';
            }
        } else {
            $_SESSION['forum_error'] = 'Title and content are required.';
        }
    }
}

// 检查是否有成功/错误消息
$forum_message = $_SESSION['forum_message'] ?? '';
$forum_error = $_SESSION['forum_error'] ?? '';
unset($_SESSION['forum_message'], $_SESSION['forum_error']);

// 获取数据
$forumStats = getForumStats($pdo);
$popularTags = getPopularTags($pdo);
$latestDiscussions = getLatestDiscussions($pdo);

// 获取所有标签用于选择
$allTags = [];
try {
    $stmt = $pdo->query("SELECT name FROM forum_tags ORDER BY name");
    $allTags = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Get all tags error: " . $e->getMessage());
}

function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Forum - TechStore</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="logo.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #007bff;
            --primary-dark: #0056b3;
            --accent: #28a745;
            --accent-dark: #218838;
            --warning: #ffc107;
            --danger: #dc3545;
            --bg: #f8f9fa;
            --card-bg: #ffffff;
            --text: #212529;
            --text-light: #6c757d;
            --border: #e9ecef;
            --shadow: 0 6px 20px rgba(0,0,0,0.08);
            --shadow-light: 0 4px 12px rgba(0,0,0,0.05);
            --radius: 12px;
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.7;
            margin: 0;
            padding: 0;
            position: relative;
            min-height: 100vh;
            padding-bottom: 120px; /* 为页脚留出空间 */
        }

        /* 消息样式 */
        .forum-message {
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: var(--radius);
            margin: 1rem auto;
            max-width: 1200px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideDown 0.3s ease;
        }

        .forum-error {
            background: linear-gradient(135deg, var(--danger) 0%, #c82333 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: var(--radius);
            margin: 1rem auto;
            max-width: 1200px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .forum-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 2rem;
        }

        /* Hero Section */
        .forum-hero {
            grid-column: 1 / -1;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: var(--radius);
            padding: 2.5rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .forum-hero::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            width: 300px;
            background: url('https://images.unsplash.com/photo-1558494949-ef010cbdcc31?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80') center/cover no-repeat;
            opacity: 0.1;
        }

        .forum-hero h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 2.8rem;
            font-weight: 700;
            margin: 0 0 1rem 0;
            position: relative;
            z-index: 1;
        }

        .forum-hero p {
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 0 2rem 0;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: var(--radius);
            padding: 1.5rem;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            background: rgba(255, 255, 255, 0.15);
        }

        .stat-number {
            font-family: 'Poppins', sans-serif;
            font-size: 2.2rem;
            font-weight: 700;
            display: block;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Main Content */
        .forum-main {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .forum-section {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 2rem;
            box-shadow: var(--shadow-light);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border);
        }

        .section-header h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.6rem;
            margin: 0;
            color: var(--text);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius);
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
        }

        /* Discussion List */
        .discussion-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .discussion-card {
            background: var(--bg);
            border-radius: var(--radius);
            padding: 1.5rem;
            border: 1px solid var(--border);
            transition: var(--transition);
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 1.5rem;
            align-items: center;
        }

        .discussion-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
            border-color: var(--primary);
        }

        .author-avatar {
            width: 50px;
            height: 50px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .discussion-content {
            flex: 1;
        }

        .discussion-title {
            font-family: 'Poppins', sans-serif;
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0 0 0.5rem 0;
            color: var(--text);
        }

        .discussion-title a {
            color: inherit;
            text-decoration: none;
        }

        .discussion-title a:hover {
            color: var(--primary);
        }

        .discussion-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.9rem;
            color: var(--text-light);
        }

        .discussion-author {
            font-weight: 500;
            color: var(--primary);
        }

        .discussion-tags {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .tag {
            background: rgba(0, 123, 255, 0.1);
            color: var(--primary);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .discussion-stats {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            text-align: center;
            min-width: 80px;
        }

        .stat-item {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: var(--text-light);
        }

        .stat-item i {
            font-size: 1rem;
        }

        .stat-replies i { color: var(--primary); }
        .stat-likes i { color: var(--accent); }
        .stat-views i { color: var(--warning); }

        /* Sidebar */
        .forum-sidebar {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .sidebar-widget {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow-light);
        }

        .widget-title {
            font-family: 'Poppins', sans-serif;
            font-size: 1.2rem;
            margin: 0 0 1.5rem 0;
            color: var(--text);
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--border);
        }

        /* Tags Widget */
        .tags-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .tag-large {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 0.75rem 1rem;
            border-radius: var(--radius);
            font-weight: 600;
            flex: 1;
            min-width: 120px;
            text-align: center;
            transition: var(--transition);
            cursor: pointer;
            border: none;
            font-family: inherit;
        }

        .tag-large:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        }

        .tag-description {
            display: block;
            font-size: 0.8rem;
            opacity: 0.9;
            margin-top: 0.25rem;
        }

        /* Quick Post Form */
        .quick-post-form {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: var(--radius);
            padding: 2rem;
            margin-top: 2rem;
            margin-bottom: 3rem; /* 为页脚留出更多空间 */
            position: relative;
            z-index: 10;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--border);
            border-radius: var(--radius);
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            background: white;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .tag-selector {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .tag-option {
            background: var(--bg);
            border: 2px solid var(--border);
            border-radius: 20px;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .tag-option:hover {
            border-color: var(--primary);
            background: rgba(0, 123, 255, 0.1);
        }

        .tag-option.selected {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .tag-option input {
            display: none;
        }

        .btn-submit {
            width: 100%;
            padding: 0.85rem;
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-size: 1rem;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        /* 页脚修复 */
        footer.glass-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(8px);
            color: rgba(255, 255, 255, 0.8);
            padding: 1.2rem;
            text-align: center;
            z-index: 1000;
        }

        /* 响应式设计 */
        @media (max-width: 992px) {
            .forum-container {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .discussion-card {
                grid-template-columns: 1fr;
                text-align: center;
            }
        }

        @media (max-width: 768px) {
            .forum-hero h1 {
                font-size: 2.2rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .section-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .forum-container {
                padding: 0 1rem;
            }
            
            .forum-hero {
                padding: 1.5rem;
            }
            
            .forum-section, .sidebar-widget {
                padding: 1.25rem;
            }
            
            body {
                padding-bottom: 100px;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <!-- 显示消息 -->
    <?php if ($forum_message): ?>
    <div class="forum-message">
        <i class="fas fa-check-circle"></i>
        <?php echo htmlspecialchars($forum_message); ?>
    </div>
    <?php endif; ?>
    
    <?php if ($forum_error): ?>
    <div class="forum-error">
        <i class="fas fa-exclamation-triangle"></i>
        <?php echo htmlspecialchars($forum_error); ?>
    </div>
    <?php endif; ?>

    <div class="forum-container">
        <!-- Hero Section -->
        <section class="forum-hero">
            <h1>TechStore Community Forum</h1>
            <p>Connect with fellow tech enthusiasts, share your experiences, and get expert advice on the latest technology and gadgets.</p>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-number"><?php echo $forumStats['today_posts']; ?></span>
                    <span class="stat-label">Today's Posts</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo $forumStats['total_replies']; ?></span>
                    <span class="stat-label">Total Replies</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo $forumStats['active_topics']; ?></span>
                    <span class="stat-label">Active Topics</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo $forumStats['total_members']; ?></span>
                    <span class="stat-label">Community Members</span>
                </div>
            </div>
        </section>

        <!-- Main Content -->
        <main class="forum-main">
            <!-- Latest Discussions -->
            <section class="forum-section">
                <div class="section-header">
                    <h2>Latest Discussions</h2>
                    <button class="btn-primary" onclick="document.getElementById('new-post-form').scrollIntoView({behavior: 'smooth'})">
                        <i class="fas fa-plus"></i> Start New Discussion
                    </button>
                </div>
                
                <div class="discussion-list">
                    <?php foreach ($latestDiscussions as $discussion): ?>
                    <div class="discussion-card" data-id="<?php echo $discussion['id']; ?>">
                        <div class="author-avatar"><?php echo $discussion['author_avatar']; ?></div>
                        
                        <div class="discussion-content">
                            <h3 class="discussion-title">
                                <a href="forum-topic.php?id=<?php echo $discussion['id']; ?>"><?php echo $discussion['title']; ?></a>
                            </h3>
                            
                            <div class="discussion-meta">
                                <span class="discussion-author"><?php echo $discussion['author']; ?></span>
                                <span>•</span>
                                <span>Last activity: <?php echo $discussion['last_activity']; ?></span>
                            </div>
                            
                            <div class="discussion-tags">
                                <?php foreach ($discussion['tags'] as $tag): ?>
                                <span class="tag">#<?php echo htmlspecialchars($tag); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="discussion-stats">
                            <div class="stat-item stat-replies">
                                <i class="fas fa-comment"></i>
                                <span><?php echo $discussion['replies']; ?> replies</span>
                            </div>
                            <div class="stat-item stat-likes">
                                <i class="fas fa-heart"></i>
                                <span><?php echo $discussion['likes']; ?> likes</span>
                            </div>
                            <div class="stat-item stat-views">
                                <i class="fas fa-eye"></i>
                                <span><?php echo $discussion['views']; ?> views</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Quick Post Form -->
            <section class="forum-section quick-post-form" id="new-post-form">
                <h2 style="margin: 0 0 1.5rem 0; font-family: 'Poppins', sans-serif;">Start a New Discussion</h2>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                <form method="post" action="">
                    <input type="hidden" name="action" value="create_post">
                    
                    <div class="form-group">
                        <input type="text" name="title" class="form-control" placeholder="Discussion Title" required>
                    </div>
                    
                    <div class="form-group">
                        <textarea name="content" class="form-control" placeholder="What would you like to discuss? Be descriptive..." required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Select Tags:</label>
                        <div class="tag-selector" id="tagSelector">
                            <?php foreach ($allTags as $tag): ?>
                            <label class="tag-option">
                                <input type="checkbox" name="tags[]" value="<?php echo htmlspecialchars($tag); ?>">
                                <?php echo htmlspecialchars($tag); ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <small style="display: block; margin-top: 0.5rem; color: var(--text-light);">You can also type new tags in the text box below</small>
                    </div>
                    
                    <div class="form-group">
                        <input type="text" name="new_tags" class="form-control" placeholder="Add custom tags (separate with commas)">
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Publish Discussion
                    </button>
                </form>
                <?php else: ?>
                <div style="text-align: center; padding: 3rem 1rem;">
                    <i class="fas fa-user-lock" style="font-size: 3rem; color: var(--text-light); margin-bottom: 1.5rem;"></i>
                    <h3 style="margin: 0 0 1rem 0; color: var(--text);">Login Required</h3>
                    <p style="color: var(--text-light); margin-bottom: 2rem;">Please login to start a new discussion.</p>
                    <a href="login.php" class="btn-primary" style="text-decoration: none;">
                        <i class="fas fa-sign-in-alt"></i> Login to Continue
                    </a>
                </div>
                <?php endif; ?>
            </section>
        </main>

        <!-- Sidebar -->
        <aside class="forum-sidebar">
            <!-- Popular Tags -->
            <div class="sidebar-widget">
                <h3 class="widget-title">Popular Tags</h3>
                <div class="tags-grid">
                    <?php foreach ($popularTags as $tag): ?>
                    <button type="button" class="tag-large" onclick="addTagToForm('<?php echo htmlspecialchars($tag['name']); ?>')">
                        #<?php echo htmlspecialchars($tag['name']); ?>
                        <span class="tag-description"><?php echo htmlspecialchars($tag['description'] ?? ''); ?></span>
                        <?php if (isset($tag['count'])): ?>
                        <span class="tag-description"><?php echo $tag['count']; ?> posts</span>
                        <?php endif; ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Community Guidelines -->
            <div class="sidebar-widget">
                <h3 class="widget-title">Community Guidelines</h3>
                <ul style="margin: 0; padding-left: 1.2rem; color: var(--text-light); font-size: 0.9rem; line-height: 1.8;">
                    <li>Be respectful to all members</li>
                    <li>Stay on topic in discussions</li>
                    <li>No spam or self-promotion</li>
                    <li>Share knowledge and help others</li>
                    <li>Report inappropriate content</li>
                </ul>
            </div>

            <!-- Quick Links -->
            <div class="sidebar-widget">
                <h3 class="widget-title">Quick Links</h3>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <a href="index.php" style="display: flex; align-items: center; gap: 0.75rem; color: var(--primary); text-decoration: none; padding: 0.75rem; background: var(--bg); border-radius: var(--radius); transition: var(--transition);">
                        <i class="fas fa-home"></i>
                        <span>Back to Home</span>
                    </a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="profile.php" style="display: flex; align-items: center; gap: 0.75rem; color: var(--primary); text-decoration: none; padding: 0.75rem; background: var(--bg); border-radius: var(--radius); transition: var(--transition);">
                        <i class="fas fa-user"></i>
                        <span>My Profile</span>
                    </a>
                    <?php endif; ?>
                    <a href="products.php" style="display: flex; align-items: center; gap: 0.75rem; color: var(--primary); text-decoration: none; padding: 0.75rem; background: var(--bg); border-radius: var(--radius); transition: var(--transition);">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Browse Products</span>
                    </a>
                </div>
            </div>
        </aside>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 标签选择器
            const tagOptions = document.querySelectorAll('.tag-option');
            tagOptions.forEach(option => {
                option.addEventListener('click', function(e) {
                    e.preventDefault();
                    const checkbox = this.querySelector('input[type="checkbox"]');
                    checkbox.checked = !checkbox.checked;
                    this.classList.toggle('selected', checkbox.checked);
                });
            });

            // 表单提交验证
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const title = this.querySelector('input[name="title"]');
                    const content = this.querySelector('textarea[name="content"]');
                    
                    if (!title.value.trim()) {
                        e.preventDefault();
                        alert('Please enter a discussion title.');
                        title.focus();
                        return;
                    }
                    
                    if (!content.value.trim()) {
                        e.preventDefault();
                        alert('Please enter discussion content.');
                        content.focus();
                        return;
                    }
                    
                    // 处理自定义标签
                    const newTagsInput = this.querySelector('input[name="new_tags"]');
                    if (newTagsInput && newTagsInput.value.trim()) {
                        const newTags = newTagsInput.value.split(',').map(tag => tag.trim()).filter(tag => tag);
                        newTags.forEach(tag => {
                            const hiddenInput = document.createElement('input');
                            hiddenInput.type = 'hidden';
                            hiddenInput.name = 'tags[]';
                            hiddenInput.value = tag;
                            this.appendChild(hiddenInput);
                        });
                    }
                    
                    // 显示加载状态
                    const submitBtn = this.querySelector('button[type="submit"]');
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publishing...';
                    submitBtn.disabled = true;
                });
            }

            // 点赞功能
            document.querySelectorAll('.stat-likes').forEach(likeBtn => {
                likeBtn.style.cursor = 'pointer';
                likeBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const countElement = this.querySelector('span');
                    let count = parseInt(countElement.textContent) || 0;
                    countElement.textContent = (count + 1) + ' likes';
                    this.style.color = 'var(--accent)';
                    
                    // 可以发送AJAX请求到服务器保存点赞
                    const discussionCard = this.closest('.discussion-card');
                    const postId = discussionCard ? discussionCard.dataset.id : null;
                    
                    if (postId) {
                        fetch('forum-like.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                action: 'like',
                                post_id: postId
                            })
                        }).catch(err => console.error('Like error:', err));
                    }
                });
            });

            // 自动滚动到新帖子（如果有的话）
            <?php if (isset($_SESSION['new_post_id'])): ?>
                const newPostId = <?php echo $_SESSION['new_post_id']; ?>;
                const newPostElement = document.querySelector(`.discussion-card[data-id="${newPostId}"]`);
                if (newPostElement) {
                    newPostElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    newPostElement.style.animation = 'pulse 2s infinite';
                    
                    // 添加脉冲动画
                    const style = document.createElement('style');
                    style.textContent = `
                        @keyframes pulse {
                            0% { box-shadow: 0 0 0 0 rgba(0, 123, 255, 0.4); }
                            70% { box-shadow: 0 0 0 10px rgba(0, 123, 255, 0); }
                            100% { box-shadow: 0 0 0 0 rgba(0, 123, 255, 0); }
                        }
                    `;
                    document.head.appendChild(style);
                }
            <?php unset($_SESSION['new_post_id']); endif; ?>
        });

        // 将标签添加到表单的函数
        function addTagToForm(tagName) {
            const tagSelector = document.getElementById('tagSelector');
            const tagOptions = tagSelector.querySelectorAll('.tag-option');
            
            // 查找是否已有该标签
            let found = false;
            tagOptions.forEach(option => {
                const checkbox = option.querySelector('input[type="checkbox"]');
                if (checkbox.value === tagName) {
                    checkbox.checked = !checkbox.checked;
                    option.classList.toggle('selected', checkbox.checked);
                    found = true;
                }
            });
            
            // 如果没有找到，添加到自定义标签输入框
            if (!found) {
                const newTagsInput = document.querySelector('input[name="new_tags"]');
                if (newTagsInput) {
                    const currentTags = newTagsInput.value.split(',').map(tag => tag.trim()).filter(tag => tag);
                    if (!currentTags.includes(tagName)) {
                        currentTags.push(tagName);
                        newTagsInput.value = currentTags.join(', ');
                        
                        // 显示成功消息
                        const message = document.createElement('div');
                        message.textContent = `Tag "${tagName}" added to custom tags`;
                        message.style.cssText = 'background: var(--accent); color: white; padding: 0.5rem 1rem; border-radius: var(--radius); margin-top: 0.5rem; font-size: 0.9rem;';
                        newTagsInput.parentNode.insertBefore(message, newTagsInput.nextSibling);
                        
                        setTimeout(() => message.remove(), 3000);
                    }
                }
            }
        }

        // 平滑滚动
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
