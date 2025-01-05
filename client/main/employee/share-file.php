<?php
// Start session
session_start();

// Check if the session contains a user ID
if (!isset($_SESSION['client_role'], $_SESSION['client_token'], $_SESSION['client_user_id'])) {
    header("Location: ../../login?error=session_expired");
    exit;
} else {
    if ($_SESSION['client_role'] == 'Head') {
        header("Location: ../head/");
        exit;
    }
}

require_once '../../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the email is provided in the POST data
    if (!isset($_POST['fileid']) || empty($_POST['fileid'])) {
        header("Location: my-files?error=invalid_request");
        exit;
    }
    if (!isset($_POST['filenameInput']) || empty($_POST['filenameInput'])) {
        header("Location: my-files?error=invalid_request");
        exit;
    }
    if (isset($_POST['email']) && !empty($_POST['email'])) {
        $email = trim($_POST['email']);

        try {
            $db = new Database();
            $conn = $db->getConnection();

            // Check if the email exists in the database
            $stmt = $conn->prepare(
                "SELECT user_email 
                 FROM tb_client_userdetails 
                 WHERE user_email = :email AND user_role = 'Head' AND user_status = 1"
            );
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                header("Location: verify-password?email=$email&id=" . $_POST['fileid'] . "&file=" . $_POST['filenameInput']);
            } else {
                header("Location: share-file?id=" . $_POST['fileid'] . "&file=" . $_POST['filenameInput'] . "&error=email_not_found");
            }
        } catch (PDOException $e) {
            header("Location: ../share-file?error=server_error");
            exit;
        }
    } else {
        header("Location: my-files?error=invalid_request");
        exit;
    }
} else {
    header("Location: my-files?error=invalid_request");
}
