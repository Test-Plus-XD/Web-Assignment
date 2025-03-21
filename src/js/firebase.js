// firebase.js
// Self-invoking async function to load Firebase modules (app, analytics, auth)
// from local (npm) sources first, and fall back to CDN if local modules are unavailable.
(async () => {
    let initializeApp, getAnalytics, getAuth;

    try {
        // Attempt to import Firebase modules from local (npm) sources.
        // These modules will be resolved from node_modules folder.
        const firebaseAppModule = await import('firebase/app');
        const firebaseAnalyticsModule = await import('firebase/analytics');
        const firebaseAuthModule = await import('firebase/auth');
        ({ initializeApp } = firebaseAppModule);
        getAnalytics = firebaseAnalyticsModule.getAnalytics;
        getAuth = firebaseAuthModule.getAuth;
        console.log("Loaded Firebase modules from local sources.");
    } catch (error) {
        console.warn("Local Firebase modules not available, falling back to CDN.", error);
        // Fallback: Import Firebase modules from the CDN.
        const firebaseAppModule = await import("https://www.gstatic.com/firebasejs/11.5.0/firebase-app.js");
        const firebaseAnalyticsModule = await import("https://www.gstatic.com/firebasejs/11.5.0/firebase-analytics.js");
        const firebaseAuthModule = await import("https://www.gstatic.com/firebasejs/11.5.0/firebase-auth.js");
        ({ initializeApp } = firebaseAppModule);
        getAnalytics = firebaseAnalyticsModule.getAnalytics;
        getAuth = firebaseAuthModule.getAuth;
        console.log("Loaded Firebase modules from CDN.");
    }

    // Your Firebase configuration
    const firebaseConfig = {
        apiKey: "AIzaSyCQDJLKGSEzBn3HMqe7c3KHp1iUapZOYm4",
        authDomain: "web-assignment-4237d.firebaseapp.com",
        projectId: "web-assignment-4237d",
        storageBucket: "web-assignment-4237d.firebasestorage.app",
        messagingSenderId: "836483725829",
        appId: "1:836483725829:web:0e296ae8d81f6a7d4c3a6c",
        measurementId: "G-S00EXY5D6J"
    };

    // Initialize Firebase. Since we're using modules from either local or CDN,
    // we call initializeApp directly.
    const app = initializeApp(firebaseConfig);
    const analytics = getAnalytics(app);
    const auth = getAuth(app);

    // Expose the initialized Firebase objects globally if needed
    window.firebaseApp = app;
    window.firebaseAnalytics = analytics;
    window.firebaseAuth = auth;

    // Log that Firebase is initialized
    console.log("Firebase initialized:", app);
})();