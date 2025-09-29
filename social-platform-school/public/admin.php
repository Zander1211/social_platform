<?php
session_start();
require_once '../config/database.php';
require_once __DIR__ . '/../src/Controller/AdminController.php';

// $pdo is created in config/database.php
$adminController = new AdminController($pdo);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Include the attachment processing function from index.php
function generateAttachmentHtml($attachmentPath) {
    $ext = strtolower(pathinfo($attachmentPath, PATHINFO_EXTENSION));
    $filename = basename($attachmentPath);
    
    // Check if file actually exists
    $fullPath = __DIR__ . '/' . $attachmentPath;
    if (!file_exists($fullPath)) {
        return '<div style="padding:8px;background:#fef3cd;border:1px solid #fbbf24;border-radius:6px;color:#92400e;margin:4px 0;">
                    <i class="fa fa-exclamation-triangle" style="margin-right:4px;"></i>
                    <strong>Missing:</strong> ' . htmlspecialchars($filename) . '
                </div>';
    }
    
    $output = '<div class="attachment-item" style="margin:8px 0;">';
    
    if (in_array($ext, ['png','jpg','jpeg','gif','webp'])) {
        // Image - smaller size for admin view
        $output .= '<img src="'.htmlspecialchars($attachmentPath).'" alt="'.htmlspecialchars($filename).'" 
                   style="max-width:200px;max-height:150px;object-fit:cover;border-radius:6px;border:1px solid #ddd;cursor:pointer;" 
                   onclick="window.open(this.src, \'_blank\')" 
                   title="Click to view full size">';
    } else {
        // Other files
        $output .= '<div style="padding:8px;background:#f3f4f6;border-radius:6px;border:1px solid #d1d5db;">
                <i class="fas fa-file" style="margin-right:8px;color:#6b7280;"></i>
                <span style="font-size:14px;">'.htmlspecialchars($filename).'</span>
                <a href="'.htmlspecialchars($attachmentPath).'" target="_blank" style="margin-left:8px;color:#059669;text-decoration:none;">
                   <i class="fas fa-external-link-alt"></i>
                </a>
              </div>';
    }
    
    $output .= '</div>';
    return $output;
}

function processPostContent($content) {
    // First, process attachment references BEFORE html escaping
    $processed = preg_replace_callback(
        '/\[Attachment: <a href="([^"]+)">([^<]+)<\/a>\]/',
        function($matches) {
            $attachmentPath = $matches[1];
            return '{{ATTACHMENT:' . $attachmentPath . '}}';
        },
        $content
    );
    
    // Now escape the HTML content
    $escaped = htmlspecialchars($processed);
    
    // Finally, replace the attachment placeholders with actual HTML
    $final = preg_replace_callback(
        '/\{\{ATTACHMENT:([^}]+)\}\}/',
        function($matches) {
            return generateAttachmentHtml($matches[1]);
        },
        $escaped
    );
    
    return $final;
}

