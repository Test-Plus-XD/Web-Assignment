console.log("login.js: Waiting for Firebase");

// Function to attach login event listeners using global functions
function setupEventListeners() {
    console.log("login.js: Firebase is ready! Setting up event listeners.");
}

// Check if Firebase is already ready before waiting for event
if (window.firebase?.auth) {
    setupEventListeners();
} else {
    document.addEventListener("firebase-ready", setupEventListeners);
}

// Function to set the login state and auto-redirect after 2 seconds
function setLoginState() {
    localStorage.setItem('isLoggedIn', 'true');
    setTimeout(() => {
        window.location.href = localStorage.getItem("preLoginUrl") || "account.php";
    }, 2000);
}

// Callback function to submit the login form
function submitLoginForm(token) {
    console.log("reCAPTCHA token received for login:", token);
    document.getElementById("recaptchaToken").value = token;
    document.getElementById("login-form").submit();
}

// Callback function for anonymous sign-in
function handleAnonymousSignIn(token) {
    console.log("reCAPTCHA token received for anonymous sign-in:", token);
    if (typeof window.anonymousSignIn === "function") {
        window.anonymousSignIn();
    } else {
        console.error("window.anonymousSignIn is not a function.");
    }
}

// Callback function for email sign-in
function handleEmailSignIn(token) {
    console.log("reCAPTCHA token received for email sign-in:", token);
    const email = document.getElementById("loginEmail")?.value;
    const password = document.getElementById("loginPassword")?.value;
    if (typeof window.signInWithEmailPassword === "function" && email && password) {
        window.signInWithEmailPassword(email, password);
    } else {
        console.error("window.signInWithEmailPassword is not a function or email/password not found.");
    }
}

// Callback function for Google sign-in
function handleGoogleSignIn(token) {
    console.log("reCAPTCHA token received for Google sign-in:", token);
    if (typeof window.googleSignInPopup === "function") {
        window.googleSignInPopup();
    } else {
        console.error("window.googleSignInPopup is not a function.");
    }
}

// Callback function for GitHub sign-in
function handleGitHubSignIn(token) {
    console.log("reCAPTCHA token received for GitHub sign-in:", token);
    if (typeof window.githubSignInPopup === "function") {
        window.githubSignInPopup();
    } else {
        console.error("window.githubSignInPopup is not a function.");
    }
}

