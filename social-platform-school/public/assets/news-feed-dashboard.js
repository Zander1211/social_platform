// News Feed Dashboard JavaScript for School Platform

document.addEventListener('DOMContentLoaded', function() {
    initializeNewsFeed();
});

function initializeNewsFeed() {
    initializePostInteractions();
    initializeInfiniteScroll();
    initializePostComposer();
    initializeFilters();
    initializeSearch();
    
    console.log('News Feed Dashboard initialized');
}

// Post Interactions
function initializePostInteractions() {
    // Like functionality
    document.addEventListener('click', function(e) {
        if (e.target.closest('.like-btn')) {
            handleLike(e.target.closest('.like-btn'));
        }
        
        if (e.target.closest('.share-btn')) {
            handleShare(e.target.closest('.share-btn'));
        }
        
        if (e.target.closest('.comment-btn')) {
            handleComment(e.target.closest('.comment-btn'));
        }
    });
}

function handleLike(button) {
    const postId = button.dataset.postId;
    const icon = button.querySelector('i');
    const count = button.querySelector('.count');
    
    // Optimistic UI update
    const isLiked = button.classList.contains('liked');
    button.classList.toggle('liked');
    
    if (isLiked) {
        icon.className = 'far fa-heart';
        count.textContent = parseInt(count.textContent) - 1;
        button.style.color = 'var(--text-secondary)';
    } else {
        icon.className = 'fas fa-heart';
        count.textContent = parseInt(count.textContent) + 1;
        button.style.color = 'var(--secondary-red)';
        
        // Heart animation
        icon.style.animation = 'heartBeat 0.6s ease-out';
        setTimeout(() => icon.style.animation = '', 600);
    }
    
    // Send to server using the correct endpoint
    const formData = new FormData();
    formData.append('action', 'react');
    formData.append('post_id', postId);
    formData.append('type', 'like');
    
    fetch('index.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    }).then(response => response.json())
    .then(data => {
        if (!data.ok) {
            // Revert on error
            button.classList.toggle('liked');
            if (isLiked) {
                icon.className = 'fas fa-heart';
                count.textContent = parseInt(count.textContent) + 1;
                button.style.color = 'var(--secondary-red)';
            } else {
                icon.className = 'far fa-heart';
                count.textContent = parseInt(count.textContent) - 1;
                button.style.color = 'var(--text-secondary)';
            }
            if (window.SchoolPlatform && window.SchoolPlatform.showNotification) {
                window.SchoolPlatform.showNotification('Failed to update like', 'error');
            }
        }
    }).catch(error => {
        // Revert on error
        button.classList.toggle('liked');
        if (isLiked) {
            icon.className = 'fas fa-heart';
            count.textContent = parseInt(count.textContent) + 1;
            button.style.color = 'var(--secondary-red)';
        } else {
            icon.className = 'far fa-heart';
            count.textContent = parseInt(count.textContent) - 1;
            button.style.color = 'var(--text-secondary)';
        }
        if (window.SchoolPlatform && window.SchoolPlatform.showNotification) {
            window.SchoolPlatform.showNotification('Failed to update like', 'error');
        }
    });
}

function handleShare(button) {
    const postId = button.dataset.postId;
    const postTitle = button.dataset.postTitle || 'Check out this post';
    
    if (navigator.share) {
        navigator.share({
            title: postTitle,
            url: window.location.href + '#post-' + postId
        });
    } else {
        // Fallback: copy to clipboard
        const url = window.location.href + '#post-' + postId;
        navigator.clipboard.writeText(url).then(() => {
            window.SchoolPlatform.showNotification('Link copied to clipboard!', 'success');
        });
    }
    
    // Update share count
    const count = button.querySelector('.count');
    count.textContent = parseInt(count.textContent) + 1;
    
    // Animation
    button.style.transform = 'scale(1.1)';
    setTimeout(() => button.style.transform = '', 200);
}

function handleComment(button) {
    const postId = button.dataset.postId;
    const commentsSection = document.querySelector(`#comments-${postId}`);
    
    if (commentsSection) {
        commentsSection.style.display = commentsSection.style.display === 'none' ? 'block' : 'none';
        
        if (commentsSection.style.display === 'block') {
            const commentInput = commentsSection.querySelector('.comment-input');
            if (commentInput) {
                commentInput.focus();
            }
        }
    }
}

// Infinite Scroll
function initializeInfiniteScroll() {
    let loading = false;
    let page = 1;
    
    const loadMorePosts = window.SchoolPlatform.throttle(async () => {
        if (loading) return;
        
        loading = true;
        showLoadingSpinner();
        
        try {
            const response = await fetch(`/api/posts?page=${page + 1}`);
            const data = await response.json();
            
            if (data.posts && data.posts.length > 0) {
                appendPosts(data.posts);
                page++;
            } else {
                showEndMessage();
            }
        } catch (error) {
            window.SchoolPlatform.showNotification('Failed to load more posts', 'error');
        } finally {
            loading = false;
            hideLoadingSpinner();
        }
    }, 1000);
    
    window.addEventListener('scroll', () => {
        if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 1000) {
            loadMorePosts();
        }
    });
}

