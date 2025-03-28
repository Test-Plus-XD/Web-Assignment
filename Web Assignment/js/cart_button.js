document.addEventListener("DOMContentLoaded", async () => {
    const addToCartButton = document.getElementById("add-to-cart");
    if (!addToCartButton) {
        console.error("Add to Cart button not found.");
        return;
    }
    const productId = addToCartButton.getAttribute("data-product-id");
    console.log("Product ID from data attribute:", productId);
    // Fetch product details using the product id.
    try {
        const response = await fetch("Class_fetch.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest" // Ensure AJAX detection
            },
            // Send the product id in the JSON body with action "product"
            body: JSON.stringify({ action: "product", id: productId})
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const rawText = await response.text();
        // If the response appears to be HTML (e.g., starting with "<!DOCTYPE"), log an error.
        if (!rawText || rawText.trim().startsWith("<!DOCTYPE")) {
            console.error("Received HTML content instead of JSON. This may indicate extra output in PHP.");
            return;
        }

        let product;
        try {
            product = JSON.parse(rawText);
        } catch (e) {
            console.error("Failed to parse JSON:", e, rawText);
            return;
        }
        console.log("Product data fetched:", product);
        console.log("isOwned value fetched:", product.isOwned);
        if (!product || product.error) {
            console.error("Product data could not be fetched:", product?.error || "Unknown error");
            return;
        }

        // Get the Add to Cart button
        const addToCartButton = document.getElementById("add-to-cart");
        if (!addToCartButton) {
            console.error("Add to Cart button not found.");
            return;
        }

        // Attach event listener to the Add to Cart button
        addToCartButton.addEventListener("click", async () => {
            // Check if the user is logged in (assuming isLoggedIn is globally defined)
            if (!isLoggedIn) {
                alert("You must be logged in to add items to your cart.");
                window.location.href = "login.php";
                return;
            }

            // Destructure fields from product
            const { pid: productId, name: productName, price: productPrice, isFree, isOwned } = product;
            console.log("Product ID:", productId);
            console.log("Product data being sent:", { productId });

            if (product.isOwned) {
                alert(`${product.name} is already in your library.`);
                return;
            }

            if (isFree) {
                // Add to library if the product is free
                const response = await fetch("Class_fetch.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest"
                    },
                    body: JSON.stringify({ action: "libraryUpdate", productId: productId })
                });
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const result = await response.json();
                console.log("Library update response:", result);
                if (result.success) {
                    alert(`${productName} added to your library.`);
                    let count = parseInt(localStorage.getItem("libraryCount")) || 0;
                    count++;
                    localStorage.setItem("libraryCount", count);
                    updateLibraryBadge();
                } else {
                    alert(`Failed to add ${productName} to your library: ${result.message}`);
                }
            } else {
                // For paid products, add to cart.
                const cartItems = JSON.parse(localStorage.getItem("cartItems")) || [];
                if (!cartItems.some(item => item.pid === productId)) {
                    cartItems.push({ pid: productId, quantity: 1 });
                    localStorage.setItem("cartItems", JSON.stringify(cartItems));
                    alert(`${productName} added to your cart.`);
                    updateCartBadge();
                } else {
                    alert(`${productName} is already in your cart.`);
                }
            }
        });
    } catch (error) {
        console.error("Error fetching product data:", error);
    }
});