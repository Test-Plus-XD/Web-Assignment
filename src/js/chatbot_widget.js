document.addEventListener('DOMContentLoaded', function () {
    const chatbotButton = document.getElementById('chatbot-button');
    const chatContainer = document.getElementById('chat-container');
    const closeButton = document.getElementById('close-button');
    const chatMessages = document.getElementById('chat-messages');
    const messageInput = document.getElementById('message-input');
    const sendButton = document.getElementById('send-button');
    // Node.js API endpoint
    const apiEndpoint = 'http://localhost:3000/chat';

    chatbotButton.addEventListener('click', toggleChatbot);
    closeButton.addEventListener('click', toggleChatbot);
    sendButton.addEventListener('click', sendMessage);
    messageInput.addEventListener('keypress', function (event) {
        if (event.key === 'Enter') {
            sendMessage();
        }
    });

    function toggleChatbot() {
        chatContainer.style.display = chatContainer.style.display === 'none' ? 'flex' : 'none';
    }

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

            } catch (error) {
                console.error('Error sending message to API:', error);
                displayMessage('ai-error', 'Network error occurred.');
            }
        }
    }

    function displayMessage(sender, text) {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add(`${sender}-message`);
        messageDiv.textContent = `${sender === 'user' ? 'You:' : 'Gemini:'} ${text}`;
        chatMessages.appendChild(messageDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight; // Scroll to the bottom
    }

    // Make the chat container draggable
    let isDragging = false;
    let offsetX, offsetY;
    const chatHeader = document.getElementById('chat-header');

    chatHeader.addEventListener('mousedown', (e) => {
        isDragging = true;
        offsetX = e.clientX - chatContainer.getBoundingClientRect().left;
        offsetY = e.clientY - chatContainer.getBoundingClientRect().top;
        chatContainer.style.cursor = 'grabbing';
    });

    document.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        chatContainer.style.left = e.clientX - offsetX + 'px';
        chatContainer.style.top = e.clientY - offsetY + 'px';
    });

    document.addEventListener('mouseup', () => {
        isDragging = false;
        chatContainer.style.cursor = 'grab';
    });
});