<?php
session_start();
// Check if session variables are set
header('Content-Type: application/json');

if (isset($_SESSION['client_role'], $_SESSION['client_token'], $_SESSION['client_user_id'])) {
    echo json_encode(['sessionValid' => true]);
} else {
    echo json_encode(['sessionValid' => false]);
}
