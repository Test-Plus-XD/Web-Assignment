// Function to execute reCAPTCHA v2 with a given callback (Plain Script version)
// Attach the executeRecaptcha function to the global window
window.executeRecaptcha = function (action, callback) {
    grecaptcha.ready(function () {
        // Execute reCAPTCHA with action
        grecaptcha.execute('6Left_4qAAAAAGSyUGZfW4CPtYlVch3kqI5NWR6X', { action: action })
            .then(function (token) {
                console.log("reCAPTCHA token received for action", action, ":", token);

                // Send token to server for verification
                fetch('reCAPTCHA.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'g-recaptcha-response=' + encodeURIComponent(token)
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            console.log("reCAPTCHA verification passed.");
                            callback(token); // Proceed with login
                        } else {
                            console.error("reCAPTCHA verification failed.");
                            alert("reCAPTCHA validation failed. Please try again.");
                        }
                    })
                    .catch(error => console.error("Error verifying reCAPTCHA:", error));
            })
            .catch(function (error) {
                console.error("reCAPTCHA execution error for action", action, ":", error);
            });
    });
};