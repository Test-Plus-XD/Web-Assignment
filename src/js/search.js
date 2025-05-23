// Fetch products from the Firestore database
document.addEventListener("DOMContentLoaded", async () => {
    // Function to fetch products from the server
    async function fetchProducts() {
        try {
            // URL to Class_products.php endpoint for fetching all products
            const apiEndpoint = 'http://localhost/Web%20Assignment/Class_products.php/all';
            const response = await fetch(apiEndpoint);

            // Check if the response is okay
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            // Parse the JSON response
            const productsData = await response.json();

            // Log the parsed JSON data
            console.log("Parsed JSON data:", productsData);

            // The new endpoint format directly returns an array of product objects.
            // Each product object will have an 'ID' field containing the document ID and other fields like cardTitle, itemPrice, etc.
            initializeSearch(productsData); // Initialize the search functionality with the fetched products

        } catch (error) {
            // Handle any errors in the fetch request
            console.error("Error fetching products:", error);
        }
    }
    // Call the function to fetch products
    await fetchProducts();
});

// Initialize search functionality
function initializeSearch(products) {
    const searchInput = document.getElementById("searchInput");
    const clearButton = document.getElementById("clearButton");
    const suggestions = document.getElementById("suggestions");
    const searchResults = document.getElementById("searchResults");
    const priceFilter = document.getElementById("priceFilter");
    const typeFilter = document.getElementById("typeFilter");

    // Clear input Function
    function clearInput(event) {
        if (event) event.preventDefault();
        searchInput.value = ""; // Clear search input
        suggestions.innerHTML = ""; // Clear suggestions

        // Reset visibility of all product cards
        const productCards = document.querySelectorAll(".p-3.col-12.col-sm-12.col-md-6.col-lg-4");
        productCards.forEach(card => {
            card.style.display = "block"; // Show all cards
        });

        // Hide the clear button
        clearButton.style.display = "none";
        handleSearch(event);
    }

    // Toggle clear button visibility based on input value
    function toggleClearButton() {
        clearButton.style.display = searchInput.value.length > 0 ? "inline-block" : "none";
    }

    // Search Function
    function handleSearch(event) {
        if (event) event.preventDefault();

        const searchQuery = searchInput.value.toLowerCase();
        const selectedFilter1 = priceFilter.value;
        const selectedFilter2 = typeFilter.value;
        let matches = 0;
        //console.log("Price Filter:", selectedFilter1, "Type Filter:", selectedFilter2);
        searchResults.innerHTML = ""; // Clear previous results

        products.forEach(product => {
            // Use product.ID to find the corresponding DOM element
            const productCard = document.getElementById(product.ID);
            if (!productCard) {
                console.warn(`Product card with ID ${product.ID} not found in the DOM.`);
                return;
            }

            const matchesQuery =
                (product.cardTitle && product.cardTitle.toLowerCase().includes(searchQuery)) ||
                (product.cardText && product.cardText.toLowerCase().includes(searchQuery)) ||
                searchQuery === "";

            const isFree = product.itemPrice === "0" || product.itemPrice === 0;
            const isDigital = product.isDigital === 'true' || product.isDigital === true;

            const matchesFilter1 =
                selectedFilter1 === "all" ||
                (selectedFilter1 === "free" && isFree) ||
                (selectedFilter1 === "paid" && !isFree);

            const matchesFilter2 =
                selectedFilter2 === "all" ||
                (selectedFilter2 === "digital" && isDigital) ||
                (selectedFilter2 === "physical" && !isDigital);

            if (matchesQuery && matchesFilter1 && matchesFilter2) {
                productCard.style.display = "block"; // Show card if it matches
                matches++;
            } else {
                productCard.style.display = "none"; // Hide card if it doesn't match
            }
        });

        // Display "No products found" if no matches
        if (matches === 0) {
            const noResultItem = document.createElement("div");
            noResultItem.classList.add("no_results");
            noResultItem.innerHTML = "<p>No products found</p>";
            searchResults.appendChild(noResultItem);
            searchResults.style.display = 'block'; // Ensure the searchResults div is visible
        } else {
            searchResults.style.display = 'none'; // Hide the searchResults div if there are matches
        }
    }

    // Suggestion Function
    function handleSuggestions() {
        const searchQuery = searchInput.value.toLowerCase();
        suggestions.innerHTML = ""; // Clear previous suggestions

        const matchedProducts = products.filter(product =>
            (product.cardTitle && product.cardTitle.toLowerCase().includes(searchQuery))
        );

        if (matchedProducts.length > 0 && searchQuery !== "") {
            matchedProducts.forEach(product => {
                const suggestionItem = document.createElement("div");
                suggestionItem.classList.add("suggestion-item");
                suggestionItem.textContent = product.cardTitle;

                suggestionItem.addEventListener("click", () => {
                    searchInput.value = product.cardTitle;
                    suggestions.innerHTML = ""; // Clear suggestions after selection
                    toggleClearButton(); // Ensure clear button visibility is updated
                    handleSearch(); // Trigger search after selecting a suggestion
                });
                suggestions.appendChild(suggestionItem); // Add each suggestion to the container
            });
        }
    }
    // Event Listeners + Trigger search immediately when the filter changes
    priceFilter.addEventListener("change", handleSearch);
    typeFilter.addEventListener("change", handleSearch);
    clearButton.addEventListener("click", clearInput);
    document.getElementById("searchForm").addEventListener("submit", handleSearch);
    searchInput.addEventListener("input", () => {
        toggleClearButton();
        handleSuggestions();
    });
    // Initially hide the searchResults div
    searchResults.style.display = 'none';
}