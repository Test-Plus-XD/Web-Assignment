// Payment Integration
import express from 'express';
import Stripe from 'stripe';
import dotenv from 'dotenv';
import bodyParser from 'body-parser';
import cors from 'cors';

dotenv.config();
console.log("Stripe secret key loaded:", process.env.stripe_secret_key ? "Yes" : "No");

const app = express();
const port = 4242; // Port for API
const stripe = new Stripe(process.env.stripe_secret_key);

app.use(cors()); // Enable Cross-Origin Resource Sharing for the PHP page
app.use(bodyParser.json()); // Parse JSON request bodies
app.use(express.json()); // for POST body parsing

// Creates a checkout session based on cart data
app.post('/checkout', async (req, res) => {
    console.log('Received checkout payload:', req.body);
    try {
        const { items } = req.body;
        // Convert your product structure to Stripe line items
        const lineItems = items.map(item => ({
            price_data: {
                currency: 'hkd',
                product_data: {
                    name: item.name,
                },
                unit_amount: Math.round(item.price * 100), // Convert to cents
            },
            quantity: item.quantity,
        }));
        console.dir(lineItems, { depth: null });
        const session = await stripe.checkout.sessions.create({
            //payment_method_types: ['card'],
            line_items: lineItems,
            mode: 'payment',
            success_url: 'http://localhost/Web%20Assignment/payment_completed.php?session_id={CHECKOUT_SESSION_ID}', //Stripe will automatically replace {CHECKOUT_SESSION_ID} with the real session ID on redirect
            cancel_url: 'http://localhost/Web%20Assignment/cart.php',
        });
        res.json({ url: session.url });
        console.log("Stripe Checkout session created:", session.id); // 
    } catch (error) {
        console.error('Stripe session creation failed:', error.message || error);
        res.status(500).json({
            error: 'Failed to create Stripe session',
            details: error.message || 'Unknown error'
        });
    }
});

// Verifies Stripe session after checkout
app.get('/verify-Stripe', async (req, res) => {
    const sessionId = req.query.session_id;
    if (!sessionId) {
        return res.status(400).json({ error: "Missing session_id" });
    } else {
        console.log("Stripe session_id to be verified:", sessionId);
    }

    try {
        const session = await stripe.checkout.sessions.retrieve(sessionId);
        if (session.payment_status === "paid") {
            res.json({ verified: true, session });
        } else {
            res.status(403).json({ verified: false, message: "Payment not completed" });
        }
    } catch (error) {
        console.error("Error verifying session:", error);
        res.status(500).json({ error: "Failed to verify session" });
    }
});

app.listen(port, () => {
    console.log(`Stripe API server listening at http://localhost:${port}`);
});

//Navigate to project directory: cd C: \wamp64\www\Web Assignment\
//Run PM2 using npx: npx pm2 start src/js/stripe.js --name stripe --env production
//Test card: Visa	4242424242424242		\Any 3 digits	\Any future date