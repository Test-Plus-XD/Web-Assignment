// Check if logged-in Function
window.onload = function () {
    // Debugging isLoggedIn variable
    console.log("isLoggedIn:", typeof isLoggedIn !== "undefined" ? isLoggedIn : "Not defined");

    if (typeof isLoggedIn === "undefined") {
        console.error("isLoggedIn is not defined");
        return;
    }

    // Debugging isAdmin variable
    console.log("isAdmin:", typeof isAdmin !== "undefined" ? isAdmin : "Not defined");

    if (typeof isAdmin === "undefined") {
        console.error("isAdmin is not defined");
        return;
    }

    // Get button and badge elements
    const loginButton = document.getElementById('loginButton');
    const libraryButton = document.getElementById('libraryButton');
    const libraryBadge = document.getElementById('library_badge');
    const cartButton = document.getElementById('cartButton');
    const cartBadge = document.getElementById('cart_badge');
    const sessionButton = document.getElementById('sessionButton');
    const adminButton = document.getElementById('adminButton');

    // Update UI based on login state
    if (isLoggedIn) {
        loginButton.innerHTML = "My&nbsp;&nbsp;Account";
        loginButton.href = "account.php";
        libraryButton.classList.remove("disabled");
        libraryButton.removeAttribute("aria-disabled");
        //libraryBadge?.removeAttribute('hidden'); 
        cartButton.classList.remove("disabled");
        cartButton.removeAttribute("aria-disabled");
        //cartBadge?.removeAttribute('hidden');
        sessionButton?.removeAttribute('hidden');
    } else {
        loginButton.innerHTML = "Login";
        loginButton.href = "login.php";
        libraryButton.classList.add("disabled");
        libraryButton.setAttribute("aria-disabled", "true");
        libraryBadge?.setAttribute('hidden', 'hidden');
        cartButton.classList.add("disabled");
        cartButton.setAttribute("aria-disabled", "true");
        cartBadge?.setAttribute('hidden', 'hidden');
        sessionButton?.setAttribute('hidden', 'hidden');
    }
    if (isAdmin) {
        adminButton?.removeAttribute('hidden');
    }
};

//Update cart badge on header Function
function updateCartBadge() {
    const cartItems = JSON.parse(localStorage.getItem('cartItems')) || []; // Retrieve cart items from localStorage
    const cartCount = cartItems.length || 0; // Get the number of items in the cart

    // Find the badge element in the Cart button
    const cartBadge = document.querySelector('#cartButton .badge');
    if (cartBadge) {
        cartBadge.textContent = cartCount; // Update badge count
        cartBadge.hidden = cartCount == 0; // Hide badge if count is 0
    }
}
// Update library badge on header Function
function updateLibraryBadge() {
    const libraryCount = JSON.parse(localStorage.getItem('libraryCount')) || 0; // Get count from localStorage
    const libraryBadge = document.querySelector('#libraryButton .badge'); 
    // Find the badge element in the Library button
    if (libraryBadge) {
        libraryBadge.textContent = libraryCount; // Update badge count
        libraryBadge.hidden = libraryCount == 0; // Hide badge if count is 0
    }
}

// Show/Hide Password Function
document.addEventListener("DOMContentLoaded", () => {
    // Select all password fields that start with "password" [id^='password']
    document.querySelectorAll("input[type='password']").forEach(passwordInput => {
        const id = passwordInput.id; // Get the ID of the password input
        const parent = passwordInput.parentElement; // Get the parent container

        // Create the toggle icon dynamically
        const toggleIcon = document.createElement("i");
        toggleIcon.classList.add("bi", "bi-eye-slash", "position-absolute", "top-50", "end-0", "translate-middle-y", "me-3");
        toggleIcon.style.cursor = "pointer";
        toggleIcon.id = `toggle${id}`; // Assign a unique ID

        // Append the icon inside the parent container
        parent.appendChild(toggleIcon);

        // Add event listener for toggling password visibility
        toggleIcon.addEventListener("click", () => {
            if (passwordInput.type === "password") {
                passwordInput.type = "text"; // Show password
                toggleIcon.classList.replace("bi-eye-slash", "bi-eye-fill");
            } else {
                passwordInput.type = "password"; // Hide password
                toggleIcon.classList.replace("bi-eye-fill", "bi-eye-slash");
            }
        });
    });
});

// Call the function initially to ensure the badge is updated on page load
updateCartBadge();
updateLibraryBadge();
// Save the current page URL before redirecting to the login page
if (!localStorage.getItem("preLoginUrl") && window.location.pathname.split("/").pop() !== "login.php") localStorage.setItem("preLoginUrl", window.location.href);

// Sentry Debug
Sentry.onLoad(function () {
    Sentry.init({
        // Tracing
        tracesSampleRate: 1.0, // Capture 100% of the transactions
        // Session Replay
        replaysSessionSampleRate: 0.5, // This sets the sample rate at 50%. You may want to change it to 100% while in development and then sample at a lower rate in production.
        replaysOnErrorSampleRate: 1.0, // If you're not already sampling the entire session, change the sample rate to 100% when sampling sessions where errors occur.
    });
});
//myUndefinedFunction();