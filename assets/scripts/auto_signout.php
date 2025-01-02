<?php
session_start(); // Start the session
require_once '../../config/database.php';


try {
    $db = new Database();
    $conn = $db->getConnection();

    // Mark user as "Offline"
    $updateStatusStmt = $conn->prepare(
        "UPDATE tb_client_logindetails 
            SET user_status = 'Offline' 
                user_log = NULL, 
            WHERE TIMESTAMPDIFF(MINUTE, user_log, NOW()) > 4"
    );
    $updateStatusStmt->execute();
} catch (PDOException $e) {
    error_log("Signout error: " . $e->getMessage());
    // Optionally, you can show a message or log this for further review
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Mark user as "Offline"
    $updateStatusStmt = $conn->prepare(
        "UPDATE tb_admin_logindetails 
            SET user_status = 'Offline' 
                user_log = NULL, 
            WHERE TIMESTAMPDIFF(MINUTE, user_log, NOW()) > 4"
    );
    $updateStatusStmt->execute();
} catch (PDOException $e) {
    error_log("Signout error: " . $e->getMessage());
    // Optionally, you can show a message or log this for further review
}
