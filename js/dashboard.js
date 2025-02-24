document.addEventListener("DOMContentLoaded", () => {
    // Function to delete a Product
    window.deleteProduct = function (productId) {
        if (!confirm("Are you sure you want to delete this product?")) return;
        fetch("Class_products.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ action: "delete", product_id: productId })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById(`product-${productId}`).remove();
                    alert("Product deleted successfully!");
                } else {
                    alert("Failed to delete product: " + data.message);
                }
            })
            .catch(error => console.error("Error:", error));
    };

    // Function to delete a User
    window.deleteUser = function (userId) {
        if (!confirm("Are you sure you want to delete this user?")) return;
        fetch("Class_users.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ action: "delete", user_id: userId })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById(`user-${userId}`).remove();
                    alert("User deleted successfully!");
                } else {
                    alert("Failed to delete user: " + data.message);
                }
            })
            .catch(error => console.error("Error:", error));
    };

    // Function to delete a Purchase Record
    window.deleteOwnedProduct = function (userId, productId) {
        if (!confirm("Are you sure you want to delete this purchase record?")) return;
        fetch("Class_owned_products.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ action: "delete", user_id: userId, product_id: productId })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById(`ownedProduct-${userId}-${productId}`).remove();
                    alert("Record deleted successfully!");
                } else {
                    alert("Failed to delete record: " + data.message);
                }
            })
            .catch(error => console.error("Error:", error));
    };

    window.insertRecord = function (event, type) {
        event.preventDefault();
        if (confirm("Proceed to add new " + type + "?")) {
            window.location.href = `dashboard.php?content=update&type=${type}`;
        }
    };

    // Function to update a Record
    window.editRecord = function (event, type, id) {
        event.preventDefault();
        if (confirm("Are you sure you want to edit this record?")) {
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
});