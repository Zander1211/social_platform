<?php

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / strlen($x)))), 1, $length);
}

function formatDate($date) {
    return date("F j, Y, g:i a", strtotime($date));
}

function isUserLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirectTo($url) {
    header("Location: $url");
    exit();
}

function flashMessage($message) {
    $_SESSION['flash_message'] = $message;
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}