<?php
require_once '../config/app.php';
require_once '../config/database.php';
require_once '../src/Controller/AuthController.php';
require_once '../src/Controller/UserController.php';
require_once '../src/Controller/PostController.php';
require_once '../src/Controller/CommentController.php';
require_once '../src/Controller/ReactionController.php';
require_once '../src/Controller/ChatController.php';
require_once '../src/Controller/EventController.php';
require_once '../src/Controller/AdminController.php';

// Start session
session_start();

// $pdo is created in config/database.php

// Improved attachment parsing function
function parseAttachments($content) {
    $attachments = [];
    
    // Pattern 1: Look for href="uploads/filename" in anchor tags
    if (preg_match_all('/href=["\']uploads\/([^"\'\/><]+\.[^"\'\/><]+)["\']/i', $content, $matches)) {
        foreach ($matches[1] as $filename) {
            $attachments[] = 'uploads/' . $filename;
        }
    }
    
    // Pattern 2: Look for direct uploads/filename references
    if (preg_match_all('/uploads\/([^\s"\'\/><]+\.[^\s"\'\/><]+)/i', $content, $matches)) {
        $attachments = array_merge($attachments, $matches[0]);
    }
    
    // Remove duplicates and return
    return array_unique($attachments);
}

// Function to render attachments properly
function renderAttachment($attachmentPath) {
    $ext = strtolower(pathinfo($attachmentPath, PATHINFO_EXTENSION));
    $filename = basename($attachmentPath);
    
    // Check if file actually exists
    $fullPath = __DIR__ . '/' . $attachmentPath;
    if (!file_exists($fullPath)) {
        // Try to create a placeholder or provide a better error message
        $uploadsDir = __DIR__ . '/uploads';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }
        
        // Create a placeholder file if it's an image
        if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
            // Create a simple placeholder image (1x1 transparent PNG)
            $placeholderImage = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
            file_put_contents($fullPath, $placeholderImage);
        }
        
        // If still doesn't exist, show a user-friendly message with option to remove
        if (!file_exists($fullPath)) {
            return '<div style="padding:12px;background:#fef3cd;border:1px solid #fbbf24;border-radius:8px;color:#92400e;margin:8px 0;display:flex;align-items:center;justify-content:space-between;">
                        <div>
                            <i class="fa fa-exclamation-triangle" style="margin-right:8px;"></i>
                            <strong>Missing Attachment:</strong> ' . htmlspecialchars($filename) . '
                            <br><small>This file may have been moved or deleted.</small>
                        </div>
                        <button onclick="removeAttachmentReference(this)" class="btn secondary" style="font-size:11px;padding:4px 8px;margin-left:12px;">
                            <i class="fa fa-times"></i> Remove
                        </button>
                    </div>';
        }
    }
    
    $output = '<div class="attachment-item">';
    
    if (in_array($ext, ['png','jpg','jpeg','gif','webp'])) {
        // Image
        $output .= '<img src="'.htmlspecialchars($attachmentPath).'" alt="'.htmlspecialchars($filename).'" 
                   class="attachment-image" 
                   onclick="window.open(this.src, \'_blank\')" 
                   title="Click to view full size">';
    } elseif (in_array($ext, ['mp4','webm','ogg','ogv'])) {
        // Video
        $type = ($ext === 'ogv') ? 'ogg' : $ext;
        $output .= '<video controls class="attachment-video">
                <source src="'.htmlspecialchars($attachmentPath).'" type="video/'.$type.'">
                Your browser does not support the video tag.
              </video>';
    } elseif (in_array($ext, ['mp3','wav','ogg'])) {
        // Audio
        $output .= '<audio controls class="attachment-audio">
                <source src="'.htmlspecialchars($attachmentPath).'">
                Your browser does not support the audio tag.
              </audio>';
    } else {
        // Other files
        $output .= '<div class="attachment-file">
                <i class="fas fa-file"></i>
                <span class="file-name">'.htmlspecialchars($filename).'</span>
                <a class="btn btn-secondary btn-sm" href="'.htmlspecialchars($attachmentPath).'" target="_blank" rel="noopener">
                   <i class="fas fa-download"></i> Download
                </a>
              </div>';
    }
    
    $output .= '</div>';
    return $output;
}

