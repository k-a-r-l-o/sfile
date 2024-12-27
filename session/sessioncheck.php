<?php

// Check if session variables are set
if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || !isset($_SESSION['user_id'])) {
    // If session variables are not set, redirect to the index page
    header("Location: /../login.php");
    exit();
}
