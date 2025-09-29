// Main Application JavaScript for School Platform

// Initialize application when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    // Initialize all components
    initializeAnimations();
    initializeInteractions();
    initializeFormEnhancements();
    initializeNotifications();
    initializeTheme();
    
    console.log('School Platform initialized successfully');
}

// Animation System
function initializeAnimations() {
    // Intersection Observer for scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, observerOptions);
    
    // Observe all animatable elements
    document.querySelectorAll('.card, .post, .form-group').forEach(el => {
        observer.observe(el);
    });
    
    // Add CSS for scroll animations
    const style = document.createElement('style');
    style.textContent = `
        .card, .post, .form-group {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.6s ease-out;
        }
        
        .animate-in {
            opacity: 1 !important;
            transform: translateY(0) !important;
        }
    `;
    document.head.appendChild(style);
}

// Interactive Elements
function initializeInteractions() {
    // Enhanced button interactions
    document.querySelectorAll('.btn, .nav-item, .post-action').forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = '';
        });
        
        button.addEventListener('mousedown', function() {
            this.style.transform = 'translateY(0)';
        });
        
        button.addEventListener('mouseup', function() {
            this.style.transform = 'translateY(-2px)';
        });
    });
    
    // Ripple effect for buttons
    document.querySelectorAll('.btn').forEach(button => {
        button.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                background: rgba(255, 255, 255, 0.3);
                border-radius: 50%;
                transform: scale(0);
                animation: ripple 0.6s ease-out;
                pointer-events: none;
            `;
            
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);
            
            setTimeout(() => ripple.remove(), 600);
        });
    });
    
    // Add ripple animation CSS
    const rippleStyle = document.createElement('style');
    rippleStyle.textContent = `
        @keyframes ripple {
            to {
                transform: scale(2);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(rippleStyle);
}

// Form Enhancements
function initializeFormEnhancements() {
    // Floating labels
    document.querySelectorAll('.form-input').forEach(input => {
        const wrapper = document.createElement('div');
        wrapper.className = 'form-input-wrapper';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        
        if (input.placeholder) {
            const label = document.createElement('label');
            label.textContent = input.placeholder;
            label.className = 'floating-label';
            wrapper.appendChild(label);
            input.placeholder = '';
            
            // Handle focus/blur events
            input.addEventListener('focus', () => {
                label.classList.add('active');
            });
            
            input.addEventListener('blur', () => {
                if (!input.value) {
                    label.classList.remove('active');
                }
            });
            
            // Check initial value
            if (input.value) {
                label.classList.add('active');
            }
        }
    });
    
    // Add floating label styles
    const labelStyle = document.createElement('style');
    labelStyle.textContent = `
        .form-input-wrapper {
            position: relative;
        }
        
        .floating-label {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: var(--bg-primary);
            padding: 0 0.5rem;
            color: var(--text-tertiary);
            transition: all 0.3s ease;
            pointer-events: none;
            font-size: 1rem;
        }
        
        .floating-label.active {
            top: 0;
            font-size: 0.8rem;
            color: var(--primary-green);
            font-weight: 600;
        }
        
        .form-input:focus + .floating-label {
            color: var(--primary-green);
        }
    `;
    document.head.appendChild(labelStyle);
    
    // Form validation feedback (exclude comment forms)
    document.querySelectorAll('form:not(.comment-form)').forEach(form => {
        form.addEventListener('submit', function(e) {
            const inputs = this.querySelectorAll('.form-input[required]');
            let isValid = true;
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    showFieldError(input, 'This field is required');
                    isValid = false;
                } else {
                    clearFieldError(input);
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    });
}

function showFieldError(input, message) {
    clearFieldError(input);
    
    input.style.borderColor = 'var(--secondary-red)';
    
    const error = document.createElement('div');
    error.className = 'field-error';
    error.textContent = message;
    error.style.cssText = `
        color: var(--secondary-red);
        font-size: 0.8rem;
        margin-top: 0.5rem;
        animation: slideDown 0.3s ease-out;
    `;
    
    input.parentNode.appendChild(error);
}

function clearFieldError(input) {
    input.style.borderColor = '';
    const error = input.parentNode.querySelector('.field-error');
    if (error) {
        error.remove();
    }
}

// Notification System
function initializeNotifications() {
    window.showNotification = function(message, type = 'info', duration = 5000) {
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
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => notification.remove(), 300);
        }, duration);
        
        // Manual close
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => notification.remove(), 300);
        });
    };
    
    // Add notification styles
    const notificationStyle = document.createElement('style');
    notificationStyle.textContent = `
        .notification-content {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .notification-close {
            background: none;
            border: none;
            color: var(--text-tertiary);
            cursor: pointer;
            padding: 0.25rem;
            margin-left: 1rem;
        }
        
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
    `;
    document.head.appendChild(notificationStyle);
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

// Theme System
function initializeTheme() {
    // Theme toggle functionality
    const themeToggle = document.createElement('button');
    themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
    themeToggle.className = 'theme-toggle';
    themeToggle.style.cssText = `
        position: fixed;
        bottom: 80px;
        right: 20px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: var(--bg-primary);
        border: 2px solid var(--gray-200);
        color: var(--text-primary);
        cursor: pointer;
        box-shadow: var(--shadow-lg);
        transition: all 0.3s ease;
        z-index: 1000;
    `;
    
    document.body.appendChild(themeToggle);
    
    themeToggle.addEventListener('click', function() {
        document.body.classList.toggle('dark-theme');
        const isDark = document.body.classList.contains('dark-theme');
        this.innerHTML = `<i class="fas fa-${isDark ? 'sun' : 'moon'}"></i>`;
        
        // Save preference
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
    });
    
    // Load saved theme
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-theme');
        themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
    }
}

// Utility Functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Export functions for global use
window.SchoolPlatform = {
    showNotification,
    debounce,
    throttle
};

// Also make showNotification available globally for backward compatibility
window.showNotification = showNotification;