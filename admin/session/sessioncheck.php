<?php
session_start();
// Check if session variables are set
header('Content-Type: application/json');

if (isset($_SESSION['admin_role'], $_SESSION['admin_token'], $_SESSION['admin_user_id'])) {
    echo json_encode(['sessionValid' => true]);
} else {
    echo json_encode(['sessionValid' => false]);
}
