document.getElementById('add-to-cart').addEventListener('click', function () {
    // 商品詳細信息  
    const productId = this.getAttribute('data-id'); // 假設的商品ID  
    const productName = this.getAttribute('data-name');
    const productPrice = parseFloat(this.getAttribute('data-price')); // 假設的價格  
    const productImg = this.getAttribute('data-img');

    // 儲存商品信息到 localStorage  
    const cartItems = JSON.parse(localStorage.getItem('cartItems')) || [];
    const existingItem = cartItems.find(item => item.id === productId);

    if (!existingItem) {
        // 如果商品不在購物車中，則添加  
        cartItems.push({ id: productId, name: productName, price: productPrice, img: productImg, quantity: 1 });
        localStorage.setItem('cartItems', JSON.stringify(cartItems));
        this.textContent = '已加入購物車'; // 更新按鈕文本  
    } else {
        alert('This product is already in your cart');
        window.location.href = 'cart.php';
    }
    updateCartBadge();
    console.log('Add to Cart button:', addToCartButton);
});

// 當用戶移除商品時，重置按鈕文本  
function resetCartButton() {
    const cartItems = JSON.parse(localStorage.getItem('cartItems')) || [];
    const productId = 'CS'; // 假設的商品ID  
    const itemInCart = cartItems.find(item => item.id === productId);

    if (!itemInCart) {
        document.getElementById('add-to-cart').textContent = '加入購物車';
    }
    updateCartBadge();
} 