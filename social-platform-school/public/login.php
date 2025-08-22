<?php
session_start();
require_once '../config/database.php';
require_once __DIR__ . '/../src/Controller/AuthController.php';

$auth = new AuthController($pdo);
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $res = $auth->login($_POST['email'], $_POST['password']);
    if ($res['status'] === 'success') {
        header('Location: index.php'); exit();
    }
    $error = $res['message'] ?? 'Login failed';
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="assets/style.css">
    <title>Login</title>
</head>
<body>
<header>
    <h1>School Platform</h1>
    <nav>
        <a href="index.php">Home</a>
    </nav>
</header>
<main class="container">
    <section class="card" style="max-width:480px;margin:20px auto">
        <h2>Login</h2>
        <?php if ($error): ?><div class="warning"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-row"><input class="input" type="email" name="email" placeholder="Email" required></div>
            <div class="form-row" style="margin-top:8px"><input class="input" type="password" name="password" placeholder="Password" required></div>
            <div style="margin-top:10px"><button class="btn" type="submit">Login</button></div>
        </form>
    </section>
</main>
</body>
</html>
