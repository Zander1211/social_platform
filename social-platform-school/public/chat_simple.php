<?php
session_start();
require_once '../config/database.php';
require_once __DIR__ . '/../src/Controller/ChatController.php';
require_once __DIR__ . '/../src/Helpers/OnlineStatusHelper.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

$chatController = new ChatController($pdo);
$onlineStatusHelper = new OnlineStatusHelper($pdo);

// Update current user's online status
$onlineStatusHelper->updateUserActivity($_SESSION['user_id']);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create group
    if (isset($_POST['create_group'])) {
        $name = trim($_POST['group_name'] ?? '');
        if ($name !== '') {
            $gid = $chatController->createGroup($name, $_SESSION['user_id']);
            header('Location: chat_simple.php?room='.$gid); exit();
        }
        header('Location: chat_simple.php'); exit();
    }

    // Start a direct chat with a user
    if (isset($_POST['start_dm']) && isset($_POST['user_id'])) {
        $rid = $chatController->getOrCreateDirectRoom($_SESSION['user_id'], (int)$_POST['user_id']);
        header('Location: chat_simple.php?room='.$rid); exit();
    }

    // Send message to a room
    if (isset($_POST['send']) && isset($_POST['room_id'])) {
        $rid = (int)$_POST['room_id'];
        $content = trim($_POST['message'] ?? '');
        if ($content !== '') {
            try {
                $chatController->sendMessage($rid, $_SESSION['user_id'], $content);
                header('Location: chat_simple.php?room='.$rid); exit();
            } catch (Exception $e) {
                $error_message = "Error sending message: " . $e->getMessage();
                header('Location: chat_simple.php?error=' . urlencode($error_message)); exit();
            }
        } else {
            header('Location: chat_simple.php?room='.$rid); exit();
        }
    }
}

// Data for UI
$myRooms = $chatController->listUserRooms($_SESSION['user_id']);
$allUsers = $chatController->getAllUsersLite($_SESSION['user_id']);

