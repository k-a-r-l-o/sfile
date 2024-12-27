<?php
session_start();
// Check if session variables are set
if (!isset($_SESSION['email'], $_SESSION['role'], $_SESSION['user_id'])) {
    header("Location: /../login.php");
    exit();
}
