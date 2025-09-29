// Comments functionality with page refresh
// Prevent multiple initializations
if (!window.commentsInitialized) {
    document.addEventListener('DOMContentLoaded', function() {
        initializeComments();
    });
    window.commentsInitialized = true;
}

function initializeComments() {
    // Handle comment form submissions with more specific targeting
    document.querySelectorAll('.comment-form').forEach(form => {
        // Remove any existing listeners to prevent duplicates
        form.removeEventListener('submit', handleCommentSubmissionWrapper);
        // Add the event listener only if not already marked
        if (!form.hasAttribute('data-listener-added')) {
            form.addEventListener('submit', handleCommentSubmissionWrapper);
            form.setAttribute('data-listener-added', 'true');
        }
    });
    
    // Handle dynamically added forms (backup for forms created after page load)
    document.addEventListener('submit', function(e) {
        if (e.target.classList.contains('comment-form') && !e.target.hasAttribute('data-listener-added')) {
            e.preventDefault();
            e.target.setAttribute('data-listener-added', 'true');
            handleCommentSubmission(e.target);
        }
    });
    
    // Handle reaction buttons
    document.addEventListener('click', function(e) {
        if (e.target.closest('.reaction-trigger')) {
            const button = e.target.closest('.reaction-trigger');
            
            // Check if user is holding Shift for emoji picker, otherwise just like
            if (e.shiftKey) {
                handleReactionClick(button);
            } else {
                // Direct like action
                handleDirectReaction(button, 'like');
            }
        }
        
        if (e.target.closest('.emoji')) {
            handleEmojiClick(e.target.closest('.emoji'));
        }
    });
}

// Wrapper function for event listener removal
function handleCommentSubmissionWrapper(e) {
    e.preventDefault();
    handleCommentSubmission(e.target);
}

function handleCommentSubmission(form) {
    const postId = form.dataset.postId;
    const textarea = form.querySelector('.comment-input');
    const submitBtn = form.querySelector('.comment-submit');
    const content = textarea.value.trim();
    
    if (!content) {
        alert('Please enter a comment');
        return;
    }
    
    // Prevent double submission with timestamp check
    const now = Date.now();
    const lastSubmit = parseInt(form.dataset.lastSubmit || '0');
    
    if (form.dataset.submitting === 'true' || (now - lastSubmit) < 2000) {
        console.log('Preventing double submission');
        return;
    }
    
    // Mark form as submitting
    form.dataset.submitting = 'true';
    form.dataset.lastSubmit = now.toString();
    
    // Disable form during submission
    textarea.disabled = true;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    // Create form data for regular form submission
    const formData = new FormData();
    formData.append('action', 'add_comment');
    formData.append('post_id', postId);
    formData.append('content', content);
    
    // Submit via regular form submission to refresh the page
    fetch('index.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Refresh the page to show the new comment
        window.location.reload();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
        
        // Re-enable form on error
        form.dataset.submitting = 'false';
        textarea.disabled = false;
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
    });
}

// Note: addCommentToList and updateCommentCount functions removed
// since we now refresh the page instead of using AJAX updates

