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
                            <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                            <p><?php echo htmlspecialchars($post['content']); ?></p>
                            <form method="POST">
                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                <button class="btn" type="submit" name="delete_post">Delete Post</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </section>
            </div>

            <aside>
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
            </aside>
        </div>
    </main>
    <?php require_once __DIR__ . '/../src/View/footer.php'; ?>
</body>
</html>