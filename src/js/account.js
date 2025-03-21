// Import Firebase modules from the global firebase object that should be initialized in firebase.js
const auth = window.firebase.auth();

// Initialize FirebaseUI only if it's available
if (typeof firebaseui !== "undefined") {
// Create a new FirebaseUI Auth instance using the global firebase.auth()
    var ui = new firebaseui.auth.AuthUI(auth);

    // Set Firebase auth language (e.g., Traditional Chinese)
    auth.languageCode = 'zh_tw';

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
            uiShown: function () {
                // Hide the loader element once the widget is rendered
                var loader = document.getElementById('loader');
                if (loader) {
                    loader.style.display = 'none';
                }
            }
        },
        // Will use popup for IDP Providers sign-in flow instead of the default, redirect.
        signInFlow: 'popup',
        // Automatically upgrade anonymous users
        autoUpgradeAnonymousUsers: true,
        // URL to redirect to on successful sign-in
        signInSuccessUrl: '<url-to-redirect-to-on-success>',
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
                    // Forces account selection even when one account
                    // is available.
                    prompt: 'select_account'
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
        tosUrl: '<your-tos-url>',
        // Privacy policy url.
        privacyPolicyUrl: '<your-privacy-policy-url>'
    };

    // Start FirebaseUI if there is a pending redirect
    if (ui.isPendingRedirect()) {
        ui.start('#firebaseui-auth-container', uiConfig);
    }
}

// Function to sign in with email and password (parameters to avoid duplication)
function signInWithEmailPassword(email, password) {
    auth.signInWithEmailAndPassword(email, password)
        .then((userCredential) => {
            // Signed in successfully; log the user object
            console.log("User signed in:", userCredential.user);
        })
        .catch((error) => {
            // Log any errors
            console.error("Error during sign in:", error);
        });
}

// Function to sign up with email and password (parameters to avoid duplication)
function signUpWithEmailPassword(email, password) {
    auth.createUserWithEmailAndPassword(email, password)
        .then((userCredential) => {
            // User created successfully; log the user object
            console.log("User signed up:", userCredential.user);
        })
        .catch((error) => {
            // Log any errors
            console.error("Error during sign up:", error);
        });
}

// Function to send an email verification to the current user
function sendEmailVerification() {
    if (auth.currentUser) {
        auth.currentUser.sendEmailVerification()
            .then(() => {
                console.log("Verification email sent");
            })
            .catch((error) => {
                console.error("Error sending verification email:", error);
            });
    } else {
        console.warn("No current user to send verification email to");
    }
}

// Function to send a password reset email (requires an email parameter)
function sendPasswordReset(email) {
    auth.sendPasswordResetEmail(email)
        .then(() => {
            console.log("Password reset email sent");
        })
        .catch((error) => {
            console.error("Error sending password reset email:", error);
        });
}

function anonymousSignIn() {
    // [START auth_anon_sign_in]
    auth.signInAnonymously()
        .then(() => {
            // Signed in..
        })
        .catch((error) => {
            var errorCode = error.code;
            var errorMessage = error.message;
            // ...
        });
    // [END auth_anon_sign_in]
}

// Google Authorisation ----------------------------------------------------------------------------------------------------
// Docs: https://source.corp.google.com/piper///depot/google3/third_party/devsite/firebase/en/docs/auth/web/google-signin.md
function googleProvider() {
    // [START auth_google_provider_create]
    var provider = new firebase.auth.GoogleAuthProvider();
    // [END auth_google_provider_create]

    // [START auth_google_provider_scopes]
    provider.addScope('https://www.googleapis.com/auth/contacts.readonly');
    // [END auth_google_provider_scopes]

    // [START auth_google_provider_params]
    provider.setCustomParameters({
        'login_hint': 'user@example.com'
    });
    // [END auth_google_provider_params]
}

function googleSignInPopup(provider) {
    // [START auth_google_signin_popup]
    auth
        .signInWithPopup(provider)
        .then((result) => {
            /** @type {firebase.auth.OAuthCredential} */
            var credential = result.credential;

            // This gives you a Google Access Token. You can use it to access the Google API.
            var token = credential.accessToken;
            // The signed-in user info.
            var user = result.user;
            // IdP data available in result.additionalUserInfo.profile.
            // ...
        }).catch((error) => {
            // Handle Errors here.
            var errorCode = error.code;
            var errorMessage = error.message;
            // The email of the user's account used.
            var email = error.email;
            // The firebase.auth.AuthCredential type that was used.
            var credential = error.credential;
            // ...
        });
    // [END auth_google_signin_popup]
}

function googleSignInRedirectResult() {
    // [START auth_google_signin_redirect_result]
    auth
        .getRedirectResult()
        .then((result) => {
            if (result.credential) {
                /** @type {firebase.auth.OAuthCredential} */
                var credential = result.credential;

                // This gives you a Google Access Token. You can use it to access the Google API.
                var token = credential.accessToken;
                // ...
            }
            // The signed-in user info.
            var user = result.user;
            // IdP data available in result.additionalUserInfo.profile.
            // ...
        }).catch((error) => {
            // Handle Errors here.
            var errorCode = error.code;
            var errorMessage = error.message;
            // The email of the user's account used.
            var email = error.email;
            // The firebase.auth.AuthCredential type that was used.
            var credential = error.credential;
            // ...
        });
    // [END auth_google_signin_redirect_result]
}

