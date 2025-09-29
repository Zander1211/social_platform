// Messenger JavaScript for School Platform

document.addEventListener('DOMContentLoaded', function() {
    if (window.location.pathname.includes('chat')) {
        initializeMessenger();
    }
});

function initializeMessenger() {
    initializeChatInterface();
    initializeMessageSending();
    initializeUserList();
    initializeNotifications();
    
    console.log('Messenger initialized');
}

function initializeChatInterface() {
    // Auto-scroll to bottom of messages
    const messagesContainer = document.querySelector('.messages-container');
    if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    // Message input auto-resize
    const messageInput = document.querySelector('.message-input');
    if (messageInput) {
        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });
        
        // Send on Enter, new line on Shift+Enter
        messageInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    }
}

function initializeMessageSending() {
    const sendButton = document.querySelector('.send-message-btn');
    if (sendButton) {
        sendButton.addEventListener('click', sendMessage);
    }
}

function sendMessage() {
    const messageInput = document.querySelector('.message-input');
    const message = messageInput.value.trim();
    
    if (!message) return;
    
    // Add message to UI immediately (optimistic update)
    addMessageToUI({
        id: Date.now(),
        content: message,
        sender_id: window.__ME__,
        created_at: new Date().toISOString(),
        is_me: true
    });
    
    // Clear input
    messageInput.value = '';
    messageInput.style.height = 'auto';
    
    // Send to server
    const chatId = getCurrentChatId();
    fetch('/api/messages', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            chat_id: chatId,
            content: message
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            throw new Error(data.message || 'Failed to send message');
        }
    })
    .catch(error => {
        window.SchoolPlatform.showNotification('Failed to send message', 'error');
        // Remove the optimistic message on error
        const lastMessage = document.querySelector('.message:last-child');
        if (lastMessage) {
            lastMessage.remove();
        }
    });
}

function addMessageToUI(message) {
    const messagesContainer = document.querySelector('.messages-container');
    if (!messagesContainer) return;
    
    const messageElement = createMessageElement(message);
    messagesContainer.appendChild(messageElement);
    
    // Scroll to bottom
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    
    // Animate in
    setTimeout(() => {
        messageElement.classList.add('animate-in');
    }, 10);
}

