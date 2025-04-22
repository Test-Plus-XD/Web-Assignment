console.log("login.js: Waiting for Firebase");
// Make actionAfterCaptcha global so reCAPTCHA.js can access it
window.actionAfterCaptcha = null;
console.log("login.js: reCAPTCHA verified via PHP session:", recaptchaVerified);

// Wait for DOM then Firebase to be ready before setting up
document.addEventListener("DOMContentLoaded", () => {
    console.log("DOM ready.");
    if (window.firebase?.auth) {
        console.log("Firebase ready.");
        setupEventListeners(); // Firebase already ready
    } else {
        document.addEventListener("firebase-ready", setupEventListeners); // Wait if not yet ready
    }
});

// Attach event listeners to buttons
function setupEventListeners() {
    console.log("login.js: Setting up sign-in buttons.");

    // Prevent native form submission
    const form = document.getElementById("login-form");
    form?.addEventListener("submit", e => e.preventDefault());

    // Assign click behaviour to each sign-in button
    const bind = (id, action) => {
        const button = document.getElementById(id);
        if (button) {
            button.addEventListener("click", () => {
                console.log(`${id} clicked`);

                // If already verified via PHP session, just execute the action
                if (recaptchaVerified) {
                    console.log("reCAPTCHA already passed. Executing sign-in.");
                    action();
                } else {
                    console.log("reCAPTCHA required. Prompting user to verify.");
                    window.actionAfterCaptcha = action;

                    // User experience hint and focus for accessibility
                    alert("Please verify you're not a robot by clicking the reCAPTCHA checkbox.");
                    document.querySelector(".g-recaptcha iframe")?.focus();     // Focuses iframe
                }
            });
        }
    };
    // Bind buttons to corresponding handlers
    bind("google-signin-btn", handleGoogleSignIn);
    bind("github-signin-btn", handleGitHubSignIn);
    bind("anonymous-signin-btn", handleAnonymousSignIn);
}

// Manual form submission for username/password after reCAPTCHA passes
window.submitLoginForm = function (token) {
    console.log("Submitting login form with reCAPTCHA token:", token);
    document.getElementById("recaptchaToken").value = token;
    document.getElementById("login-form").submit();
};

// Client-side guard for native login
function validateLoginForm() {
    if (recaptchaVerified) {
        return true;
    } else {
        alert("Please complete the reCAPTCHA before logging in.");
        return false;
    }
}

// Set login session state after successful Firebase sign-in
function setLoginState() {
    localStorage.setItem("isLoggedIn", "true"); // Double-check
    setTimeout(() => { window.location.href = localStorage.getItem("preLoginUrl") || "account.php"; }, 10);
}

// Set library badge count by fetching user's purchases after successful Firebase sign-in
async function setLibraryBadge(uid) { // This function is async
    console.log(`Attempting to fetch library count for UID: ${uid}`);
    const purchasesApiBaseUrl = 'http://localhost/Web Assignment/Class_purchases.php';
    const purchasesApiEndpoint = `${purchasesApiBaseUrl}/uid/${encodeURIComponent(uid)}`;

    try {
        // Await the fetch call to get the Response object
        const purchasesResponse = await fetch(purchasesApiEndpoint, {
            method: 'GET', // Use GET method
            headers: { 'Accept': 'application/json' } // Expect JSON
        });

        if (purchasesResponse.ok) { // Check response status (200)
            const purchasesData = await purchasesResponse.json(); // Await the JSON parsing

            // Check if the response is a successful array of purchases (not an API error object)
            if (purchasesData && !purchasesData.error && Array.isArray(purchasesData)) {
                // Successfully fetched an array of purchases
                const purchaseCount = purchasesData.length;
                // Store the count in localStorage as a string
                localStorage.setItem("libraryCount", purchaseCount.toString());
                console.log(`Workspaceed ${purchaseCount} purchases for UID ${uid}. Library count set in localStorage.`);
            } else {
                // API returned an error structure or unexpected format (e.g., {error: ...})
                console.error("Error or unexpected format fetching purchases:", purchasesData);
                localStorage.setItem("libraryCount", "0"); // Set count to 0 on API error/unexpected response
            }
        } else {
            // HTTP error fetching purchases (e.g., 400, 404, 500)
            const errorBody = await purchasesResponse.text(); // Await getting the error body
            console.error(`HTTP error fetching purchases: ${purchasesResponse.status} ${purchasesResponse.statusText}`, errorBody);
            localStorage.setItem("libraryCount", "0"); // Set count to 0 on HTTP error
        }
    } catch (purchaseFetchError) {
        // Network or other error during fetch operation
        console.error("Error fetching purchases API:", purchaseFetchError);
        localStorage.setItem("libraryCount", "0"); // Set count to 0 on network error
    }
}