function showLoadingSpinner() {
    const spinner = document.createElement('div');
    spinner.id = 'loading-spinner';
    spinner.innerHTML = `
        <div class="spinner"></div>
        <p>Loading more posts...</p>
    `;
    spinner.style.cssText = `
        text-align: center;
        padding: 2rem;
        color: var(--text-secondary);
    `;
    
    document.querySelector('.content-body').appendChild(spinner);
}

function hideLoadingSpinner() {
    const spinner = document.getElementById('loading-spinner');
    if (spinner) {
        spinner.remove();
    }
}

function appendPosts(posts) {
    const container = document.querySelector('.posts-container') || document.querySelector('.content-body');
    
    posts.forEach(post => {
        const postElement = createPostElement(post);
        container.appendChild(postElement);
        
        // Animate in
        setTimeout(() => {
            postElement.classList.add('animate-in');
        }, 100);
    });
}

function createPostElement(post) {
    const postDiv = document.createElement('div');
    postDiv.className = 'post';
    postDiv.innerHTML = `
        <div class="post-header">
            <div class="post-author">
                <div class="post-avatar">
                    ${post.author.name.charAt(0).toUpperCase()}
                </div>
                <div class="post-author-info">
                    <h4>${post.author.name}</h4>
                    <div class="post-meta">${formatDate(post.created_at)}</div>
                </div>
            </div>
        </div>
        <div class="post-content">
            <h3 class="post-title">${post.title}</h3>
            <p class="post-text">${post.content}</p>
        </div>
        <div class="post-actions">
            <button class="post-action like-btn" data-post-id="${post.id}">
                <i class="far fa-heart"></i>
                <span class="count">${post.likes_count || 0}</span>
            </button>
            <button class="post-action comment-btn" data-post-id="${post.id}">
                <i class="far fa-comment"></i>
                <span class="count">${post.comments_count || 0}</span>
            </button>
            <button class="post-action share-btn" data-post-id="${post.id}" data-post-title="${post.title}">
                <i class="fas fa-share"></i>
                <span class="count">${post.shares_count || 0}</span>
            </button>
        </div>
    `;
    
    return postDiv;
}

function showEndMessage() {
    const message = document.createElement('div');
    message.className = 'end-message';
    message.innerHTML = `
        <i class="fas fa-check-circle"></i>
        <p>You've reached the end of the feed!</p>
    `;
    message.style.cssText = `
        text-align: center;
        padding: 2rem;
        color: var(--text-secondary);
        opacity: 0;
        animation: fadeIn 0.5s ease-out forwards;
    `;
    
    document.querySelector('.content-body').appendChild(message);
}

// Post Composer
function initializePostComposer() {
    const modal = createPostComposerModal();
    document.body.appendChild(modal);
    
    // Form submission
    const form = modal.querySelector('#post-composer-form');
    form.addEventListener('submit', handlePostSubmission);
    
    // File upload preview
    const fileInput = modal.querySelector('#post-attachment');
    fileInput.addEventListener('change', handleFilePreview);
    
    // Character counter
    const textarea = modal.querySelector('#post-content');
    const counter = modal.querySelector('.character-counter');
    textarea.addEventListener('input', () => {
        const remaining = 500 - textarea.value.length;
        counter.textContent = `${remaining} characters remaining`;
        counter.style.color = remaining < 50 ? 'var(--secondary-red)' : 'var(--text-secondary)';
    });
}

