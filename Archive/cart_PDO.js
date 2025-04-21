// Fetch cart items
document.addEventListener("DOMContentLoaded", async () => {
    const cartItems = JSON.parse(localStorage.getItem("cartItems")) || [];
    const cartContainer = document.getElementById("cart-items");

    if (cartItems.length === 0) {
        cartContainer.innerHTML = "<p>Your cart is empty.</p>";
        return;
    }
    // Fetch product details from the server
    try {
        const response = await fetch("fetch_product.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ action: "cart", productIds: cartItems.map(item => item.pid) }),
        });

        const products = await response.json();
        console.log("Fetched products:", products);

        if (!products || products.error) {
            cartContainer.innerHTML = `<p>${products.error || "Failed to load cart items"}</p>`;
            return;
        }

        cartContainer.innerHTML = ""; // Clear the container
        let totalPrice = 0;
        // Insert the HTML into the page
        products.forEach(product => {
            totalPrice += product.price;
            cartContainer.innerHTML += `
                <div style="border: 2px solid #007bff; padding: 10px; margin: 10px;">
                    <img src="${product.imageSrc}" alt="${product.imageAlt}" style="width: 100px; height: auto; padding: 10px;">
                    <span>${product.cardTitle} - HK$ ${product.price}</span>
                    <button onclick="removeItem(${product.product_id})" style="align-content: end; text-align: end;">Remove</button>
                </div>
            `;
        });
        // Total Price Calculation
        cartContainer.innerHTML += `<div>Total Price: HK$ ${totalPrice.toFixed(2)}</div>`;
    } catch (error) {
        console.error("Error fetching cart products:", error);
        cartContainer.innerHTML = "<p>Failed to load cart items.</p>";
    }
});
// Remove Item Function
function removeItem(productId) {
    const cartItems = JSON.parse(localStorage.getItem("cartItems")) || [];
    const updatedCart = cartItems.filter(item => item.pid !== productId);
    localStorage.setItem("cartItems", JSON.stringify(updatedCart));
    location.reload(); // Refresh the page to update the cart
}
// 初始載入時更新購物車顯示  
//updateCartDisplay();  
updateCartBadge();