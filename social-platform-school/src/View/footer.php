<?php
// Global footer for templates and public pages
?>

<?php if (empty($suppressTopNav)): ?>
      </main> <!-- .content-body -->
      
      <!-- Footer -->
      <footer class="content-footer">
        <div class="footer-content">
          <div class="footer-text">&copy; <?php echo date('Y'); ?> School Platform. All rights reserved.</div>
          <div class="footer-links">
            <a href="#">Privacy Policy</a>
            <a href="#">Terms of Service</a>
            <a href="#">Help</a>
          </div>
        </div>
      </footer>
    </div> <!-- .main-content -->
  </div> <!-- .dashboard-layout -->
  <?php else: ?>
    </main> <!-- .simple-content -->
  </div> <!-- .simple-layout -->
  <?php endif; ?>

  <script src="assets/app.js"></script>
  <script src="assets/news-feed-dashboard.js"></script>
  <script src="assets/comments.js"></script>
  <script>
    // User dropdown functionality
    function toggleUserDropdown() {
      const dropdown = document.getElementById('userDropdownMenu');
      const button = document.querySelector('.user-profile-btn');
      
      if (dropdown && button) {
        dropdown.classList.toggle('show');
        button.classList.toggle('active');
      }
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
      const dropdown = document.getElementById('userDropdownMenu');
      const button = document.querySelector('.user-profile-btn');
      
      if (dropdown && button && !button.contains(e.target)) {
        dropdown.classList.remove('show');
        button.classList.remove('active');
      }
    });
  </script>
  <script>
    // expose current user id for messenger rendering of "me"
    window.__ME__ = <?php echo isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 'null'; ?>;
  </script>
  <script src="assets/messenger.js"></script>
  <script>
    // small inline helpers
    (function(){
      // polyfills or tiny UI helpers can go here
    })();
    
    // Post Composer Modal Functions (available globally for admins)
    function openPostComposer() {
        const modal = document.getElementById('postComposerModal');
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Focus on title input
            setTimeout(() => {
                const titleInput = document.getElementById('post-title');
                if (titleInput) {
                    titleInput.focus();
                }
            }, 100);
        }
    }
    
    function closePostComposer() {
        const modal = document.getElementById('postComposerModal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
            
            // Reset form
            const form = modal.querySelector('form');
            if (form) {
                form.reset();
                // Reset file info
                const fileInfo = form.querySelector('.file-info');
                if (fileInfo) {
                    fileInfo.textContent = 'Supported: Images, Videos, Audio, Documents (Max 10MB)';
                    fileInfo.style.color = '#6b7280';
                }
            }
        }
    }
    
    // Global event listeners for modal
    document.addEventListener('DOMContentLoaded', function() {
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePostComposer();
            }
        });
        
        // Enhanced file input with preview
        const fileInput = document.getElementById('post-attachment');
        if (fileInput) {
            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Check file size (10MB limit)
                    if (file.size > 10 * 1024 * 1024) {
                        alert('File size must be less than 10MB');
                        this.value = '';
                        return;
                    }
                    
                    // Update file info
                    const fileInfo = this.parentNode.querySelector('.file-info');
                    if (fileInfo) {
                        fileInfo.textContent = `Selected: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                        fileInfo.style.color = '#059669';
                    }
                }
            });
        }
        
        // Click outside modal to close
        const modal = document.getElementById('postComposerModal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closePostComposer();
                }
            });
        }
    });
    
    // Modern Sidebar Toggle Functionality
    function toggleSidebar() {
        const sidebar = document.querySelector('.modern-sidebar');
        const layout = document.querySelector('.dashboard-layout');
        const toggle = document.querySelector('.mobile-menu-toggle');
        
        if (sidebar && layout && toggle) {
            sidebar.classList.toggle('open');
            layout.classList.toggle('sidebar-open');
            
            // Update toggle icon
            const icon = toggle.querySelector('i');
            if (sidebar.classList.contains('open')) {
                icon.className = 'fas fa-times';
            } else {
                icon.className = 'fas fa-bars';
            }
        }
    }
    
    // Close sidebar when clicking overlay
    document.addEventListener('click', function(e) {
        const sidebar = document.querySelector('.modern-sidebar');
        const layout = document.querySelector('.dashboard-layout');
        const toggle = document.querySelector('.mobile-menu-toggle');
        
        if (layout && layout.classList.contains('sidebar-open')) {
            if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
                toggleSidebar();
            }
        }
    });
    
    // Enhanced sidebar animations
    document.addEventListener('DOMContentLoaded', function() {
        // Add staggered animation to nav items
        const navItems = document.querySelectorAll('.nav-item');
        navItems.forEach((item, index) => {
            item.style.animationDelay = `${(index + 1) * 0.1}s`;
        });
        
        // Add smooth scroll behavior for nav links
        const navLinks = document.querySelectorAll('.nav-item');
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                // Add loading state
                const icon = this.querySelector('i');
                if (icon) {
                    const originalClass = icon.className;
                    icon.className = 'fas fa-spinner fa-spin';
                    
                    // Restore icon after delay
                    setTimeout(() => {
                        icon.className = originalClass;
                    }, 1000);
                }
            });
        });
        
        // Add keyboard navigation
        document.addEventListener('keydown', function(e) {
            // Toggle sidebar with Escape key
            if (e.key === 'Escape') {
                const sidebar = document.querySelector('.modern-sidebar');
                if (sidebar && sidebar.classList.contains('open')) {
                    toggleSidebar();
                }
            }
            
            // Quick navigation shortcuts
            if (e.altKey) {
                switch(e.key) {
                    case '1':
                        e.preventDefault();
                        window.location.href = 'index.php';
                        break;
                    case '2':
                        e.preventDefault();
                        window.location.href = 'index.php?filter=day';
                        break;
                    case '3':
                        e.preventDefault();
                        window.location.href = 'index.php?filter=week';
                        break;
                    case '4':
                        e.preventDefault();
                        window.location.href = 'index.php?filter=month';
                        break;
                }
            }
        });
        
        // Enhanced search functionality
        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.closest('form').submit();
                }
            });
        }
        
        // Add loading states to buttons
        const buttons = document.querySelectorAll('.btn, .action-btn');
        buttons.forEach(button => {
            button.addEventListener('click', function() {
                if (this.type === 'submit' || this.tagName === 'A') {
                    this.style.opacity = '0.7';
                    this.style.pointerEvents = 'none';
                    
                    // Re-enable after delay
                    setTimeout(() => {
                        this.style.opacity = '1';
                        this.style.pointerEvents = 'auto';
                    }, 2000);
                }
            });
        });
        
        // Smooth scroll to top functionality
        let scrollToTopBtn = document.createElement('button');
        scrollToTopBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
        scrollToTopBtn.className = 'scroll-to-top';
        scrollToTopBtn.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-dark) 100%);
            color: white;
            border: none;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: var(--shadow-lg);
        `;
        
        document.body.appendChild(scrollToTopBtn);
        
        scrollToTopBtn.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                scrollToTopBtn.style.opacity = '1';
                scrollToTopBtn.style.visibility = 'visible';
            } else {
                scrollToTopBtn.style.opacity = '0';
                scrollToTopBtn.style.visibility = 'hidden';
            }
        });
    });
  </script>
  </body>
</html>
