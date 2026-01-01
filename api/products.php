<?php
// api/products.php - 产品数据 API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// 模拟数据库连接失败时的备用数据
function getHardcodedProducts() {
    return [
        [
            'id' => 1,
            'product_code' => 'ELEC-001',
            'name' => 'iPhone 15 Pro',
            'description' => 'The most advanced iPhone ever with A17 Pro chip, titanium design, and professional camera system.',
            'price' => 999.00,
            'brand' => 'Apple',
            'category' => 'Smartphones',
            'image_url' => 'https://images.unsplash.com/photo-1695048133142-1a20484d2569?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
            'stock_quantity' => 10,
            'specifications' => json_encode([
                'Processor' => 'A17 Pro',
                'Display' => '6.1-inch Super Retina XDR',
                'Storage' => ['128GB', '256GB', '512GB', '1TB'],
                'Camera' => '48MP Main + 12MP Ultra Wide + 12MP Telephoto'
            ])
        ],
        [
            'id' => 2,
            'product_code' => 'ELEC-002',
            'name' => 'Galaxy S24 Ultra',
            'description' => 'Epic in every way with advanced AI features, professional-grade camera, and titanium frame.',
            'price' => 1199.00,
            'brand' => 'Samsung',
            'category' => 'Smartphones',
            'image_url' => 'https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
            'stock_quantity' => 8,
            'specifications' => json_encode([
                'Processor' => 'Snapdragon 8 Gen 3',
                'Display' => '6.8-inch Dynamic AMOLED 2X',
                'Storage' => ['256GB', '512GB', '1TB'],
                'Camera' => '200MP Main + 12MP Ultra Wide + 10MP Telephoto'
            ])
        ],
        [
            'id' => 3,
            'product_code' => 'ELEC-003',
            'name' => 'MacBook Air M2',
            'description' => 'Lightning fast performance with M2 chip, stunning Retina display, and all-day battery life.',
            'price' => 1299.00,
            'brand' => 'Apple',
            'category' => 'Laptops',
            'image_url' => 'https://images.unsplash.com/photo-1541807084-5c52b6b3adef?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
            'stock_quantity' => 5,
            'specifications' => json_encode([
                'Processor' => 'Apple M2',
                'Display' => '13.6-inch Liquid Retina',
                'RAM' => ['8GB', '16GB', '24GB'],
                'Storage' => ['256GB', '512GB', '1TB', '2TB']
            ])
        ],
        [
            'id' => 4,
            'product_code' => 'ELEC-004',
            'name' => 'PS5 Console',
            'description' => 'Play Has No Limits. Experience lightning-fast loading with an ultra-high speed SSD.',
            'price' => 499.00,
            'brand' => 'Sony',
            'category' => 'Gaming',
            'image_url' => 'https://images.unsplash.com/photo-1606144042614-b2417e99c4e3?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
            'stock_quantity' => 15,
            'specifications' => json_encode([
                'Storage' => '825GB SSD',
                'Resolution' => '4K',
                'Frame Rate' => 'Up to 120fps',
                'Features' => ['Ray Tracing', '3D Audio', 'Backward Compatibility']
            ])
        ],
        [
            'id' => 5,
            'product_code' => 'ELEC-005',
            'name' => 'AirPods Pro 2',
            'description' => 'Adaptive Audio. Now playing. Active Noise Cancellation and Transparency mode.',
            'price' => 249.00,
            'brand' => 'Apple',
            'category' => 'Audio',
            'image_url' => 'https://images.unsplash.com/photo-1590658165737-15a047b8b5e6?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
            'stock_quantity' => 20,
            'specifications' => json_encode([
                'Battery Life' => '6 hours (with ANC)',
                'Noise Cancellation' => 'Yes',
                'Water Resistance' => 'IPX4',
                'Connectivity' => 'Bluetooth 5.3'
            ])
        ],
        [
            'id' => 6,
            'product_code' => 'ELEC-006',
            'name' => 'iPad Pro M2',
            'description' => 'The ultimate iPad experience with the M2 chip, Liquid Retina XDR display, and pro performance.',
            'price' => 1099.00,
            'brand' => 'Apple',
            'category' => 'Tablets',
            'image_url' => 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
            'stock_quantity' => 7,
            'specifications' => json_encode([
                'Processor' => 'Apple M2',
                'Display' => '12.9-inch Liquid Retina XDR',
                'Storage' => ['128GB', '256GB', '512GB', '1TB', '2TB'],
                'Camera' => '12MP Wide + 10MP Ultra Wide'
            ])
        ],
        [
            'id' => 7,
            'product_code' => 'ELEC-007',
            'name' => 'Xbox Series X',
            'description' => 'The fastest, most powerful Xbox ever. Experience true 4K gaming.',
            'price' => 499.00,
            'brand' => 'Microsoft',
            'category' => 'Gaming',
            'image_url' => 'https://images.unsplash.com/photo-1621259182978-fbf83132d22d?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
            'stock_quantity' => 12,
            'specifications' => json_encode([
                'Storage' => '1TB SSD',
                'Resolution' => 'True 4K',
                'Frame Rate' => 'Up to 120fps',
                'Features' => ['Quick Resume', 'Smart Delivery', 'Game Pass']
            ])
        ],
        [
            'id' => 8,
            'product_code' => 'ELEC-008',
            'name' => 'Sony WH-1000XM5',
            'description' => 'Industry-leading noise cancellation with 30-hour battery life.',
            'price' => 399.00,
            'brand' => 'Sony',
            'category' => 'Audio',
            'image_url' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
            'stock_quantity' => 18,
            'specifications' => json_encode([
                'Battery Life' => '30 hours',
                'Noise Cancellation' => 'Industry-leading',
                'Weight' => '250g',
                'Connectivity' => 'Bluetooth 5.2'
            ])
        ]
    ];
}

// 获取查询参数
$category = isset($_GET['category']) ? $_GET['category'] : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 0;
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : PHP_FLOAT_MAX;

// 获取产品数据
$products = getHardcodedProducts();

// 应用过滤器
$filteredProducts = $products;

// 按分类过滤
if (!empty($category)) {
    $filteredProducts = array_filter($filteredProducts, function($product) use ($category) {
        return strcasecmp($product['category'], $category) === 0;
    });
}

// 按价格范围过滤
$filteredProducts = array_filter($filteredProducts, function($product) use ($min_price, $max_price) {
    $price = floatval($product['price']);
    return $price >= $min_price && $price <= $max_price;
});

// 限制数量
if ($limit > 0 && count($filteredProducts) > $limit) {
    $filteredProducts = array_slice($filteredProducts, 0, $limit);
}

// 重新索引数组
$filteredProducts = array_values($filteredProducts);

// 输出 JSON
echo json_encode([
    'success' => true,
    'count' => count($filteredProducts),
    'products' => $filteredProducts
]);
?>