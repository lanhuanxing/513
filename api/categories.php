<?php
// api/categories.php
require_once '../config.php';   // 引入 PDO 实例 $pdo
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT DISTINCT category FROM products ORDER BY category");
    $cats = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    echo json_encode($cats ?: []);   // 永远返回数组
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([]);            // 失败也返回空数组
}
?>