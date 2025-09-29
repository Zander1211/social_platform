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
        // Add the event listener
        form.addEventListener('submit', handleCommentSubmissionWrapper);
    });
    
    // Also handle dynamically added forms
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
            handleReactionClick(e.target.closest('.reaction-trigger'));
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
        showNotification('Please enter a comment', 'warning');
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
        showNotification('An error occurred. Please try again.', 'error');
        
        // Re-enable form on error
        form.dataset.submitting = 'false';
        textarea.disabled = false;
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
    });
}

// Note: addCommentToList and updateCommentCount functions removed
// since we now refresh the page instead of using AJAX updates

function handleReactionClick(button) {
    const postId = button.dataset.postId;
    const emojiPicker = document.querySelector(`[data-post-id="${postId}"] .emoji-picker`);
    
    if (emojiPicker) {
        const isVisible = emojiPicker.style.display !== 'none';
        
        // Hide all other emoji pickers
        document.querySelectorAll('.emoji-picker').forEach(picker => {
            picker.style.display = 'none';
        });
        
        // Toggle this one
        emojiPicker.style.display = isVisible ? 'none' : 'flex';
        
        if (!isVisible) {
            // Position the picker
            const rect = button.getBoundingClientRect();
            emojiPicker.style.position = 'absolute';
            emojiPicker.style.top = (rect.top - emojiPicker.offsetHeight - 10) + 'px';
            emojiPicker.style.left = rect.left + 'px';
        }
    }
}

function handleEmojiClick(emoji) {
    const emojiPicker = emoji.closest('.emoji-picker');
    const postId = emojiPicker.dataset.postId;
    const reactionType = emoji.dataset.type;
    
    // Hide the picker
    emojiPicker.style.display = 'none';
    
    // Send reaction
    const formData = new FormData();
    formData.append('action', 'react');
    formData.append('post_id', postId);
    formData.append('type', reactionType);
    
    fetch('index.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.ok) {
            // Update reaction count
            updateReactionCount(postId, 1);
            showNotification('Reaction added!', 'success', 2000);
            
            // Add visual feedback
            emoji.style.transform = 'scale(1.3)';
            setTimeout(() => {
                emoji.style.transform = 'scale(1)';
            }, 200);
        } else {
            showNotification('Failed to add reaction', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
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

function showNotification(message, type = 'info', duration = 3000) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${getNotificationIcon(type)}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Style the notification
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: var(--bg-primary);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-xl);
        padding: 1rem 1.5rem;
        z-index: 3000;
        max-width: 400px;
        border-left: 4px solid var(--${getNotificationColor(type)});
        animation: slideInRight 0.3s ease-out;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove
    const autoRemove = setTimeout(() => {
        removeNotification(notification);
    }, duration);
    
    // Manual close
    notification.querySelector('.notification-close').addEventListener('click', () => {
        clearTimeout(autoRemove);
        removeNotification(notification);
    });
}

function removeNotification(notification) {
    notification.style.animation = 'slideOutRight 0.3s ease-out';
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 300);
}

function getNotificationIcon(type) {
    const icons = {
        info: 'info-circle',
        success: 'check-circle',
        warning: 'exclamation-triangle',
        error: 'exclamation-circle'
    };
    return icons[type] || 'info-circle';
}

function getNotificationColor(type) {
    const colors = {
        info: 'primary-green',
        success: 'primary-green',
        warning: 'secondary-orange',
        error: 'secondary-red'
    };
    return colors[type] || 'primary-green';
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