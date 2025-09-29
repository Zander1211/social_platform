// Messenger JavaScript for School Platform

document.addEventListener('DOMContentLoaded', function() {
    if (window.location.pathname.includes('chat')) {
        initializeMessenger();
    }
});

function initializeMessenger() {
    initializeChatInterface();
    // Don't initialize message sending - let the form handle it natively
    initializeUserList();
    // Don't initialize notifications that make API calls
    
    console.log('Messenger initialized (basic mode)');
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
        
        // Send on Enter, new line on Shift+Enter - but let form handle submission
        messageInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                const form = this.closest('form');
                if (form) {
                    form.submit();
                }
            }
        });
    }
}

function initializeUserList() {
    // Search users
    const userSearch = document.querySelector('.user-search');
    if (userSearch) {
        let searchTimeout;
        userSearch.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => searchUsers(e), 300);
        });
    }
    
    // User selection is handled by the links in the PHP template
}

function searchUsers(e) {
    const query = e.target.value.toLowerCase().trim();
    const userItems = document.querySelectorAll('.user-item');
    
    userItems.forEach(item => {
        const userName = item.querySelector('.user-item-name')?.textContent.toLowerCase() || '';
        const userRole = item.querySelector('.user-item-role')?.textContent.toLowerCase() || '';
        
        if (query === '' || userName.includes(query) || userRole.includes(query)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

// Remove all the API-calling functions that don't work with this backend
// The chat functionality now works with native form submissions

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