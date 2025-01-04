<?php
session_start();
require_once '../../../../config/database.php';

// Check if the session contains a user ID
if (!isset($_SESSION['client_role'], $_SESSION['client_token'], $_SESSION['client_user_id'])) {
  header("Location: ../../../login?error=session_expired");
} else {
  if ($_SESSION['client_role'] == 'Employee') {
    header("Location: ../../employee/");
  }
}

$userId = $_SESSION['client_user_id'];

try {
  $db = new Database();
  $conn = $db->getConnection();

  // Fetch user details from the database
  $query = $conn->prepare(
    "SELECT user_id, user_fname, user_lname, user_email, user_role 
         FROM tb_client_userdetails 
         WHERE user_id = :user_id AND user_status = 1"
  );
  $query->bindParam(':user_id', $userId, PDO::PARAM_INT);
  $query->execute();

  // Check if the user exists
  if ($query->rowCount() === 0) {
    echo "User not found.";
    exit;
  }

  $user = $query->fetch(PDO::FETCH_ASSOC);

  // Return the user details as JSON
  echo json_encode([
    'status' => 'success',
    'data' => $user,
  ]);
} catch (PDOException $e) {
  error_log("Error fetching user details: " . $e->getMessage());
  echo json_encode([
    'status' => 'error',
    'message' => 'An error occurred while fetching user details.',
  ]);
}
