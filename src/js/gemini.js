﻿// Project: 339693945370
import { GoogleGenerativeAI } from "@google/generative-ai";
import dotenv from "dotenv";
import express from "express";
import bodyParser from "body-parser";
import cors from "cors";
import languageDetect from 'languagedetect';

dotenv.config();
console.log("Gemini secret key loaded:", process.env.gemini_api_key ? "Yes" : "No");

const app = express();
const port = 3000; // Port for API
const languageDetector = new languageDetect();

app.use(cors()); // Enable Cross-Origin Resource Sharing for the PHP page
app.use(bodyParser.json()); // Parse JSON request bodies

const googleAI = new GoogleGenerativeAI(process.env.gemini_api_key);
const geminiConfig = {
    temperature: 0.8,
    topP: 1,
    topK: 1,
    maxOutputTokens: 1024,
};

const geminiModel = googleAI.getGenerativeModel({
    //model: "gemini-2.5-pro-exp-03-25",
    model: "gemini-1.5-flash-8b",
    geminiConfig,
});

// Middleware to log input tokens and attach it to res.locals
app.use('/chat', async (req, res, next) => {
    const userMessage = req.body.message;
    if (userMessage) {
        try {
            const encoding = await geminiModel.countTokens(userMessage);
            console.log("Input tokens:", encoding.totalTokens); // Log to server console
            res.locals.inputTokens = encoding.totalTokens; // Attach to res.locals
        } catch (error) {
            console.error("Error counting tokens:", error);
            res.locals.inputTokens = null;
        }
    } else {
        res.locals.inputTokens = null;
    }
    next();
});

// Middleware to add Gemini instructions(Detect contact inquiry and Language)
app.use('/chat', async (req, res, next) => {
    const userMessage = req.body.message ? req.body.message.toLowerCase() : '';
    const contactKeywords = [
        'contact', 'location', 'address', 'place', 'phone', 'email', 'find', 'where', 'near', // English keywords
        '聯絡', '位置', '地址', '地點', '電話', '電郵', '郵箱', '找', '哪裡', '邊度', '近', // Traditional Chinese keywords
    ];
    let prompt = userMessage + "\n\n";

    if (contactKeywords.some(keyword => userMessage.includes(keyword))) {
        prompt += "Answer using the following contact information: Location English: Yiu On Estate, 2 Hang Hong St Ma On Shan, Hong Kong (MTR Ma On Shan Station Exit B, near MOSTown);Location Chinese: 香港新界沙田馬鞍山恆康街2號耀安邨,港鐵馬鞍山站B出口 鄰近MOSTown; Phone: +852 29261222; Email: enquiry@hkct.edu.hk. Format it clearly.";
        req.body.processedPrompt = prompt;
    } else {
        try {
            const detectedLanguages = languageDetector.detect(userMessage, 1); // Detect the top 1 language
            const nativeLanguage = detectedLanguages.length > 0 ? detectedLanguages[0][0] : 'English'; // Default

            prompt += "Answer this in British English, Traditional Chinese (Hong Kong), and ";
            prompt += `${nativeLanguage} if this language is not similar to English nor Chinese. `;
            prompt += "Please add some spacing '\n' between each translation, and NEVER respond with language prefixes, phonetic symbols or labels such as **Language:** and (Nǐ hǎo).";
            req.body.processedPrompt = prompt;
        } catch (error) {
            console.error("Language detection error:", error);
            prompt += "Answer this in British English, Traditional Chinese (Hong Kong), and English. Please add some spacing between each translation, and NEVER respond with language prefixes, phonetic symbols or labels such as **Language:** and (Nǐ hǎo).";
            req.body.processedPrompt = prompt;
        }
    }
    next();
});

// POST request handler for chat messages
app.post('/chat', async (req, res) => {
    try {
        const userMessage = req.body.message;
        if (!userMessage) return res.status(400).json({ error: 'Message is required.' });

        // Use the prompt generated by the middleware
        const prompt = req.body.processedPrompt;
        const result = await geminiModel.generateContent(prompt);
        const response = result.response;
        let aiResponse = response.text().replace(/<br>/gi, '\n');
        const usageMetadata = response ? response.usageMetadata : undefined;
        //console.log("Full Gemini API Response:", response);

        // Send the cleaned response and include input & output tokens in the JSON response
        res.json({
            response: aiResponse.trim(),
            inputTokens: res.locals.inputTokens,
            usageMetadata: usageMetadata ? { totalOutputTokens: usageMetadata.candidatesTokenCount } : null
        });
        // Call the logging function after sending the response
        logOutputTokens(usageMetadata);
    } catch (error) {
        console.error("Gemini API error:", error);
        res.status(500).json({ error: 'Failed to get response from AI.' });
    }
});

// Middleware Function to log output tokens to the server console
function logOutputTokens(usageMetadata) {
    if (usageMetadata && usageMetadata.candidatesTokenCount) {
        console.log("Output tokens:", usageMetadata.candidatesTokenCount);
    } else {
        console.log("Output tokens: Could not be determined.");
        console.log("Usage Metadata:", usageMetadata);
    }
}

app.listen(port, () => {
    console.log(`Gemini API server listening at http://localhost:${port}`);
});

//Navigate to project directory: cd C: \wamp64\www\Web Assignment\
//Run PM2 using npx: npx pm2 start src/js/gemini.js --name gemini
//References: https://medium.com/@rajreetesh7/integrating-google-gemini-to-node-js-application-e45328613130