<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Include database connection
    require_once '../../../config/config.php';

    // Retrieve form data
    $email = $_POST['email'] ?? '';
    $firstname = $_POST['firstname'] ?? '';
    $lastname = $_POST['lastname'] ?? '';
    $role = $_POST['role'] ?? '';

    // Validate form data
    if (empty($email) || empty($firstname) || empty($lastname)) {
        header("Location: add-client?error=missing_fields");
        exit;
    }

    try {
        $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if email already exists
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM tb_client_userdetails WHERE user_email = :email");
        $checkStmt->bindParam(':email', $email);
        $checkStmt->execute();
        $emailExists = $checkStmt->fetchColumn();

        if ($emailExists > 0) {
            header("Location: add-client?error=email_exists");
            exit;
        }

        // Start a transaction
        $pdo->beginTransaction();

        // Insert into tb_client_userdetails
        $stmt = $pdo->prepare("INSERT INTO tb_client_userdetails (user_fname, user_lname, user_email, user_role) 
                                VALUES (:fname, :lname, :email, :role)");
        $stmt->bindParam(':fname', $firstname);
        $stmt->bindParam(':lname', $lastname);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':role', $role);
        $stmt->execute();

        // Fetch the inserted user ID
        $user_id = $pdo->lastInsertId();

        // Generate and hash the password
        $password = $lastname . '_' . preg_replace("/[^a-zA-Z0-9]/", "", $user_id);
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert into tb_client_logindetails
        $loginStmt = $pdo->prepare("INSERT INTO tb_client_logindetails (password, user_id) 
                                    VALUES (:password, :user_id)");
        $loginStmt->bindParam(':password', $hashed_password);
        $loginStmt->bindParam(':user_id', $user_id);
        $loginStmt->execute();

        // Commit the transaction
        $pdo->commit();

        // Fetch the current user's email
        $doerUserId = $_SESSION['client_user_id'];
        $userStmt = $pdo->prepare("SELECT user_email FROM tb_client_userdetails WHERE user_id = :user_id");
        $userStmt->bindParam(':user_id', $doerUserId);
        $userStmt->execute();
        $logEmail = $userStmt->fetchColumn() ?: 'Unknown';

        // Log the user addition
        $logStmt = $pdo->prepare("INSERT INTO tb_logs (doer, log_action) VALUES (:doer, :action)");
        $logStmt->execute([
            ':doer' => $logEmail,
            ':action' => "$role user $user_id added successfully."
        ]);

        // Return success response
        echo json_encode(["success" => true]);
        header("Location: add-client?error=none");
        exit;
    } catch (PDOException $e) {
        // Rollback the transaction on error
        $pdo->rollBack();
        echo json_encode(["error" => "An error occurred: " . $e->getMessage()]);
        header("Location: add-client?error=server_error");
        exit;
    }
}
header("Location: ../");
exit;
