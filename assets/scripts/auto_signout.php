<?php
session_start(); // Start the session
require_once '../../config/database.php';
$currentTimestamp = date('Y-m-d H:i:s');

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Mark client users as "Offline" if inactive for more than minutes
    $updateClientStatusStmt = $conn->prepare(
        "UPDATE tb_client_logindetails 
        SET user_status = 'Offline', user_log = NULL 
        WHERE TIMESTAMPDIFF(MINUTE, user_log, :currentTimestamp) > 6"
    );
    $updateClientStatusStmt->bindParam(':currentTimestamp', $currentTimestamp, PDO::PARAM_STR);
    $updateClientStatusStmt->execute();

    // Mark admin users as "Offline" if inactive for more than minutes
    $updateAdminStatusStmt = $conn->prepare(
        "UPDATE tb_admin_logindetails 
        SET user_status = 'Offline', user_log = NULL 
        WHERE TIMESTAMPDIFF(MINUTE, user_log, :currentTimestamp) > 6"
    );
    $updateAdminStatusStmt->bindParam(':currentTimestamp', $currentTimestamp, PDO::PARAM_STR);
    $updateAdminStatusStmt->execute();
} catch (PDOException $e) {
    error_log("Signout error: " . $e->getMessage());
}
