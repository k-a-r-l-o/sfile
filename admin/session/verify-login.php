<?php
session_start();
require_once '../../config/database.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    try {
        $db = new Database();
        $conn = $db->getConnection();

        // Join tb_admin_logindetails and tb_admin_userdetails to retrieve role and other details
        $stmt = $conn->prepare(
            "SELECT l.user_id, u.user_role, l.token, l.token_expiration 
             FROM tb_admin_logindetails l
             JOIN tb_admin_userdetails u ON l.user_id = u.user_id
             WHERE l.token_expiration > NOW()"
        );
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($token, $user['token'])) {
            // Clear token to prevent reuse
            $clearTokenStmt = $conn->prepare(
                "UPDATE tb_admin_logindetails 
                 SET token = NULL, token_expiration = NULL, verified = 1, user_status = 'Online' 
                 WHERE user_id = :user_id"
            );
            $clearTokenStmt->execute([':user_id' => $user['user_id']]);

            // Store user details in session
            $_SESSION['admin_user_id'] = $user['user_id'];
            $_SESSION['admin_role'] = $user['user_role'];
            $_SESSION['admin_token'] = $token;

            // Fetch the current user's email and role for logging purposes
            $doerUserId = $_SESSION['admin_user_id'];
            $logRole = $user['user_role'];

            // Log the edit action
            $logAction = "Successfully logged in.";
            $logStmt = $conn->prepare("INSERT INTO tb_logs (doer, role, log_action) VALUES (:doer, :role, :action)");
            $logStmt->execute([
                ':doer' => $doerUserId,
                ':role' => $logRole,
                ':action' => $logAction
            ]);
            // Redirect based on role
            header("Location: ../verification-success");
            exit();
        } else {
            header("Location: ../token-expired");
        }
    } catch (PDOException $e) {
        error_log("Verification error: " . $e->getMessage());
        header("Location: ../token-expired");
    }
} else {
    header("Location: ../token-expired");
}
