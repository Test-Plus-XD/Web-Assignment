// Payment Integration
document.addEventListener("DOMContentLoaded", async () => {
    //Stripe Key
    const stripe = Stripe('sk_test_51QQiCZCA0AswCry58ef8rBfji4V8MJjjsSEmBeN9mYJ9Lcsc3mQuyDgZSnptWjlpgSLnbFS6bpK6Lp7UNInN83NZ00PIQlSaTy');
    const { clientSecret } = await fetch('/create-payment-intent').then(r => r.json());

    const elements = stripe.elements({ clientSecret });
    const paymentElement = elements.create('payment');
    paymentElement.mount('#payment-element');

    const payButton = document.getElementById("payButton");

    payButton.addEventListener("click", async () => {
        const { error } = await stripe.confirmPayment({
            elements,
            confirmParams: {
                return_url: 'payment_completed.php',
            },
        });

        if (error) {
            console.error(error.message);
        } else {
            console.log("Payment confirmed. Awaiting database update...");
        }
    });
});

// Sentry Debug
Sentry.onLoad(function () {
    Sentry.init({
        // Tracing
        tracesSampleRate: 1.0, // Capture 100% of the transactions
        // Session Replay
        replaysSessionSampleRate: 0.5, // This sets the sample rate at 50%. You may want to change it to 100% while in development and then sample at a lower rate in production.
        replaysOnErrorSampleRate: 1.0, // If you're not already sampling the entire session, change the sample rate to 100% when sampling sessions where errors occur.
    });
});
//myUndefinedFunction();