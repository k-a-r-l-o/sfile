<?php
session_start();

// Check if the session contains a user ID
if (!isset($_SESSION['admin_role'], $_SESSION['admin_token'], $_SESSION['admin_user_id'])) {
    header("Location: ../../login?error=session_expired");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Include database connection
    require_once '../../../config/config.php';

    // Retrieve form data
    $Id = $_POST['user_id'] ?? '';
    $email = $_POST['email'] ?? '';
    $firstname = $_POST['firstname'] ?? '';
    $lastname = $_POST['lastname'] ?? '';
    $role = $_POST['role'] ?? '';

    // Validate form data
    if (empty($Id) || empty($firstname) || empty($lastname) || empty($role)) {
        header("Location: edit-client?error=missing_fields&id=$Id&email=$email&fname=$firstname&lname=$lastname&role=$role");
        exit;
    }

    try {
        $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if user exists in the database
        $checkUserStmt = $pdo->prepare("SELECT COUNT(*) FROM tb_client_userdetails WHERE user_id = :Id");
        $checkUserStmt->bindParam(':Id', $Id);
        $checkUserStmt->execute();
        $userExists = $checkUserStmt->fetchColumn();

        if ($userExists == 0) {
            // If the user doesn't exist, redirect with an error
            header("Location: edit-client?error=user_not_found&id=$Id&email=$email&fname=$firstname&lname=$lastname&role=$role");
            exit;
        }

        // Start a transaction
        $pdo->beginTransaction();

        // Update tb_client_userdetails (only updating the first name and last name)
        $stmt = $pdo->prepare("UPDATE tb_client_userdetails 
                               SET user_fname = :fname, user_lname = :lname, user_role = :role
                               WHERE user_id = :Id");
        $stmt->bindParam(':fname', $firstname);
        $stmt->bindParam(':lname', $lastname);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':Id', $Id);
        $stmt->execute();

        // Commit the transaction
        $pdo->commit();

        // Fetch the current user's email and role for logging purposes
        $doerUserId = $_SESSION['admin_user_id'];
        $userStmt = $pdo->prepare("SELECT user_email, user_role FROM tb_admin_userdetails WHERE user_id = :user_id");
        $userStmt->bindParam(':user_id', $doerUserId);
        $userStmt->execute();
        $userDetails = $userStmt->fetch(PDO::FETCH_ASSOC);
        $logRole = $userDetails['user_role'] ?? 'Unknown';

        // Log the edit action
        $logAction = "$role user $Id updated successfully.";
        $logAction = "Administrator user $user_id added successfully.";
        $logdate = date('Y-m-d H:i:s');
        $logStmt = $pdo->prepare("
            INSERT INTO tb_logs (doer, log_date, role, log_action) 
            VALUES (:doer, :log_date, :role, :action)
        ");
        $logStmt->execute([
            ':doer' => $doerUserId,
            ':log_date' => $logdate,
            ':role' => $logRole,
            ':action' => $logAction
        ]);

        // Return success response
        echo json_encode(["success" => true]);
        header("Location: edit-client?error=none&id=$Id&email=$email&fname=$firstname&lname=$lastname&role=$role");
        exit;
    } catch (PDOException $e) {
        // Rollback the transaction on error
        $pdo->rollBack();
        echo json_encode(["error" => "An error occurred: " . $e->getMessage()]);
        header("Location: edit-client?error=server_error&id=$Id&email=$email&fname=$firstname&lname=$lastname&role=$role");
        exit;
    }
}
header("Location: ../");
exit;
