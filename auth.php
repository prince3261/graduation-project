<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$user_type = $_SESSION['user_type'];

$restricted_pages = [
    "admin_home.php" => ["ผู้เช่า"]
];

$current_page = basename($_SERVER['PHP_SELF']);

if (array_key_exists($current_page, $restricted_pages)) {
    if (in_array($user_type, $restricted_pages[$current_page])) {
        header("Location: index.php");
        exit();
    }
}

?>