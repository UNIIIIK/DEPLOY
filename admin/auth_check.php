<?php
session_start();

$allowed_pages = ['login.php', 'logout.php'];
$current_page = basename($_SERVER['PHP_SELF']);

if (!isset($_SESSION['admin']) && !isset($_SESSION['student']) && !in_array($current_page, $allowed_pages)) {
    header("Location: login.php");
    exit();
}
?>