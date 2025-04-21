// Fetch products from the Firestore database
document.addEventListener("DOMContentLoaded", async () => {
    console.log("cart_button.js - Global Login Status Read:", {
        isLoggedIn: isLoggedIn
    });

    const addToCartButton = document.getElementById("add-to-cart");
    if (!addToCartButton) {
        console.error("Add to Cart button not found.");
        return;
    }

    // Get the product's document ID from the button's data attribute
    const productId = addToCartButton.getAttribute("data-product-id");
    if (!productId) {
        console.error("Product ID not found on the Add to Cart button.");
        if (addToCartButton) {
            addToCartButton.disabled = true;
            addToCartButton.textContent = "Product ID Error";
        }
        return; // Exit if no product ID
    }
    console.log("Product ID from data attribute:", productId);

    let product = null; // Variable to store fetched product data

    // Fetch product details and perform initial ownership check on page load
    try {
        const productsApiEndpoint = `http://localhost/Web%20Assignment/Class_products.php/product/${productId}`;
        console.log("Fetching product details from:", productsApiEndpoint);
        const response = await fetch(productsApiEndpoint, {
            method: "GET", // Use GET for fetching a specific resource
            headers: {
                "X-Requested-With": "XMLHttpRequest" // Ensure AJAX detection
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error fetching product: status ${response.status}`);
        }

        product = await response.json();
        console.log("Product data fetched:", product);

        if (!product || product.error) {
            console.error("Product data could not be fetched:", product?.error || "Unknown error");
            // Disable the button if product data cannot be fetched
            if (addToCartButton) {
                addToCartButton.disabled = true;
                addToCartButton.textContent = "Product Unavailable";
            }
            return;
        }

        // At this point, 'product' data is loaded. Now, perform initial ownership check on page load
        // Use the global isLoggedIn and read user ID from data attribute on load
        const currentUserUidOnLoad = addToCartButton.getAttribute("data-user-id");

        if (isLoggedIn && currentUserUidOnLoad) { // Only check ownership if user is logged in (globally) and UID attribute is available
            const purchasesApiEndpoint = `http://localhost/Web%20Assignment/Class_purchases.php/product_uid/${productId}/${currentUserUidOnLoad}`;
            console.log("Checking product ownership on load from:", purchasesApiEndpoint);
            const ownershipResponse = await fetch(purchasesApiEndpoint, {
                method: "GET",
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                }
            });

            if (!ownershipResponse.ok) {
                console.error(`HTTP error checking ownership on load: status ${ownershipResponse.status}`);
                // Continue without ownership check results if API fails
            } else {
                const ownedProducts = await ownershipResponse.json();
                console.log("Ownership check response on load:", ownedProducts);
                if (ownedProducts && Array.isArray(ownedProducts) && ownedProducts.length > 0) {
                    product.isOwned = true;
                    console.log("User already owns this product (checked on load).");
                    if (addToCartButton) {
                        addToCartButton.textContent = "Owned"; // Update button text
                    }
                } else {
                    product.isOwned = false;
                    console.log("User does not own this product (checked on load).");
                }
            }
        } else {
            product.isOwned = false; // Not logged in or UID missing for check
            console.log("User not logged in or UID attribute missing on load for ownership check, does not own this product.");
        }


    } catch (error) {
        console.error("Error during initial product data fetch or ownership check:", error);
        // Disable the button on error
        if (addToCartButton) {
            addToCartButton.disabled = true;
            addToCartButton.textContent = "Error Loading Product";
        }
        return;
    }


    // Attach event listener to the Add to Cart button
    addToCartButton.addEventListener("click", async () => {
        console.log("Add to Cart button clicked.");

        // Check login status on click using global variable
        // Use the global isLoggedIn variable that should be set in head.php
        const isUserLoggedInOnClick = window.isLoggedIn ?? false;

        console.log("Click - Global Login Status Read Again:", {
            isLoggedIn: isUserLoggedInOnClick
        });

        // Check if user is logged in on click based *only* on the global variable
        if (!isUserLoggedInOnClick) {
            console.warn("Click - User not logged in based on global isLoggedIn. Prompting redirect.");

            // Show the confirm dialog only on click
            const shouldRedirect = confirm("You must be logged in to add items to your cart. Do you want to go to the login page now?");

            if (shouldRedirect) {
                // If the user clicks OK, redirect after a short delay
                setTimeout(() => {
                    window.location.href = "login.php";
                }, 500);
            } else {
                // If the user clicks Cancel, they stay on the current page
                console.log("Click - User chose not to redirect to login.");
                alert("Please log in to add this item."); // Provide feedback that action failed
            }
            return; // Stop further execution of the click handler if not logged in
        }

        // If user is logged in (based on global isLoggedIn), proceed with adding to cart/library
        console.log("Click - User is logged in. Proceeding with add logic.");

        // Read User ID and Session ID from data attributes ON CLICK for the API call
        const currentUserIdOnClick = addToCartButton.getAttribute("data-user-id");
        const sessionIdForPurchase = addToCartButton.getAttribute("data-session-id");

        // Add a safeguard: If isLoggedIn is true but UID/SessionID attributes are missing, something is wrong.
        if (!currentUserIdOnClick || currentUserIdOnClick === '' || !sessionIdForPurchase || sessionIdForPurchase === '') {
            console.error("Click - User is logged in but UID or Session ID attributes are missing/empty.");
            alert("Could not add product. User data missing from button attributes.");
            return;
        }


        // Ensure product data was successfully loaded and parsed on page load
        if (!product) {
            console.error("Click - Product data not available on button click.");
            alert("Could not add product to cart. Product data unavailable.");
            return;
        }

        // Check if the user already owns the product (status determined on page load)
        if (product.isOwned) {
            alert(`${product.cardTitle || 'This product'} is already in your library.`);
            return;
        }

        // Determine if the product is free based on the fetched product data
        const isFree = product.itemPrice === 0 || product.itemPrice === "0"; // Handle both number 0 and string "0"

        if (isFree) {
            // Add to library (create a purchase record) if the product is free
            // Use the user ID and session ID read from attributes ON CLICK for the API call
            const purchasesApiEndpoint = `http://localhost/Web%20Assignment/Class_purchases.php/purchase`;
            console.log("Click - Adding free product to library via:", purchasesApiEndpoint);

            const purchaseData = {
                product_id: product.ID, // Use the capitalized ID from parsed data
                uid: currentUserIdOnClick, // Use the variable read from attribute on click
                date: new Date().toISOString(), // Use ISO 8601 format
                session: sessionIdForPurchase // Use the variable read from attribute on click
            };

            try {
                const response = await fetch(purchasesApiEndpoint, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest"
                    },
                    body: JSON.stringify(purchaseData)
                });

                if (!response.ok) {
                    throw new Error(`HTTP error creating purchase: status ${response.status}`);
                }

                const result = await response.json();
                console.log("Click - Purchase creation response:", result);

                if (result.name || (typeof result === 'object' && result !== null && !result.error)) { // Check for Firestore doc name or a successful-looking response
                    alert(`${product.cardTitle || 'Product'} added to your library.`);
                    // Update library count and badge if you have that logic
                    let count = parseInt(localStorage.getItem("libraryCount")) || 0;
                    count++;
                    localStorage.setItem("libraryCount", count);
                    updateLibraryBadge();  //Assuming updateLibraryBadge exists
                    if (addToCartButton) {
                        addToCartButton.textContent = "Owned"; // Update button text
                    }
                    product.isOwned = true; // Update local product state
                } else {
                    alert(`Failed to add ${product.cardTitle || 'Product'} to your library: ${result.message || JSON.stringify(result)}`);
                }
            } catch (error) {
                console.error("Click - Error adding free product to library:", error);
                alert(`Error adding ${product.cardTitle || 'product'} to library.`);
            }

        } else {
            // For paid products, add to cart (localStorage).
            const cartItems = JSON.parse(localStorage.getItem("cartItems")) || [];
            // Check if the product is already in the cart
            if (!cartItems.some(item => item.pid === product.ID)) { // Use the capitalized ID
                cartItems.push({ pid: product.ID, quantity: 1 }); // Use the capitalized ID
                localStorage.setItem("cartItems", JSON.stringify(cartItems));
                alert(`${product.cardTitle || 'Product'} added to your cart.`);
                updateCartBadge(); // Assuming updateCartBadge exists
            } else {
                alert(`${product.cardTitle || 'Product'} is already in your cart.`);
            }
        }
    });
});

// Function to update the cart badge (assuming you have this HTML element and function)
function updateCartBadge() {
    const cartItems = JSON.parse(localStorage.getItem("cartItems")) || [];
    const cartBadge = document.getElementById("cart_badge");
    if (cartBadge) {
        cartBadge.textContent = cartItems.length;
        cartBadge.hidden = cartItems.length === 0;
    }
}

// Function to update the library badge (assuming you have this HTML element and function)
function updateLibraryBadge() {
    const libraryCount = parseInt(localStorage.getItem("libraryCount")) || 0;
    const libraryBadge = document.getElementById("library_badge");
    if (libraryBadge) {
        libraryBadge.textContent = libraryCount;
        libraryBadge.hidden = libraryCount === 0;
    }
}