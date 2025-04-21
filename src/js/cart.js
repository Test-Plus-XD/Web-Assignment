// Stripe Checkout
document.getElementById("checkoutButton").addEventListener("click", async () => {
    //confirm("Proceed to checkout?");
    const cartItems = JSON.parse(localStorage.getItem("cartItems")) || [];

    const formattedItems = await Promise.all(cartItems.map(async item => {
        const product = await fetchProductById(item.pid);
        return {
            name: product.cardTitle ?? "Untitled Product",
            price: product.itemPrice ?? 0,
            quantity: item.quantity ?? 1
        };
    }));

    const response = await fetch('http://localhost:4242/checkout', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ items: formattedItems })
    });

    const data = await response.json();
    if (data.url) {
        window.location.href = data.url; // Redirect to Stripe
    }
});

// Helper function to fetch product details by Document ID from the Class_products.php API
async function fetchProductById(productId) {
    const url = `http://localhost/Web%20Assignment/Class_products.php/product/${encodeURIComponent(productId)}`; // Construct the API URL
    try {
        const response = await fetch(url, {
            method: 'GET', // Use GET method
            headers: {
                'Accept': 'application/json' // Expect JSON response
            }
        });

        if (!response.ok) {
            // Handle HTTP errors (e.g., 404 Not Found, 500 Server Error)
            const errorBody = await response.text(); // Get error response body
            console.error(`HTTP error fetching product ${productId}: ${response.status} ${response.statusText}`, errorBody);
            // Return an error indicator or null, depending on desired behavior
            return { error: `Failed to fetch product ${productId}: ${response.status}` };
        }

        const productData = await response.json(); // Parse the JSON response

        if (productData && productData.error) {
            // Handle API-specific errors returned in the JSON body
            console.error(`API error fetching product ${productId}:`, productData.error);
            return { error: `API error fetching product ${productId}: ${productData.error}` };
        }
        // Assuming the API returns the product object directly on success
        return productData;
    } catch (error) {
        // Handle network errors, JSON parsing errors, etc.
        console.error(`Error fetching product ${productId}:`, error);
        return { error: `Error fetching product ${productId}: ${error.message}` };
    }
}

// Fetch cart items and display them
document.addEventListener("DOMContentLoaded", async () => {
    // Retrieve cart items from localStorage. Assume cartItems is [{ pid: 'product_id_1', quantity: 1 }, ...]
    const cartItems = JSON.parse(localStorage.getItem("cartItems")) || [];
    const cartContainer = document.getElementById("cart-items");
    const totalElement = document.getElementById("cart-total"); // Element to display total

    if (cartItems.length === 0) {
        cartContainer.innerHTML = "<p>Your cart is empty.</p>";
        if (totalElement) totalElement.textContent = ''; // Clear total if exists
        return;
    }
    cartContainer.innerHTML = "<p>Loading cart items...</p>"; // Show loading indicator

    try {
        // Extract unique product IDs from cart items to avoid fetching the same product multiple times
        const uniqueProductIds = [...new Set(cartItems.map(item => item.pid))];

        // Create an array of Promises for fetching each unique product concurrently
        const fetchPromises = uniqueProductIds.map(productId => fetchProductById(productId));

        // Wait for all fetch promises to settle
        const productsResults = await Promise.all(fetchPromises);

        // Create a map of fetched products keyed by their ID for easy lookup
        const productsMap = {};
        let overallFetchError = false;

        productsResults.forEach(result => {
            if (result && !result.error) {
                // Assuming the product object has an 'ID' field for the document ID
                if (result.ID) {
                    productsMap[result.ID] = result;
                } else {
                    console.warn("Fetched product object is missing 'ID' field:", result);
                }
            } else {
                overallFetchError = true;
                console.error("Failed to fetch one or more product details:", result);
                // You might want to handle this more gracefully, e.g., display a partial cart
            }
        });

        // Clear the loading indicator/previous content
        cartContainer.innerHTML = "";
        let totalPrice = 0;

        // Iterate over the original cartItems array to display each item (including duplicates/quantities)
        if (!overallFetchError && Object.keys(productsMap).length > 0) { // Only proceed if some products were fetched successfully
            cartItems.forEach(cartItem => {
                const product = productsMap[cartItem.pid]; // Look up the product details by pid

                if (product) {
                    const quantity = cartItem.quantity || 1; // Use quantity from cart item, default to 1
                    const price = product.itemPrice ?? 0; // Use itemPrice from fetched product, default to 0 if missing
                    const itemSubtotal = price * quantity;
                    totalPrice += itemSubtotal;

                    // Add HTML for the cart item
                    cartContainer.innerHTML += `
                        <div style="border: 1px solid ccc; padding: 10px; margin-bottom: 10px; display: flex; align-items: center;">
                            <img src="${product.imageSrc ?? ''}" alt="${product.imageAlt ?? 'Product Image'}" style="width: 135px; height: auto; margin-right: 15px;">
                            <div style="flex-grow: 1;">
                                <h5>${product.cardTitle ?? 'Untitled Product'}</h5>
                                <p>HK$ ${price.toFixed(2)} x ${quantity} = HK$ ${itemSubtotal.toFixed(2)}</p>
                            </div>
                            <div>
                                <button class="btn btn-danger btn-sm" onclick="removeItem('${product.ID}')">Remove</button>
                            </div>
                        </div>
                    `;
                } else {
                    // Display a placeholder for items whose product details failed to fetch
                    cartContainer.innerHTML += `
                         <div style="border: 1px solid #ffc107; padding: 10px; margin-bottom: 10px; display: flex; align-items: center; background-color: #fff3cd;">
                            <div style="flex-grow: 1; color: grey;">
                                <p>Product ID: ${cartItem.pid} - Details unavailable</p>
                            </div>
                             <div>
                                 <button class="btn btn-secondary btn-sm" onclick="removeItem('${cartItem.pid}')">Remove</button>
                            </div>
                        </div>
                    `;
                    // Item is not included in the total price
                }
            });

            // Update Total Price Display
            if (totalElement) {
                totalElement.textContent = `Total Price: HK$ ${totalPrice.toFixed(2)}`;
            }
        } else if (overallFetchError) {
            // Display a general error if fetching products failed for multiple items
            cartContainer.innerHTML = "<p class='text-danger'>Error loading some cart items. Please try again.</p>";
            if (totalElement) totalElement.textContent = ''; // Clear total on general error
        } else {
            // This case should ideally not be hit if cartItems was not empty, but added as a safeguard
            cartContainer.innerHTML = "<p>Your cart is empty after processing.</p>";
            if (totalElement) totalElement.textContent = ''; // Clear total
        }
    } catch (error) {
        console.error("Error processing cart products:", error);
        cartContainer.innerHTML = "<p class='text-danger'>Failed to load cart items due to an unexpected error.</p>";
        if (totalElement) totalElement.textContent = ''; // Clear total on unexpected error
    }
});

// Remove Item Function. This function assumes productId is the document ID stored in localStorage
function removeItem(productId) {
    const cartItems = JSON.parse(localStorage.getItem("cartItems")) || [];
    // Filter out ALL items with the matching productId
    const updatedCart = cartItems.filter(item => item.pid !== productId);
    localStorage.setItem("cartItems", JSON.stringify(updatedCart));
    location.reload(); // Refresh the page to update the cart display
}
// updateCartDisplay() is removed as the display logic is now inside the DOMContentLoaded listener
updateCartBadge();