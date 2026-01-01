<?php
// forum.php - TechStore Community Forum
session_start();
require_once 'config.php';

// 获取数据库连接
$pdo = getDatabaseConnection(); // 添加这一行
if (!$pdo) {
    die("Database connection failed");
}

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
        $stats['today_posts'] = $stmt->fetchColumn();
        
        // 获取总回复数
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM forum_replies");
        $stmt->execute();
        $stats['total_replies'] = $stmt->fetchColumn();
        
        // 获取活跃主题数（最近7天有活动的主题）
        $weekAgo = date('Y-m-d', strtotime('-7 days'));
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT topic_id) FROM forum_activity WHERE activity_date >= ?");
        $stmt->execute([$weekAgo]);
        $stats['active_topics'] = $stmt->fetchColumn();
        
        // 获取总会员数
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
        $stmt->execute();
        $stats['total_members'] = $stmt->fetchColumn();
        
    } catch (PDOException $e) {
        error_log("Forum stats error: " . $e->getMessage());
    }
    
    return $stats;
}

// 获取热门标签
function getPopularTags($pdo) {
    $tags = [
        ['name' => 'Hardware', 'description' => 'Tech Hardware Discussions', 'count' => 24],
        ['name' => 'Software', 'description' => 'Software & Apps', 'count' => 18],
        ['name' => 'Reviews', 'description' => 'Product Reviews', 'count' => 15],
        ['name' => 'Troubleshooting', 'description' => 'Technical Support', 'count' => 12],
        ['name' => 'Deals', 'description' => 'Deals & Discounts', 'count' => 9],
        ['name' => 'News', 'description' => 'Tech News', 'count' => 7]
    ];
    
    return $tags;
}

// 获取最新讨论
function getLatestDiscussions($pdo) {
    $discussions = [
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
        ],
        [
            'id' => 2,
            'title' => 'iPhone 15 Pro vs Samsung S24 Ultra - Which to choose?',
            'author' => 'SarahTech',
            'author_avatar' => 'ST',
            'replies' => 28,
            'likes' => 67,
            'views' => 540,
            'last_activity' => '5 hours ago',
            'tags' => ['Reviews', 'Hardware']
        ],
        [
            'id' => 3,
            'title' => 'Windows 11 Update Causing Bluetooth Issues',
            'author' => 'MikeHelper',
            'author_avatar' => 'MH',
            'replies' => 9,
            'likes' => 23,
            'views' => 180,
            'last_activity' => '1 day ago',
            'tags' => ['Troubleshooting', 'Software']
        ],
        [
            'id' => 4,
            'title' => 'Black Friday Tech Deals Tracking Thread',
            'author' => 'DealFinder',
            'author_avatar' => 'DF',
            'replies' => 45,
            'likes' => 89,
            'views' => 920,
            'last_activity' => '2 days ago',
            'tags' => ['Deals', 'News']
        ],
        [
            'id' => 5,
            'title' => 'Building a Gaming PC - Parts Compatibility Check',
            'author' => 'GamerPro',
            'author_avatar' => 'GP',
            'replies' => 32,
            'likes' => 56,
            'views' => 680,
            'last_activity' => '3 days ago',
            'tags' => ['Hardware', 'Troubleshooting']
        ]
    ];
    
    return $discussions;
}

// 获取统计数据
$forumStats = getForumStats($pdo);
$popularTags = getPopularTags($pdo);
$latestDiscussions = getLatestDiscussions($pdo);

// 处理新帖子提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_post') {
        $title = sanitize_input($_POST['title'] ?? '');
        $content = sanitize_input($_POST['content'] ?? '');
        $tags = $_POST['tags'] ?? [];
        
        if (!empty($title) && !empty($content)) {
            // 这里添加保存到数据库的逻辑
            $_SESSION['forum_message'] = 'New discussion created successfully!';
            header('Location: forum.php');
            exit;
        }
    }
}

