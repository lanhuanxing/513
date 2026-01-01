// cart.js - 独立的购物车功能
window.cartFunctions = {
    addToCart: async function(productId) {
        try {
            const response = await fetch('api/cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    action: 'add', 
                    product_id: productId 
                })
            });
            
            if (!response.ok) throw new Error('Failed to add to cart');
            
            const result = await response.json();
            
            if (result.success) {
                this.showMessage('Product added to cart!', 'success');
                this.updateCartCount();
            } else {
                this.showMessage(result.message || 'Failed to add product', 'error');
            }
        } catch (error) {
            console.error('Cart error:', error);
            this.showMessage('Network error. Please try again.', 'error');
        }
    },
    
    updateCartCount: async function() {
        try {
            const response = await fetch('api/cart.php?action=count');
            if (!response.ok) return;
            
            const result = await response.json();
            const cartCount = document.getElementById('cartCount');
            
            if (cartCount && result.success) {
                cartCount.textContent = result.count;
                cartCount.style.display = result.count > 0 ? 'inline' : 'none';
            }
        } catch (error) {
            console.error('Failed to update cart count:', error);
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
            background: ${colors[type]}; 
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
    
    init: function() {
        // 初始化购物车数量
        this.updateCartCount();
        
        // 添加动画样式
        if (!document.getElementById('cart-animations')) {
            const style = document.createElement('style');
            style.id = 'cart-animations';
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOut {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        }
    }
};

// 自动初始化
document.addEventListener('DOMContentLoaded', () => {
    if (window.cartFunctions) {
        window.cartFunctions.init();
    }
});