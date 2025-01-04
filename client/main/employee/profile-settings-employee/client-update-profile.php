<?php
session_start();
require_once '../../../../config/config.php';  // Adjust path if necessary

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $user_id = $_POST['user_id'] ?? '';
    $email = $_POST['email'] ?? '';
    $firstname = $_POST['firstname'] ?? '';
    $lastname = $_POST['lastname'] ?? '';
    $role = $_POST['role'] ?? '';

    // Check if necessary fields are provided
    if (empty($user_id) || empty($firstname) || empty($lastname)) {
        $_SESSION['error_message'] = "Missing required fields.";
        header("Location: edit-client.php?error=missing_fields&id=$user_id&email=$email");
        exit;
    }

    // Connect to the database
    try {
        $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare the SQL query to update the client profile
        $stmt = $pdo->prepare("UPDATE tb_client_userdetails 
                               SET user_fname = :firstname, user_lname = :lastname
                               WHERE user_id = :user_id");

        // Execute the query
        $stmt->execute([
            ':firstname' => $firstname,
            ':lastname' => $lastname,
            ':user_id' => $user_id
        ]);

        // Check if update was successful
        if ($stmt->rowCount() > 0) {
            $_SESSION['success_message'] = "Profile updated successfully!";
        } else {
            $_SESSION['error_message'] = "No changes were made or profile update failed.";
        }

        // Redirect to the profile page with success message
        header("Location: edit-client.php?success=true&id=$user_id&email=$email&fname=$firstname&lname=$lastname");
        exit;
    } catch (PDOException $e) {
        // Error handling
        $_SESSION['error_message'] = "An error occurred: " . $e->getMessage();
        header("Location: edit-client.php?error=server_error&id=$user_id&email=$email");
        exit;
    }
} else {
    // If not a POST request, redirect back to the form
    header("Location: ../");
    exit;
}
?>
