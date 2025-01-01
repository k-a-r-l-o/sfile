<?php
session_start();
// Check if session variables are set
if (!isset($_SESSION['admin_role'], $_SESSION['admin_token'], $_SESSION['admin_user_id'])) {
    header("Location: ../login");
    exit();
}