$posts = $adminController->getAllPosts();
$events = $adminController->getAllEvents();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_post'])) {
        $adminController->deletePost($_POST['post_id']);
        header('Location: admin.php');
        exit();
    }

    if (isset($_POST['delete_event'])) {
        $adminController->deleteEvent($_POST['event_id']);
        header('Location: admin.php');
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css">
    <title>Admin Dashboard</title>
    <style>
        /* Delete Confirmation Modal */
        .delete-modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .delete-modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease-out;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .delete-modal-header {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            padding: 20px;
            border-radius: 12px 12px 0 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .delete-modal-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }
        
        .delete-modal-body {
            padding: 25px;
            text-align: center;
        }
        
        .delete-modal-body p {
            margin: 0 0 15px 0;
            font-size: 16px;
            color: #374151;
            line-height: 1.5;
        }
        
        .post-preview {
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            margin: 15px 0;
            text-align: left;
        }
        
        .post-preview h4 {
            margin: 0 0 8px 0;
            font-size: 14px;
            font-weight: 600;
            color: #111827;
        }
        
        .post-preview-content {
            font-size: 13px;
            color: #6b7280;
            max-height: 100px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .delete-modal-footer {
            padding: 20px 25px;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            background: #f9fafb;
            border-radius: 0 0 12px 12px;
        }
        
        .btn-delete {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-delete:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        
        .btn-cancel {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-cancel:hover {
            background: #e5e7eb;
            border-color: #9ca3af;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../src/View/header.php'; ?>
    <main class="container">
        <div class="layout">
            <div>
                <section class="card">
                    <h2>Manage Posts</h2>
                    <?php foreach ($posts as $post): ?>
                        <div class="post card">
                            <h3><?php echo htmlspecialchars($post['title'] ?? 'Untitled Post'); ?></h3>
                            <div class="post-content"><?php echo processPostContent($post['content']); ?></div>
                            <small style="color:#6b7280;margin-top:8px;display:block;">
                                By: <?php echo htmlspecialchars($post['user_name'] ?? 'Unknown User'); ?> | 
                                <?php echo date('M j, Y \a\t g:i A', strtotime($post['created_at'])); ?>
                            </small>
                            <button class="btn" type="button" 
                                    onclick="showDeleteModal(<?php echo $post['id']; ?>, '<?php echo addslashes(htmlspecialchars($post['title'] ?? 'Untitled Post')); ?>')">
                                <i class="fas fa-trash"></i> Delete Post
                            </button>
                            
                            <!-- Hidden form for actual deletion -->
                            <form id="deleteForm<?php echo $post['id']; ?>" method="POST" style="display:none;">
                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                <input type="hidden" name="delete_post" value="1">
                            </form>
                        </div>
                    <?php endforeach; ?>
                </section>
            </div>

            <!-- <aside>
                <section class="card">
                    <h2>Manage Events</h2>
                    <ul class="events-list">
                        <?php foreach ($events as $event): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($event['title']); ?></strong>
                                <div class="kv"><?php echo htmlspecialchars($event['date']); ?></div>
                                <form method="POST" style="margin-top:6px;">
                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                    <button class="btn secondary" type="submit" name="delete_event">Delete</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            </aside> -->
        </div>
    </main>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="delete-modal">
        <div class="delete-modal-content">
            <div class="delete-modal-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Confirm Delete</h3>
            </div>
            <div class="delete-modal-body">
                <p>Are you sure you want to delete this post? This action cannot be undone.</p>
                <div class="post-preview">
                    <h4 id="deletePostTitle">Post Title</h4>
                    <div class="post-preview-content" id="deletePostContent">Post content preview...</div>
                </div>
            </div>
            <div class="delete-modal-footer">
                <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button type="button" class="btn-delete" onclick="confirmDelete()">Delete Post</button>
            </div>
        </div>
    </div>
    
    <script>
        let currentDeletePostId = null;
        
        function showDeleteModal(postId, postTitle) {
            currentDeletePostId = postId;
            document.getElementById('deletePostTitle').textContent = postTitle || 'Untitled Post';
            
            // Get post content from the page
            const postElement = document.querySelector(`#deleteForm${postId}`).closest('.post');
            const contentElement = postElement.querySelector('.post-content');
            const contentText = contentElement ? contentElement.textContent.trim() : '';
            
            // Truncate content for preview
            const maxLength = 150;
            const truncated = contentText.length > maxLength ? 
                contentText.substring(0, maxLength) + '...' : contentText;
            
            document.getElementById('deletePostContent').textContent = truncated || 'No content';
            document.getElementById('deleteModal').style.display = 'block';
            
            // Prevent body scroll
            document.body.style.overflow = 'hidden';
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            currentDeletePostId = null;
        }
        
        function confirmDelete() {
            if (currentDeletePostId) {
                document.getElementById(`deleteForm${currentDeletePostId}`).submit();
            }
        }
        
        // Close modal when clicking outside
        document.getElementById('deleteModal').onclick = function(event) {
            if (event.target === this) {
                closeDeleteModal();
            }
        }
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && document.getElementById('deleteModal').style.display === 'block') {
                closeDeleteModal();
            }
        });
    </script>
    
    <?php require_once __DIR__ . '/../src/View/footer.php'; ?>
</body>
</html>