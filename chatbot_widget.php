<?php
function chatbotWidget() {
    $html = <<<HTML
    <div id="chatbot-button-container" style="position: fixed; bottom: 15%; left: 15px; z-index: 15;">
        <button id="chatbot-button" class="btn btn-primary" title="Click to chat with AI"><i class="bi bi-chat-left-text-fill"></i></button>
    </div>

    <div id="chat-container">
        <div id="chat-header" class="bg-light p-2 mb-3 d-flex justify-content-between align-items-center border-bottom rounded-top" style="cursor: grab;">
            <span id="chat-header-title" class="fw-bold">Ask Gemini</span>
            <button id="close-button" type="button" class="btn-close" aria-label="Close"></button>
        </div>
        <div id="chat-messages" style="flex-grow: 1; overflow-y: auto; padding-bottom: 10px;">
        </div>
        <div id="chat-input-area" class="d-flex mt-3">
            <input type="text" id="message-input" class="form-control" placeholder="Type your message...">
            <button id="send-button" class="btn btn-primary ms-2">Send</button>
        </div>
    </div>
HTML;
    echo $html;
}
?>