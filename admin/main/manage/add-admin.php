<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Include database connection
    require_once '../../../config/config.php';

    // Retrieve form data
    $email = $_POST['email'] ?? '';
    $firstname = $_POST['firstname'] ?? '';
    $lastname = $_POST['lastname'] ?? '';

    // Validate form data
    if (empty($email) || empty($firstname) || empty($lastname)) {
        header("Location: add-admin?error=missing_fields");
        exit;
    }

    try {
        $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if email already exists
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM tb_admin_userdetails WHERE user_email = :email AND user_status = '1'");
        $checkStmt->bindParam(':email', $email);
        $checkStmt->execute();
        $emailExists = $checkStmt->fetchColumn();

        if ($emailExists > 0) {
            header("Location: add-admin?error=email_exists");
            exit;
        }

        // Start a transaction
        $pdo->beginTransaction();
        $role = 'Administrator';

        // Insert into tb_admin_userdetails
        $stmt = $pdo->prepare("INSERT INTO tb_admin_userdetails (user_fname, user_lname, user_email, user_role) 
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

        // Insert into tb_admin_logindetails
        $loginStmt = $pdo->prepare("INSERT INTO tb_admin_logindetails (password, user_id) 
                                    VALUES (:password, :user_id)");
        $loginStmt->bindParam(':password', $hashed_password);
        $loginStmt->bindParam(':user_id', $user_id);
        $loginStmt->execute();

        // Commit the transaction
        $pdo->commit();

        // Fetch the current user's email and role
        $doerUserId = $_SESSION['admin_user_id'];
        $userStmt = $pdo->prepare("
            SELECT user_email, user_role 
            FROM tb_admin_userdetails 
            WHERE user_id = :user_id
        ");
        $userStmt->bindParam(':user_id', $doerUserId);
        $userStmt->execute();
        $userDetails = $userStmt->fetch(PDO::FETCH_ASSOC);
        $logRole = $userDetails['user_role'] ?? 'Unknown';

        // Log the user addition
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
        header("Location: add-admin?error=none");
        exit;
    } catch (PDOException $e) {
        // Rollback the transaction on error
        $pdo->rollBack();
        echo json_encode(["error" => "An error occurred: " . $e->getMessage()]);
        header("Location: add-admin?error=server_error");
        exit;
    }
}
header("Location: ../");
exit;
