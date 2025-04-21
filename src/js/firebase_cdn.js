// Firebase CDN Version
console.log("firebase-cdn.js: Using standalone CDN version of Firebase");

// Firebase Configuration
const firebaseConfig = {
    apiKey: "AIzaSyCQDJLKGSEzBn3HMqe7c3KHp1iUapZOYm4",
    authDomain: "web-assignment-4237d.firebaseapp.com",
    projectId: "web-assignment-4237d",
    storageBucket: "web-assignment-4237d.firebasestorage.app",
    messagingSenderId: "836483725829",
    appId: "1:836483725829:web:0e296ae8d81f6a7d4c3a6c",
    measurementId: "G-S00EXY5D6J"
};

// Initialize Firebase
const app = firebase.initializeApp(firebaseConfig);
const analytics = firebase.analytics(); // Not fully integrated
const auth = firebase.auth(); // Not fully integrated
const firestore = firebase.firestore(); // Not fully integrated
const appCheck = firebase.appCheck(); // Not yet integrated
//appCheck.activate('6Left_4qAAAAAGSyUGZfW4CPtYlVch3kqI5NWR6X', false); // Instantise a reCAPTCHA onload. If true, the SDK automatically refreshes App Check tokens as needed.
//site key v3: 6Lfniv8qAAAAAFd_IKlfvcKGTrKkjda5y2Rat40Z
//secret key v3: 6Lfniv8qAAAAAOhGK2XfG07fZHw4eTlk5eczgKBZ
//site key v2: 6Left_4qAAAAAGSyUGZfW4CPtYlVch3kqI5NWR6X
//secret key v2: 6Left_4qAAAAAJoPcX2VF4aAZbQhVlJDLv8A9YJZ
//debug token: 913A6B2B-FDCB-464A-B69E-BFEF50736A2C÷2ed
console.log(firebase.app().name);

// Attach Firebase objects to global window
window.firebaseApp = app;
window.firebaseAppCheck = appCheck;
window.firebaseAuth = auth;
window.firebaseAnalytics = analytics;
window.firebaseFirestore = firestore;
// Ensure all scripts have loaded before dispatching event
setTimeout(() => {
    console.log("firebase-cdn.js: Dispatching firebase-ready event.");
    document.dispatchEvent(new Event("firebase-ready"));
}, 0);
//Reference: https://gist.github.com/shadowlion/e85106cbcd3cd4542a66e3c8b42702b3