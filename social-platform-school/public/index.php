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
    <main class="container">
        <?php include '../src/View/templates'; ?>
    </main>
    <?php require_once __DIR__ . '/../src/View/footer.php'; ?>
    <?php
}
?>