// quick action endpoints (form posts from the home page)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'create_post') {
        // require login
        if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
        
        // Debug logging
        error_log('Create post attempt by user: ' . $_SESSION['user_id'] . ' (' . ($_SESSION['role'] ?? 'no role') . ')');
        
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        
        // Validate input
        if (empty($title) && empty($content)) {
            $_SESSION['error'] = 'Please provide a title or content for your post.';
            header('Location: index.php'); exit();
        }
        
        // handle optional file upload
        if (!empty($_FILES['attachment']['name'])) {
            $up = $_FILES['attachment'];
            $targetDir = __DIR__ . '/uploads';
            if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
            
            // Validate file type and size
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/webm', 'audio/mp3', 'audio/wav', 'application/pdf'];
            $maxSize = 10 * 1024 * 1024; // 10MB
            
            if (!in_array($up['type'], $allowedTypes)) {
                $_SESSION['error'] = 'Invalid file type. Please upload images, videos, audio, or PDF files only.';
                header('Location: index.php'); exit();
            }
            
            if ($up['size'] > $maxSize) {
                $_SESSION['error'] = 'File size too large. Maximum size is 10MB.';
                header('Location: index.php'); exit();
            }
            
            $ext = pathinfo($up['name'], PATHINFO_EXTENSION);
            $safe = uniqid('att_') . '.' . $ext;
            $dest = $targetDir . '/' . $safe;
            
            if (move_uploaded_file($up['tmp_name'], $dest)) {
                // Set proper file permissions
                chmod($dest, 0644);
                
                // Verify the file was actually created and is readable
                if (file_exists($dest) && is_readable($dest)) {
                    // append link to content
                    $content .= "\n\n[Attachment: <a href=\"uploads/{$safe}\">{$up['name']}</a>]";
                    $_SESSION['success'] = 'Post created with attachment successfully!';
                } else {
                    $_SESSION['error'] = 'File upload completed but file verification failed.';
                }
            } else {
                $_SESSION['error'] = 'Failed to upload attachment. Please check file permissions and try again.';
            }
        }
        
        try {
            $pc = new PostController($pdo);
            $postId = $pc->createPost([
                'user_id' => $_SESSION['user_id'],
                'title' => $title,
                'content' => $content,
            ]);
            
            if ($postId) {
                $_SESSION['success'] = 'Post created successfully!';
                error_log('Post created successfully with ID: ' . $postId);
            } else {
                $_SESSION['error'] = 'Failed to create post. Please try again.';
                error_log('Post creation failed - no ID returned');
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error creating post: ' . $e->getMessage();
            error_log('Post creation error: ' . $e->getMessage());
        }
        
        header('Location: index.php'); exit();
    }

    if (isset($_POST['action']) && $_POST['action'] === 'add_comment') {
        if (!isset($_SESSION['user_id'])) {
            // If AJAX, return 401
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) { header('HTTP/1.1 401 Unauthorized'); exit(); }
            header('Location: login.php'); exit();
        }
        $cc = new CommentController($pdo);
        $result = $cc->addComment($_POST['post_id'], $_SESSION['user_id'], trim($_POST['content'] ?? ''));
        // Always redirect back to the page to refresh and show the new comment
        if ($result['status'] === 'success') {
            $_SESSION['success'] = 'Comment added successfully!';
        } else {
            $_SESSION['error'] = 'Failed to add comment. Please try again.';
        }
        header('Location: index.php'); exit();
    }

    if (isset($_POST['action']) && $_POST['action'] === 'react') {
        if (!isset($_SESSION['user_id'])) {
            header('HTTP/1.1 401 Unauthorized'); exit();
        }
        $rc = new ReactionController($pdo);
        $ok = $rc->addReaction($_SESSION['user_id'], $_POST['post_id'], $_POST['type']);
        // if this was an AJAX request, return JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => (bool)$ok]);
            exit();
        }
        header('Location: index.php'); exit();
    }
}


// Routing logic
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

