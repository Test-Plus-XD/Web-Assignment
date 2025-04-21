// Fetch products from the database
document.addEventListener("DOMContentLoaded", async () => {
    // Function to fetch products from the server
    async function fetchProducts() {
        try {
            // Send a POST request to Class_fetch.php with action "default"
            const response = await fetch("Class_fetch.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest" // Manually add header
                },
                body: JSON.stringify({ action: "default" }) // Action for allProducts function
            });

            // Check if the response is okay
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            // Get the raw response text
            const rawText = await response.text();
            //console.log("Raw response:", rawText);

            // Check if the response is empty or appears invalid
            if (!rawText || rawText.trim() === "") {
                console.error("Empty response from server");
                return;
            }
            if (rawText.trim().startsWith("<") || rawText.includes("Warning")) {
                console.error("Invalid JSON response from server:", rawText);
                return;
            }

            // Parse the JSON from the raw response text
            let data;
            try {
                data = JSON.parse(rawText);
            } catch (e) {
                console.error("Failed to parse JSON:", e, rawText);
                return;
            }

            // Log the parsed JSON data
            console.log("Parsed JSON data:", data);

            // Check if there's an error message from PHP
            if (data.error) {
                console.error("PHP Error:", data.error);
            } else {
                // Proceed with the rest of the logic if no error
                initializeSearch(data); // Initialize the search functionality with the fetched products
            }
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
        const productCards = document.querySelectorAll(".card");
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
            const productCard = document.getElementById(product.id);
            const matchesQuery = product.name.toLowerCase().includes(searchQuery) || searchQuery === "";
            const matchesFilter1 =
                selectedFilter1 === "all" ||
                (selectedFilter1 === "free" && product.isFree) ||
                (selectedFilter1 === "paid" && !product.isFree);
            const matchesFilter2 =
                selectedFilter2 === "all" ||
                (selectedFilter2 === "digital" && product.isDigital) ||
                (selectedFilter2 === "physical" && !product.isDigital);

            if (matchesQuery && matchesFilter1 && matchesFilter2) {
                productCard.style.display = "block"; // Show card if it matches the query
                matches++;
            } else {
                productCard.style.display = "none"; // Hide card if it doesn't match
            }
        });

        // Display "No products found" if no matches
        if (matches === 0) {
            const noResultItem = document.createElement("div");
            noResultItem.classList.add("no_results");
            noResultItem.innerHTML = "<p>No products found.</p>";
            searchResults.appendChild(noResultItem);
        }
    }

    // Suggestion Function
    function handleSuggestions() {
        const searchQuery = searchInput.value.toLowerCase();
        suggestions.innerHTML = ""; // Clear previous suggestions

        const matchedProducts = products.filter(product =>
            product.name.toLowerCase().startsWith(searchQuery)
        );

        if (matchedProducts.length > 0 && searchQuery !== "") {
            matchedProducts.forEach(product => {
                const suggestionItem = document.createElement("div");
                suggestionItem.classList.add("suggestion-item");
                suggestionItem.textContent = product.name;

                suggestionItem.addEventListener("click", () => {
                    searchInput.value = product.name;
                    suggestions.innerHTML = ""; // Clear suggestions after selection
                    toggleClearButton(); // Ensure clear button visibility is updated
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
}