function createMessageElement(message) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${message.is_me ? 'message-sent' : 'message-received'}`;
    
    const time = formatMessageTime(message.created_at);
    
    messageDiv.innerHTML = `
        <div class="message-content">
            <div class="message-text">${escapeHtml(message.content)}</div>
            <div class="message-time">${time}</div>
        </div>
    `;
    
    messageDiv.style.cssText = `
        margin-bottom: 1rem;
        display: flex;
        ${message.is_me ? 'justify-content: flex-end;' : 'justify-content: flex-start;'}
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.3s ease-out;
    `;
    
    const contentStyle = `
        max-width: 70%;
        padding: 0.75rem 1rem;
        border-radius: 1rem;
        position: relative;
        ${message.is_me ? 
            'background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-dark)); color: white; border-bottom-right-radius: 0.25rem;' : 
            'background: var(--gray-100); color: var(--text-primary); border-bottom-left-radius: 0.25rem;'
        }
    `;
    
    messageDiv.querySelector('.message-content').style.cssText = contentStyle;
    
    messageDiv.querySelector('.message-text').style.cssText = `
        margin-bottom: 0.25rem;
        line-height: 1.4;
        word-wrap: break-word;
    `;
    
    messageDiv.querySelector('.message-time').style.cssText = `
        font-size: 0.7rem;
        opacity: 0.7;
        text-align: right;
    `;
    
    return messageDiv;
}

function initializeUserList() {
    // Search users
    const userSearch = document.querySelector('.user-search');
    if (userSearch) {
        const debouncedSearch = window.SchoolPlatform.debounce(searchUsers, 300);
        userSearch.addEventListener('input', debouncedSearch);
    }
    
    // User selection
    document.addEventListener('click', function(e) {
        if (e.target.closest('.user-item')) {
            selectUser(e.target.closest('.user-item'));
        }
    });
}

function searchUsers(e) {
    const query = e.target.value.toLowerCase().trim();
    const userItems = document.querySelectorAll('.user-item');
    
    userItems.forEach(item => {
        const userName = item.querySelector('.user-name')?.textContent.toLowerCase() || '';
        const userRole = item.querySelector('.user-role')?.textContent.toLowerCase() || '';
        
        if (query === '' || userName.includes(query) || userRole.includes(query)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

function selectUser(userItem) {
    // Remove active state from all users
    document.querySelectorAll('.user-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Add active state to selected user
    userItem.classList.add('active');
    
    // Load chat with this user
    const userId = userItem.dataset.userId;
    loadChat(userId);
}

function loadChat(userId) {
    const messagesContainer = document.querySelector('.messages-container');
    if (!messagesContainer) return;
    
    // Show loading
    messagesContainer.innerHTML = '<div class="loading-messages">Loading messages...</div>';
    
    fetch(`/api/chats/${userId}/messages`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayMessages(data.messages);
            } else {
                throw new Error(data.message || 'Failed to load messages');
            }
        })
        .catch(error => {
            messagesContainer.innerHTML = '<div class="error-messages">Failed to load messages</div>';
            window.SchoolPlatform.showNotification('Failed to load chat', 'error');
        });
}

function displayMessages(messages) {
    const messagesContainer = document.querySelector('.messages-container');
    messagesContainer.innerHTML = '';
    
    messages.forEach(message => {
        message.is_me = message.sender_id === window.__ME__;
        addMessageToUI(message);
    });
}

function initializeNotifications() {
    // Check for new messages periodically
    setInterval(checkForNewMessages, 5000);
}

function checkForNewMessages() {
    const activeChat = document.querySelector('.user-item.active');
    if (!activeChat) return;
    
    const userId = activeChat.dataset.userId;
    const lastMessageId = getLastMessageId();
    
    fetch(`/api/chats/${userId}/messages?after=${lastMessageId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.messages.length > 0) {
                data.messages.forEach(message => {
                    message.is_me = message.sender_id === window.__ME__;
                    addMessageToUI(message);
                });
                
                // Show notification if not focused
                if (!document.hasFocus()) {
                    showDesktopNotification('New message received');
                }
            }
        })
        .catch(error => {
            console.error('Failed to check for new messages:', error);
        });
}

function getLastMessageId() {
    const messages = document.querySelectorAll('.message');
    if (messages.length === 0) return 0;
    
    const lastMessage = messages[messages.length - 1];
    return lastMessage.dataset.messageId || 0;
}

function showDesktopNotification(message) {
    if ('Notification' in window && Notification.permission === 'granted') {
        new Notification('School Platform', {
            body: message,
            icon: '/assets/icon.png'
        });
    }
}

function getCurrentChatId() {
    const activeUser = document.querySelector('.user-item.active');
    return activeUser ? activeUser.dataset.userId : null;
}

function formatMessageTime(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);
    
    if (minutes < 1) return 'now';
    if (minutes < 60) return `${minutes}m`;
    if (hours < 24) return `${hours}h`;
    if (days < 7) return `${days}d`;
    
    return date.toLocaleDateString();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Request notification permission
if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
}

// Add message animation styles
const messageStyles = document.createElement('style');
messageStyles.textContent = `
    .message.animate-in {
        opacity: 1 !important;
        transform: translateY(0) !important;
    }
    
    .loading-messages, .error-messages {
        text-align: center;
        padding: 2rem;
        color: var(--text-secondary);
        font-style: italic;
    }
    
    .user-item.active {
        background: var(--primary-blue-lighter);
        border-left: 4px solid var(--primary-blue);
    }
    
    .message-input {
        resize: none;
        min-height: 40px;
        max-height: 120px;
    }
`;
document.head.appendChild(messageStyles);