if ($requestUri === '/login' && $requestMethod === 'POST') {
    $authController = new AuthController($pdo);
    $authController->login($_POST['email'] ?? null, $_POST['password'] ?? null);
} elseif ($requestUri === '/register' && $requestMethod === 'POST') {
    $authController = new AuthController($pdo);
    $authController->register($_POST);
} elseif (strpos($requestUri, '/posts') === 0) {
    $postController = new PostController($pdo);
    // Handle post-related requests
} elseif (strpos($requestUri, '/comments') === 0) {
    $commentController = new CommentController($pdo);
    // Handle comment-related requests
} elseif (strpos($requestUri, '/chat') === 0) {
    $chatController = new ChatController($pdo);
    // Handle chat-related requests
} elseif (strpos($requestUri, '/events') === 0) {
    $eventController = new EventController($pdo);
    // Handle event-related requests
} elseif (strpos($requestUri, '/admin') === 0) {
    $adminController = new AdminController($pdo);
    // Handle admin-related requests
} else {
    // Default to home page or 404
    // If not authenticated, require login first
    if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
    
    // Prepare feed (support search q) and an AJAX endpoint for reactions
    $postController = new PostController($pdo);
    $commentController = new CommentController($pdo);
    $reactionController = new ReactionController($pdo);
    $q = $_GET['q'] ?? null;
    // AJAX endpoint to fetch reactions for a post
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'reactions' && isset($_GET['post_id'])) {
    header('Content-Type: application/json');
    echo json_encode($reactionController->getReactions((int)$_GET['post_id']));
    exit();
    }
    // Render a consistent header then include the template content
    ?>
    <?php require_once __DIR__ . '/../src/View/header.php'; ?>
    
    <!-- Display success/error messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div style="background:#d4edda;color:#155724;padding:12px;margin:16px;border:1px solid #c3e6cb;border-radius:8px;">
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div style="background:#f8d7da;color:#721c24;padding:12px;margin:16px;border:1px solid #f5c6cb;border-radius:8px;">
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <!-- Post Composer Modal (for admins) -->
    <?php if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin'): ?>
    <div id="postComposerModal" class="post-composer-modal" style="display: none;">
        <div class="modal-overlay" onclick="closePostComposer()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fa fa-plus-circle"></i> Create New Post</h3>
                <button class="modal-close" onclick="closePostComposer()">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data" class="post-composer-form">
                <input type="hidden" name="action" value="create_post">
                
                <div class="form-group">
                    <label for="post-title">Post Title</label>
                    <input type="text" id="post-title" name="title" class="input" placeholder="Enter a compelling title..." required>
                </div>
                
                <div class="form-group">
                    <label for="post-content">Content</label>
                    <textarea id="post-content" name="content" class="input" rows="6" placeholder="What would you like to share with the community?" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="post-attachment">Attachment (Optional)</label>
                    <input type="file" id="post-attachment" name="attachment" class="input" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.txt">
                    <div class="file-info">Supported: Images, Videos, Audio, Documents (Max 10MB)</div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn secondary" onclick="closePostComposer()">Cancel</button>
                    <button type="submit" class="btn primary">
                        <i class="fa fa-paper-plane"></i> Publish Post
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
        <!-- Main Content Container -->
        <div class="content-container">
        <?php 
          $scope = $_GET['scope'] ?? 'all';
          $uc = new UserController($pdo);
          $showUserResults = ($scope === 'users');
          
          // Enhanced search logic: if there's a search query, also search for users
          $searchQuery = $q && trim($q) !== '' ? $q : '';
          $users = [];
          
          // If scope is 'users', show people search results
          if ($showUserResults) {
            // If there's a search query, use it; otherwise show all users
            $users = $searchQuery ? $uc->searchUsers($searchQuery, 50) : $uc->getAllUsers(50);
            
            echo '<section class="content-card people-section">';
            echo '<div class="section-header">';
            echo '<h2><i class="fas fa-users"></i> People</h2>';
            if ($searchQuery) {
              echo '<div class="search-info">Search results for "<strong>'.htmlspecialchars($searchQuery).'</strong>"</div>';
            } else {
              echo '<div class="search-info">All users</div>';
            }
            echo '</div>';
            
            if (!$users || empty($users)) {
              echo '<div class="no-results">';
              echo '<i class="fas fa-users"></i>';
              echo '<div class="no-results-title">No people found</div>';
              if ($searchQuery) {
                echo '<div class="no-results-text">Try searching with different keywords or <a href="index.php?scope=users">browse all users</a></div>';
              } else {
                echo '<div class="no-results-text">No users are registered yet.</div>';
              }
              echo '</div>';
            } else {
              echo '<div class="people-grid">';
              foreach ($users as $u) {
                $uid = (int)$u['id'];
                $avatar = null;
                $glob = glob(__DIR__ . '/uploads/avatar_' . $uid . '.*');
                if ($glob) $avatar = 'uploads/' . basename($glob[0]);
                
                echo '<div class="person-card">';
                echo '<div class="person-header">';
                echo '<div class="person-avatar">';
                if ($avatar) {
                  echo '<img src="'.$avatar.'" alt="'.htmlspecialchars($u['name'] ?? '').'\'s avatar">';
                } else {
                  echo '<i class="fas fa-user"></i>';
                }
                echo '</div>';
                echo '<div class="person-info">';
                echo '<div class="person-name"><a href="profile.php?id='.$uid.'">'.htmlspecialchars($u['name'] ?? '').'</a></div>';
                echo '<div class="person-role">'.htmlspecialchars($u['role'] ?? 'Student').'</div>';
                echo '<div class="person-email">'.htmlspecialchars($u['email'] ?? '').'</div>';
                echo '</div>';
                echo '</div>';
                
                // Action buttons
                echo '<div class="person-actions">';
                echo '<a href="profile.php?id='.$uid.'" class="btn secondary"><i class="fas fa-user"></i> View Profile</a>';
                if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $uid) {
                  echo '<a href="chat.php?user_id='.$uid.'" class="btn primary"><i class="fas fa-comments"></i> Message</a>';
                }
                echo '</div>';
                echo '</div>';
              }
              echo '</div>';
              
              // Show count
              echo '<div class="results-count">';
              echo 'Showing '.count($users).' '.($searchQuery ? 'search results' : 'users');
              echo '</div>';
            }
            echo '</section>';
          }
          
          // If there's a search query but scope is not 'users', still search for users to show as suggestions
          if ($searchQuery && !$showUserResults) {
            $users = $uc->searchUsers($searchQuery, 10); // Limit to 10 for suggestions
            
            // Show user search results as a suggestion section if users are found
            if (!empty($users)) {
              echo '<section class="content-card user-suggestions-section">';
              echo '<div class="section-header">';
              echo '<h3><i class="fas fa-users"></i> People matching "<strong>'.htmlspecialchars($searchQuery).'</strong>"</h3>';
              echo '<a href="index.php?q='.urlencode($searchQuery).'&scope=users" class="btn secondary small">View All People</a>';
              echo '</div>';
              
              echo '<div class="people-grid compact">';
              foreach ($users as $u) {
                $uid = (int)$u['id'];
                $avatar = null;
                $glob = glob(__DIR__ . '/uploads/avatar_' . $uid . '.*');
                if ($glob) $avatar = 'uploads/' . basename($glob[0]);
                
                echo '<div class="person-card compact">';
                echo '<div class="person-header">';
                echo '<div class="person-avatar small">';
                if ($avatar) {
                  echo '<img src="'.$avatar.'" alt="'.htmlspecialchars($u['name'] ?? '').'\'s avatar">';
                } else {
                  echo '<i class="fas fa-user"></i>';
                }
                echo '</div>';
                echo '<div class="person-info">';
                echo '<div class="person-name"><a href="profile.php?id='.$uid.'">'.htmlspecialchars($u['name'] ?? '').'</a></div>';
                echo '<div class="person-role">'.htmlspecialchars($u['role'] ?? 'Student').'</div>';
                echo '</div>';
                echo '<div class="person-actions compact">';
                echo '<a href="profile.php?id='.$uid.'" class="btn secondary small"><i class="fas fa-user"></i></a>';
                if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $uid) {
                  echo '<a href="chat.php?user_id='.$uid.'" class="btn primary small"><i class="fas fa-comments"></i></a>';
                }
                echo '</div>';
                echo '</div>';
                echo '</div>';
              }
              echo '</div>';
              
              echo '<div class="results-count small">';
              echo 'Showing '.count($users).' people • <a href="index.php?q='.urlencode($searchQuery).'&scope=users">View all people results</a>';
              echo '</div>';
              echo '</section>';
            }
          }

          $filter = $_GET['filter'] ?? null;
          $posts = $postController->getAllPosts($q, $filter);
          echo '<section class="content-card posts-section">';
          echo '<div class="section-header">';
          echo '<h2><i class="fas fa-newspaper"></i> News Feed</h2>';
          echo '</div>';
          
          if (!$posts) {
            echo '<div class="no-results">';
            if ($searchQuery && !empty($users)) {
              echo '<i class="fas fa-search"></i>';
              echo '<div class="no-results-title">No posts found</div>';
              echo '<div class="no-results-text">No posts found for "<strong>'.htmlspecialchars($searchQuery).'</strong>", but we found people with that name above.</div>';
            } else if ($searchQuery) {
              echo '<i class="fas fa-search"></i>';
              echo '<div class="no-results-title">No results found</div>';
              echo '<div class="no-results-text">No posts or people found for "<strong>'.htmlspecialchars($searchQuery).'</strong>".</div>';
            } else {
              echo '<i class="fas fa-newspaper"></i>';
              echo '<div class="no-results-title">No posts found</div>';
              echo '<div class="no-results-text">Be the first to create a post!</div>';
            }
            echo '</div>';
          } else {
            echo '<div class="posts-container">';
            foreach ($posts as $p) {
              echo '<article class="post-card">';
              echo '<div class="post-header">';
              echo '<div class="post-meta">';
              $authorId = (int)($p['user_id'] ?? 0);
              $authorName = htmlspecialchars($p['author'] ?? '');
              if ($authorId > 0) {
                echo '<a href="profile.php?id='.$authorId.'" class="post-author-link">';
                echo '<span class="post-author">'.$authorName.'</span>';
                echo '</a>';
              } else {
                echo '<span class="post-author">'.$authorName.'</span>';
              }
              echo '<span class="post-date">'.htmlspecialchars($p['created_at'] ?? '').'</span>';
              echo '</div>';
              echo '</div>';
              
              echo '<div class="post-content">';
              echo '<h3 class="post-title">'.htmlspecialchars($p['title'] ?? '').'</h3>';
              
              $rawContent = $p['content'] ?? '';
              
              // Use improved attachment parsing
              $attachments = parseAttachments($rawContent);
              
              // Strip anchor tags that link to uploads and any [Attachment: ...] text markers
              $displayContent = preg_replace('%<a[^>]+href=["\']?uploads/[^"\'>]+[^>]*>.*?</a>%is', '', $rawContent);
              $displayContent = preg_replace('%\[Attachment:[^\]]*\]%i', '', $displayContent);
              $displayContent = trim($displayContent);
              
              echo '<div class="post-text">'.nl2br(htmlspecialchars($displayContent)).'</div>';
              
              // Display attachments using improved rendering
              if (!empty($attachments)) {
                echo '<div class="post-attachments">';
                foreach ($attachments as $attachmentPath) {
                  echo renderAttachment($attachmentPath);
                }
                echo '</div>';
              }
              
              if (!empty($p['event'])) {
                echo '<div class="post-event">';
                echo '<i class="fas fa-calendar"></i>';
                echo '<span>Event: '.htmlspecialchars($p['event']['title'] ?? '').' on '.htmlspecialchars($p['event']['event_date'] ?? '').'</span>';
                echo '</div>';
              }
              echo '</div>';

              // Reactions and comments UI
              $pid = (int)($p['id'] ?? 0);
              $rx = $reactionController->getReactions($pid);
              $rxTotal = is_array($rx) ? count($rx) : 0;
              $rxCounts = ['like'=>0,'haha'=>0,'heart'=>0,'sad'=>0,'angry'=>0];
              if (is_array($rx)) { foreach ($rx as $r0) { if (!empty($r0['type']) && isset($rxCounts[$r0['type']])) { $rxCounts[$r0['type']]++; } } }

              $comments = $commentController->getComments($pid);
              $cTotal = is_array($comments) ? count($comments) : 0;

              echo '<div class="post-footer">';
              echo '<div class="post-stats">';
              echo '<div class="reaction-stats" id="reactions-'.$pid.'" data-post-id="'.$pid.'">';
              echo '<i class="fas fa-heart"></i>';
              echo '<span>'.($rxTotal ? $rxTotal.' reactions' : 'No reactions').'</span>';
              echo '</div>';
              echo '<div class="comment-stats">';
              echo '<i class="fas fa-comment"></i>';
              echo '<span>'.$cTotal.' comments</span>';
              echo '</div>';
              echo '</div>';
              
              echo '<div class="post-actions">';
              echo '<button class="action-btn reaction-trigger" data-post-id="'.$pid.'">';
              echo '<i class="fas fa-thumbs-up"></i>';
              echo '<span>Like</span>';
              echo '</button>';
              echo '<button class="action-btn comment-btn" data-post-id="'.$pid.'">';
              echo '<i class="fas fa-comment"></i>';
              echo '<span>Comment</span>';
              echo '</button>';
              echo '</div>';
              
              // emoji picker for this post
              echo '<div class="emoji-picker" data-post-id="'.$pid.'" style="display:none">';
              echo '<span class="emoji" data-type="like" title="Like">👍</span>';
              echo '<span class="emoji" data-type="heart" title="Love">❤️</span>';
              echo '<span class="emoji" data-type="haha" title="Haha">😂</span>';
              echo '<span class="emoji" data-type="sad" title="Sad">😢</span>';
              echo '<span class="emoji" data-type="angry" title="Angry">😡</span>';
              echo '</div>';
              echo '</div>';

              // comments section
              echo '<div class="post-comments">';
              if ($comments) {
                echo '<div class="comments-list">';
                foreach ($comments as $c) {
                  $commentAuthorId = (int)($c['user_id'] ?? 0);
                  $commentAuthorName = htmlspecialchars($c['author'] ?? '');
                  $text = nl2br(htmlspecialchars($c['content'] ?? ''));
                  echo '<div class="comment-item">';
                  echo '<div class="comment-author">';
                  if ($commentAuthorId > 0) {
                    echo '<a href="profile.php?id='.$commentAuthorId.'" class="comment-author-link">'.$commentAuthorName.'</a>';
                  } else {
                    echo $commentAuthorName;
                  }
                  echo '</div>';
                  echo '<div class="comment-text">'.$text.'</div>';
                  echo '</div>';
                }
                echo '</div>';
              }
              
              // comment form (AJAX handled in assets/app.js)
              echo '<form class="comment-form" data-post-id="'.$pid.'">';
              echo '<div class="comment-input-group">';
              echo '<textarea class="comment-input" name="content" placeholder="Write a comment..." rows="2" required></textarea>';
              echo '<button class="comment-submit" type="submit">';
              echo '<i class="fas fa-paper-plane"></i>';
              echo '</button>';
              echo '</div>';
              echo '</form>';
              echo '</div>';

              echo '</article>';
            }
            echo '</div>';
          }
          echo '</section>';
        ?>
        </div>
    <?php require_once __DIR__ . '/../src/View/footer.php'; ?>
    
    <script>
    // Function to remove attachment reference
    function removeAttachmentReference(button) {
        if (confirm('Remove this missing attachment reference from the post?')) {
            const attachmentDiv = button.closest('div');
            if (attachmentDiv) {
                attachmentDiv.style.opacity = '0.5';
                attachmentDiv.innerHTML = '<div style="padding:8px;color:#6b7280;font-style:italic;"><i class="fa fa-check"></i> Attachment reference removed</div>';
                
                // You could add AJAX call here to update the post in the database
                // For now, it just hides the error message
                setTimeout(() => {
                    attachmentDiv.style.display = 'none';
                }, 2000);
            }
        }
    }
    
    // Enhanced People Button Functionality
    document.addEventListener('DOMContentLoaded', function() {
        const peopleBtn = document.querySelector('.people-btn');
        const searchInput = document.querySelector('input[name="q"]');
        const searchForm = document.querySelector('.search form');
        
        // Add visual feedback when People button is clicked
        if (peopleBtn) {
            peopleBtn.addEventListener('click', function(e) {
                // Add loading state
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Loading...';
                this.disabled = true;
                
                // Re-enable after a short delay (form submission will redirect anyway)
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.disabled = false;
                }, 1000);
            });
        }
        
        // Enhance search functionality for people
        if (searchForm && searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    
                    // If there's a search query, search in people by default
                    const query = this.value.trim();
                    if (query) {
                        const scopeInput = document.getElementById('search-scope');
                        if (scopeInput) {
                            scopeInput.value = 'users';
                        }
                    }
                    
                    searchForm.submit();
                }
            });
        }
        
        // Add keyboard shortcut for People button (Ctrl/Cmd + P)
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                if (peopleBtn) {
                    peopleBtn.click();
                }
            }
        });
        
        // Highlight active scope
        const urlParams = new URLSearchParams(window.location.search);
        const currentScope = urlParams.get('scope');
        if (currentScope === 'users' && peopleBtn) {
            peopleBtn.style.background = 'linear-gradient(135deg, var(--primary-green) 0%, var(--primary-green-dark) 100%)';
            peopleBtn.style.boxShadow = '0 0 0 2px rgba(16, 185, 129, 0.3)';
        }
    });
    
    // Function to toggle between All Posts and People views
    function togglePeopleView() {
        const currentUrl = new URL(window.location);
        const currentScope = currentUrl.searchParams.get('scope');
        
        if (currentScope === 'users') {
            // Switch back to all posts
            currentUrl.searchParams.delete('scope');
        } else {
            // Switch to people view
            currentUrl.searchParams.set('scope', 'users');
        }
        
        window.location.href = currentUrl.toString();
    }
    
    // Enhanced Filter Functionality
    document.addEventListener('DOMContentLoaded', function() {
        const filterButtons = document.querySelectorAll('.sidebar-filter-btn');
        
        // Add loading state when filter is clicked
        filterButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                // Add loading state
                const originalContent = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Loading...</span>';
                this.style.pointerEvents = 'none';
                
                // Re-enable after navigation (this won't actually run due to page redirect)
                setTimeout(() => {
                    this.innerHTML = originalContent;
                    this.style.pointerEvents = 'auto';
                }, 1000);
            });
        });
        
        // Add keyboard shortcuts for filters
        document.addEventListener('keydown', function(e) {
            // Only if not typing in an input field
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                return;
            }
            
            if (e.altKey) {
                switch(e.key) {
                    case '1':
                        e.preventDefault();
                        const allBtn = document.querySelector('.sidebar-filter-btn:not([href*="filter"])');
                        if (allBtn) allBtn.click();
                        break;
                    case '2':
                        e.preventDefault();
                        const weekBtn = document.querySelector('.sidebar-filter-btn[href*="filter=week"]');
                        if (weekBtn) weekBtn.click();
                        break;
                    case '3':
                        e.preventDefault();
                        const monthBtn = document.querySelector('.sidebar-filter-btn[href*="filter=month"]');
                        if (monthBtn) monthBtn.click();
                        break;
                }
            }
        });
        
        // Add filter animation on page load
        const filterSection = document.querySelector('.floating-filters');
        if (filterSection) {
            filterSection.style.opacity = '0';
            filterSection.style.transform = 'translateX(-20px)';
            
            setTimeout(() => {
                filterSection.style.transition = 'all 0.8s ease';
                filterSection.style.opacity = '1';
                filterSection.style.transform = 'translateX(0)';
            }, 200);
        }
        
        // Add floating effect on scroll
        let lastScrollTop = 0;
        const dashboard = document.querySelector('.news-feed-dashboard');
        
        window.addEventListener('scroll', function() {
            if (!dashboard) return;
            
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            if (scrollTop > lastScrollTop && scrollTop > 100) {
                // Scrolling down
                dashboard.style.transform = 'translateY(-2px) scale(0.98)';
                dashboard.style.opacity = '0.95';
            } else {
                // Scrolling up or at top
                dashboard.style.transform = 'translateY(0) scale(1)';
                dashboard.style.opacity = '1';
            }
            
            lastScrollTop = scrollTop;
        });
        
        // Enhanced hover effects for sidebar buttons
        filterButtons.forEach((button, index) => {
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(6px) scale(1.02)';
            });
            
            button.addEventListener('mouseleave', function() {
                if (this.classList.contains('active')) {
                    this.style.transform = 'translateX(2px) scale(1)';
                } else {
                    this.style.transform = 'translateX(0) scale(1)';
                }
            });
        });
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
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                // Add loading state
                const icon = this.querySelector('i');
                const originalClass = icon.className;
                icon.className = 'fas fa-spinner fa-spin';
                
                // Restore icon after delay
                setTimeout(() => {
                    icon.className = originalClass;
                }, 1000);
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
    });
    </script>
    
    <!-- Post Composer Modal Styles and Scripts -->
    <?php if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin'): ?>
    <style>
    /* Post Composer Modal Styles */
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
    
    .modal-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
    }
    
    .modal-content {
        position: relative;
        background: white;
        border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        width: 90%;
        max-width: 600px;
        max-height: 90vh;
        overflow-y: auto;
        animation: modalSlideIn 0.3s ease-out;
    }
    
    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-20px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 24px;
        border-bottom: 1px solid #e5e7eb;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-radius: 12px 12px 0 0;
    }
    
    .modal-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .modal-header h3 i {
        color: var(--primary-green);
    }
    
    .modal-close {
        background: none;
        border: none;
        font-size: 24px;
        color: #6b7280;
        cursor: pointer;
        padding: 4px;
        border-radius: 4px;
        transition: all 0.2s ease;
    }
    
    .modal-close:hover {
        background: #f3f4f6;
        color: #374151;
    }
    
    .post-composer-form {
        padding: 24px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
        color: #374151;
        font-size: 14px;
    }
    
    .form-group .input {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    
    .form-group .input:focus {
        outline: none;
        border-color: var(--primary-green);
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }
    
    .form-group textarea.input {
        resize: vertical;
        min-height: 120px;
        font-family: inherit;
    }
    
    .file-info {
        margin-top: 6px;
        font-size: 12px;
        color: #6b7280;
    }
    
    .form-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        margin-top: 24px;
        padding-top: 20px;
        border-top: 1px solid #e5e7eb;
    }
    
    .form-actions .btn {
        padding: 10px 20px;
        font-weight: 600;
        border-radius: 8px;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .form-actions .btn.primary {
        background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-green-dark) 100%);
        color: white;
        border: none;
    }
    
    .form-actions .btn.primary:hover {
        background: linear-gradient(135deg, var(--primary-green-dark) 0%, #047857 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }
    
    .form-actions .btn.secondary {
        background: #f9fafb;
        color: #374151;
        border: 1px solid #d1d5db;
    }
    
    .form-actions .btn.secondary:hover {
        background: #f3f4f6;
        border-color: #9ca3af;
    }
    
    /* Responsive adjustments */
    @media (max-width: 640px) {
        .modal-content {
            width: 95%;
            margin: 20px;
        }
        
        .modal-header {
            padding: 16px 20px;
        }
        
        .post-composer-form {
            padding: 20px;
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
    // Post Composer Modal Functions
    function openPostComposer() {
        console.log('openPostComposer called');
        const modal = document.getElementById('postComposerModal');
        console.log('Modal element:', modal);
        
        if (modal) {
            console.log('Opening modal...');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Focus on title input
            setTimeout(() => {
                const titleInput = document.getElementById('post-title');
                console.log('Title input:', titleInput);
                if (titleInput) {
                    titleInput.focus();
                }
            }, 100);
        } else {
            console.error('Modal not found! Check if user is admin and modal is rendered.');
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
            }
        }
    }
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closePostComposer();
        }
    });
    
    // Check if modal exists on page load
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('postComposerModal');
        console.log('Modal check on page load:', modal ? 'Found' : 'Not found');
        
        // Check if create post button exists
        const createBtn = document.querySelector('.create-post-btn');
        console.log('Create post button:', createBtn ? 'Found' : 'Not found');
        
        if (createBtn && !modal) {
            console.error('Create post button exists but modal is missing!');
        }
    });
    
    // Enhanced file input with preview
    document.addEventListener('DOMContentLoaded', function() {
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
    });
    </script>
    <?php endif; ?>
    
    <?php
}
?>