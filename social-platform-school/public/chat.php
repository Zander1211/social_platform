<?php
session_start();
require_once '../config/database.php';
require_once __DIR__ . '/../src/Controller/ChatController.php';

$chatController = new ChatController($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_group'])) {
        if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
        $name = trim($_POST['group_name'] ?? '');
        $members = isset($_POST['members']) && is_array($_POST['members']) ? $_POST['members'] : [];
        if ($name !== '') {
            $gid = $chatController->createGroup($name, $_SESSION['user_id']);
            if ($gid && !empty($members)) {
                foreach ($members as $mid) { $chatController->addMember($gid, (int)$mid); }
            }
        }
        header('Location: chat.php'); exit();
    }

    if (isset($_POST['send_group']) && isset($_POST['group_id'])) {
        if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
        $gid = (int)$_POST['group_id'];
        $chatController->sendMessageToGroup($gid, $_SESSION['user_id'], trim($_POST['message'] ?? ''));
        header('Location: chat.php?group='.$gid); exit();
    }
}

$onlineUsers = $chatController->getActiveUsers();
$groups = $chatController->listGroups();
?>

<?php require_once __DIR__ . '/../src/View/header.php'; ?>

<div class="chat-container">
    <!-- Chat Sidebar -->
    <div class="chat-sidebar">
        <div class="chat-header">
            <h3><i class="fas fa-comments"></i> Messages</h3>
            <div class="chat-search">
                <input type="text" class="user-search" placeholder="Search conversations..." id="chatSearch">
            </div>
        </div>
        
        <div class="chat-actions">
            <button class="btn btn-primary btn-sm" onclick="openNewGroupModal()">
                <i class="fas fa-plus"></i> New Group
            </button>
        </div>
        
        <div class="users-list">
            <?php if (empty($groups)): ?>
                <div class="no-chats">
                    <i class="fas fa-comments"></i>
                    <p>No conversations yet</p>
                    <small>Start a new group chat to begin messaging</small>
                </div>
            <?php else: ?>
                <?php foreach ($groups as $g): ?>
                    <div class="user-item <?php echo (isset($_GET['group']) && $_GET['group'] == $g['id']) ? 'active' : ''; ?>">
                        <a href="chat.php?group=<?php echo $g['id']; ?>" class="user-link">
                            <div class="user-item-avatar">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="user-item-info">
                                <div class="user-item-name"><?php echo htmlspecialchars($g['name']); ?></div>
                                <div class="user-item-role">Group Chat</div>
                            </div>
                            <div class="chat-time">
                                <?php echo date('M j', strtotime($g['created_at'])); ?>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Chat Main Area -->
    <div class="chat-main">
        <?php if (isset($_GET['group'])): ?>
            <?php 
            $gid = (int)$_GET['group']; 
            $messages = $chatController->getGroupMessages($gid); 
            $groupName = 'Group';
            foreach ($groups as $gg) {
                if($gg['id'] == $gid) {
                    $groupName = $gg['name'];
                    break;
                }
            }
            ?>
            
            <!-- Chat Header -->
            <div class="chat-main-header">
                <div class="chat-info">
                    <div class="chat-avatar">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="chat-details">
                        <h4><?php echo htmlspecialchars($groupName); ?></h4>
                        <span class="chat-status">Group Chat • <?php echo count($messages); ?> messages</span>
                    </div>
                </div>
                <div class="chat-actions">
                    <button class="btn btn-secondary btn-sm" title="Voice Call">
                        <i class="fas fa-phone"></i>
                    </button>
                    <button class="btn btn-secondary btn-sm" title="Video Call">
                        <i class="fas fa-video"></i>
                    </button>
                    <button class="btn btn-secondary btn-sm" title="Group Info">
                        <i class="fas fa-info-circle"></i>
                    </button>
                </div>
            </div>
            
            <!-- Messages Container -->
            <div class="messages-container" id="messagesContainer">
                <?php if (empty($messages)): ?>
                    <div class="no-messages">
                        <i class="fas fa-comment-dots"></i>
                        <p>No messages yet</p>
                        <small>Start the conversation by sending a message below</small>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                        <?php $isMe = isset($_SESSION['user_id']) && $_SESSION['user_id'] == ($message['sender_id'] ?? $message['sender']); ?>
                        <div class="message <?php echo $isMe ? 'message-sent' : 'message-received'; ?>">
                            <?php if (!$isMe): ?>
                                <div class="message-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                            <div class="message-content">
                                <?php if (!$isMe): ?>
                                    <div class="message-author"><?php echo htmlspecialchars($message['sender_name'] ?? 'User'); ?></div>
                                <?php endif; ?>
                                <div class="message-text"><?php echo nl2br(htmlspecialchars($message['content'])); ?></div>
                                <div class="message-time"><?php echo date('g:i A', strtotime($message['created_at'] ?? 'now')); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Message Input -->
            <div class="message-input-container">
                <form method="POST" class="message-form" id="messageForm">
                    <input type="hidden" name="group_id" value="<?php echo $gid; ?>">
                    <div class="message-input-group">
                        <textarea 
                            class="message-input" 
                            name="message" 
                            placeholder="Type a message..." 
                            rows="1"
                            required
                            id="messageInput"
                        ></textarea>
                        <button class="send-message-btn" type="submit" name="send_group">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </form>
            </div>
            
        <?php else: ?>
            <!-- Welcome Screen -->
            <div class="chat-welcome">
                <div class="welcome-content">
                    <i class="fas fa-comments"></i>
                    <h3>Welcome to Messages</h3>
                    <p>Select a conversation from the sidebar to start chatting, or create a new group to begin messaging with your classmates and teachers.</p>
                    <button class="btn btn-primary" onclick="openNewGroupModal()">
                        <i class="fas fa-plus"></i> Start New Group
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- New Group Modal -->
<div id="newGroupModal" class="modal" style="display: none;">
    <div class="modal-overlay" onclick="closeNewGroupModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-users"></i> Create New Group</h3>
            <button class="modal-close" onclick="closeNewGroupModal()">&times;</button>
        </div>
        <form method="POST" class="modal-form">
            <div class="form-group">
                <label for="group_name">Group Name</label>
                <input type="text" id="group_name" name="group_name" class="form-input" placeholder="Enter group name..." required>
            </div>
            
            <div class="form-group">
                <label>Add Members (Optional)</label>
                <div class="members-list">
                    <?php foreach ($onlineUsers as $user): ?>
                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                            <label class="member-item">
                                <input type="checkbox" name="members[]" value="<?php echo $user['id']; ?>">
                                <div class="member-info">
                                    <div class="member-avatar">
                                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                    </div>
                                    <div class="member-details">
                                        <div class="member-name"><?php echo htmlspecialchars($user['name']); ?></div>
                                        <div class="member-role"><?php echo htmlspecialchars($user['role'] ?? 'Student'); ?></div>
                                    </div>
                                </div>
                            </label>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeNewGroupModal()">Cancel</button>
                <button type="submit" name="create_group" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Group
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../src/View/footer.php'; ?>

