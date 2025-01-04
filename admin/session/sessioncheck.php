<?php
session_start();
// Check if session variables are set
header('Content-Type: application/json');

if (isset($_SESSION['admin_role'], $_SESSION['admin_token'], $_SESSION['admin_user_id'])) {

    require_once '../../config/database.php';

    if (isset($_SESSION['admin_user_id'])) {
        try {
            $db = new Database();
            $conn = $db->getConnection();

            $userlog = date('Y-m-d H:i:s'); // Current timestamp
            $updateStatusStmt = $conn->prepare(
                "UPDATE tb_admin_logindetails 
                SET token = NULL, 
                    user_status = 'Online', 
                    user_log = :userlog 
                WHERE user_id = :user_id"
            );

            $updateStatusStmt->execute([
                ':userlog' => $userlog,
                ':user_id' => $_SESSION['admin_user_id']
            ]);
        } catch (PDOException $e) {
            error_log("Signout error: " . $e->getMessage());
            // Optionally, you can show a message or log this for further review
        }
    }
    echo json_encode(['sessionValid' => true]);
    exit;
} else {
    echo json_encode(['sessionValid' => false]);
    exit;
}
header("Location: ../login");
exit;
