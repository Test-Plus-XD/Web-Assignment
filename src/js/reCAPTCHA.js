// Global gunction to execute reCAPTCHA v2 with a given callback (Plain Script version)
let recaptchaSent = false; // Prevents duplicate submissions
// Called by reCAPTCHA UI after checkbox is verified
function onRecaptchaSuccess(token) {
    if (recaptchaSent) return;
    recaptchaSent = true;
    console.log("reCAPTCHA success:", token);
    // Verify the token with server
    fetch('reCAPTCHA.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'g-recaptcha-response=' + encodeURIComponent(token)
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                console.log("reCAPTCHA verified by server.");
                window.recaptchaVerified = true;
                // If a specific action was assigned
                if (typeof window.actionAfterCaptcha === "function") {
                    console.log("Calling post-captcha action:", window.actionAfterCaptcha.name);
                    window.actionAfterCaptcha();
                    window.actionAfterCaptcha = null;
                }
                // Otherwise assume it's a form login
                else if (typeof window.submitLoginForm === "function") {
                    window.submitLoginForm(token);
                } else {
                    console.warn("No action specified after reCAPTCHA.");
                }
            } else {
                console.log("Server reCAPTCHA verification failed:", data);
                alert("Please verify you are human.");
            }
        })
        .catch(err => {
            console.log("Verification request failed:", err);
            alert("Network error verifying reCAPTCHA.");
        });
}

// Handles visible reCAPTCHA UI failure
function onRecaptchaError() {
    alert("reCAPTCHA verification failed. Please reload and try again.");
}