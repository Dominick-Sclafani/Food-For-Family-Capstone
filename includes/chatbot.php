<?php
// Chatbot Interface
?>
<div class="chatbot-container">
    <button class="chatbot-toggle" onclick="toggleChatbot()">
        <i class="fas fa-comments"></i>
    </button>

    <div class="chatbot-window" id="chatbotWindow">
        <div class="chatbot-header">
            <h5>Food For Family Assistant</h5>
            <button class="close-btn" onclick="toggleChatbot()">&times;</button>
        </div>

        <div class="chatbot-messages" id="chatbotMessages">
            <div class="message bot">
                <p>Hello! I'm your Food For Family assistant. How can I help you today? I can help you with:</p>
                <ul>
                    <li>Finding and filtering meals</li>
                    <li>Becoming a chef</li>
                    <li>Posting meals</li>
                    <li>Leaving reviews</li>
                    <li>Managing your account</li>
                    <li>And much more!</li>
                </ul>
            </div>
        </div>

        <div class="chatbot-input">
            <input type="text" id="userInput" placeholder="Type your question here..."
                onkeypress="handleKeyPress(event)">
            <button onclick="sendMessage()">Send</button>
        </div>
    </div>
</div>

<style>
    .chatbot-container {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 9999;
    }

    .chatbot-toggle {
        background-color: #007bff;
        color: white;
        border: none;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        cursor: pointer;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .chatbot-toggle:hover {
        background-color: #0056b3;
        transform: translateY(-2px) scale(1.05);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    }

    .chatbot-toggle i {
        font-size: 24px;
    }

    .chatbot-window {
        display: none;
        width: 350px;
        height: 500px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        flex-direction: column;
        position: absolute;
        bottom: 80px;
        right: 0;
        animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
        from {
            transform: translateY(20px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .chatbot-window.active {
        display: flex;
    }

    .chatbot-header {
        padding: 15px;
        background: #007bff;
        color: white;
        border-radius: 10px 10px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .chatbot-header h5 {
        margin: 0;
        font-size: 18px;
    }

    .close-btn {
        background: none;
        border: none;
        color: white;
        font-size: 24px;
        cursor: pointer;
        padding: 0;
        line-height: 1;
    }

    .chatbot-messages {
        flex: 1;
        padding: 15px;
        overflow-y: auto;
        background: #f8f9fa;
    }

    .message {
        margin-bottom: 10px;
        padding: 12px;
        border-radius: 10px;
        max-width: 80%;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .message.user {
        background: #007bff;
        color: white;
        margin-left: auto;
    }

    .message.bot {
        background: white;
        margin-right: auto;
        border: 1px solid #dee2e6;
    }

    .chatbot-input {
        padding: 15px;
        border-top: 1px solid #dee2e6;
        display: flex;
        gap: 10px;
        background: white;
    }

    .chatbot-input input {
        flex: 1;
        padding: 10px;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        font-size: 14px;
    }

    .chatbot-input button {
        background: #007bff;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .chatbot-input button:hover {
        background: #0056b3;
    }

    .typing-indicator {
        display: flex;
        gap: 5px;
        padding: 12px;
        background: white;
        border-radius: 10px;
        margin-right: auto;
        max-width: 80%;
        border: 1px solid #dee2e6;
    }

    .typing-indicator span {
        width: 8px;
        height: 8px;
        background: #007bff;
        border-radius: 50%;
        animation: typing 1s infinite;
    }

    .typing-indicator span:nth-child(2) {
        animation-delay: 0.2s;
    }

    .typing-indicator span:nth-child(3) {
        animation-delay: 0.4s;
    }

    @keyframes typing {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-5px);
        }
    }

    /* Responsive adjustments */
    @media (max-width: 576px) {
        .chatbot-window {
            width: 100%;
            height: 100%;
            bottom: 0;
            right: 0;
            border-radius: 0;
        }

        .chatbot-toggle {
            width: 50px;
            height: 50px;
        }

        .chatbot-toggle i {
            font-size: 20px;
        }
    }
</style>

<script>
    function toggleChatbot() {
        const chatbotWindow = document.getElementById('chatbotWindow');
        chatbotWindow.classList.toggle('active');
    }

    function handleKeyPress(event) {
        if (event.key === 'Enter') {
            sendMessage();
        }
    }

    function showTypingIndicator() {
        const messagesDiv = document.getElementById('chatbotMessages');
        const typingDiv = document.createElement('div');
        typingDiv.className = 'typing-indicator';
        typingDiv.id = 'typingIndicator';
        typingDiv.innerHTML = '<span></span><span></span><span></span>';
        messagesDiv.appendChild(typingDiv);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }

    function hideTypingIndicator() {
        const typingIndicator = document.getElementById('typingIndicator');
        if (typingIndicator) {
            typingIndicator.remove();
        }
    }

    function sendMessage() {
        const userInput = document.getElementById('userInput');
        const message = userInput.value.trim();

        if (message) {
            // Add user message to chat
            addMessage(message, 'user');

            // Clear input
            userInput.value = '';

            // Show typing indicator
            showTypingIndicator();

            // Get bot response
            fetch('includes/chatbot_response.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'message=' + encodeURIComponent(message)
            })
                .then(response => response.text())
                .then(response => {
                    hideTypingIndicator();
                    addMessage(response, 'bot');
                })
                .catch(error => {
                    hideTypingIndicator();
                    addMessage("I'm sorry, I'm having trouble connecting right now. Please try again later.", 'bot');
                });
        }
    }

    function addMessage(text, sender) {
        const messagesDiv = document.getElementById('chatbotMessages');
        const messageDiv = document.createElement('div');
        messageDiv.className = 'message ' + sender;
        messageDiv.innerHTML = '<p>' + text + '</p>';
        messagesDiv.appendChild(messageDiv);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }
</script>