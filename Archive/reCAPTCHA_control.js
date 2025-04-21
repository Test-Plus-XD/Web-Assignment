// Listen for the trigger from IPcheck.js or fallback
document.addEventListener("DOMContentLoaded", () => {
    const requireRecaptcha = sessionStorage.getItem("requireRecaptcha");

    if (requireRecaptcha === "true") {
        document.getElementById("recaptcha-container")?.classList.remove("d-none");
    } else {
        // Hide the reCAPTCHA if it's not needed
        document.getElementById("recaptcha-container")?.classList.add("d-none");
    }
});

// Also show it immediately if dynamically triggered
document.addEventListener("show-recaptcha", () => {
    document.getElementById("recaptcha-container")?.classList.remove("d-none");
});