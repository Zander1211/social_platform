<?php
session_start();
require_once '../config/database.php';
require_once __DIR__ . '/../src/Controller/AuthController.php';

$auth = new AuthController($pdo);
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $res = $auth->register($_POST);
    if ($res['status'] === 'success') {
        header('Location: login.php'); exit();
    }
    $error = $res['message'] ?? 'Registration failed';
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="assets/style.css">
    <title>Register</title>
</head>
<body>
<header>
    <h1>School Platform</h1>
    <nav>
        <a href="index.php">Home</a>
        <a href="login.php">Login</a>
    </nav>
</header>
<main class="container">
    <section class="card" style="max-width:540px;margin:20px auto">
        <h2>Register</h2>
        <?php if ($error): ?><div class="warning"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-row"><input class="input" type="text" name="name" placeholder="Full name" required></div>
            <div class="form-row" style="margin-top:8px"><input class="input" type="email" name="email" placeholder="Email" required></div>
            <div class="form-row" style="margin-top:8px"><input class="input" type="text" name="contact_number" placeholder="Contact number"></div>
            <div class="form-row" style="margin-top:8px"><input class="input" type="password" name="password" placeholder="Password" required></div>
            <div style="margin-top:10px"><button class="btn" type="submit">Register</button></div>
        </form>
    </section>
</main>
</body>
</html>