function createPostComposerModal() {
    const modal = document.createElement('div');
    modal.id = 'postComposerModal';
    modal.className = 'modal';
    modal.style.display = 'none';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Create New Post</h3>
                <button class="modal-close" onclick="closePostComposer()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="post-composer-form">
                    <div class="form-group">
                        <label class="form-label">Title</label>
                        <input type="text" id="post-title" class="form-input" placeholder="Enter post title" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Content</label>
                        <textarea id="post-content" class="form-input form-textarea" placeholder="What's on your mind?" required></textarea>
                        <div class="character-counter">500 characters remaining</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Attachment (Optional)</label>
                        <input type="file" id="post-attachment" class="form-input" accept="image/*,video/*,.pdf,.doc,.docx">
                        <div class="file-info">Supported: Images, Videos, Documents (Max 10MB)</div>
                        <div id="file-preview"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select id="post-category" class="form-input form-select">
                            <option value="general">General</option>
                            <option value="announcement">Announcement</option>
                            <option value="academic">Academic</option>
                            <option value="event">Event</option>
                            <option value="social">Social</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closePostComposer()">Cancel</button>
                <button type="submit" form="post-composer-form" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i>
                    Publish Post
                </button>
            </div>
        </div>
    `;
    
    return modal;
}

function handlePostSubmission(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('title', document.getElementById('post-title').value);
    formData.append('content', document.getElementById('post-content').value);
    formData.append('category', document.getElementById('post-category').value);
    
    const fileInput = document.getElementById('post-attachment');
    if (fileInput.files[0]) {
        formData.append('attachment', fileInput.files[0]);
    }
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publishing...';
    
    fetch('/api/posts', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.SchoolPlatform.showNotification('Post published successfully!', 'success');
            closePostComposer();
            // Optionally refresh the feed or prepend the new post
            location.reload();
        } else {
            throw new Error(data.message || 'Failed to publish post');
        }
    })
    .catch(error => {
        window.SchoolPlatform.showNotification(error.message, 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Publish Post';
    });
}

function handleFilePreview(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('file-preview');
    const fileInfo = e.target.parentNode.querySelector('.file-info');
    
    preview.innerHTML = '';
    
    if (file) {
        if (file.size > 10 * 1024 * 1024) {
            fileInfo.textContent = 'File size must be less than 10MB';
            fileInfo.style.color = 'var(--secondary-red)';
            e.target.value = '';
            return;
        }
        
        fileInfo.textContent = `Selected: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
        fileInfo.style.color = 'var(--secondary-green)';
        
        if (file.type.startsWith('image/')) {
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.style.cssText = `
                max-width: 100%;
                max-height: 200px;
                border-radius: var(--radius-lg);
                margin-top: 1rem;
            `;
            preview.appendChild(img);
        }
    }
}

// Filters
function initializeFilters() {
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const filter = this.dataset.filter;
            applyFilter(filter);
            
            // Update active state
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
        });
    });
}

function applyFilter(filter) {
    const posts = document.querySelectorAll('.post');
    
    posts.forEach(post => {
        const category = post.dataset.category || 'general';
        
        if (filter === 'all' || category === filter) {
            post.style.display = 'block';
            post.classList.add('fade-in');
        } else {
            post.style.display = 'none';
        }
    });
    
    window.SchoolPlatform.showNotification(`Showing ${filter} posts`, 'info', 2000);
}

// Search
function initializeSearch() {
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        const debouncedSearch = window.SchoolPlatform.debounce(performSearch, 300);
        searchInput.addEventListener('input', debouncedSearch);
    }
}

function performSearch(e) {
    const query = e.target.value.toLowerCase().trim();
    const posts = document.querySelectorAll('.post');
    
    if (query === '') {
        posts.forEach(post => {
            post.style.display = 'block';
        });
        return;
    }
    
    posts.forEach(post => {
        const title = post.querySelector('.post-title')?.textContent.toLowerCase() || '';
        const content = post.querySelector('.post-text')?.textContent.toLowerCase() || '';
        const author = post.querySelector('.post-author-info h4')?.textContent.toLowerCase() || '';
        
        if (title.includes(query) || content.includes(query) || author.includes(query)) {
            post.style.display = 'block';
            highlightSearchTerms(post, query);
        } else {
            post.style.display = 'none';
        }
    });
}

function highlightSearchTerms(post, query) {
    const elements = post.querySelectorAll('.post-title, .post-text');
    
    elements.forEach(element => {
        const text = element.textContent;
        const regex = new RegExp(`(${query})`, 'gi');
        const highlightedText = text.replace(regex, '<mark>$1</mark>');
        element.innerHTML = highlightedText;
    });
}

// Utility Functions
function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);
    
    if (minutes < 1) return 'Just now';
    if (minutes < 60) return `${minutes}m ago`;
    if (hours < 24) return `${hours}h ago`;
    if (days < 7) return `${days}d ago`;
    
    return date.toLocaleDateString();
}

// Add heart beat animation
const heartBeatStyle = document.createElement('style');
heartBeatStyle.textContent = `
    @keyframes heartBeat {
        0% { transform: scale(1); }
        25% { transform: scale(1.3); }
        50% { transform: scale(1); }
        75% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    
    .character-counter {
        font-size: 0.8rem;
        text-align: right;
        margin-top: 0.5rem;
        color: var(--text-secondary);
    }
    
    .file-info {
        font-size: 0.8rem;
        color: var(--text-tertiary);
        margin-top: 0.5rem;
    }
    
    mark {
        background: var(--primary-green-lighter);
        color: var(--primary-green-dark);
        padding: 0.1em 0.2em;
        border-radius: 0.2em;
    }
`;
document.head.appendChild(heartBeatStyle);