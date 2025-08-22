<?php
session_start();
require_once '../config/database.php';
require_once __DIR__ . '/../src/Controller/AuthController.php';
$auth = new AuthController($pdo);
$auth->logout();
header('Location: index.php');
exit();
