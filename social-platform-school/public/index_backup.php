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

// quick action endpoints (form posts from the home page)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'create_post') {
        // require login
        if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        // handle optional file upload
        if (!empty($_FILES['attachment']['name'])) {
            $up = $_FILES['attachment'];
            $targetDir = __DIR__ . '/uploads';
            if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
            $ext = pathinfo($up['name'], PATHINFO_EXTENSION);
            $safe = uniqid('att_') . '.' . $ext;
            $dest = $targetDir . '/' . $safe;
            if (move_uploaded_file($up['tmp_name'], $dest)) {
                // append link to content
                $content .= "\n\n[Attachment: <a href=\"uploads/{$safe}\">{$up['name']}</a>]";
            }
        }
        $pc = new PostController($pdo);
        $pc->createPost([
            'user_id' => $_SESSION['user_id'],
            'title' => $title,
            'content' => $content,
        ]);
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
        // If AJAX request, return JSON with author name
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
            header('Content-Type: application/json');
            $author = null;
            try {
                $s = $pdo->prepare('SELECT name FROM users WHERE id = :id');
                $s->execute([':id' => $_SESSION['user_id']]);
                $r = $s->fetch(PDO::FETCH_ASSOC);
                $author = $r['name'] ?? null;
            } catch (Exception $e) {
                $author = null;
            }
            echo json_encode(['status' => $result['status'] ?? 'error', 'author' => $author]);
            exit();
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
    
    <!-- Post Composer Modal (for admins) -->
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
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
    
    <main class="container">
    <?php 
      $scope = $_GET['scope'] ?? 'all';
      $uc = new UserController($pdo);
      $showUserResults = ($scope === 'users');
      
      // If scope is 'users', show people search results
      if ($showUserResults) {
        // If there's a search query, use it; otherwise show all users
        $searchQuery = $q && trim($q) !== '' ? $q : '';
        $users = $searchQuery ? $uc->searchUsers($searchQuery, 50) : $uc->getAllUsers(50);
        
        echo '<section class="card people-section">';
        echo '<div class="section-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">';
        echo '<h2><i class="fa fa-users"></i> People</h2>';
        if ($searchQuery) {
          echo '<div class="search-info">Search results for "<strong>'.htmlspecialchars($searchQuery).'</strong>"</div>';
        } else {
          echo '<div class="search-info">All users</div>';
        }
        echo '</div>';
        
        if (!$users || empty($users)) {
          echo '<div class="no-results" style="text-align:center;padding:40px;color:#6b7280;">';
          echo '<i class="fa fa-users" style="font-size:48px;margin-bottom:16px;opacity:0.5;"></i>';
          echo '<div style="font-size:18px;margin-bottom:8px;">No people found</div>';
          if ($searchQuery) {
            echo '<div>Try searching with different keywords or <a href="index.php?scope=users">browse all users</a></div>';
          } else {
            echo '<div>No users are registered yet.</div>';
          }
          echo '</div>';
        } else {
          echo '<div class="people-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">';
          foreach ($users as $u) {
            $uid = (int)$u['id'];
            $avatar = null;
            $glob = glob(__DIR__ . '/uploads/avatar_' . $uid . '.*');
            if ($glob) $avatar = 'uploads/' . basename($glob[0]);
            
            echo '<div class="person-card card" style="padding:16px;transition:transform 0.2s ease,box-shadow 0.2s ease;" onmouseover="this.style.transform=\'translateY(-2px)\';this.style.boxShadow=\'0 8px 25px rgba(0,0,0,0.1)\';" onmouseout="this.style.transform=\'translateY(0)\';this.style.boxShadow=\'\';">';
            echo '<div style="display:flex;gap:12px;align-items:center;margin-bottom:12px;">';
            echo '<div class="avatar" style="width:50px;height:50px;border-radius:50%;overflow:hidden;background:#e5e7eb;flex:0 0 auto;position:relative;">';
            if ($avatar) {
              echo '<img src="'.$avatar.'" style="width:100%;height:100%;object-fit:cover" alt="'.htmlspecialchars($u['name'] ?? '').'\'s avatar">';
            } else {
              echo '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:20px;"><i class="fa fa-user"></i></div>';
            }
            echo '</div>';
            echo '<div style="flex:1;min-width:0;">';
            echo '<div class="name" style="font-weight:600;font-size:16px;margin-bottom:4px;"><a href="profile.php?id='.$uid.'" style="text-decoration:none;color:#1f2937;">'.htmlspecialchars($u['name'] ?? '').'</a></div>';
            echo '<div class="role" style="font-size:12px;color:#6b7280;text-transform:uppercase;font-weight:500;margin-bottom:2px;">'.htmlspecialchars($u['role'] ?? 'Student').'</div>';
            echo '<div class="email" style="font-size:13px;color:#9ca3af;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">'.htmlspecialchars($u['email'] ?? '').'</div>';
            echo '</div>';
            echo '</div>';
            
            // Action buttons
            echo '<div class="person-actions" style="display:flex;gap:8px;">';
            echo '<a href="profile.php?id='.$uid.'" class="btn secondary" style="flex:1;font-size:13px;padding:8px 12px;text-align:center;text-decoration:none;"><i class="fa fa-user"></i> View Profile</a>';
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $uid) {
              echo '<a href="chat.php" class="btn" style="flex:1;font-size:13px;padding:8px 12px;text-align:center;text-decoration:none;"><i class="fa fa-comments"></i> Message</a>';
            }
            echo '</div>';
            echo '</div>';
          }
          echo '</div>';
          
          // Show count
          echo '<div class="results-count" style="margin-top:16px;text-align:center;color:#6b7280;font-size:14px;">';
          echo 'Showing '.count($users).' '.($searchQuery ? 'search results' : 'users');
          echo '</div>';
        }
        echo '</section>';
      }

      $filter = $_GET['filter'] ?? null;
      $posts = $postController->getAllPosts($q, $filter);
      echo '<section class="card"><h2>News Feed</h2>';
      if (!$posts) {
        echo '<div class="kv">No posts found.</div>';
      } else {
        foreach ($posts as $p) {
          echo '<article class="post" style="padding:12px;border-top:1px solid #eee">';
          echo '<div class="kv" style="color:#666">By '.htmlspecialchars($p['author'] ?? '').' &middot; '.htmlspecialchars($p['created_at'] ?? '').'</div>';
          echo '<h3 style="margin:6px 0 4px 0">'.htmlspecialchars($p['title'] ?? '').'</h3>';
          $rawContent = $p['content'] ?? '';
          
          // Find attachments and remove any raw attachment anchors from the body so links don't show up visually
          $attachments = [];
          if (preg_match_all('%uploads/([^\s\"\'\/]+\.[^\s\"\'\/]+)%i', $rawContent, $m)) {
              $attachments = $m[0]; // Full paths including 'uploads/'
          }
          
          // Strip anchor tags that link to uploads and any [Attachment: ...] text markers
          $displayContent = preg_replace('%<a[^>]+href=[\"\']?uploads/[^\"\'>]+[^>]*>.*?</a>%is', '', $rawContent);
          $displayContent = preg_replace('%\[Attachment:[^\]]*\]%i', '', $displayContent);
          $displayContent = trim($displayContent);
          
          echo '<div>'.nl2br(htmlspecialchars($displayContent)).'</div>';
          
          // Display attachments properly
          if (!empty($attachments)) {
            foreach ($attachments as $attachmentPath) {
              $ext = strtolower(pathinfo($attachmentPath, PATHINFO_EXTENSION));
              echo '<div style="margin:8px 0">';
              if (in_array($ext, ['png','jpg','jpeg','gif','webp'])) {
                echo '<img src="'.htmlspecialchars($attachmentPath).'" alt="attachment" style="max-width:100%;height:auto;border:1px solid #eee;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1)">';
              } elseif (in_array($ext, ['mp4','webm','ogg','ogv'])) {
                $type = ($ext === 'ogv') ? 'ogg' : $ext;
                echo '<video controls style="max-width:100%;border:1px solid #eee;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1)">'
                   . '<source src="'.htmlspecialchars($attachmentPath).'" type="video/'.$type.'">'
                   . 'Your browser does not support the video tag.' . '</video>';
              } elseif (in_array($ext, ['mp3','wav','ogg'])) {
                echo '<audio controls style="width:100%;border-radius:4px">'
                   . '<source src="'.htmlspecialchars($attachmentPath).'">'
                   . 'Your browser does not support the audio tag.' . '</audio>';
              } else {
                $filename = basename($attachmentPath);
                echo '<div style="padding:12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;display:flex;align-items:center;gap:8px">';
                echo '<i class="fa fa-file" style="color:#6b7280"></i>';
                echo '<span style="flex:1;color:#374151">'.htmlspecialchars($filename).'</span>';
                echo '<a class="btn secondary" href="'.htmlspecialchars($attachmentPath).'" target="_blank" rel="noopener" style="font-size:12px;padding:4px 8px">Download</a>';
                echo '</div>';
              }
              echo '</div>';
            }
          }
          if (!empty($p['event'])) {
            echo '<div class="kv" style="margin-top:6px">Event: '.htmlspecialchars($p['event']['title'] ?? '').' on '.htmlspecialchars($p['event']['event_date'] ?? '').'</div>';
          }

          // Reactions and comments UI
          $pid = (int)($p['id'] ?? 0);
          $rx = $reactionController->getReactions($pid);
          $rxTotal = is_array($rx) ? count($rx) : 0;
          $rxCounts = ['like'=>0,'haha'=>0,'heart'=>0,'sad'=>0,'angry'=>0];
          if (is_array($rx)) { foreach ($rx as $r0) { if (!empty($r0['type']) && isset($rxCounts[$r0['type']])) { $rxCounts[$r0['type']]++; } } }

          $comments = $commentController->getComments($pid);
          $cTotal = is_array($comments) ? count($comments) : 0;

          echo '<div class="post-actions" style="margin-top:8px;display:flex;gap:12px;align-items:center">';
          echo '<button class="btn small reaction-trigger" data-post-id="'.$pid.'">Like</button>';
          // emoji picker for this post
          echo '<div class="emoji-picker" data-post-id="'.$pid.'" style="display:none">';
          echo '<span class="emoji" data-type="like" title="Like">👍</span> ';
          echo '<span class="emoji" data-type="heart" title="Love">❤️</span> ';
          echo '<span class="emoji" data-type="haha" title="Haha">😂</span> ';
          echo '<span class="emoji" data-type="sad" title="Sad">😢</span> ';
          echo '<span class="emoji" data-type="angry" title="Angry">😡</span>';
          echo '</div>';
          // reaction total (click to view list)
          echo '<div class="kv reaction-total" id="reactions-'.$pid.'" data-post-id="'.$pid.'" style="cursor:pointer">';
          echo $rxTotal ? ($rxTotal.' reactions') : 'No reactions';
          echo '</div>';
          // comments count and quick button
          echo '<div class="kv comments-count" data-post-id="'.$pid.'" data-count="'.$cTotal.'">'.$cTotal.' comments</div>';
          echo '<button class="btn small secondary comment-btn" data-post-id="'.$pid.'">Comment</button>';
          echo '</div>';

          // comments list
          echo '<div class="comments" style="margin-top:8px">';
          if ($comments) {
            foreach ($comments as $c) {
              $author = htmlspecialchars($c['author'] ?? '');
              $text = nl2br(htmlspecialchars($c['content'] ?? ''));
              echo '<div class="comment" style="margin:6px 0"><strong>'.$author.':</strong> '.$text.'</div>';
            }
          }
          echo '</div>';
          // comment form (AJAX handled in assets/app.js)
          echo '<form class="comment-form" data-post-id="'.$pid.'" style="margin-top:8px">';
          echo '<textarea class="input" name="content" placeholder="Write a comment..." rows="2" required></textarea>';
          echo '<div style="margin-top:6px"><button class="btn small" type="submit">Post Comment</button></div>';
          echo '</form>';

          echo '</article>';
        }
      }
      echo '</section>';
    ?>
    </main>
    <?php require_once __DIR__ . '/../src/View/footer.php'; ?>
    
    <script>
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
            peopleBtn.style.background = 'linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%)';
            peopleBtn.style.boxShadow = '0 0 0 2px rgba(59, 130, 246, 0.3)';
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
    </script>
    
    <!-- Post Composer Modal Styles and Scripts -->
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
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
        color: #3b82f6;
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
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: white;
        border: none;
    }
    
    .form-actions .btn.primary:hover {
        background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
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
            }
        }
    }
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closePostComposer();
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