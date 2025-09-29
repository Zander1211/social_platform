<?php
session_start();
require_once '../config/database.php';
require_once __DIR__ . '/../src/Controller/ChatController.php';

$chatController = new ChatController($pdo);

// AJAX endpoint for group info - must be before any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'group_info' && isset($_GET['group_id'])) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Not authenticated']);
        exit();
    }
    
    $groupId = (int)$_GET['group_id'];
    
    try {
        $groupInfo = $chatController->getGroupInfo($groupId);
        $members = $chatController->getGroupMembers($groupId);
        
        header('Content-Type: application/json');
        echo json_encode([
            'group' => $groupInfo,
            'members' => $members
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

// AJAX endpoint for message count - must be before any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'message_count' && isset($_GET['group_id'])) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Not authenticated']);
        exit();
    }
    
    $groupId = (int)$_GET['group_id'];
    
    try {
        $count = $chatController->getMessageCount($groupId);
        
        header('Content-Type: application/json');
        echo json_encode(['count' => $count]);
    } catch (Exception $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_group'])) {
        if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
        $name = trim($_POST['group_name'] ?? '');
        $members = isset($_POST['members']) && is_array($_POST['members']) ? $_POST['members'] : [];
        
        // Debug output
        error_log("Group creation attempt - Name: $name, Creator: " . $_SESSION['user_id']);
        
        if ($name !== '') {
            $gid = $chatController->createGroup($name, $_SESSION['user_id']);
            error_log("Group creation result - ID: " . ($gid ? $gid : 'false'));
            
            if ($gid && !empty($members)) {
                foreach ($members as $mid) { 
                    $result = $chatController->addMember($gid, (int)$mid);
                    error_log("Adding member $mid to group $gid: " . ($result ? 'success' : 'failed'));
                }
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
    
    if (isset($_POST['start_dm']) && isset($_POST['user_id'])) {
        if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
        $otherUserId = (int)$_POST['user_id'];
        $roomId = $chatController->getOrCreateDirectRoom($_SESSION['user_id'], $otherUserId);
        if ($roomId) {
            header('Location: chat.php?room='.$roomId); exit();
        }
        header('Location: chat.php'); exit();
    }
}

$onlineUsers = $chatController->getActiveUsers();
$groups = $chatController->listGroups();

// Get user's rooms (both groups and DMs)
$userRooms = [];
if (isset($_SESSION['user_id'])) {
    $userRooms = $chatController->listUserRooms($_SESSION['user_id']);
}

// Handle direct message from URL parameter
if (isset($_GET['user_id']) && !isset($_GET['room']) && !isset($_GET['group'])) {
    $otherUserId = (int)$_GET['user_id'];
    if (isset($_SESSION['user_id']) && $otherUserId !== $_SESSION['user_id']) {
        $roomId = $chatController->getOrCreateDirectRoom($_SESSION['user_id'], $otherUserId);
        if ($roomId) {
            header('Location: chat.php?room='.$roomId); exit();
        }
    }
}
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
            <?php if (empty($userRooms)): ?>
                <div class="no-chats">
                    <i class="fas fa-comments"></i>
                    <p>No conversations yet</p>
                    <small>Start a new group chat or message someone to begin</small>
                </div>
            <?php else: ?>
                <?php foreach ($userRooms as $room): ?>
                    <?php 
                    $isGroup = (int)$room['is_group'] === 1;
                    $roomTitle = $chatController->getRoomTitle($room['id'], $_SESSION['user_id']);
                    $isActive = (isset($_GET['room']) && $_GET['room'] == $room['id']) || 
                               (isset($_GET['group']) && $_GET['group'] == $room['id']);
                    $roomUrl = $isGroup ? "chat.php?group={$room['id']}" : "chat.php?room={$room['id']}";
                    ?>
                    <div class="user-item <?php echo $isActive ? 'active' : ''; ?>">
                        <a href="<?php echo $roomUrl; ?>" class="user-link">
                            <div class="user-item-avatar">
                                <?php if ($isGroup): ?>
                                    <i class="fas fa-users"></i>
                                <?php else: ?>
                                    <i class="fas fa-user"></i>
                                <?php endif; ?>
                            </div>
                            <div class="user-item-info">
                                <div class="user-item-name"><?php echo htmlspecialchars($roomTitle); ?></div>
                                <div class="user-item-role"><?php echo $isGroup ? 'Group Chat' : 'Direct Message'; ?></div>
                            </div>
                            <div class="chat-time">
                                <?php echo date('M j', strtotime($room['created_at'])); ?>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Chat Main Area -->
    <div class="chat-main">
        <?php if (isset($_GET['group']) || isset($_GET['room'])): ?>
            <?php 
            $roomId = isset($_GET['group']) ? (int)$_GET['group'] : (int)$_GET['room'];
            $isGroup = isset($_GET['group']);
            $messages = $isGroup ? $chatController->getGroupMessages($roomId) : $chatController->getMessages($roomId);
            $roomTitle = $chatController->getRoomTitle($roomId, $_SESSION['user_id']);
            $roomInfo = $chatController->getRoomInfo($roomId);
            ?>
            
            <!-- Chat Header -->
            <div class="chat-main-header">
                <div class="chat-info">
                    <div class="chat-avatar">
                        <?php if ($isGroup): ?>
                            <i class="fas fa-users"></i>
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                    <div class="chat-details">
                        <h4><?php echo htmlspecialchars($roomTitle); ?></h4>
                        <span class="chat-status"><?php echo $isGroup ? 'Group Chat' : 'Direct Message'; ?> • <?php echo count($messages); ?> messages</span>
                    </div>
                </div>
                <div class="chat-actions">
                    <button class="btn btn-secondary btn-sm" title="Voice Call" disabled style="opacity: 0.5; cursor: not-allowed;">
                        <i class="fas fa-phone"></i>
                    </button>
                    <button class="btn btn-secondary btn-sm" title="Video Call" disabled style="opacity: 0.5; cursor: not-allowed;">
                        <i class="fas fa-video"></i>
                    </button>
                    <button class="btn btn-secondary btn-sm" title="Group Info" onclick="openGroupInfoModal()">
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
                    <input type="hidden" name="group_id" value="<?php echo $roomId; ?>">
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

<!-- Group Info Modal -->
<div id="groupInfoModal" class="modal" style="display: none;">
    <div class="modal-overlay" onclick="closeGroupInfoModal()"></div>
    <div class="modal-content group-info-modal">
        <div class="modal-header">
            <h3><i class="fas fa-info-circle"></i> <span id="groupInfoTitle">Group Information</span></h3>
            <button class="modal-close" onclick="closeGroupInfoModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="group-info-section">
                <h4><i class="fas fa-users"></i> Members</h4>
                <div id="groupMembersList" class="members-display">
                    <!-- Members will be loaded here -->
                </div>
            </div>
            <div class="group-info-section">
                <h4><i class="fas fa-calendar"></i> Group Details</h4>
                <div class="group-details">
                    <div class="detail-item">
                        <span class="detail-label">Created:</span>
                        <span id="groupCreatedDate" class="detail-value">Loading...</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Created by:</span>
                        <span id="groupCreator" class="detail-value">Loading...</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Total Messages:</span>
                        <span id="groupMessageCount" class="detail-value">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeGroupInfoModal()">Close</button>
        </div>
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
        <form method="POST" class="modal-form" id="createGroupForm">
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
                <button type="submit" name="create_group" class="btn btn-primary" id="createGroupBtn">
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

// Group Info Modal functions
function openGroupInfoModal() {
    const modal = document.getElementById('groupInfoModal');
    if (modal) {
        // Get current group info from the page
        const groupTitle = document.querySelector('.chat-details h4')?.textContent || 'Unknown Group';
        const messageCount = document.querySelector('.chat-status')?.textContent.match(/(\d+) messages/)?.[1] || '0';
        
        // Update modal content
        document.getElementById('groupInfoTitle').textContent = groupTitle;
        document.getElementById('groupMessageCount').textContent = messageCount;
        
        // Load group details
        loadGroupInfo();
        
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeGroupInfoModal() {
    document.getElementById('groupInfoModal').style.display = 'none';
    document.body.style.overflow = '';
}

function loadGroupInfo() {
    const urlParams = new URLSearchParams(window.location.search);
    const groupId = urlParams.get('group');
    
    if (groupId) {
        // Show loading state
        document.getElementById('groupCreatedDate').textContent = 'Loading...';
        document.getElementById('groupCreator').textContent = 'Loading...';
        document.getElementById('groupMessageCount').textContent = 'Loading...';
        document.getElementById('groupMembersList').innerHTML = '<div style="text-align: center; padding: 20px; color: #666;">Loading members...</div>';
        
        // Fetch group info from server
        fetch(`chat.php?action=group_info&group_id=${groupId}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.group) {
                // Update modal title with group name
                document.getElementById('groupInfoTitle').textContent = data.group.name || 'Group Information';
                
                // Update group details
                const createdDate = new Date(data.group.created_at).toLocaleDateString();
                document.getElementById('groupCreatedDate').textContent = createdDate;
                document.getElementById('groupCreator').textContent = data.group.creator_name || 'Unknown';
                
                // Get message count
                fetch(`chat.php?action=message_count&group_id=${groupId}`, {
                    method: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => response.json())
                .then(countData => {
                    document.getElementById('groupMessageCount').textContent = countData.count || '0';
                })
                .catch(() => {
                    document.getElementById('groupMessageCount').textContent = '-';
                });
            }
            
            if (data.members && data.members.length > 0) {
                // Update members list
                const membersList = document.getElementById('groupMembersList');
                membersList.innerHTML = '';
                
                data.members.forEach(member => {
                    const memberDiv = document.createElement('div');
                    memberDiv.className = 'member-display-item';
                    
                    const isCreator = member.user_id == data.group.created_by;
                    const initial = member.name ? member.name.charAt(0).toUpperCase() : 'U';
                    
                    memberDiv.innerHTML = `
                        <div class="member-display-avatar">
                            ${initial}
                        </div>
                        <div class="member-display-info">
                            <div class="member-display-name">${member.name || 'Unknown User'}</div>
                            <div class="member-display-role">${isCreator ? 'Group Creator' : 'Member'}</div>
                        </div>
                    `;
                    
                    membersList.appendChild(memberDiv);
                });
            } else {
                // No members found
                document.getElementById('groupMembersList').innerHTML = '<div style="text-align: center; padding: 20px; color: #666;">No members found</div>';
            }
        })
        .catch(error => {
            console.error('Error loading group info:', error);
            document.getElementById('groupCreatedDate').textContent = 'Error loading';
            document.getElementById('groupCreator').textContent = 'Error loading';
            document.getElementById('groupMessageCount').textContent = 'Error';
            document.getElementById('groupMembersList').innerHTML = '<div style="text-align: center; padding: 20px; color: #666;">Failed to load members</div>';
        });
    }
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
            closeGroupInfoModal();
        }
    });
});
</script>