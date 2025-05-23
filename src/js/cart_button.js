// Function to display product details on the page
function displayProductDetails(product) {
    if (!product) {
        console.error("No product data to display.");
        return;
    }

    // Get the elements by their IDs
    const ytLinkIframe = document.getElementById('product-yt-link');
    const titleElement = document.getElementById('product-title');
    const imageElement = document.getElementById('product-image');
    const cardTextElement = document.getElementById('product-card-text');
    const descriptionElement = document.getElementById('product-description');

    // Update the elements with product data
    if (ytLinkIframe && product.YTLink) {
        ytLinkIframe.src = product.YTLink;
    }
    if (titleElement && product.cardTitle) {
        titleElement.textContent = product.cardTitle;
    }
    if (imageElement) {
        if (product.imageSrc) imageElement.src = product.imageSrc;
        if (product.imageAlt) imageElement.alt = product.imageAlt;
    }
    if (cardTextElement && product.cardText) {
        cardTextElement.textContent = product.cardText;
    }
    if (descriptionElement && product.description) {
        descriptionElement.innerHTML = product.description; // Use innerHTML if description contains HTML like line breaks
        // Consider using textContent if description is plain text to prevent XSS
        // descriptionElement.textContent = product.description;
    }
    console.log("Product details displayed on page.");
}

// DOMContentLoaded event ensures the HTML is fully parsed before we try to access elements
document.addEventListener("DOMContentLoaded", async () => {
    // Access the product data from the global variable set by PHP
    const product = window.productData;

    if (!product) {
        console.error("Product data not found in global variable.");
        // Consider showing an error message on the page
        const mainContentArea = document.querySelector('.product_main .container-fluid .row'); // Adjust selector as needed
        if (mainContentArea) {
            mainContentArea.innerHTML = '<p class="text-center">Failed to load product details.</p>';
        }

        // Disable the add to cart button if data is missing
        const addToCartButton = document.getElementById("add-to-cart");
        if (addToCartButton) {
            addToCartButton.disabled = true;
            addToCartButton.textContent = "Data Error";
        }
        return; // Stop execution if product data is missing
    }

    // Display the product details using the function
    displayProductDetails(product);

    console.log("cart_button.js - Global Login Status Read:", {
        isLoggedIn: isLoggedIn
    });

    const addToCartButton = document.getElementById("add-to-cart");
    // Check again if button exists, though DOMContentLoaded and previous checks should ensure this
    if (!addToCartButton) {
        console.error("Add to Cart button not found after display logic.");
        return;
    }

    // Get the product's document ID from the button's data attribute
    const productId = addToCartButton.getAttribute("data-product-id");
    if (!productId) {
        console.error("Product ID not found on the Add to Cart button after display.");
        if (addToCartButton) {
            addToCartButton.disabled = true;
            addToCartButton.textContent = "Product ID Error";
        }
        return; // Exit if no product ID
    }
    console.log("Product ID from data attribute:", productId);


    // Perform initial ownership check on page load using global isLoggedIn and data attribute UID
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
                product.isOwned = true; // Update the global product object's owned status
                console.log("User already owns this product (checked on load).");
                if (addToCartButton) {
                    addToCartButton.textContent = "Owned"; // Update button text
                }
            } else {
                product.isOwned = false; // Update the global product object's owned status
                console.log("User does not own this product (checked on load).");
            }
        }
    } else {
        product.isOwned = false; // Update the global product object's owned status
        console.log("User not logged in or UID attribute missing on load for ownership check, does not own this product.");
    }


    // Attach event listener to the Add to Cart button
    addToCartButton.addEventListener("click", async () => {
        console.log("Add to Cart button clicked.");

        // Check login status on click using global variable
        // Use the global isLoggedIn variable that should be set in head.php
        const isUserLoggedInOnClick = isLoggedIn ?? false;

        console.log("Click - Global Login Status Read Again:", {
            isLoggedIn: isUserLoggedInOnClick
        });

        // Check if user is logged in on click based *only* on the global variable
        if (!isUserLoggedInOnClick) {
            // Show the confirm dialog only on click
            const shouldRedirect = confirm("You must be logged in to add items to your cart. Do you want to go to the login page now?");

            if (shouldRedirect) {
                // If the user clicks OK, redirect after a short delay
                setTimeout(() => {
                    window.location.href = "login.php";
                }, 50);
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
            alert("Could not add product. User data missing from attributes.");
            return;
        }

        // Ensure product data was successfully loaded and parsed (from the global variable)
        if (!product) { // Check the global product variable
            console.error("Click - Product data not available on button click from global variable.");
            alert("Could not add product to cart. Product data unavailable.");
            return;
        }

        // Check if the user already owns the product (status determined on page load and stored in global product object)
        if (product.isOwned) { // Use the owned status from the global product object
            alert(`${product.cardTitle || 'This product'} is already in your library.`);
            return;
        }

        // Determine if the product is free based on the global product data
        const isFree = product.itemPrice === 0 || product.itemPrice === "0"; // Handle both number 0 and string "0"

        if (isFree) {
            // Add to library (create a purchase record) if the product is free
            // Use the user ID and session ID read from attributes ON CLICK for the API call
            const purchasesApiEndpoint = `http://localhost/Web%20Assignment/Class_purchases.php/purchase`;
            console.log("Click - Adding free product to library via:", purchasesApiEndpoint);

            const purchaseData = {
                product_id: product.ID, // Use the capitalised ID from global product data
                uid: currentUserIdOnClick, // Use the variable read from attribute on click
                date: Date.now(), // Send current time in milliseconds
                //date: Math.floor(Date.now() / 1000), // Send current time as Unix timestamp in seconds
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
                    product.isOwned = true; // Update global product object's owned status
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
            if (!cartItems.some(item => item.pid === product.ID)) { // Use the capitalised ID from global product data
                cartItems.push({ pid: product.ID, quantity: 1 }); // Use the capitalised ID from global product data
                localStorage.setItem("cartItems", JSON.stringify(cartItems));
                alert(`${product.cardTitle || 'Product'} added to your cart.`);
                updateCartBadge(); // Assuming updateCartBadge exists
            } else {
                alert(`${product.cardTitle || 'Product'} is already in your cart.`);
            }
        }
    });
});