console.log("firebaseUI.js: Waiting for Firebase");

// Retrieve the stored URL and set a default fallback page
var redirectUrl = localStorage.getItem("preLoginUrl") || "http://localhost/Web%20Assignment/account.php"; // Default page if no URL is stored

// Wait for Firebase to be ready (firebase_cdn.js dispatches "firebase-ready" when initialization is complete)
document.addEventListener("firebase-ready", onFirebaseReady);

function onFirebaseReady() {
    console.log("firebaseUI.js: Firebase is ready! Proceeding");

    // Retrieve the auth instance from the global object (set in firebase_cdn.js)
    //const auth = window.firebaseAuth; // Not useful currently
    const auth = firebase.auth(); 

    if (!auth) {
        console.error("Firebase Auth is not initialized.");
        return;
    }

    // Create provider instances using the global firebase.auth() (compat version)
    const googleProvider = new firebase.auth.GoogleAuthProvider();
    const githubProvider = new firebase.auth.GithubAuthProvider();
    const twitterProvider = new firebase.auth.TwitterAuthProvider();
    const facebookProvider = new firebase.auth.FacebookAuthProvider();
    const emailProvider = firebase.auth.EmailAuthProvider ? new firebase.auth.EmailAuthProvider() : null;
    const phoneProvider = firebase.auth.PhoneAuthProvider ? new firebase.auth.PhoneAuthProvider(auth) : null;
    if (!phoneProvider) console.error("PhoneAuthProvider is not available.");


    // Initialize FirebaseUI only if it's available
    if (typeof firebaseui !== "undefined") {
        // FirebaseUI configuration object
        var uiConfig = {
            // Callbacks for various events
            callbacks: {
                // Called when a user has been successfully signed in
                signInSuccessWithAuthResult: function (authResult, redirectUrl) {
                    // Returning true will allow the redirect to continue automatically
                    return true;
                },
                // signInFailure callback must be provided to handle merge conflicts which occur when an existing credential is linked to an anonymous user.
                signInFailure: function (error) {
                    // For merge conflicts, the error.code will be 'firebaseui/anonymous-upgrade-merge-conflict'.
                    if (error.code != 'firebaseui/anonymous-upgrade-merge-conflict') {
                        return Promise.resolve();
                    }
                    // The credential the user tried to sign in with.
                    var cred = error.credential;
                    // Copy data from anonymous user to permanent user and delete anonymous user.
                    // Finish sign-in after data is copied.
                    return auth.signInWithCredential(cred);
                },
                // Called when the UI is fully rendered
                uiShown: () => {
                    document.getElementById('loader').style.display = 'none';
                    console.log("✅ Firebase UI has been rendered!");
                }
            },
            // Will use popup for IDP Providers sign-in flow instead of the default, redirect.
            signInFlow: 'popup',
            // Automatically upgrade anonymous users
            autoUpgradeAnonymousUsers: true,
            // URL to redirect to on successful sign-in
            signInSuccessUrl: 'redirectUrl',
            signInOptions: [
                // List of OAuth providers supported.
                firebase.auth.PhoneAuthProvider.PROVIDER_ID,
                firebase.auth.TwitterAuthProvider.PROVIDER_ID, // Twitter does not support scopes.
                firebase.auth.GithubAuthProvider.PROVIDER_ID, // Github does not support scopes.
                {
                    provider: firebase.auth.GoogleAuthProvider.PROVIDER_ID,
                    scopes: [
                        'https://www.googleapis.com/auth/contacts.readonly'
                    ],
                    customParameters: {
                        // Forces account selection even when one account is available.
                        login_hint: "user@example.com",
                        prompt: "select_account",
                        hd: "example.com"
                    }
                },
                {
                    provider: firebase.auth.FacebookAuthProvider.PROVIDER_ID,
                    scopes: [
                        'public_profile',
                        'email',
                        'user_likes',
                        'user_friends'
                    ],
                    customParameters: {
                        // Forces password re-entry.
                        auth_type: 'reauthenticate'
                    }
                },
                {
                    provider: firebase.auth.EmailAuthProvider.PROVIDER_ID,
                    signInMethod: firebase.auth.EmailAuthProvider.EMAIL_LINK_SIGN_IN_METHOD,
                    requireDisplayName: false,
                    forceSameDevice: false,
                    emailLinkSignIn: function () {
                        return {
                            // Additional state showPromo=1234 can be retrieved from URL on sign-in completion in signInSuccess callback by checking window.location.href.
                            url: 'https://www.example.com/completeSignIn?showPromo=1234',
                            // Custom FDL domain.
                            dynamicLinkDomain: 'example.page.link',
                            // Always true for email link sign-in.
                            handleCodeInApp: true,
                            // Whether to handle link in iOS app if installed.
                            iOS: {
                                bundleId: 'com.example.ios'
                            },
                            // Whether to handle link in Android app if opened in an Android device.
                            android: {
                                packageName: 'com.example.android',
                                installApp: true,
                                minimumVersion: '12'
                            }
                        };
                    }
                }
            ],
            // Terms of service url.
            tosUrl: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&pp=ygUJcmljayByb2xs0gcJCU8JAYcqIYzv',
            // Privacy policy url.
            privacyPolicyUrl: '<your-privacy-policy-url>'
        };

        // Create a new FirebaseUI Auth instance using the global firebase.auth()
        const ui = new firebaseui.auth.AuthUI(auth);

        // Set Firebase auth language (e.g., Traditional Chinese)
        auth.languageCode = 'zh_tw';

        // Start FirebaseUI if there is (not) a pending redirect
        if (!ui.isPendingRedirect()) {
            ui.start('#firebaseui-auth-container', uiConfig);
        }
    } else {
        console.error("FirebaseUI is not loaded.");
    }
}

// Check if Firebase is already ready before waiting for event
if (window.firebase?.auth) {
    onFirebaseReady();
} else {
    document.addEventListener("firebase-ready", onFirebaseReady);
}

// Define and attach sign-in functions to the global window
window.anonymousSignIn = function () {
    firebase.auth().signInAnonymously()
        .then(() => console.log("Anonymous sign-in successful."))
        .catch((error) => console.error("Anonymous sign-in error:", error));
};

window.signInWithEmailPassword = function (email, password) {
    firebase.auth().signInWithEmailAndPassword(email, password)
        .then((userCredential) => console.log("User signed in:", userCredential.user))
        .catch((error) => console.error("Error during sign-in:", error));
};

window.googleSignInPopup = function () {
    const provider = new firebase.auth.GoogleAuthProvider();
    firebase.auth().signInWithPopup(provider)
        .then((result) => console.log("Google sign-in successful:", result.user))
        .catch((error) => console.error("Google sign-in error:", error));
};

window.githubSignInPopup = function () {
    const provider = new firebase.auth.GithubAuthProvider();
    firebase.auth().signInWithPopup(provider)
        .then((result) => console.log("GitHub sign-in successful:", result.user))
        .catch((error) => console.error("GitHub sign-in error:", error));
};