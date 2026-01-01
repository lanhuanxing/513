// app.js - TechStore 主JavaScript文件（增强版）
// 修改：添加首页检测，避免重复加载产品

// ====== 首页检测 ======
// 检查是否是首页，如果是则跳过产品加载
const currentPath = window.location.pathname;
const isHomePage = currentPath.includes('index.php') || 
                   currentPath === '/' || 
                   currentPath === '/techstore/' ||
                   currentPath.endsWith('.php') && !currentPath.includes('products.php');

// 如果首页已经有产品显示，跳过 app.js 的产品加载
if (isHomePage && document.getElementById('featuredProducts')) {
    console.log('检测到首页，app.js 将跳过产品加载，只初始化通用功能');
    
    // 只初始化通用功能，不加载产品
    document.addEventListener('DOMContentLoaded', function() {
        console.log('首页：初始化通用功能');
        
        // 购物车数量
        if (typeof cartFunctions !== 'undefined') {
            cartFunctions.initCartCount();
        }
        
        // 初始化 AOS
        if (typeof AOS !== 'undefined') {
            AOS.init({
                duration: 800,
                offset: 100,
                once: true,
                easing: 'ease-out-cubic'
            });
        }
        
        // 添加动画样式（如果不存在）
        if (!document.getElementById('global-animations')) {
            const style = document.createElement('style');
            style.id = 'global-animations';
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOut {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
                .loading { 
                    text-align: center; 
                    padding: 40px; 
                    font-size: 1.2em; 
                    color: #666; 
                }
                .product-meta { 
                    display: flex; 
                    gap: 8px; 
                    margin: 10px 0; 
                }
                .brand-tag, .category-tag { 
                    background: #e9ecef; 
                    padding: 4px 10px; 
                    border-radius: 12px; 
                    font-size: 0.8em; 
                    color: #495057; 
                }
                .product-image { 
                    height: 200px; 
                    overflow: hidden; 
                    border-radius: 10px; 
                    margin: 15px 0; 
                }
                .product-image img { 
                    width: 100%; 
                    height: 100%; 
                    object-fit: cover; 
                }
            `;
            document.head.appendChild(style);
        }
    });
    
    // 停止执行后续的 app.js 产品加载逻辑
    // 创建空函数占位，防止其他页面调用时报错
    window.productFunctions = window.productFunctions || {
        loadProducts: function() { console.log('首页：跳过产品加载'); },
        displayProducts: function() { console.log('首页：跳过产品显示'); },
        displayEmpty: function() { console.log('首页：跳过空状态显示'); }
    };
    
    // 停止执行后续代码
    throw new Error('首页检测：停止执行 app.js 的产品加载逻辑');
}

// ====== 通用功能（所有页面共用） ======

// 兜底：500 不崩 + 自动弹真实错误
async function safeFetch(url, options = {}) {
    try {
        const response = await fetch(url, options);
        if (!response.ok) {
            const text = await response.text();
            console.error(`🚨 ${url} 返回 ${response.status}`, text);
            let msg = 'Server error. Please try again.';
            try { const json = JSON.parse(text); msg = json.message || msg; } catch {}
            alert(msg);          // 立即弹真实错误
            return { success: false, message: msg };
        }
        return await response.json();
    } catch (err) {
        console.error('🚨 Network or CORS error:', err);
        alert('Network error. Please check connection.');
        return { success: false, message: 'Network error.' };
    }
}

// 全局购物车函数（500 兜底）
window.cartFunctions = {
    addToCart: async function(productId) {
        console.log("Adding product to cart...");
        const result = await safeFetch('api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', product_id: productId })
        });
        if (result.success) {
            this.showMessage('Product added to cart!', 'success');
            this.updateCartCount();
        } else {
            this.showMessage(result.message || 'Failed to add product to cart', 'error');
        }
    },

    updateCartCount: async function() {
        console.log("Updating cart count...");
        const result = await safeFetch('api/cart.php?action=count');
        const cartCount = document.getElementById('cartCount');
        if (!cartCount) return;
        if (result.success) {
            cartCount.textContent = result.count;
            cartCount.style.display = result.count > 0 ? 'inline' : 'none';
        }
    },

    showMessage: function(message, type = 'info') {
        const colors = { 
            success: '#28a745', 
            error: '#dc3545', 
            warning: '#ffc107', 
            info: '#17a2b8' 
        };
        
        const div = document.createElement('div');
        div.style.cssText = `
            position: fixed; 
            top: 20px; 
            right: 20px; 
            padding: 15px 25px;
            background: ${colors[type] || colors.info}; 
            color: white; 
            border-radius: 8px;
            z-index: 10000; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            font-weight: 500;
            animation: slideIn 0.3s ease;
        `;
        div.textContent = message;
        document.body.appendChild(div);
        
        setTimeout(() => {
            div.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => div.remove(), 300);
        }, 3000);
    },

    initCartCount: function() {
        this.updateCartCount();
    }
};

// 产品加载函数（500 兜底）
window.productFunctions = {
    loadProducts: async function(url, containerId) {
        console.log("Loading products from:", url);
        const products = await safeFetch(url);
        if (products && Array.isArray(products)) {
            this.displayProducts(products, containerId);
        } else {
            this.displayEmpty(containerId);
        }
    },

    displayProducts: function(products, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = products.map(product => `
            <div class="product-card" data-category="${product.category}">
                <div class="product-code">${product.product_code || 'PROD-' + product.id}</div>
                <h3>${product.name}</h3>
                <div class="product-image">
                    <img src="${product.image_url || 'https://images.unsplash.com/photo-1498049794561-7780e7231661?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80'}" 
                         alt="${product.name}" 
                         onerror="this.src='https://images.unsplash.com/photo-1498049794561-7780e7231661?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80'">
                </div>
                <p>${product.description ? product.description.substring(0, 80) + '…' : 'No description available.'}</p>
                <div class="product-meta">
                    <span class="brand-tag">${product.brand || 'Unknown Brand'}</span>
                    <span class="category-tag">${product.category || 'Uncategorized'}</span>
                </div>
                <p class="price">$${parseFloat(product.price || 0).toFixed(2)}</p>
                <button onclick="cartFunctions.addToCart(${product.id})" class="add-to-cart">
                    Add to Cart
                </button>
            </div>
        `).join('');
    },

    displayEmpty: function(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = `
            <div class="empty-state" style="grid-column: 1 / -1;">
                <div class="empty-state-icon">📦</div>
                <h3>No products found</h3>
                <p>Try adjusting your filters or browse all categories.</p>
                <a href="products.php" class="btn-primary">Browse All Products</a>
            </div>
        `;
    }
};

// 统一空态 / 加载 / 消息
window.utils = {
    showLoading: function(elementId) {
        const el = document.getElementById(elementId);
        if (el) el.innerHTML = '<div class="loading">Loading...</div>';
    },

    showMessage: function(message, type = 'info') {
        const colors = { success: '#28a745', error: '#dc3545', warning: '#ffc107', info: '#17a2b8' };
        const div = document.createElement('div');
        div.style.cssText = `
            position: fixed; top: 20px; right: 20px; padding: 15px 25px;
            background: ${colors[type]}; color: white; border-radius: 5px;
            z-index: 1000; animation: slideIn 0.3s ease;
        `;
        div.textContent = message;
        document.body.appendChild(div);
        setTimeout(() => div.remove(), 3000);
    }
};

// 统一加载动画
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    .loading { 
        text-align: center; 
        padding: 40px; 
        font-size: 1.2em; 
        color: #666; 
    }
    .empty-state { 
        text-align: center; 
        padding: 4rem 1rem; 
        color: #6c757d; 
        grid-column: 1 / -1;
    }
    .empty-state-icon { 
        font-size: 3rem; 
        margin-bottom: 1rem; 
        opacity: .4; 
    }
    .empty-state h3 { 
        margin-bottom: .5rem; 
        font-weight: 500; 
    }
    .empty-state a { 
        margin-top: 1rem; 
        display: inline-block; 
        background: #007bff;
        color: white;
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
    }
    .product-meta { 
        display: flex; 
        gap: 8px; 
        margin: 10px 0; 
    }
    .brand-tag, .category-tag { 
        background: #e9ecef; 
        padding: 4px 10px; 
        border-radius: 12px; 
        font-size: 0.8em; 
        color: #495057; 
    }
    .product-image { 
        height: 200px; 
        overflow: hidden; 
        border-radius: 10px; 
        margin: 15px 0; 
    }
    .product-image img { 
        width: 100%; 
        height: 100%; 
        object-fit: cover; 
    }
`;
document.head.appendChild(style);

// 小工具
window.utils.formatPrice = function(price) {
    return '$' + parseFloat(price).toFixed(2);
};

window.utils.formatDate = function(dateString) {
    return new Date(dateString).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
};

// ====== 页面初始化（非首页） ======
document.addEventListener('DOMContentLoaded', function() {
    console.log("Document loaded. Initializing app.js...");
    
    // 购物车数量
    if (typeof cartFunctions !== 'undefined') {
        cartFunctions.initCartCount();
    }
    
    // 根据当前页面加载内容（非首页才执行）
    const path = window.location.pathname;
    
    // 检查是否是首页（再次确认）
    const isHome = path.includes('index.php') || path === '/' || path === '/techstore/';
    const featuredContainer = document.getElementById('featuredProducts');
    const hasHomeProducts = featuredContainer && featuredContainer.children.length > 0;
    
    if (!isHome || !hasHomeProducts) {
        // 不是首页，或者首页没有硬编码产品，才执行 API 加载
        
        if ((path.includes('index.php') || path === '/' || path === '/techstore/') && featuredContainer) {
            // 首页加载特色产品（备用，当硬编码失败时）
            console.log('首页：从 API 加载备用产品');
            productFunctions.loadProducts('api/products.php?limit=8', 'featuredProducts');
        }
        
        if (path.includes('products.php') && !path.includes('index.php')) {
            // 产品页面逻辑
            console.log('产品页面：加载所有产品');
            if (typeof window.loadAllProducts === 'function') {
                window.loadAllProducts();
            } else {
                // 备用：直接加载产品
                productFunctions.loadProducts('api/products.php', 'productsContainer');
            }
        }
    }
    
    // 初始化 AOS（所有页面）
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            offset: 100,
            once: true,
            easing: 'ease-out-cubic'
        });
    }
});