function googleBuildAndSignIn(id_token) {
    // [START auth_google_build_signin]
    // Build Firebase credential with the Google ID token.
    var credential = firebase.auth.GoogleAuthProvider.credential(id_token);

    // Sign in with credential from the Google user.
    auth.signInWithCredential(credential).catch((error) => {
        // Handle Errors here.
        var errorCode = error.code;
        var errorMessage = error.message;
        // The email of the user's account used.
        var email = error.email;
        // The firebase.auth.AuthCredential type that was used.
        var credential = error.credential;
        // ...
    });
    // [END auth_google_build_signin]
}

// [START auth_google_callback]
function onSignIn(googleUser) {
    console.log('Google Auth Response', googleUser);
    // We need to register an Observer on Firebase Auth to make sure auth is initialized.
    var unsubscribe = auth.onAuthStateChanged((firebaseUser) => {
        unsubscribe();
        // Check if we are already signed-in Firebase with the correct user.
        if (!isUserEqual(googleUser, firebaseUser)) {
            // Build Firebase credential with the Google ID token.
            var credential = firebase.auth.GoogleAuthProvider.credential(
                googleUser.getAuthResponse().id_token);

            // Sign in with credential from the Google user.
            // [START auth_google_signin_credential]
            auth.signInWithCredential(credential).catch((error) => {
                // Handle Errors here.
                var errorCode = error.code;
                var errorMessage = error.message;
                // The email of the user's account used.
                var email = error.email;
                // The firebase.auth.AuthCredential type that was used.
                var credential = error.credential;
                // ...
            });
            // [END auth_google_signin_credential]
        } else {
            console.log('User already signed-in Firebase.');
        }
    });
}
// [END auth_google_callback]

// [START auth_google_checksameuser]
function isUserEqual(googleUser, firebaseUser) {
    if (firebaseUser) {
        var providerData = firebaseUser.providerData;
        for (var i = 0; i < providerData.length; i++) {
            if (providerData[i].providerId === firebase.auth.GoogleAuthProvider.PROVIDER_ID &&
                providerData[i].uid === googleUser.getBasicProfile().getId()) {
                // We don't need to reauth the Firebase connection.
                return true;
            }
        }
    }
    return false;
}
// [END auth_google_checksameuser]

function googleProviderCredential(idToken) {
    // [START auth_google_provider_credential]
    var credential = firebase.auth.GoogleAuthProvider.credential(idToken);
    // [END auth_google_provider_credential]
}

// GitHub Authorisation ----------------------------------------------------------------------------------------------------
function githubProvider() {
    // [START auth_github_provider_create]
    var provider = new firebase.auth.GithubAuthProvider();
    // [END auth_github_provider_create]

    // [START auth_github_provider_scopes]
    provider.addScope('repo');
    // [END auth_github_provider_scopes]

    // [START auth_github_provider_params]
    provider.setCustomParameters({
        'allow_signup': 'false'
    });
    // [END auth_github_provider_params]
}

function githubProviderCredential(token) {
    // [START auth_github_provider_credential]
    var credential = firebase.auth.GithubAuthProvider.credential(token);
    // [END auth_github_provider_credential]
}

function githubSignInPopup(provider) {
    // [START auth_github_signin_popup]
    firebase
        .auth()
        .signInWithPopup(provider)
        .then((result) => {
            /** @type {firebase.auth.OAuthCredential} */
            var credential = result.credential;

            // This gives you a GitHub Access Token. You can use it to access the GitHub API.
            var token = credential.accessToken;

            // The signed-in user info.
            var user = result.user;
            // IdP data available in result.additionalUserInfo.profile.
            // ...
        }).catch((error) => {
            // Handle Errors here.
            var errorCode = error.code;
            var errorMessage = error.message;
            // The email of the user's account used.
            var email = error.email;
            // The firebase.auth.AuthCredential type that was used.
            var credential = error.credential;
            // ...
        });
    // [END auth_github_signin_popup]
}

function githubSignInRedirectResult() {
    // [START auth_github_signin_redirect_result]
    auth
        .getRedirectResult()
        .then((result) => {
            if (result.credential) {
                /** @type {firebase.auth.OAuthCredential} */
                var credential = result.credential;

                // This gives you a GitHub Access Token. You can use it to access the GitHub API.
                var token = credential.accessToken;
                // ...
            }

            // The signed-in user info.
            var user = result.user;
            // IdP data available in result.additionalUserInfo.profile.
            // ...
        }).catch((error) => {
            // Handle Errors here.
            var errorCode = error.code;
            var errorMessage = error.message;
            // The email of the user's account used.
            var email = error.email;
            // The firebase.auth.AuthCredential type that was used.
            var credential = error.credential;
            // ...
        });
    // [END auth_github_signin_redirect_result]
}

// Export functions if using modules
export {
    signInWithEmailPassword,
    signUpWithEmailPassword,
    sendEmailVerification,
    sendPasswordReset,
    anonymousSignIn,
    googleProvider,
    googleSignInPopup,
    googleSignInRedirectResult,
    googleBuildAndSignIn,
    isUserEqual,
    googleProviderCredential,
    githubProvider,
    githubProviderCredential,
    githubSignInPopup,
    githubSignInRedirectResult
};