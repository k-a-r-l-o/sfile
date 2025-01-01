<?php
session_start();
// Check if session variables are set
if (!isset($_SESSION['client_role'], $_SESSION['client_token'], $_SESSION['client_user_id'])) {
    header("Location: ../login");
    exit();
}
