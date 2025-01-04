<?php
session_start();
require_once '../../../../config/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=unauthorized");
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Fetch user details
    $query = "SELECT user_id, user_fname, user_lname, user_email, user_role FROM tb_client_userdetails WHERE user_id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header("Location: profile-settings?error=user_not_found");
        exit;
    }
} catch (PDOException $e) {
    error_log("Error fetching user details: " . $e->getMessage());
    header("Location: profile-settings?error=database_error");
    exit;
}

// Handle form submission for updating first name and last name
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);

    if (empty($firstname) || empty($lastname)) {
        header("Location: profile-settings?error=empty_fields");
        exit;
    }

    try {
        // Update first name and last name
        $updateQuery = "UPDATE tb_client_userdetails SET user_fname = :firstname, user_lname = :lastname WHERE user_id = :user_id";
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->bindParam(':firstname', $firstname, PDO::PARAM_STR);
        $updateStmt->bindParam(':lastname', $lastname, PDO::PARAM_STR);
        $updateStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $updateStmt->execute();

        header("Location: profile-settings?success=profile_updated");
        exit;
    } catch (PDOException $e) {
        error_log("Error updating user details: " . $e->getMessage());
        header("Location: profile-settings?error=database_error");
        exit;
    }
}
?>