// Updates the PHP session after successful Firebase authentication.
// Sends the Firebase UID to the Class_account.php endpoint to set the session variables.
// @param {firebase.User} firebaseUser The Firebase user object obtained after successful sign-in
async function updateSessionAfterFirebase(firebaseUser) { // This function is async
    if (firebaseUser && firebaseUser.uid) {
        fetch("Class_account.php?action=update_firebase_session", { // Call the specific action in Class_account.php
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
                "X-Requested-With": "XMLHttpRequest" // Add this header to identify it as an AJAX request
            },
            body: `firebaseLogin=true&uid=${firebaseUser.uid}` // Send the UID as POST data
        })
            .then(res => {
                console.log("Raw session update response:", res); // Log the raw Response object
                return res.json(); // Try to parse the response as JSON
                //return res.text(); // Instead of res.json(), try to get the response as text
            })
            .then(async data => { // <<< Marked as async
                //const data = JSON.parse(text); // Try to parse the text as JSON
                console.log("Session updated via Class_account.php:", data);
                if (data && data.success) {
                    console.log("Session update successful. Proceeding to fetch library count.");
                    // Call the setLibraryBadge function and AWAIT its completion
                    await setLibraryBadge(firebaseUser.uid); 
                    // Proceed with login state and redirect AFTER attempting to fetch the count
                    setLoginState();
                } else if (data && data.error) {
                    console.error("Error updating session:", data.error);
                    alert("Error updating session after Firebase login.");
                } else {
                    // Unexpected response from session update endpoint
                    console.error("Unexpected response from session update endpoint:", data);
                    alert("An unexpected error occurred during login session update.");
                }
            })
            .catch(err => {
                console.error("Session update fetch failed:", err);
                alert("Network error while updating session.");
            });
    } else {
        console.error("Firebase user or UID not found.");
        alert("Error: Could not retrieve Firebase user information.");
    }
}

// Function to send Firebase user info to local database via AJAX
function registerFirebaseUser(firebaseUser) {
    // Extract relevant information from firebaseUser
    const displayName = firebaseUser.displayName || null;
    const email = firebaseUser.email || null;
    const uid = firebaseUser.uid;
    const phoneNumber = firebaseUser.phoneNumber || null;
    const photoURL = firebaseUser.photoURL || null;
    const emailVerified = firebaseUser.emailVerified || false;
    const isAnonymous = firebaseUser.isAnonymous || false;

    // Construct raw JSON payload for Firestore
    /*const payload = {
        fields: {
            uid: { stringValue: firebaseUser.uid },
            displayName: { stringValue: displayName },
            email: { stringValue: email },
            phoneNumber: { stringValue: phoneNumber || "" },
            photoURL: { stringValue: photoURL || "" },
            emailVerified: { booleanValue: emailVerified },
            isAnonymous: { booleanValue: isAnonymous },
            isAdmin: { booleanValue: false } // Add the isAdmin field
        }
    };*/
    // Construct JSON payload for Firestore
    const payload = {
        uid: uid, // Always present
        displayName: displayName, // May be null (e.g., Anonymous, new Email/Password)
        email: email, // May be null (e.g., Anonymous, Phone)
        phoneNumber: phoneNumber || "", // May be empty string
        photoURL: photoURL || "", // May be empty string
        emailVerified: emailVerified, // Always present, boolean
        isAnonymous: isAnonymous, // Always present, boolean
        isAdmin: false // Hardcoded
    };

    // Define the API endpoint for user creation
    const apiEndpoint = 'http://localhost/Web Assignment/Class_users.php/create';

    // Send to Class_users.php for processing
    fetch(apiEndpoint, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
    })
        .then(res => {
            console.log("Raw server response:", res); // Log the raw Response object
            return res.text(); // Instead of res.json(), try to get the response as text for better error handling
        })
        .then(text => {
            console.log("Server response as text:", text);
            try {
                let data = JSON.parse(text); // Try to parse the text as JSON
                console.log("Server response from Firestore registration (parsed JSON):", data);
                if (data) {
                    console.log("Firestore user creation/login successful.");
                    updateSessionAfterFirebase(firebaseUser); // Update the PHP session with UID
                } else {
                    alert("Error creating Firestore user: " + data.message);
                }
            } catch (error) {
                console.error("Error parsing server response as JSON:", error);
                console.log("Full server response causing the error:", text); // Log the full text that failed to parse
                alert("An unexpected error occurred during user registration.");
            }
        })
        .catch(err => {
            console.error("Error sending Firebase user to server:", err);
        });
}

