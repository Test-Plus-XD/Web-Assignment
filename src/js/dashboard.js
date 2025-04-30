document.addEventListener("DOMContentLoaded", () => {
    // Define a single base URL for all API endpoints (adjust if necessary)
    const apiBaseUrl = 'http://localhost/Web Assignment';

    // Function to delete a Product using the API endpoint
    // Assumes Class_products.php has a DELETE /product/{id} endpoint
    window.deleteProduct = function (productId) {
        if (!confirm("Are you sure you want to delete this product (" + (productId) + ") ? This action cannot be undone.")) return; // User cancelled deletion
        // Construct the full API endpoint URL using the base URL, class file, and specific path
        const deleteEndpoint = `${apiBaseUrl}/Class_products.php/product/${encodeURIComponent(productId)}`;

        fetch(deleteEndpoint, {
            method: "DELETE", // Use the DELETE HTTP method
            headers: {
                'Accept': 'application/json' // Indicate that we expect a JSON response
                // No 'Content-Type' or 'body' needed for DELETE requests to this endpoint style
            }
        })
            .then(response => {
                // Check for HTTP errors (status codes outside 2XX)
                if (!response.ok) {
                    // Attempt to read and parse the error body from the API
                    return response.json().then(errorData => {
                        console.error(`HTTP error deleting product ${productId}: ${response.status} ${response.statusText}`, errorData);
                        // Throw an error with combined details
                        throw new Error(`Delete API Error ${response.status}: ${errorData.error || JSON.stringify(errorData)}`);
                    }).catch(() => {
                        // Fallback if the error response body is not JSON
                        console.error(`HTTP error deleting product ${productId}: ${response.status} ${response.statusText}. Response body was not JSON.`);
                        throw new Error(`HTTP error deleting product ${productId}: ${response.status} ${response.statusText}`);
                    });
                }
                // Assuming successful delete might return an empty body or a simple JSON success message
                // Parse response body as JSON if it's not empty, otherwise return an empty object
                return response.text().then(text => text ? JSON.parse(text) : {});
            })
            .then(data => {
                console.log("Product delete successful:", data);
                // Assuming your HTML element ID for the product row is 'product-{productId}'
                const productRow = document.getElementById(`product-${productId}`);
                if (productRow) {
                    productRow.remove(); // Remove the row from the table
                    alert("Product deleted successfully!");
                } else {
                    // If the row wasn't found, maybe just reload the page as a fallback
                    alert("Product deleted successfully, but could not find the row to remove. Reloading page.");
                    location.reload();
                }
            })
            .catch(error => {
                console.error("Error deleting product:", error);
                alert("Failed to delete product: " + error.message);
            });
    };

    // Function to delete both a Firebase Auth user and their corresponding Firestore document using the API endpoint
    // Assumes Class_users.php has DELETE /user/{id} and /auth/{uid} endpoints
    window.deleteUser = function (userId, userUID) {
        if (!confirm("Are you sure you want to delete this user (" + userId + ")? This action cannot be undone.")) return;
        const deleteAuthEndpoint = `${apiBaseUrl}/Class_users.php/auth/${encodeURIComponent(userUID)}`;
        const deleteDocEndpoint = `${apiBaseUrl}/Class_users.php/user/${encodeURIComponent(userId)}`;
        // Attempt to delete the Firebase Auth account
        fetch(deleteAuthEndpoint, {
            method: "DELETE", // Use the DELETE HTTP method
            headers: {
                'Accept': 'application/json' // Expect a JSON response
            }
        })
            .then(response => {
                // If the deletion failed, log the error but continue with document deletion
                if (!response.ok) {
                    return response.json().then(err => {
                        console.warn("Firebase Auth deletion failed:", err.message || err);
                        return null; // Allow Firestore deletion regardless of Auth failure
                    });
                }
                // Return the parsed JSON response (or empty object if no body)
                return response.text().then(t => t ? JSON.parse(t) : {});
            })
            .then(() => {
                // Proceed to delete the Firestore document
                return fetch(deleteDocEndpoint, {
                    method: "DELETE", // Use the DELETE HTTP method
                    headers: {
                        'Accept': 'application/json' // Expect a JSON response
                    }
                });
            })
            .then(response => {
                // If the document deletion failed, try to parse and throw the error
                if (!response.ok) {
                    return response.json().then(errorData => {
                        console.error(`HTTP error deleting user ${userId}:`, errorData);
                        throw new Error(`Delete API Error ${response.status}: ${errorData.error || JSON.stringify(errorData)}`);
                    }).catch(() => {
                        // If response is not JSON, throw a generic error
                        throw new Error(`HTTP error deleting user ${userId}: ${response.status} ${response.statusText}`);
                    });
                }
                // Return the parsed JSON response (or empty object if no body)
                return response.text().then(text => text ? JSON.parse(text) : {});
            })
            .then(data => {
                // Final step: remove the user row from the DOM or reload page
                console.log("User delete successful:", data);
                const userRow = document.getElementById(`user-${userId}`);
                if (userRow) {
                    userRow.remove(); // Remove the row from the table
                    alert("User deleted successfully!");
                } else {
                    // Fallback if the row could not be found
                    alert("User deleted successfully, but could not find the row to remove. Reloading page.");
                    location.reload();
                }
            })
            .catch(error => {
                // Handle any unexpected errors in the process
                console.error("Error deleting user:", error);
                alert("Failed to delete user: " + error.message);
            });
    };

    // Function to delete a Purchase Record using the API endpoint
    // Assumes Class_purchases.php has a DELETE /purchase/{id} endpoint
    window.deletePurchase = function (purchaseId) {
        if (!confirm("Are you sure you want to delete this record (" + (purchaseId) + ") ? This action cannot be undone.")) return; // User cancelled deletion
        // Construct the full API endpoint URL using the base URL, class file, and specific path
        const deleteEndpoint = `${apiBaseUrl}/Class_purchases.php/purchase/${encodeURIComponent(purchaseId)}`;

        fetch(deleteEndpoint, {
            method: 'DELETE', // Use the DELETE method
            headers: {
                'Accept': 'application/json' // Expect JSON response
            }
        })
            .then(response => {
                // Check for HTTP errors
                if (!response.ok) {
                    // Attempt to read and parse the error body
                    return response.json().then(errorData => {
                        console.error(`HTTP error deleting purchase ${purchaseId}: ${response.status} ${response.statusText}`, errorData);
                        throw new Error(`Delete API Error ${response.status}: ${errorData.error || JSON.stringify(errorData)}`);
                    }).catch(() => {
                        // Fallback if the error response body is not JSON
                        console.error(`Failed to parse JSON error response for purchase deletion ${purchaseId}. HTTP Status: ${response.status} ${response.statusText}`);
                        throw new Error(`HTTP error deleting purchase ${purchaseId}: ${response.status} ${response.statusText}`);
                    });
                }
                // Assuming successful delete might return an empty body or a simple JSON success message
                // Parse response body as JSON if it's not empty, otherwise return an empty object
                return response.text().then(text => text ? JSON.parse(text) : {});
            })
            .then(data => {
                console.log('Delete successful:', data);
                // On successful deletion, update the UI by removing the row
                // Assuming your HTML element ID for the purchase row is 'purchase-row-{purchaseId}'
                const purchaseRow = document.getElementById('purchase-row-' + purchaseId);
                if (purchaseRow) {
                    purchaseRow.remove(); // Remove the row from the table
                    alert("Purchase record deleted successfully!");
                } else {
                    // If the row wasn't found, maybe just reload the page as a fallback
                    alert("Purchase record deleted successfully, but could not find the row to remove. Reloading page.");
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error deleting purchase:', error);
                alert('Failed to delete purchase record: ' + error.message);
            });
    };

    // Function to insert a new Record
    window.insertRecord = function (event, type) {
        event.preventDefault();
        let labelText = type.replace(/^all_/i, '');
        if (labelText.endsWith('s')) {
            labelText = labelText.slice(0, -1);
        }
        labelText = labelText.charAt(0).toUpperCase() + labelText.slice(1);
        if (confirm(`Proceed to add new ${labelText}?`)) {
            window.location.href = `dashboard.php?content=update&type=${labelText.toLowerCase()}`;
        }
    };

    // Function to update a Record
    window.editRecord = function (event, type, id) {
        event.preventDefault();
        if (confirm("Are you sure you want to edit this record (" + id + ") ?")) {
            window.location.href = `dashboard.php?content=update&type=${type}&id=${id}`;
        }
    };

    // Function to toggle stock field visibility when "isDigital" is selected
    document.addEventListener("change", function (event) {
        if (event.target && event.target.name === "isDigital") {
            let stockField = document.getElementById("stockField");
            if (stockField) {
                stockField.hidden = (event.target.value === "1");
            }
        }
    });

    // Run immediately in case the value is already set when the page loads
    let isDigitalYes = document.getElementById("isDigital_yes");
    let isDigitalNo = document.getElementById("isDigital_no");
    if (isDigitalYes && isDigitalNo) {
        let stockField = document.getElementById("stockField");
        if (stockField) {
            stockField.hidden = isDigitalYes.checked; // Hide if "Yes" is selected
        }
    }

    // Function for toggling descriptions
    document.addEventListener("click", (event) => {
        const target = event.target;
        // Check if Read More was clicked
        if (target.matches("[data-action='expand-description']")) {
            const productId = target.dataset.productId;
            const shortDesc = document.getElementById(`shortDesc${productId}`);
            const fullDesc = document.getElementById(`descCollapse${productId}`);
            const readLessBtn = document.getElementById(`readLess${productId}`);

            if (!shortDesc || !fullDesc || !readLessBtn) return;

            shortDesc.hidden = true;    // Hide truncated text
            fullDesc.hidden = false;    // Show full text
            target.hidden = true;       // Hide "Read More"
            readLessBtn.hidden = false; // Show "Read Less"
        }

        // Check if Read Less was clicked
        if (target.matches("[data-action='collapse-description']")) {
            const productId = target.dataset.productId;
            const shortDesc = document.getElementById(`shortDesc${productId}`);
            const fullDesc = document.getElementById(`descCollapse${productId}`);
            const readMoreBtn = document.getElementById(`toggleBtn${productId}`);

            if (!shortDesc || !fullDesc || !readMoreBtn) return;

            shortDesc.hidden = false;    // Show truncated text
            fullDesc.hidden = true;      // Hide full text
            readMoreBtn.hidden = false;  // Show "Read More"
            target.hidden = true;        // Hide "Read Less"
        }
    });
    // Function to handle YouTube link conversion
    const updateForm = document.getElementById('updateForm');
    if (updateForm) {
        updateForm.addEventListener('submit', function (event) {
            event.preventDefault(); // Prevent the form from submitting normally
            const YTLink = document.getElementById('YTLink'); // Locate the YouTube input field
            // Only proceed if the YTLink element exists
            if (YTLink) {
                const originalUrl = YTLink.value;              // Get the original URL from the input
                const embedUrl = convertToEmbedURL(originalUrl); // Convert the URL to embed format
                YTLink.value = embedUrl;                         // Replace the input value with embed version
            }
            this.submit(); // Submit the form programmatically after modification
        });
    }
    function convertToEmbedURL(youtubeURL) {
        if (typeof youtubeURL !== 'string' || youtubeURL.trim() === '') {
            return youtubeURL;
        }
        let videoId = null; // Variable to store the extracted video ID.
        let url; // Variable to hold the parsed URL object.
        try {
            // Use the built-in URL object for robust parsing of the URL string.
            url = new URL(youtubeURL);
        } catch (e) {
            console.error("convertToEmbedURL: Failed to parse URL:", youtubeURL, e);
            return youtubeURL;
        }
        // Get the hostname from the parsed URL and convert it to lowercase for case-insensitive comparison.
        const hostname = url.hostname.toLowerCase();
        if (hostname === 'www.youtube.com' || hostname === 'youtube.com') {
            if (url.pathname === '/watch') {
                const params = new URLSearchParams(url.search);
                videoId = params.get('v'); // The video ID is typically in the 'v' query parameter.
            }
            else if (url.pathname.startsWith('/embed/')) {
                videoId = url.pathname.substring('/embed/'.length);
                const queryIndex = videoId.indexOf('?');
                if (queryIndex !== -1) videoId = videoId.substring(0, queryIndex);
                const fragmentIndex = videoId.indexOf('#');
                if (fragmentIndex !== -1) videoId = videoId.substring(0, fragmentIndex);
                if (videoId) {
                    return `https://www.youtube.com/embed/${videoId}`;
                }
            }
        } else if (hostname === 'youtu.be') {
            videoId = url.pathname.substring(1);
            if (videoId === '') {
                videoId = null;
            }
        }
        if (videoId) {
            // Construct and return the standard YouTube embed URL using the extracted video ID.
            return `https://www.youtube.com/embed/${videoId}`;
        }
        return youtubeURL;
    }

    // Pagination functionality
    function updateQueryStringParameter(uri, key, value) {
        var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
        var separator = uri.indexOf('?') !== -1 ? "&" : "?";
        if (uri.match(re)) {
            return uri.replace(re, '$1' + key + "=" + value + '$2');
        }
        else {
            return uri + separator + key + "=" + value;
        }
    }
    window.updateQueryStringParameter = updateQueryStringParameter; // Make it globally accessible if needed
});