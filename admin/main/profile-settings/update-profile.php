<?php
session_start();
require_once '../../../config/database.php';

// Check if session variables are set
if (!isset($_SESSION['admin_role'], $_SESSION['admin_token'], $_SESSION['admin_user_id'])) {
    header("Location: ../login?error=session_expired");
}

//Encrypt and decrypt functions
// Load the key and IV from the .meta file
$metaFilePath = "../../../security/key.meta";

// Check if the file exists
if (!file_exists($metaFilePath)) {
    throw new Exception("Key meta file does not exist at $metaFilePath");
}

// Decode the JSON data from the file
$keys = json_decode(file_get_contents($metaFilePath), true);

// Check if JSON decoding was successful
if ($keys === null) {
    throw new Exception("Error decoding JSON from $metaFilePath");
}

// Check if both the 'key' and 'iv' fields are present
if (!isset($keys['key']) || !isset($keys['iv'])) {
    throw new Exception("Key or IV missing in the meta file");
}

// Decode the base64-encoded key and IV
$key = base64_decode($keys['key'], true);
$iv = base64_decode($keys['iv'], true);

// Validate the decoded key and IV lengths
if ($key === false || strlen($key) !== 32) {
    throw new Exception("Invalid AES key. Ensure it is 256 bits (32 bytes) base64 encoded.");
}

if ($iv === false || strlen($iv) !== 16) {
    throw new Exception("Invalid AES IV. Ensure it is 128 bits (16 bytes) base64 encoded.");
}

// Define the constants for key and IV
define('AES_KEY', $key);
define('AES_IV', $iv);



function aesEncrypt($input)
{
    // Add random padding to make the plaintext longer
    $padding = bin2hex(random_bytes(32)); // 64 characters of padding
    $paddedInput = $input . "::" . $padding;

    // Encrypt the padded input
    $encrypted = openssl_encrypt(
        $paddedInput,
        'AES-256-CBC',
        AES_KEY,
        0,
        AES_IV
    );

    // Base64-encode the encrypted string
    return base64_encode($encrypted);
}

function aesDecrypt($input)
{
    // Decode and decrypt the input
    $decrypted = openssl_decrypt(
        base64_decode($input),
        'AES-256-CBC',
        AES_KEY,
        0,
        AES_IV
    );

    // Remove the padding if it exists
    if ($decrypted !== false && strpos($decrypted, "::") !== false) {
        list($originalData,) = explode("::", $decrypted, 2);
        return $originalData;
    }

    return $decrypted;
}
// End of encrypt and decrypt functions
$userId = $_SESSION['admin_user_id'];

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
        "UPDATE tb_admin_userdetails 
         SET user_fname = :firstname, user_lname = :lastname 
         WHERE user_id = :user_id AND user_status = 1"
    );
    $updateQuery->bindParam(':firstname', aesEncrypt($firstname), PDO::PARAM_STR);
    $updateQuery->bindParam(':lastname', aesEncrypt($lastname), PDO::PARAM_STR);
    $updateQuery->bindParam(':user_id', $userId, PDO::PARAM_INT);

    if ($updateQuery->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Profile updated successfully.',
        ]);

        // Fetch the current user's email and role
        $doerUserId = $_SESSION['admin_user_id'];
        $userStmt = $conn->prepare("
            SELECT user_email, user_role 
            FROM tb_admin_userdetails 
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
