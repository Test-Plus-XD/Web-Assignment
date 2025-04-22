document.addEventListener("DOMContentLoaded", async () => {
    const boughtProductsContainer = document.getElementById("bought-products");
    const cartItems = JSON.parse(localStorage.getItem("cartItems")) || [];
    const sessionId = new URLSearchParams(window.location.search).get('session_id');

    if (!sessionId) {
        document.body.innerHTML = "<h2>Invalid access. No Stripe session id provided.</h2>";
        return;
    }

    if (!Array.isArray(cartItems) || cartItems.length === 0) {
        boughtProductsContainer.innerHTML = "<p>No items were found in your cart.</p>";
        return;
    }
    boughtProductsContainer.innerHTML = "Loading, please be patient....";

    // Fetch the Stripe session to verify payment
    try {
        const response = await fetch(`http://localhost:4242/verify-Stripe?session_id=${sessionId}`);
        const result = await response.json();

        if (!result.verified) {
            document.body.innerHTML = "<h2>Payment not confirmed. Please contact support.</h2>";
            return;
        }
        console.log("Stripe Session Verified:", result.session);

        // Fetch full product info using shared cart.js function
        const uniqueIds = [...new Set(cartItems.map(i => i.pid))];
        const fetchedProducts = await Promise.all(uniqueIds.map(pid => fetchProductById(pid)));

        boughtProductsContainer.innerHTML = "";

        // Only add the product if Stripe session is verified
        for (const cartItem of cartItems) {
            const product = fetchedProducts.find(p => p?.ID === cartItem.pid);
            if (!product) continue;

            const quantity = cartItem.quantity || 1;
            const price = product.itemPrice || 0;
            const total = price * quantity;

            // Submit purchase record for each item (per quantity)
            for (let i = 0; i < quantity; i++) {
                const payload = {
                    product_id: product.ID,
                    uid: User_id, // from <script> in PHP head
                    session: sessionId, // Stripe session ID
                    date: Date.now()
                };

                try {
                    const res = await fetch("http://localhost/Web%20Assignment/Class_purchases.php/purchase", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-Requested-With": "XMLHttpRequest"
                        },
                        body: JSON.stringify(payload)
                    });
                    if (!res.ok) {
                        console.error(`Purchase record failed for ${product.ID}`, await res.text());
                    }
                } catch (err) {
                    console.error(`Network error while recording purchase for ${product.ID}:`, err);
                }
                // Incree library count in localStorage after adding each item to Firestore
                localStorage.setItem("libraryCount", (parseInt(localStorage.getItem("libraryCount")) || 0) + 1);
            }

            // Display purchased product
            boughtProductsContainer.innerHTML += `
                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm">
                        <img src="${product.imageSrc}" class="card-img-top" alt="${product.imageAlt || 'Product'}">
                        <div class="card-body">
                            <h5 class="card-title">${product.cardTitle}</h5>
                            <p class="card-text">Quantity: ${quantity}</p>
                            <p class="card-text">Unit Price: HK$ ${price.toFixed(2)}</p>
                            <p class="card-text"><strong>Total: HK$ ${total.toFixed(2)}</strong></p>
                        </div>
                    </div>
                </div>
            `;
        }
        // Clean up cart and refresh badges
        localStorage.removeItem("cartItems");
        updateLibraryBadge();
        updateCartBadge();
    } catch (error) {
        console.error("Verification error:", error);
        document.body.innerHTML = "<h2>Verification failed. Please try again later.</h2>";
    }
});