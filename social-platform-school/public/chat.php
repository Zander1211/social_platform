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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css">
    <title>Chat</title>
</head>
<body>
        <?php require_once __DIR__ . '/../src/View/header.php'; ?>

        <!-- main-layout's left sidebar is rendered by header.php: place chat list in the center and conversation in the right column -->
        <section class="feed-center">
            <section class="card">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                    <h3>Chats</h3>
                    <div><button class="btn" title="New">✎</button></div>
                </div>
                <div class="search-row" style="margin-bottom:8px">
                    <input class="input" placeholder="Search Messenger" style="width:100%">
                </div>
                <?php $groups = $chatController->listGroups(); ?>
                <?php foreach ($groups as $g): ?>
                    <div class="chat-item">
                        <form method="GET" action="chat.php" style="display:flex;gap:8px;width:100%">
                            <input type="hidden" name="group" value="<?php echo $g['id']; ?>">
                            <button class="chat-btn" type="submit" style="display:flex;align-items:center;gap:8px;width:100%;border:0;background:transparent;padding:8px;text-align:left">
                                <div class="avatar"></div>
                                <div style="flex:1;min-width:0">
                                    <div style="font-weight:600"><?php echo htmlspecialchars($g['name']); ?></div>
                                    <div class="kv"><?php echo htmlspecialchars($g['created_at']); ?></div>
                                </div>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </section>
        </section>

        <aside class="right-sidebar card">
            <?php if (isset($_GET['group'])): ?>
                <?php $gid = (int)$_GET['group']; $messages = $chatController->getGroupMessages($gid); $groupName='Group'; foreach ($groups as $gg) if($gg['id']==$gid) $groupName=$gg['name']; ?>
                <div class="header" style="display:flex;align-items:center;gap:12px;padding:12px;border-bottom:1px solid #eef2f6">
                    <div class="avatar"></div>
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:700"><?php echo htmlspecialchars($groupName); ?></div>
                        <div class="kv">Group chat</div>
                    </div>
                    <div class="actions" style="display:flex;gap:8px;align-items:center">
                        <button class="btn">📞</button>
                        <button class="btn">🎥</button>
                        <button class="btn">ℹ</button>
                    </div>
                </div>
                <div class="messages" style="min-height:240px;max-height:420px;overflow:auto;padding:12px">
                    <?php foreach ($messages as $message): ?>
                        <?php $isMe = isset($_SESSION['user_id']) && $_SESSION['user_id'] == ($message['sender_id'] ?? $message['sender']); ?>
                        <div class="msg-row <?php echo $isMe? 'me':''; ?>">
                            <?php if (!$isMe): ?><div class="avatar" style="width:36px;height:36px"></div><?php endif; ?>
                            <div class="msg <?php echo $isMe? 'me':''; ?>"><?php echo htmlspecialchars($message['content']); ?>
                                <div class="kv" style="font-size:0.8rem;margin-top:6px;text-align:right"><?php echo htmlspecialchars($message['created_at'] ?? ''); ?></div>
                            </div>
                            <?php if ($isMe): ?><div class="avatar" style="width:36px;height:36px"></div><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <form method="POST" class="chat-input" style="margin-top:8px">
                    <input type="hidden" name="group_id" value="<?php echo $gid; ?>">
                    <input type="text" name="message" placeholder="Aa" required>
                    <div class="actions">
                        <button class="btn" type="submit" name="send_group">Send</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="kv">Select or create a group to start chatting.</div>
            <?php endif; ?>
        </aside>
        <?php require_once __DIR__ . '/../src/View/footer.php'; ?>
    <script src="assets/chat.js"></script>
</body>
</html>