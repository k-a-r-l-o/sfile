<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Include database connection
    require_once '../../../../config/config.php';

    // Retrieve form data
    $userId = $_SESSION['user_id']; // Assuming the user ID is stored in the session
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validate form data
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        echo json_encode(["error" => "All fields are required."]);
        exit;
    }

    try {
        $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Fetch the user's current password from the database
        $stmt = $pdo->prepare("SELECT user_password FROM tb_users WHERE user_id = :userId");
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $storedPassword = $stmt->fetchColumn();

        if (!$storedPassword) {
            echo json_encode(["error" => "User not found."]);
            exit;
        }

        // Verify the current password
        if (!password_verify($currentPassword, $storedPassword)) {
            echo json_encode(["error" => "Current password is incorrect."]);
            exit;
        }

        // Validate the new password requirements
        if (strlen($newPassword) < 8 || !preg_match('/[A-Z]/', $newPassword) || !preg_match('/\d/', $newPassword)) {
            echo json_encode([
                "error" => "New password must be at least 8 characters long, contain at least one uppercase letter, and one number."
            ]);
            exit;
        }

        // Check if new password matches confirm password
        if ($newPassword !== $confirmPassword) {
            echo json_encode(["error" => "New password and confirmation password do not match."]);
            exit;
        }

        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Start a transaction
        $pdo->beginTransaction();

        // Update the user's password in the database
        $updateStmt = $pdo->prepare("UPDATE tb_users SET user_password = :hashedPassword WHERE user_id = :userId");
        $updateStmt->bindParam(':hashedPassword', $hashedPassword);
        $updateStmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $updateStmt->execute();

        // Log the password update action
        $logAction = "User $userId updated their password.";
        $logStmt = $pdo->prepare("INSERT INTO tb_logs (doer, role, log_action) VALUES (:doer, :role, :action)");
        $logStmt->execute([
            ':doer' => $userId,
            ':role' => 'User', // Adjust role as needed
            ':action' => $logAction
        ]);

        // Commit the transaction
        $pdo->commit();

        // Return success response
        echo json_encode(["success" => true, "message" => "Password updated successfully."]);
    } catch (PDOException $e) {
        // Rollback the transaction on error
        $pdo->rollBack();
        echo json_encode(["error" => "An error occurred: " . $e->getMessage()]);
    }
    exit;
}

// Redirect to home if accessed improperly
header("Location: ../");
exit;
