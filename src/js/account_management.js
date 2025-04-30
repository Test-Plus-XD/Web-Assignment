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
            if (!confirm("Are you sure you want to delete your account permanently? This action cannot be undone.")) return;
            // Get the currently signed-in Firebase user
            const user = firebase.auth().currentUser;

            if (!user) {
                // If no Firebase user is logged in, prompt re-authentication
                alert("You must be signed in to delete your Firebase account.");

                try {
                    // Default to Google re-authentication provider
                    const provider = new firebase.auth.GoogleAuthProvider();

                    // Reauthenticate via popup
                    const result = await firebase.auth().signInWithPopup(provider);
                    const reauthenticatedUser = result.user;  // Get the reauthenticated user

                    // After successful reauthentication, call the delete function
                    await deleteUserAccount(reauthenticatedUser);
                } catch (reauthError) {
                    console.error("Reauthentication failed:", reauthError);
                    alert("Could not reauthenticate. Account not deleted.");
                    return;
                }
            } else {
                // If the user is already logged in, proceed with deletion
                await deleteUserAccount(user);
            }
        });
    } else {
        // Warn in the console if the delete button is not found
        console.warn("Delete button element not found.");
    }

    // Function to handle account deletion after successful re-authentication or if already logged in
    async function deleteUserAccount(user) {
        // Get the providerId of the user for reauthentication
        let providerId = user.providerData[0]?.providerId;
        try {
            // Attempt to delete the user from Firebase Authentication
            await user.delete();
            console.log("Firebase Auth user deleted");
        } catch (err) {
            // Log the error and handle specific Firebase error codes
            console.error("Delete error:", err);
            if (err.code === "auth/requires-recent-login") {
                // If recent login is required, inform the user and reauthenticate
                alert("Please re-login before deleting your account for security reasons.");
                // Determine the appropriate provider for reauthentication
                let provider =
                    providerId === 'github.com'
                        ? new firebase.auth.GithubAuthProvider()
                        : new firebase.auth.GoogleAuthProvider(); // Fallback to Google
                try {
                    // Reauthenticate the user via popup
                    const result = await user.reauthenticateWithPopup(provider);
                    // After successful reauthentication, delete the user
                    await result.user.delete();
                    console.log("Reauthenticated and deleted Auth user");
                } catch (reauthErr) {
                    console.error("Reauth failed:", reauthErr);
                    alert("Could not reauthenticate—account not deleted.");
                    return;
                }
            } else {
                alert("Could not delete your account: " + err.message);
                return;
            }
        }

        // After Firebase Auth deletion, attempt to delete user data from Firestore
        let AuthResult;
        try {
            AuthResult = await deleteFirestoreUserData(user.uid);
            console.log("Firestore user data deleted:", AuthResult);
        } catch (AuthError) {
            console.warn("Firestore delete failed:", AuthError);
            // Still proceed to kill the session below
        }

        // Notify backend to destroy the PHP session and clean up
        let sessionResult;
        try {
            sessionResult = await postAccountAction("delete");
            console.log("Server session destroyed:", sessionResult);
        } catch (sessErr) {
            console.error("Session destroy failed:", sessErr);
            alert("Auth deleted, but failed to destroy your session.");
            return;
        }

        // Clear storage and redirect
        alert(sessionResult.message);
        localStorage.clear();
        localStorage.removeItem("cartItems");
        window.location.href = "login.php";
    }
    // Helper function: delete user data from Firestore via internal PHP API
    async function deleteFirestoreUserData(uid) {
        // Send a DELETE request to the backend API to remove user data by UID
        const response = await fetch(
            `http://localhost/Web%20Assignment/Class_users.php/uid/${encodeURIComponent(uid)}`,
            {
                method: 'DELETE',
                headers: { 'Accept': 'application/json' },
                keepalive: true
            }
        );
        if (!response.ok) {
            const err = await response.json().catch(() => ({ message: response.statusText }));
            throw new Error(err.message || `HTTP ${response.status}`);
        }
        // Returns the parsed JSON response, or throws on network error/non-2XX
        return response.json();
    }
});