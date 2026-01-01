<?php
// test-db.php
require_once __DIR__ . '/config.php';

echo "<h1>Database Connection Test</h1>";

$pdo = getDatabaseConnection();
if ($pdo) {
    echo "<p style='color:green;'>✅ Database connection successful!</p>";
    
    // 测试查询
    try {
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<h3>Tables in database:</h3>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
    } catch (Exception $e) {
        echo "<p style='color:red;'>Query error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:red;'>❌ Database connection failed!</p>";
}
?>