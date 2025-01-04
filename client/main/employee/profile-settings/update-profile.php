<?php
session_start();
require_once '../../../../config/database.php';

// Check if the session contains a user ID
if (!isset($_SESSION['client_role'], $_SESSION['client_token'], $_SESSION['client_user_id'])) {
    header("Location: ../../../login?error=session_expired");
} else {
    if ($_SESSION['client_role'] == 'Head') {
        header("Location: ../../head/");
    }
}

$userId = $_SESSION['client_user_id'];

// Validate POST data
if (!isset($_POST['firstname']) || !isset($_POST['lastname'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid input.',
    ]);
    header("Location: ./profile-settings?error=invalid_input");
    exit;
}

$firstname = trim($_POST['firstname']);
$lastname = trim($_POST['lastname']);

// Check if input is valid
if (empty($firstname) || empty($lastname)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'First name and last name are required.',
    ]);
    header("Location: ./profile-settings?error=required_fields");
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Update user details in the database
    $updateQuery = $conn->prepare(
        "UPDATE tb_client_userdetails 
         SET user_fname = :firstname, user_lname = :lastname 
         WHERE user_id = :user_id AND user_status = 1"
    );
    $updateQuery->bindParam(':firstname', $firstname, PDO::PARAM_STR);
    $updateQuery->bindParam(':lastname', $lastname, PDO::PARAM_STR);
    $updateQuery->bindParam(':user_id', $userId, PDO::PARAM_INT);

    if ($updateQuery->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Profile updated successfully.',
        ]);

        // Fetch the current user's email and role
        $doerUserId = $_SESSION['client_user_id'];
        $userStmt = $conn->prepare("
            SELECT user_email, user_role 
            FROM tb_client_userdetails 
            WHERE user_id = :user_id AND user_status = 1
        ");
        $userStmt->bindParam(':user_id', $doerUserId);
        $userStmt->execute();
        $userDetails = $userStmt->fetch(PDO::FETCH_ASSOC);
        $logRole = $userDetails['user_role'] ?? 'Unknown';

        // Log the user addition
        $logAction = "Profile name updated successfully";
        $logdate = date('Y-m-d H:i:s');
        $logStmt = $conn->prepare("
            INSERT INTO tb_logs (doer, log_date, role, log_action) 
            VALUES (:doer, :log_date, :role, :action)
        ");
        $logStmt->execute([
            ':doer' => $doerUserId,
            ':log_date' => $logdate,
            ':role' => $logRole,
            ':action' => $logAction
        ]);

        header("Location: ./profile-settings?error=none");
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update profile.',
        ]);
    }
} catch (PDOException $e) {
    error_log("Error updating user details: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred while updating profile.',
    ]);
    header("Location: ./profile-settings?error=1");
}
