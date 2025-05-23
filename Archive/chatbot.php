<!DOCTYPE html>
<html>
<head>
    <title>Gemini Chatbot</title>
    <style>
        body { font-family: sans-serif; }
        #chat-container { width: 400px; border: 1px solid #ccc; height: 300px; overflow-y: scroll; padding: 10px; }
        .user-message { text-align: right; margin-bottom: 5px; color: blue; }
        .ai-message { text-align: left; margin-bottom: 5px; color: green; }
        #message-input { width: 300px; padding: 5px; }
        #send-button { padding: 5px 10px; }
    </style>
</head>
<?php include '../background.php'; ?>
<body>
    <h1>Gemini Chatbot</h1>
    <div id="chat-container">
        </div>
    <div>
        <input type="text" id="message-input" placeholder="Type your message...">
        <button id="send-button">Send</button>
    </div>

    <script>
       const chatContainer = document.getElementById('chat-container');
       const messageInput = document.getElementById('message-input');
       const sendButton = document.getElementById('send-button');
       const apiEndpoint = 'http://localhost:3000/chat'; // Node.js API endpoint

       sendButton.addEventListener('click', sendMessage);
       messageInput.addEventListener('keypress', function(event) {
           if (event.key === 'Enter') {
               sendMessage();
           }
       });

       async function sendMessage() {
           const message = messageInput.value.trim();
           if (message) {
               displayMessage('user', message);
               messageInput.value = '';

               try {
                   const response = await fetch(apiEndpoint, {
                       method: 'POST',
                       headers: {
                           'Content-Type': 'application/json'
                       },
                       body: JSON.stringify({ message: message })
                   });

                   if (!response.ok) {
                       const errorData = await response.json();
                       displayMessage('ai-error', errorData.error || 'Failed to get AI response.');
                       return;
                   }

                   const data = await response.json();
                   displayMessage('ai', data.response);

                   // Log input token count to the browser console
                   if (data.inputTokens !== null && data.inputTokens !== undefined) {
                       console.log('Input Token Count:', data.inputTokens);
                   } else {
                       console.log('Input Token Count: Could not be determined.');
                   }

                   // Log output token count to the browser console
                    if (data.usageMetadata && data.usageMetadata.totalOutputTokens !== undefined) {
                        console.log('Output Token Count:', data.usageMetadata.totalOutputTokens);
                    } else {
                        console.log('Output Token Count: Could not be determined.');
                    }

               } catch (error) {
                   console.error('Error sending message to API:', error);
                   displayMessage('ai-error', 'Network error occurred.');
               }
           }
       }

       function displayMessage(sender, text) {
           const messageDiv = document.createElement('div');
           messageDiv.classList.add(`${sender}-message`);
           messageDiv.textContent = `${sender === 'user' ? 'You:' : 'AI:'} ${text}`;
           chatContainer.appendChild(messageDiv);
           chatContainer.scrollTop = chatContainer.scrollHeight; // Scroll to the bottom
       }
   </script>
</body>
</html>