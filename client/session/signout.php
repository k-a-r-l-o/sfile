<?php
session_start(); // Start the session
require_once '../../config/database.php';

if (isset($_SESSION['client_user_id'])) {
    try {
        $db = new Database();
        $conn = $db->getConnection();

        // Mark user as "Offline"
        $updateStatusStmt = $conn->prepare(
            "UPDATE tb_client_logindetails 
             SET user_status = 'Offline', 
                 user_log = NULL
             WHERE user_id = :user_id"
        );
        $updateStatusStmt->execute([':user_id' => $_SESSION['client_user_id']]);

        // Fetch the current user's email and role
        $doerUserId = $_SESSION['client_user_id'];
        $userStmt = $conn->prepare("
            SELECT user_email, user_role 
            FROM tb_client_userdetails 
            WHERE user_id = :user_id
        ");
        $userStmt->bindParam(':user_id', $doerUserId);
        $userStmt->execute();
        $userDetails = $userStmt->fetch(PDO::FETCH_ASSOC);
        $logRole = $userDetails['user_role'] ?? 'Unknown';

        // Log the signout action 
        $logAction = "Signed out successfully.";
        $userlog = date('Y-m-d H:i:s'); // Current timestamp
        $logStmt = $conn->prepare("
                INSERT INTO tb_logs (log_date, doer, role, log_action)
                VALUES (:userlog, :doer, :role, :action)
            ");
        $logStmt->execute([
            ':userlog' => $userlog,
            ':doer' => $doerUserId,
            ':role' => $logRole,
            ':action' => $logAction
        ]);

        // Destroy all session variables
        session_unset();

        // Destroy the session
        session_destroy();

        // Redirect the user to the login page
        header("Location: ../index.php");
        exit; // Ensure no further code is executed
    } catch (PDOException $e) {
        error_log("Signout error: " . $e->getMessage());
        echo "Signout error:  . $e.";
    }
}