<script>
// Auto-scroll to bottom of messages
function scrollToBottom() {
    const container = document.getElementById('messagesContainer');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
}

// Auto-resize textarea
function autoResize(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
}

// Modal functions
function openNewGroupModal() {
    document.getElementById('newGroupModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeNewGroupModal() {
    document.getElementById('newGroupModal').style.display = 'none';
    document.body.style.overflow = '';
}

// Search functionality
function filterChats() {
    const searchTerm = document.getElementById('chatSearch').value.toLowerCase();
    const chatItems = document.querySelectorAll('.user-item');
    
    chatItems.forEach(item => {
        const name = item.querySelector('.user-item-name').textContent.toLowerCase();
        if (name.includes(searchTerm)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Scroll to bottom on page load
    scrollToBottom();
    
    // Auto-resize message input
    const messageInput = document.getElementById('messageInput');
    if (messageInput) {
        messageInput.addEventListener('input', function() {
            autoResize(this);
        });
        
        // Submit on Enter (but not Shift+Enter)
        messageInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                document.getElementById('messageForm').submit();
            }
        });
    }
    
    // Search functionality
    const searchInput = document.getElementById('chatSearch');
    if (searchInput) {
        searchInput.addEventListener('input', filterChats);
    }
    
    // Close modal on Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeNewGroupModal();
        }
    });
});
</script>