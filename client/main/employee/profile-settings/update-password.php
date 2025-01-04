<?php
session_start();
require_once '../../../../config/database.php';

// Check if the session contains a user ID
if (!isset($_SESSION['client_role'], $_SESSION['client_token'], $_SESSION['client_user_id'])) {
    header("Location: ../../login?error=session_expired");
} else {
    if ($_SESSION['client_role'] != 'Head') {
        header("Location: ../../head/");
    }
}

$userId = $_SESSION['client_user_id'];

// Validate POST data
if (!isset($_POST['current-password'], $_POST['password'], $_POST['confirm-password'])) {
    header("Location: ./change-password?error=required_fields");
    exit;
}

$currentPassword = trim($_POST['current-password']);
$newPassword = trim($_POST['password']);
$confirmPassword = trim($_POST['confirm-password']);

// Validate input fields
if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
    header("Location: ./change-password?error=required_fields");
    exit;
}

// Check if new password and current password match
if ($newPassword == $currentPassword) {
    header("Location: ./change-password?error=old_new_password_match");
    exit;
}

// Check if new password and confirm password match
if ($newPassword !== $confirmPassword) {
    header("Location: ./change-password?error=password_mismatch");
    exit;
}

// Validate new password strength
if (
    strlen($newPassword) < 12 ||
    !preg_match('/[A-Z]/', $newPassword) ||
    !preg_match('/[a-z]/', $newPassword) ||
    !preg_match('/\d/', $newPassword) ||
    !preg_match('/[!@#$%^&*()\-_=+{}[\]:;"\'<>,.?]/', $newPassword)
) {
    header("Location: ./change-password?error=password_strength");
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Fetch the current password hash from the database
    $query = $conn->prepare(
        "SELECT l.password 
         FROM tb_client_logindetails l
         JOIN tb_client_userdetails u ON l.user_id = u.user_id
         WHERE u.user_id = :user_id AND u.user_status = 1"
    );
    $query->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $query->execute();

    if ($query->rowCount() === 0) {
        header("Location: ./change-password?error=user_not_found");
        exit;
    }

    $user = $query->fetch(PDO::FETCH_ASSOC);
    $currentPasswordHash = $user['password'];

    // Verify the current password
    if (!password_verify($currentPassword, $currentPasswordHash)) {
        header("Location: ./change-password?error=incorrect_password");
        exit;
    }

    // Hash the new password
    $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);

    // Update the password in the database
    $updateQuery = $conn->prepare(
        "UPDATE tb_client_logindetails l
     JOIN tb_client_userdetails u ON l.user_id = u.user_id
     SET l.password = :new_password
     WHERE l.user_id = :user_id AND u.user_status = 1"
    );
    $updateQuery->bindParam(':new_password', $newPasswordHash, PDO::PARAM_STR);
    $updateQuery->bindParam(':user_id', $userId, PDO::PARAM_INT);

    if ($updateQuery->execute()) {

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
        $logAction = "Changed password successfully";
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

        header("Location: ./change-password?error=none");
        exit;
    } else {
        header("Location: ./change-password?error=update_failed");
        exit;
    }
} catch (PDOException $e) {
    error_log("Error updating password: " . $e->getMessage());
    header("Location: ./change-password?error=exception");
    exit;
}
