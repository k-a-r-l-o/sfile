<?php
session_start(); // Start the session
require_once '../../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Mark client users as "Offline" if inactive for more than 4 minutes
    $updateStatusStmt = $conn->prepare(
        "UPDATE tb_client_logindetails 
        SET user_status = 'Offline', user_log = NULL 
        WHERE TIMESTAMPDIFF(MINUTE, user_log, NOW()) > 6"
    );
    $updateStatusStmt->execute();
} catch (PDOException $e) {
    error_log("Signout error for tb_client_logindetails: " . $e->getMessage());
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Mark admin users as "Offline" if inactive for more than 4 minutes
    $updateStatusStmt = $conn->prepare(
        "UPDATE tb_admin_logindetails 
        SET user_status = 'Offline', user_log = NULL 
        WHERE TIMESTAMPDIFF(MINUTE, user_log, NOW()) > 6"
    );
    $updateStatusStmt->execute();
} catch (PDOException $e) {
    error_log("Signout error for tb_admin_logindetails: " . $e->getMessage());
}
