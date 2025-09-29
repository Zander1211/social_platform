<?php
// Global Post Composer Modal - Include this on any page where admins should be able to create posts
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    return; // Don't render if not admin
}
?>

<!-- Global Post Composer Modal -->
<div id="globalPostComposerModal" class="post-composer-modal" style="display: none;">
    <div class="modal-overlay" onclick="closeGlobalPostComposer()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> Create New Post</h3>
            <button class="modal-close" onclick="closeGlobalPostComposer()">&times;</button>
        </div>
        <form method="POST" action="create_post.php" enctype="multipart/form-data" class="post-composer-form" id="globalPostForm">
            <input type="hidden" name="action" value="create_post">
            
            <div class="form-group">
                <label for="global-post-title">Post Title</label>
                <input type="text" id="global-post-title" name="title" class="form-input" placeholder="Enter a compelling title..." required>
            </div>
            
            <div class="form-group">
                <label for="global-post-content">Content</label>
                <textarea id="global-post-content" name="content" class="form-input" rows="6" placeholder="What would you like to share with the community?" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="global-post-attachment">Attachment (Optional)</label>
                <input type="file" id="global-post-attachment" name="attachment" class="form-input" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.txt">
                <div class="file-info">Supported: Images, Videos, Audio, Documents (Max 10MB)</div>
                <div id="global-file-preview" class="file-preview"></div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeGlobalPostComposer()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="globalSubmitBtn">
                    <i class="fas fa-paper-plane"></i> Publish Post
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Global Post Composer Modal Styles */
.post-composer-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.post-composer-modal .modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
}

.post-composer-modal .modal-content {
    position: relative;
    background: var(--bg-primary);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-xl);
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    animation: modalSlideIn 0.3s ease-out;
}

.post-composer-modal .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--space-lg) var(--space-xl);
    border-bottom: 1px solid var(--gray-200);
    background: linear-gradient(135deg, var(--primary-green-lighter) 0%, var(--bg-primary) 100%);
}

.post-composer-modal .modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--primary-green-dark);
    display: flex;
    align-items: center;
    gap: var(--space-sm);
}

.post-composer-modal .modal-header h3 i {
    color: var(--primary-green);
}

.post-composer-modal .modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: var(--text-tertiary);
    cursor: pointer;
    padding: var(--space-sm);
    border-radius: var(--radius-md);
    transition: all var(--transition-fast);
}

.post-composer-modal .modal-close:hover {
    background: var(--gray-100);
    color: var(--text-primary);
}

.post-composer-form {
    padding: var(--space-xl);
}

.post-composer-form .form-group {
    margin-bottom: var(--space-lg);
}

.post-composer-form .form-group label {
    display: block;
    margin-bottom: var(--space-sm);
    font-weight: 600;
    color: var(--text-primary);
    font-size: 0.9rem;
}

.post-composer-form .form-input {
    width: 100%;
    padding: var(--space-md);
    border: 2px solid var(--gray-200);
    border-radius: var(--radius-lg);
    font-size: 0.95rem;
    transition: all var(--transition-fast);
    background: var(--bg-primary);
    font-family: inherit;
}

.post-composer-form .form-input:focus {
    outline: none;
    border-color: var(--primary-green);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.post-composer-form textarea.form-input {
    resize: vertical;
    min-height: 120px;
}

.file-info {
    margin-top: var(--space-sm);
    font-size: 0.8rem;
    color: var(--text-tertiary);
}

.file-preview {
    margin-top: var(--space-md);
}

.file-preview img {
    max-width: 100%;
    max-height: 200px;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
}

.form-actions {
    display: flex;
    gap: var(--space-md);
    justify-content: flex-end;
    margin-top: var(--space-xl);
    padding-top: var(--space-lg);
    border-top: 1px solid var(--gray-200);
}

.form-actions .btn {
    padding: var(--space-md) var(--space-lg);
    font-weight: 600;
    border-radius: var(--radius-lg);
    transition: all var(--transition-fast);
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    border: none;
    cursor: pointer;
    text-decoration: none;
}

.form-actions .btn-primary {
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-green-dark) 100%);
    color: var(--text-inverse);
    box-shadow: var(--shadow-sm);
}

.form-actions .btn-primary:hover {
    background: linear-gradient(135deg, var(--primary-green-dark) 0%, #047857 100%);
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.form-actions .btn-secondary {
    background: var(--bg-primary);
    color: var(--text-secondary);
    border: 1px solid var(--gray-300);
}

.form-actions .btn-secondary:hover {
    background: var(--gray-50);
    border-color: var(--primary-green);
    color: var(--text-primary);
}

/* Responsive adjustments */
@media (max-width: 640px) {
    .post-composer-modal .modal-content {
        width: 95%;
        margin: var(--space-md);
    }
    
    .post-composer-modal .modal-header {
        padding: var(--space-md) var(--space-lg);
    }
    
    .post-composer-form {
        padding: var(--space-lg);
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
// Global Post Composer Functions
function openPostComposer() {
    console.log('openPostComposer called');
    const modal = document.getElementById('globalPostComposerModal');
    console.log('Modal element:', modal);
    
    if (modal) {
        console.log('Opening global modal...');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Focus on title input
        setTimeout(() => {
            const titleInput = document.getElementById('global-post-title');
            console.log('Title input:', titleInput);
            if (titleInput) {
                titleInput.focus();
            }
        }, 100);
    } else {
        console.error('Global modal not found! Check if user is admin and modal is rendered.');
    }
}

function closeGlobalPostComposer() {
    const modal = document.getElementById('globalPostComposerModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        
        // Reset form
        const form = modal.querySelector('form');
        if (form) {
            form.reset();
            // Clear file preview
            const preview = document.getElementById('global-file-preview');
            if (preview) {
                preview.innerHTML = '';
            }
        }
    }
}

// Enhanced file input with preview
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('global-post-attachment');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('global-file-preview');
            const fileInfo = this.parentNode.querySelector('.file-info');
            
            if (preview) {
                preview.innerHTML = '';
            }
            
            if (file) {
                // Check file size (10MB limit)
                if (file.size > 10 * 1024 * 1024) {
                    alert('File size must be less than 10MB');
                    this.value = '';
                    if (fileInfo) {
                        fileInfo.textContent = 'Supported: Images, Videos, Audio, Documents (Max 10MB)';
                        fileInfo.style.color = '';
                    }
                    return;
                }
                
                // Update file info
                if (fileInfo) {
                    fileInfo.textContent = `Selected: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                    fileInfo.style.color = 'var(--primary-green)';
                }
                
                // Show preview for images
                if (file.type.startsWith('image/') && preview) {
                    const img = document.createElement('img');
                    img.src = URL.createObjectURL(file);
                    img.onload = function() {
                        URL.revokeObjectURL(this.src);
                    };
                    preview.appendChild(img);
                }
            } else if (fileInfo) {
                fileInfo.textContent = 'Supported: Images, Videos, Audio, Documents (Max 10MB)';
                fileInfo.style.color = '';
            }
        });
    }
    
    // Form submission handling
    const form = document.getElementById('globalPostForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('globalSubmitBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publishing...';
            }
        });
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeGlobalPostComposer();
    }
});

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('globalPostComposerModal');
    if (modal && e.target === modal.querySelector('.modal-overlay')) {
        closeGlobalPostComposer();
    }
});
</script>