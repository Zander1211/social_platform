<?php
// Global footer for templates and public pages
?>

    </div> <!-- .main-layout.container -->

    <footer class="site-footer container">
      <div class="kv">&copy; <?php echo date('Y'); ?> School Platform</div>
    </footer>

  <script src="assets/app.js"></script>
  <script src="assets/news-feed-dashboard.js"></script>
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
  </script>
  </body>
</html>