function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
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

        /* Trending Widget */
        .trending-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .trending-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--bg);
            border-radius: var(--radius);
            transition: var(--transition);
        }

        .trending-item:hover {
            background: white;
            box-shadow: var(--shadow-light);
        }

        .trending-rank {
            font-family: 'Poppins', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            min-width: 40px;
        }

        .trending-content h4 {
            margin: 0 0 0.25rem 0;
            font-size: 0.95rem;
        }

        .trending-content p {
            margin: 0;
            font-size: 0.85rem;
            color: var(--text-light);
        }

        /* Quick Post Form */
        .quick-post-form {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .form-group {
            margin-bottom: 1rem;
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
            min-height: 120px;
            resize: vertical;
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
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        /* Responsive Design */
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
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

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
                    <div class="discussion-card">
                        <div class="author-avatar"><?php echo $discussion['author_avatar']; ?></div>
                        
                        <div class="discussion-content">
                            <h3 class="discussion-title">
                                <a href="forum-topic.php?id=<?php echo $discussion['id']; ?>"><?php echo htmlspecialchars($discussion['title']); ?></a>
                            </h3>
                            
                            <div class="discussion-meta">
                                <span class="discussion-author"><?php echo htmlspecialchars($discussion['author']); ?></span>
                                <span>•</span>
                                <span>Last activity: <?php echo $discussion['last_activity']; ?></span>
                            </div>
                            
                            <div class="discussion-tags">
                                <?php foreach ($discussion['tags'] as $tag): ?>
                                <span class="tag">#<?php echo $tag; ?></span>
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
                
                <form method="post" action="">
                    <input type="hidden" name="action" value="create_post">
                    
                    <div class="form-group">
                        <input type="text" name="title" class="form-control" placeholder="Discussion Title" required>
                    </div>
                    
                    <div class="form-group">
                        <textarea name="content" class="form-control" placeholder="What would you like to discuss? Be descriptive..." required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <select name="tags[]" class="form-control" multiple style="height: auto; min-height: 80px;">
                            <option value="Hardware">Hardware</option>
                            <option value="Software">Software</option>
                            <option value="Reviews">Reviews</option>
                            <option value="Troubleshooting">Troubleshooting</option>
                            <option value="Deals">Deals</option>
                            <option value="News">News</option>
                        </select>
                        <small style="display: block; margin-top: 0.5rem; color: var(--text-light);">Hold Ctrl/Cmd to select multiple tags</small>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Publish Discussion
                    </button>
                </form>
            </section>
        </main>

        <!-- Sidebar -->
        <aside class="forum-sidebar">
            <!-- Popular Tags -->
            <div class="sidebar-widget">
                <h3 class="widget-title">Popular Tags</h3>
                <div class="tags-grid">
                    <?php foreach ($popularTags as $tag): ?>
                    <div class="tag-large">
                        #<?php echo $tag['name']; ?>
                        <span class="tag-description"><?php echo $tag['description']; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Trending Now -->
            <div class="sidebar-widget">
                <h3 class="widget-title">Trending Now</h3>
                <div class="trending-list">
                    <div class="trending-item">
                        <div class="trending-rank">1</div>
                        <div class="trending-content">
                            <h4>Best Wireless Earbuds 2024</h4>
                            <p>235 replies • 1.2k views</p>
                        </div>
                    </div>
                    <div class="trending-item">
                        <div class="trending-rank">2</div>
                        <div class="trending-content">
                            <h4>Windows 11 Performance Tips</h4>
                            <p>189 replies • 980 views</p>
                        </div>
                    </div>
                    <div class="trending-item">
                        <div class="trending-rank">3</div>
                        <div class="trending-content">
                            <h4>MacBook Air M3 Review</h4>
                            <p>156 replies • 850 views</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Community Guidelines -->
            <div class="sidebar-widget">
                <h3 class="widget-title">Community Guidelines</h3>
                <ul style="margin: 0; padding-left: 1.2rem; color: var(--text-light); font-size: 0.9rem;">
                    <li>Be respectful to all members</li>
                    <li>Stay on topic in discussions</li>
                    <li>No spam or self-promotion</li>
                    <li>Share knowledge and help others</li>
                    <li>Report inappropriate content</li>
                </ul>
            </div>
        </aside>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Smooth scroll for new post button
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

            // Form validation
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
                });
            }

            // Like functionality
            document.querySelectorAll('.stat-likes').forEach(likeBtn => {
                likeBtn.style.cursor = 'pointer';
                likeBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const countElement = this.querySelector('span');
                    let count = parseInt(countElement.textContent);
                    countElement.textContent = (count + 1) + ' likes';
                    this.style.color = 'var(--accent)';
                    
                    // Send AJAX request to server
                    fetch('forum-like.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'like',
                            discussion_id: this.closest('.discussion-card').dataset.id
                        })
                    });
                });
            });
        });
    </script>
</body>
</html>