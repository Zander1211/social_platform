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
$users = $adminController->getAllUsers();
$warnings = $adminController->getAllWarnings();
$blocks = $adminController->getAllBlocks();
$suspensions = $adminController->getAllSuspensions();
$reports = $adminController->getAllReports();
$moderationStats = $adminController->getModerationStats();

// Get comments for each post
$postComments = [];
foreach ($posts as $post) {
    $postComments[$post['id']] = $adminController->getAllComments(50); // Get all comments
    // Filter comments for this specific post
    $postComments[$post['id']] = array_filter($postComments[$post['id']], function($comment) use ($post) {
        return $comment['post_id'] == $post['id'];
    });
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_post'])) {
        $adminController->deletePost($_POST['post_id']);
        header('Location: admin.php');
        exit();
    }

    if (isset($_POST['delete_comment'])) {
        $adminController->deleteCommentAdmin($_POST['comment_id']);
        header('Location: admin.php');
        exit();
    }

    if (isset($_POST['delete_event'])) {
        $adminController->deleteEvent($_POST['event_id']);
        header('Location: admin.php');
        exit();
    }
    
    // Warning system actions
    if (isset($_POST['issue_warning'])) {
        $adminController->issueWarning($_POST['user_id'], $_POST['reason'], $_POST['warning_level']);
        header('Location: admin.php#users');
        exit();
    }
    
    if (isset($_POST['dismiss_warning'])) {
        $adminController->dismissWarning($_POST['warning_id']);
        header('Location: admin.php#warnings');
        exit();
    }
    
    // Suspension actions
    if (isset($_POST['suspend_user'])) {
        $until = $_POST['suspension_type'] === 'temporary' && !empty($_POST['suspended_until']) ? $_POST['suspended_until'] : null;
        $adminController->suspendUser($_POST['user_id'], $_SESSION['user_id'], $_POST['reason'], $_POST['suspension_type'], $until);
        header('Location: admin.php#users');
        exit();
    }
    
    if (isset($_POST['unsuspend_user'])) {
        $adminController->unsuspendUser($_POST['user_id']);
        header('Location: admin.php#suspensions');
        exit();
    }
    
    // Report actions
    if (isset($_POST['update_report'])) {
        $adminController->updateReportStatus($_POST['report_id'], $_POST['status'], $_POST['admin_notes'] ?? '');
        header('Location: admin.php#reports');
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
        /* Admin Tabs */
        .admin-tabs {
            display: flex;
            border-bottom: 2px solid #e5e7eb;
            margin-bottom: 20px;
            background: white;
            border-radius: 8px 8px 0 0;
            overflow: hidden;
        }
        
        .admin-tab {
            padding: 15px 25px;
            background: #f9fafb;
            border: none;
            cursor: pointer;
            font-weight: 600;
            color: #6b7280;
            transition: all 0.3s ease;
            border-right: 1px solid #e5e7eb;
        }
        
        .admin-tab:hover {
            background: #f3f4f6;
            color: #374151;
        }
        
        .admin-tab.active {
            background: #3b82f6;
            color: white;
            border-bottom: 2px solid #1d4ed8;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(59, 130, 246, 0.2);
        }
        
        .stat-card.warning { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .stat-card.danger { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .stat-card.success { background: linear-gradient(135deg, #10b981, #059669); }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        /* User Actions */
        .user-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn-warning {
            background: #f59e0b;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-warning:hover {
            background: #d97706;
            transform: translateY(-1px);
        }
        
        .btn-suspend {
            background: #ef4444;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-suspend:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }
        
        .btn-unsuspend {
            background: #10b981;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-unsuspend:hover {
            background: #059669;
            transform: translateY(-1px);
        }
        
        /* Tables */
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .admin-table th {
            background: #f9fafb;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .admin-table td {
            padding: 12px;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .admin-table tr:hover {
            background: #f9fafb;
        }
        
        /* Warning Levels */
        .warning-level {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .warning-level.low { background: #dbeafe; color: #1d4ed8; }
        .warning-level.medium { background: #fef3cd; color: #92400e; }
        .warning-level.high { background: #fee2e2; color: #dc2626; }
        .warning-level.severe { background: #fecaca; color: #991b1b; }
        
        /* Status badges */
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-badge.pending { background: #fef3cd; color: #92400e; }
        .status-badge.reviewed { background: #dbeafe; color: #1d4ed8; }
        .status-badge.resolved { background: #dcfce7; color: #166534; }
        .status-badge.dismissed { background: #f3f4f6; color: #6b7280; }
        
        /* Modals */
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
            border-radius: 12px 12px 0 0;
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #374151;
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .form-group textarea {
            height: 80px;
            resize: vertical;
        }
        
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
                <!-- Admin Dashboard Header -->
                <section class="card">
                    <h1>Admin Dashboard</h1>
                    
                    <!-- Statistics Overview -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo count($users); ?></div>
                            <div class="stat-label">Total Users</div>
                        </div>
                        <div class="stat-card warning">
                            <div class="stat-number"><?php echo $moderationStats['active_warnings']; ?></div>
                            <div class="stat-label">Active Warnings</div>
                        </div>
                        <div class="stat-card danger">
                            <div class="stat-number"><?php echo $moderationStats['active_suspensions']; ?></div>
                            <div class="stat-label">Active Suspensions</div>
                        </div>
                        <div class="stat-card success">
                            <div class="stat-number"><?php echo $moderationStats['pending_reports']; ?></div>
                            <div class="stat-label">Pending Reports</div>
                        </div>
                    </div>
                    
                    <!-- Admin Tabs -->
                    <div class="admin-tabs">
                        <button class="admin-tab active" onclick="showTab('users')">Users</button>
                        <button class="admin-tab" onclick="showTab('warnings')">Warnings</button>
                        <button class="admin-tab" onclick="showTab('suspensions')">Suspensions</button>
                        <button class="admin-tab" onclick="showTab('reports')">Reports</button>
                        <button class="admin-tab" onclick="showTab('posts')">Posts</button>
                        <button class="admin-tab" onclick="showTab('events')">Events</button>
                    </div>
                    
                    <!-- Users Tab -->
                    <div id="users" class="tab-content active">
                        <h2>User Management</h2>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['role']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="user-actions">
                                            <button class="btn-warning" onclick="showWarningModal(<?php echo $user['id']; ?>, '<?php echo addslashes($user['name']); ?>')">
                                                Warn
                                            </button>
                                            <button class="btn-suspend" onclick="showSuspensionModal(<?php echo $user['id']; ?>, '<?php echo addslashes($user['name']); ?>')">
                                                Suspend
                                            </button>
                                            <?php if ($adminController->isUserSuspended($user['id'])): ?>
                                            <button class="btn-unsuspend" onclick="unsuspendUser(<?php echo $user['id']; ?>)">
                                                Unsuspend
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Warnings Tab -->
                    <div id="warnings" class="tab-content">
                        <h2>Active Warnings</h2>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Reason</th>
                                    <th>Level</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($warnings as $warning): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($warning['user_name']); ?></td>
                                    <td><?php echo htmlspecialchars($warning['reason']); ?></td>
                                    <td><span class="warning-level <?php echo $warning['warning_level']; ?>"><?php echo $warning['warning_level']; ?></span></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($warning['created_at'])); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="warning_id" value="<?php echo $warning['id']; ?>">
                                            <button type="submit" name="dismiss_warning" class="btn-unsuspend">Dismiss</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Suspensions Tab -->
                    <div id="suspensions" class="tab-content">
                        <h2>Active Suspensions</h2>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Reason</th>
                                    <th>Type</th>
                                    <th>Until</th>
                                    <th>Suspended By</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($suspensions as $suspension): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($suspension['user_name']); ?></td>
                                    <td><?php echo htmlspecialchars($suspension['reason']); ?></td>
                                    <td><?php echo $suspension['suspension_type']; ?></td>
                                    <td><?php echo $suspension['suspended_until'] ? date('M j, Y g:i A', strtotime($suspension['suspended_until'])) : 'Permanent'; ?></td>
                                    <td><?php echo htmlspecialchars($suspension['suspended_by_name']); ?></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($suspension['created_at'])); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $suspension['user_id']; ?>">
                                            <button type="submit" name="unsuspend_user" class="btn-unsuspend">Unsuspend</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Reports Tab -->
                    <div id="reports" class="tab-content">
                        <h2>User Reports</h2>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Reporter</th>
                                    <th>Reported User</th>
                                    <th>Type</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reports as $report): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($report['reporter_name'] ?? 'Unknown User'); ?></td>
                                    <td><?php echo htmlspecialchars($report['reported_name'] ?? 'Unknown User'); ?></td>
                                    <td><?php echo htmlspecialchars($report['report_type'] ?? 'user'); ?></td>
                                    <td><?php echo htmlspecialchars($report['reason'] ?? 'No reason provided'); ?></td>
                                    <td><span class="status-badge <?php echo $report['status'] ?? 'pending'; ?>"><?php echo $report['status'] ?? 'pending'; ?></span></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($report['created_at'])); ?></td>
                                    <td>
                                        <button class="btn-warning" onclick="showReportModal(<?php echo $report['id']; ?>, '<?php echo addslashes($report['reason'] ?? ''); ?>')">
                                            Review
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Posts Tab -->
                    <div id="posts" class="tab-content">
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
                            
                            <!-- Comments Section -->
                            <?php if (!empty($postComments[$post['id']])): ?>
                                <div class="post-comments-section" style="margin-top: 20px; border-top: 1px solid #e5e7eb; padding-top: 15px;">
                                    <h4 style="color: #374151; margin-bottom: 10px;">Comments (<?php echo count($postComments[$post['id']]); ?>)</h4>
                                    <?php foreach ($postComments[$post['id']] as $comment): ?>
                                        <div class="comment-item" style="background: #f9fafb; padding: 12px; border-radius: 6px; margin-bottom: 8px; border-left: 3px solid #10b981;">
                                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                                <div style="flex: 1;">
                                                    <div style="font-size: 13px; color: #6b7280; margin-bottom: 5px;">
                                                        <strong><?php echo htmlspecialchars($comment['author'] ?? 'Unknown User'); ?></strong> • 
                                                        <?php echo date('M j, Y \a\t g:i A', strtotime($comment['created_at'])); ?>
                                                    </div>
                                                    <div style="color: #374151; font-size: 14px; line-height: 1.4;">
                                                        <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                                    </div>
                                                </div>
                                                <button type="button" class="btn-delete-comment" 
                                                        onclick="showDeleteCommentModal(<?php echo $comment['id']; ?>, '<?php echo addslashes(htmlspecialchars(substr($comment['content'], 0, 50))); ?>...')"
                                                        style="background: #ef4444; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-size: 11px; cursor: pointer; margin-left: 10px;">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div style="margin-top: 15px; padding: 10px; background: #f3f4f6; border-radius: 6px; color: #6b7280; font-style: italic; text-align: center;">
                                    No comments on this post
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    </div>
                    
                    <!-- Events Tab -->
                    <div id="events" class="tab-content">
                        <h2>Manage Events</h2>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>Date</th>
                                    <th>Location</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($events as $event): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($event['title']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($event['description'], 0, 100)) . (strlen($event['description']) > 100 ? '...' : ''); ?></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($event['date'])); ?></td>
                                    <td><?php echo htmlspecialchars($event['location'] ?? 'Not specified'); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                            <button type="submit" name="delete_event" class="btn-suspend">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </main>
    
    <!-- Warning Modal -->
    <div id="warningModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Issue Warning</h3>
                <button type="button" onclick="closeModal('warningModal')" style="float: right; background: none; border: none; font-size: 20px;">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" id="warning_user_id" name="user_id">
                    <div class="form-group">
                        <label>User: <span id="warning_user_name"></span></label>
                    </div>
                    <div class="form-group">
                        <label for="warning_level">Warning Level:</label>
                        <select name="warning_level" id="warning_level" required>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="severe">Severe</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="warning_reason">Reason:</label>
                        <textarea name="reason" id="warning_reason" required placeholder="Explain why this warning is being issued..."></textarea>
                    </div>
                    <div style="text-align: right;">
                        <button type="button" onclick="closeModal('warningModal')" class="btn-cancel">Cancel</button>
                        <button type="submit" name="issue_warning" class="btn-warning">Issue Warning</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Suspension Modal -->
    <div id="suspensionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Suspend User</h3>
                <button type="button" onclick="closeModal('suspensionModal')" style="float: right; background: none; border: none; font-size: 20px;">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" id="suspension_user_id" name="user_id">
                    <div class="form-group">
                        <label>User: <span id="suspension_user_name"></span></label>
                    </div>
                    <div class="form-group">
                        <label for="suspension_type">Suspension Type:</label>
                        <select name="suspension_type" id="suspension_type" required onchange="toggleSuspensionDate()">
                            <option value="temporary">Temporary</option>
                            <option value="permanent">Permanent</option>
                        </select>
                    </div>
                    <div class="form-group" id="suspension_date_group">
                        <label for="suspended_until">Suspended Until:</label>
                        <input type="datetime-local" name="suspended_until" id="suspended_until">
                    </div>
                    <div class="form-group">
                        <label for="suspension_reason">Reason:</label>
                        <textarea name="reason" id="suspension_reason" required placeholder="Explain why this user is being suspended..."></textarea>
                    </div>
                    <div style="text-align: right;">
                        <button type="button" onclick="closeModal('suspensionModal')" class="btn-cancel">Cancel</button>
                        <button type="submit" name="suspend_user" class="btn-suspend">Suspend User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Report Review Modal -->
    <div id="reportModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Review Report</h3>
                <button type="button" onclick="closeModal('reportModal')" style="float: right; background: none; border: none; font-size: 20px;">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" id="report_id" name="report_id">
                    <div class="form-group">
                        <label>Report Reason: <span id="report_reason"></span></label>
                    </div>
                    <div class="form-group">
                        <label for="report_status">Status:</label>
                        <select name="status" id="report_status" required>
                            <option value="pending">Pending</option>
                            <option value="reviewed">Reviewed</option>
                            <option value="resolved">Resolved</option>
                            <option value="dismissed">Dismissed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="admin_notes">Admin Notes:</label>
                        <textarea name="admin_notes" id="admin_notes" placeholder="Add any notes about your decision..."></textarea>
                    </div>
                    <div style="text-align: right;">
                        <button type="button" onclick="closeModal('reportModal')" class="btn-cancel">Cancel</button>
                        <button type="submit" name="update_report" class="btn-warning">Update Report</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
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

    <!-- Comment Delete Confirmation Modal -->
    <div id="deleteCommentModal" class="delete-modal">
        <div class="delete-modal-content">
            <div class="delete-modal-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Delete Comment</h3>
            </div>
            <div class="delete-modal-body">
                <p>Are you sure you want to delete this comment? This action cannot be undone.</p>
                <div class="post-preview">
                    <div class="post-preview-content" id="deleteCommentContent">Comment content preview...</div>
                </div>
            </div>
            <div class="delete-modal-footer">
                <button type="button" class="btn-cancel" onclick="closeDeleteCommentModal()">Cancel</button>
                <button type="button" class="btn-delete" onclick="confirmDeleteComment()">Delete Comment</button>
            </div>
        </div>
    </div>

    <!-- Hidden form for comment deletion -->
    <form id="deleteCommentForm" method="POST" style="display:none;">
        <input type="hidden" name="comment_id" id="deleteCommentId" value="">
        <input type="hidden" name="delete_comment" value="1">
    </form>

    <script>
        let currentDeletePostId = null;
        let currentDeleteCommentId = null;
        
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
        
        function showDeleteCommentModal(commentId, commentPreview) {
            currentDeleteCommentId = commentId;
            document.getElementById('deleteCommentContent').textContent = commentPreview || 'Comment content...';
            document.getElementById('deleteCommentId').value = commentId;
            document.getElementById('deleteCommentModal').style.display = 'block';
            
            // Prevent body scroll
            document.body.style.overflow = 'hidden';
        }
        
        function closeDeleteCommentModal() {
            document.getElementById('deleteCommentModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            currentDeleteCommentId = null;
        }
        
        function confirmDeleteComment() {
            if (currentDeleteCommentId) {
                document.getElementById('deleteCommentForm').submit();
            }
        }
        
        // Close modals when clicking outside
        document.getElementById('deleteModal').onclick = function(event) {
            if (event.target === this) {
                closeDeleteModal();
            }
        }
        
        document.getElementById('deleteCommentModal').onclick = function(event) {
            if (event.target === this) {
                closeDeleteCommentModal();
            }
        }
        
        // Close modals with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                if (document.getElementById('deleteModal').style.display === 'block') {
                    closeDeleteModal();
                }
                if (document.getElementById('deleteCommentModal').style.display === 'block') {
                    closeDeleteCommentModal();
                }
            }
        });
        
        // Tab functionality
        function showTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.admin-tab');
            tabs.forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
            
            // Update URL hash
            window.location.hash = tabName;
        }
        
        // Initialize tab from URL hash
        document.addEventListener('DOMContentLoaded', function() {
            // Force hide all modals on page load to fix auto-show issue
            const allModals = ['warningModal', 'suspensionModal', 'reportModal', 'deleteModal', 'deleteCommentModal'];
            allModals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.style.display = 'none';
                }
            });
            
            const hash = window.location.hash.substring(1);
            if (hash && document.getElementById(hash)) {
                showTab(hash);
                // Find the corresponding tab button and activate it
                const tabs = document.querySelectorAll('.admin-tab');
                tabs.forEach(tab => {
                    if (tab.textContent.toLowerCase() === hash) {
                        tab.classList.add('active');
                    } else {
                        tab.classList.remove('active');
                    }
                });
            }
        });
        
        // Warning Modal Functions
        function showWarningModal(userId, userName) {
            document.getElementById('warning_user_id').value = userId;
            document.getElementById('warning_user_name').textContent = userName;
            document.getElementById('warningModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        // Suspension Modal Functions
        function showSuspensionModal(userId, userName) {
            document.getElementById('suspension_user_id').value = userId;
            document.getElementById('suspension_user_name').textContent = userName;
            document.getElementById('suspensionModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        function toggleSuspensionDate() {
            const suspensionType = document.getElementById('suspension_type').value;
            const dateGroup = document.getElementById('suspension_date_group');
            if (suspensionType === 'permanent') {
                dateGroup.style.display = 'none';
                document.getElementById('suspended_until').required = false;
            } else {
                dateGroup.style.display = 'block';
                document.getElementById('suspended_until').required = true;
            }
        }
        
        // Report Modal Functions
        function showReportModal(reportId, reason) {
            document.getElementById('report_id').value = reportId;
            document.getElementById('report_reason').textContent = reason;
            document.getElementById('reportModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        // Generic Modal Functions
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        // Quick Unsuspend Function
        function unsuspendUser(userId) {
            if (confirm('Are you sure you want to unsuspend this user?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const userIdInput = document.createElement('input');
                userIdInput.type = 'hidden';
                userIdInput.name = 'user_id';
                userIdInput.value = userId;
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'unsuspend_user';
                actionInput.value = '1';
                
                form.appendChild(userIdInput);
                form.appendChild(actionInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const modals = ['warningModal', 'suspensionModal', 'reportModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (event.target === modal) {
                    closeModal(modalId);
                }
            });
        }
        
        // Close modals with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modals = ['warningModal', 'suspensionModal', 'reportModal'];
                modals.forEach(modalId => {
                    const modal = document.getElementById(modalId);
                    if (modal.style.display === 'block') {
                        closeModal(modalId);
                    }
                });
            }
        });
    </script>
    
    <?php require_once __DIR__ . '/../src/View/footer.php'; ?>
</body>
</html>