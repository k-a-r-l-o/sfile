<?php

session_start();

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['user_id']) && isset($_POST['doer']) && isset($_POST['role'])) {
        $userId = $_POST['user_id'];
        $doer = $_POST['doer'];
        $role = $_POST['role'];

        try {
            // Establish database connection
            $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Update user status to 0 (inactive)
            $updateQuery = "UPDATE tb_admin_userdetails SET user_status = 0 WHERE user_id = :user_id";
            $stmt = $pdo->prepare($updateQuery);
            $stmt->execute([':user_id' => $userId]);

            // Log the action
            $logQuery = "INSERT INTO tb_logs (doer, role, log_action) VALUES (:doer, :role, :log_action)";
            $stmt = $pdo->prepare($logQuery);
            $stmt->execute([
                ':doer' => $doer,
                ':role' => $role,
                ':log_action' => "User with ID $userId has been deactivated"
            ]);

            echo json_encode(['status' => 'success', 'message' => 'User status updated and action logged successfully.']);
        } catch (PDOException $e) {
            error_log("Error: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Failed to update user status or log the action.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request. User ID, doer, and role are required.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