$roomId = isset($_GET['room']) ? (int)$_GET['room'] : (isset($myRooms[0]['id']) ? (int)$myRooms[0]['id'] : null);
$messages = $roomId ? $chatController->getGroupMessages($roomId) : [];
$roomTitle = $roomId ? $chatController->getRoomTitle($roomId, $_SESSION['user_id']) : null;
$isGroup = false;
if ($roomId) {
    try { 
        $q = $pdo->prepare('SELECT is_group FROM chat_rooms WHERE id = :id'); 
        $q->execute([':id' => $roomId]); 
        $isGroup = (bool)$q->fetchColumn(); 
    } catch (Exception $e) { 
        $isGroup = false; 
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Simple Chat</title>
  <link rel="stylesheet" href="assets/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
    .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .header { background: #007bff; color: white; padding: 1rem; display: flex; justify-content: space-between; align-items: center; }
    .main-content { display: flex; min-height: 600px; }
    .sidebar { width: 300px; background: #f8f9fa; border-right: 1px solid #dee2e6; padding: 1rem; }
    .chat-area { flex: 1; display: flex; flex-direction: column; }
    .messages { flex: 1; padding: 1rem; overflow-y: auto; max-height: 400px; }
    .message-input { padding: 1rem; border-top: 1px solid #dee2e6; }
    .message { margin-bottom: 1rem; padding: 0.5rem; border-radius: 8px; }
    .message.own { background: #007bff; color: white; margin-left: 20%; }
    .message.other { background: #e9ecef; margin-right: 20%; }
    .conversation { padding: 0.5rem; margin-bottom: 0.5rem; border-radius: 4px; cursor: pointer; }
    .conversation:hover { background: #e9ecef; }
    .conversation.active { background: #007bff; color: white; }
    .user-card { padding: 0.5rem; margin-bottom: 0.5rem; border: 1px solid #dee2e6; border-radius: 4px; }
    .form-group { margin-bottom: 1rem; }
    .form-control { width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px; }
    .btn { padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer; }
    .btn-primary { background: #007bff; color: white; }
    .btn-success { background: #28a745; color: white; }
    .btn-secondary { background: #6c757d; color: white; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1><i class="fas fa-comments"></i> Simple Chat</h1>
      <a href="index.php" class="btn btn-secondary">← Back to News Feed</a>
    </div>
    
    <div class="main-content">
      <!-- Sidebar -->
      <div class="sidebar">
        <!-- Create Group -->
        <div class="form-group">
          <h4>Create Group</h4>
          <form method="POST">
            <input type="text" name="group_name" placeholder="Group name..." class="form-control" style="margin-bottom: 0.5rem;">
            <button type="submit" name="create_group" value="1" class="btn btn-primary">Create</button>
          </form>
        </div>
        
        <!-- Conversations -->
        <div class="form-group">
          <h4>Your Conversations</h4>
          <?php if (empty($myRooms)): ?>
            <p>No conversations yet</p>
          <?php else: ?>
            <?php foreach ($myRooms as $room): ?>
              <div class="conversation <?php echo ($roomId === (int)$room['id']) ? 'active' : ''; ?>">
                <a href="chat_simple.php?room=<?php echo (int)$room['id']; ?>" style="text-decoration: none; color: inherit;">
                  <?php echo htmlspecialchars($chatController->getRoomTitle((int)$room['id'], $_SESSION['user_id'])); ?>
                </a>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        
        <!-- Start New Chat -->
        <div class="form-group">
          <h4>Start New Chat</h4>
          <?php if (!empty($allUsers)): ?>
            <?php foreach (array_slice($allUsers, 0, 5) as $user): ?>
              <div class="user-card">
                <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                <form method="POST" style="margin-top: 0.5rem;">
                  <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                  <button type="submit" name="start_dm" value="1" class="btn btn-success btn-sm">Chat</button>
                </form>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p>No users available</p>
          <?php endif; ?>
        </div>
      </div>
      
      <!-- Chat Area -->
      <div class="chat-area">
        <?php if ($roomId): ?>
          <div style="padding: 1rem; background: #f8f9fa; border-bottom: 1px solid #dee2e6;">
            <h3><?php echo htmlspecialchars($roomTitle); ?></h3>
            <small><?php echo $isGroup ? 'Group Chat' : 'Direct Message'; ?></small>
          </div>
          
          <div class="messages" id="messages">
            <?php if (empty($messages)): ?>
              <p style="text-align: center; color: #6c757d;">No messages yet. Start the conversation!</p>
            <?php else: ?>
              <?php foreach ($messages as $message): ?>
                <?php $isMe = $_SESSION['user_id'] == $message['sender_id']; ?>
                <div class="message <?php echo $isMe ? 'own' : 'other'; ?>">
                  <?php if (!$isMe): ?>
                    <strong>
                      <?php 
                        try {
                          $senderStmt = $pdo->prepare('SELECT name FROM users WHERE id = :id');
                          $senderStmt->execute([':id' => $message['sender_id']]);
                          $sender = $senderStmt->fetch(PDO::FETCH_ASSOC);
                          echo htmlspecialchars($sender['name'] ?? 'Unknown');
                        } catch (Exception $e) {
                          echo 'Unknown';
                        }
                      ?>:
                    </strong><br>
                  <?php endif; ?>
                  <?php echo nl2br(htmlspecialchars($message['content'])); ?>
                  <br><small><?php echo date('M j, g:i A', strtotime($message['created_at'])); ?></small>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
          
          <div class="message-input">
            <form method="POST" style="display: flex; gap: 0.5rem;">
              <input type="hidden" name="room_id" value="<?php echo (int)$roomId; ?>">
              <input type="text" name="message" placeholder="Type your message..." class="form-control" required>
              <button type="submit" name="send" value="1" class="btn btn-primary">Send</button>
            </form>
          </div>
        <?php else: ?>
          <div style="display: flex; align-items: center; justify-content: center; height: 100%; text-align: center; color: #6c757d;">
            <div>
              <i class="fas fa-comments" style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.5;"></i>
              <h3>Welcome to Simple Chat</h3>
              <p>Select a conversation from the sidebar or start a new chat</p>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  
  <script>
    // Auto-scroll to bottom of messages
    function scrollToBottom() {
      const messages = document.getElementById('messages');
      if (messages) {
        messages.scrollTop = messages.scrollHeight;
      }
    }
    
    // Auto-refresh messages every 5 seconds
    <?php if ($roomId): ?>
    setInterval(function() {
      fetch(window.location.href)
        .then(response => response.text())
        .then(html => {
          const parser = new DOMParser();
          const doc = parser.parseFromString(html, 'text/html');
          const newMessages = doc.querySelector('#messages');
          const currentMessages = document.getElementById('messages');
          
          if (newMessages && currentMessages) {
            const currentCount = currentMessages.querySelectorAll('.message').length;
            const newCount = newMessages.querySelectorAll('.message').length;
            if (newCount > currentCount) {
              currentMessages.innerHTML = newMessages.innerHTML;
              scrollToBottom();
            }
          }
        })
        .catch(error => console.log('Refresh error:', error));
    }, 5000);
    <?php endif; ?>
    
    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
      scrollToBottom();
    });
  </script>
</body>
</html>