// Firebase Sign-In Implementations:
document.getElementById("email-signin-btn")?.addEventListener("click", () => {
    if (recaptchaVerified) {
        document.getElementById("email-login-modal").style.display = "flex";
    } else {
        alert("Please verify you're not a robot by clicking the reCAPTCHA checkbox.");
    }
});
function closeEmailModal() {
    document.getElementById("email-login-modal").style.display = "none";
}
function handleEmailSignIn() {
    let loginEmail = document.getElementById("loginEmail")?.value;
    let password = document.getElementById("loginPassword")?.value;
    if (!loginEmail || !loginPassword) return alert("Please enter both email and password.");

    firebase.auth().signInWithEmailAndPassword(loginEmail, loginPassword)
        .then(result => {
            console.log("Email sign-in success:", result.user);
            registerFirebaseUser(result.user);
            closeEmailModal();
        })
        .catch(err => {
            console.error("Email sign-in failed:", err);
            alert("Invalid email or password.");
        });
}
// Function to handle new user registration
function handleEmailRegistration() {
    let createEmail = document.getElementById("createEmail")?.value;
    let createPassword = document.getElementById("createPassword")?.value;
    if (!createEmail || !createPassword) return alert("Please enter both email and password.");

    firebase.auth().createUserWithEmailAndPassword(createEmail, createPassword)
        .then(userCredential => {
            const user = userCredential.user;
            console.log("User registered:", user);
            registerFirebaseUser(user);
        })
        .catch(error => {
            console.error("Email registration failed:", error);
            alert("Registration failed: " + error.message + "\n If you used the same email to sign in before,\n Please choose that option below.");
        });
}

function handleAnonymousSignIn() {
    firebase.auth().signInAnonymously()
        .then(result => {
            console.log("Anonymous sign-in success:", result.user);
            registerFirebaseUser(result.user);
        })
        .catch(err => {
            console.error("Anonymous sign-in failed:", err);
            alert("Anonymous sign-in failed.");
        });
}

function handleGoogleSignIn() {
    const provider = new firebase.auth.GoogleAuthProvider();
    firebase.auth().signInWithPopup(provider)
        .then(result => {
            console.log("Google sign-in success:", result.user);
            registerFirebaseUser(result.user);
        })
        .catch(err => {
            console.error("Google sign-in failed:", err);
            alert("Google sign-in failed.");
        });
}

function handleGitHubSignIn() {
    const provider = new firebase.auth.GithubAuthProvider();
    firebase.auth().signInWithPopup(provider)
        .then(result => {
            console.log("GitHub sign-in success:", result.user);
            registerFirebaseUser(result.user);
        })
        .catch(err => {
            console.error("GitHub sign-in failed:", err);
            alert("GitHub sign-in failed.");
        });
}