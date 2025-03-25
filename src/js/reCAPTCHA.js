// Function to execute reCAPTCHA v3 with a given callback (Plain Script version)
// Attach the executeRecaptcha function to the global window
window.executeRecaptcha = function (action, callback) {
    grecaptcha.ready(function () {
        // Execute reCAPTCHA with action
        grecaptcha.execute('6Lfniv8qAAAAAFd_IKlfvcKGTrKkjda5y2Rat40Z', { action: action })
            .then(function (token) {
                console.log("reCAPTCHA token received for action", action, ":", token);
                // Optionally: send the token to your server for verification before proceeding.
                // If verification passes, then call the provided callback.
                callback(token);
            })
            .catch(function (error) {
                console.error("reCAPTCHA execution error for action", action, ":", error);
            });
    });
};