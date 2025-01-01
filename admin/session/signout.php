<?php
session_start(); // Start the session
require_once '../../config/database.php';

if (isset($_SESSION['admin_user_id'])) {
    try {
        $db = new Database();
        $conn = $db->getConnection();

        // Mark user as "Offline"
        $updateStatusStmt = $conn->prepare(
            "UPDATE tb_admin_logindetails 
             SET user_status = 'Offline' 
             WHERE user_id = :user_id"
        );
        $updateStatusStmt->execute([':user_id' => $_SESSION['admin_user_id']]);

        // Fetch the current user's email and role
        $doerUserId = $_SESSION['admin_user_id'];
        $userStmt = $conn->prepare("
            SELECT user_email, user_role 
            FROM tb_admin_userdetails 
            WHERE user_id = :user_id
        ");
        $userStmt->bindParam(':user_id', $doerUserId);
        $userStmt->execute();
        $userDetails = $userStmt->fetch(PDO::FETCH_ASSOC);
        $logRole = $userDetails['user_role'] ?? 'Unknown';

        // Log the user addition
        $logAction = "Signed out successfully.";
        $logStmt = $pdo->prepare("
            INSERT INTO tb_logs (doer, role, log_action) 
            VALUES (:doer, :role, :action)
        ");
        $logStmt->execute([
            ':doer' => $doerUserId,
            ':role' => $logRole,
            ':action' => $logAction
        ]);
    } catch (PDOException $e) {
        error_log("Signout error: " . $e->getMessage());
        // Optionally, you can show a message or log this for further review
    }
}

// Destroy all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect the user to the login page
header("Location: ../index.php");
exit; // Ensure no further code is executed
