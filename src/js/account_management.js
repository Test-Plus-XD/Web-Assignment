// Wait until the HTML document is fully loaded and parsed before running any code
document.addEventListener('DOMContentLoaded', function () {
    // Helper function to send a JSON POST to our PHP endpoint and parse the response safely
    async function postAccountAction(action) {
        // Send a POST request with credentials and AJAX header to our server
        const res = await fetch("Class_account.php", {
            method: "POST",                          // Use POST to include a JSON body
            credentials: "same-origin",              // Send cookies for session authentication
            redirect: "manual",                      // Do not automatically follow redirects
            headers: {
                "Content-Type": "application/json",  // Tell the server
                "X-Requested-With": "XMLHttpRequest"// Identify this as an AJAX request
            },
            body: JSON.stringify({ action })         // Include the action (e.g. "logout" or "delete")
        });

        // If the server responds with 401, the user is not authenticated
        if (res.status === 401) {
            throw new Error("Not authenticated (401).");
        }
        // If the server attempted to redirect (e.g. to login.php), catch it here
        if (res.status === 302 || res.type === "opaqueredirect") {
            throw new Error("Session expired; got redirected to login.");
        }
        // For any other non-2xx status, read the error text and throw it
        if (!res.ok) {
            const txt = await res.text();
            throw new Error(`HTTP ${res.status}: ${txt}`);
        }
        // Check that the Content-Type header is JSON before parsing
        const ct = res.headers.get("Content-Type") || "";
        if (!ct.includes("application/json")) {
            const body = await res.text();
            throw new Error(`Expected JSON, got ${ct}: ${body.substring(0, 100)}¡K`);
        }
        // Finally, parse and return the JSON payload
        return res.json();
    }

    // Locate the logout button in the page (if it exists)
    const logoutButton = document.getElementById("logoutButton");
    if (logoutButton) {
        // Attach a click handler to perform logout
        logoutButton.addEventListener("click", async () => {
            // Ask the user to confirm they really want to log out
            if (!confirm("Confirm logout?")) return;

            try {
                // If a Firebase user is currently signed in, sign out first
                if (firebase.auth().currentUser) {
                    await firebase.auth().signOut();
                }
                // Then call our server to destroy the PHP session
                const data = await postAccountAction("logout");
                // If the server indicates success, notify the user and clear local data
                alert(data.message);
                localStorage.clear();
                // Redirect the browser to the login page
                window.location.href = "login.php";
            } catch (err) {
                // Log any errors and inform the user
                console.error("Logout error:", err);
                alert("Logout failed: " + err.message);
            }
        });
    } else {
        // Warn in the console if the logout button is not found
        console.warn("Logout button element not found.");
    }

    // Locate the delete-account button in the page (if it exists)
    const deleteButton = document.getElementById("deleteButton");
    if (deleteButton) {
        // Attach a click handler to perform account deletion
        deleteButton.addEventListener("click", async () => {
            // Confirm the user wants to permanently delete their account
            if (!confirm("Are you sure you want to delete your account permanently?")) return;

            // Get the currently signed-in Firebase user
            const user = firebase.auth().currentUser;
            if (!user) {
                // If no Firebase user is logged in, alert and exit
                alert("You must be signed in to delete your Firebase account.");
                return;
            }

            try {
                // Attempt to delete the user from Firebase Auth
                await user.delete();
                // Then call our server to delete their record and destroy the session
                const data = await postAccountAction("delete");
                // On success, notify the user, clear storage, and redirect to registration
                alert(data.message);
                localStorage.clear();
                localStorage.removeItem("cartItems");
                window.location.href = "registration.php";
            } catch (err) {
                // Handle specific Firebase errors (e.g. requires recent login)
                console.error("Delete error:", err);
                if (err.code === "auth/requires-recent-login") {
                    alert("Please re-login before deleting your account for security reasons.");
                } else {
                    // General error fallback
                    alert("Delete failed: " + err.message);
                }
            }
        });
    } else {
        // Warn in the console if the delete button is not found
        console.warn("Delete button element not found.");
    }
});