// UI Resources
const SuccessIcon = `<svg width="16" height="14" viewBox="0 0 16 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M15.4695 0.232963C15.8241 0.561287 15.8454 1.1149 15.5171 1.46949L6.14206 11.5945C5.97228 11.7778 5.73221 11.8799 5.48237 11.8748C5.23253 11.8698 4.99677 11.7582 4.83452 11.5681L0.459523 6.44311C0.145767 6.07557 0.18937 5.52327 0.556912 5.20951C0.924454 4.89575 1.47676 4.93936 1.79051 5.3069L5.52658 9.68343L14.233 0.280522C14.5613 -0.0740672 15.1149 -0.0953599 15.4695 0.232963Z" fill="white"/></svg>`;
const ErrorIcon = `<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M1.25628 1.25628C1.59799 0.914573 2.15201 0.914573 2.49372 1.25628L8 6.76256L13.5063 1.25628C13.848 0.914573 14.402 0.914573 14.7437 1.25628C15.0854 1.59799 15.0854 2.15201 14.7437 2.49372L9.23744 8L14.7437 13.5063C15.0854 13.848 15.0854 14.402 14.7437 14.7437C14.402 15.0854 13.848 15.0854 13.5063 14.7437L8 9.23744L2.49372 14.7437C2.15201 15.0854 1.59799 15.0854 1.25628 14.7437C0.914573 14.402 0.914573 13.848 1.25628 13.5063L6.76256 8L1.25628 2.49372C0.914573 2.15201 0.914573 1.59799 1.25628 1.25628Z" fill="white"/></svg>`;

// Simulate payment success and update the database
async function simulatePaymentSuccess() {
    try {
        console.log("Simulating payment success...");
        await updateDatabaseAfterPayment(); // Update database with cart items
        setSuccessState();
    } catch (error) {
        console.error("Error during payment simulation:", error);
        setErrorState();
    }
}

// Update the UI to show success or error states
function setSuccessState() {
    document.querySelector("#status-icon").innerHTML = SuccessIcon;
    document.querySelector("#status-text").textContent = "Payment succeeded! Items have been added to your library.";
}

function setErrorState() {
    document.querySelector("#status-icon").innerHTML = ErrorIcon;
    document.querySelector("#status-text").textContent = "Payment failed. Please try again.";
}

// Add items to the library after payment
async function updateDatabaseAfterPayment() {
    const cartItems = JSON.parse(localStorage.getItem("cartItems")) || [];
    let successfulCount = 0;

    for (const item of cartItems) {
        try {
            const response = await fetch("library_update.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ productId: item.pid }),
            });

            const result = await response.json();

            if (result.success) {
                console.log(`Product ${item.pid} successfully added to library.`);
                successfulCount++;
            } else {
                console.error(`Failed to add product ${item.pid}: ${result.message}`);
                alert(`Failed to process item: ${item.pid}. Skipping.`);
            }
        } catch (error) {
            console.error("Error updating library:", error);
        }
    }

    // Clear cart and update UI
    if (successfulCount > 0) {
        // Update libraryCount in localStorage
        let currentLibraryCount = parseInt(localStorage.getItem("libraryCount")) || 0;
        localStorage.setItem("libraryCount", currentLibraryCount + successfulCount);

        // Clear cartItems after successful processing
        localStorage.removeItem("cartItems");
        updateLibraryBadge(); // Update the library badge
    } else {
        alert("No items were added to your library. Please try again.");
    }
}
// Simulate payment success on page load
simulatePaymentSuccess();