function handleDirectReaction(button, reactionType) {
    const postId = button.dataset.postId;
    console.log('Direct reaction:', reactionType, 'for post:', postId);
    
    // Send reaction immediately
    const formData = new FormData();
    formData.append('action', 'react');
    formData.append('post_id', postId);
    formData.append('type', reactionType);
    
    console.log('Sending direct reaction request...');
    
    // Disable button temporarily
    button.disabled = true;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Liking...';
    
    fetch('index.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.ok) {
            // Refresh the page to show updated reactions
            window.location.reload();
        } else {
            console.error('Reaction failed:', data);
            alert('Failed to add reaction');
            button.disabled = false;
            button.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred: ' + error.message);
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

function handleReactionClick(button) {
    console.log('Reaction button clicked', button);
    const postId = button.dataset.postId;
    console.log('Post ID:', postId);
    
    const emojiPicker = document.querySelector(`[data-post-id="${postId}"] .emoji-picker`);
    console.log('Emoji picker found:', emojiPicker);
    
    if (emojiPicker) {
        const isVisible = emojiPicker.style.display !== 'none';
        console.log('Picker currently visible:', isVisible);
        
        // Hide all other emoji pickers
        document.querySelectorAll('.emoji-picker').forEach(picker => {
            picker.style.display = 'none';
        });
        
        // Toggle this one
        emojiPicker.style.display = isVisible ? 'none' : 'flex';
        
        if (!isVisible) {
            // Position the picker relative to the button
            const rect = button.getBoundingClientRect();
            emojiPicker.style.position = 'absolute';
            emojiPicker.style.top = (rect.top - 50) + 'px';
            emojiPicker.style.left = rect.left + 'px';
            emojiPicker.style.zIndex = '1000';
            emojiPicker.style.background = 'white';
            emojiPicker.style.border = '1px solid #ddd';
            emojiPicker.style.borderRadius = '8px';
            emojiPicker.style.padding = '8px';
            emojiPicker.style.gap = '5px';
            emojiPicker.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
        }
    } else {
        console.error('Emoji picker not found for post ID:', postId);
    }
}

function handleEmojiClick(emoji) {
    console.log('Emoji clicked:', emoji);
    const emojiPicker = emoji.closest('.emoji-picker');
    const postId = emojiPicker.dataset.postId;
    const reactionType = emoji.dataset.type;
    
    console.log('Post ID:', postId, 'Reaction Type:', reactionType);
    
    // Hide the picker
    emojiPicker.style.display = 'none';
    
    // Send reaction
    const formData = new FormData();
    formData.append('action', 'react');
    formData.append('post_id', postId);
    formData.append('type', reactionType);
    
    console.log('Sending reaction request...');
    
    fetch('index.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.ok) {
            // Refresh the page to show updated reactions
            window.location.reload();
        } else {
            console.error('Reaction failed:', data);
            alert('Failed to add reaction');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred: ' + error.message);
    });
}

function updateReactionCount(postId, increment) {
    const reactionStats = document.querySelector(`[data-post-id="${postId}"] .reaction-stats span`);
    if (reactionStats) {
        const currentText = reactionStats.textContent;
        if (currentText.includes('No reactions')) {
            reactionStats.textContent = '1 reaction';
        } else {
            const currentCount = parseInt(currentText.match(/\d+/)[0]) || 0;
            const newCount = currentCount + increment;
            reactionStats.textContent = `${newCount} reaction${newCount !== 1 ? 's' : ''}`;
        }
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Add notification styles
const notificationStyle = document.createElement('style');
notificationStyle.textContent = `
    .notification-content {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex: 1;
    }
    
    .notification-close {
        background: none;
        border: none;
        color: var(--text-tertiary);
        cursor: pointer;
        padding: 0.25rem;
        border-radius: var(--radius-sm);
        transition: all var(--transition-fast);
    }
    
    .notification-close:hover {
        background: var(--gray-100);
        color: var(--text-primary);
    }
    
    /* Removed new-comment styles since we now refresh the page */
    
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .emoji-picker {
        display: none;
        gap: var(--space-sm);
        padding: var(--space-sm);
        background: var(--bg-primary);
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-lg);
        z-index: 1000;
        position: absolute;
    }
    
    .emoji {
        font-size: 1.5rem;
        cursor: pointer;
        padding: var(--space-xs);
        border-radius: var(--radius-md);
        transition: all var(--transition-fast);
    }
    
    .emoji:hover {
        background: var(--gray-100);
        transform: scale(1.1);
    }
`;
document.head.appendChild(notificationStyle);

// Close emoji pickers when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.emoji-picker') && !e.target.closest('.reaction-trigger')) {
        document.querySelectorAll('.emoji-picker').forEach(picker => {
            picker.style.display = 'none';
        });
    }
});