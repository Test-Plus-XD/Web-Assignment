// This script dynamically loads either the NPM or CDN version
(async () => {
    console.log("firebase.js: Deciding Firebase version to load...");

    try {
        // Test if NPM Firebase is available
        await import("./firebase-npm.js");
        console.log("firebase.js: Loaded NPM version.");
    } catch (error) {
        console.warn("firebase.js: NPM version not available. Falling back to CDN.");
        const script = document.createElement("script");
        script.src = "./firebase-cdn.js";
        script.type = "module";
        document.head.appendChild(script);
    }
})();