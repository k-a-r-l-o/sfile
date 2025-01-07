<?php
session_start();

// Check if the session contains a user ID
if (!isset($_SESSION['admin_role'], $_SESSION['admin_token'], $_SESSION['admin_user_id'])) {
    header("Location: ../../login?error=session_expired");
    exit;
}

// Encrypt and decrypt functions
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Include database connection
    require_once '../../../config/config.php';

    // Retrieve form data and encrypt it
    $Id = $_POST['user_id'] ?? '';
    $email = aesEncrypt($_POST['email']) ?? '';
    $firstname = aesEncrypt($_POST['firstname']) ?? '';
    $lastname = aesEncrypt($_POST['lastname']) ?? '';
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

        // Update tb_client_userdetails (only updating the encrypted first name, last name, and role)
        $stmt = $pdo->prepare("UPDATE tb_client_userdetails 
                               SET user_email = :email, user_fname = :fname, user_lname = :lname, user_role = :role
                               WHERE user_id = :Id");
        $stmt->bindParam(':email', $email);
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

        // Folder management
        $basePath = $_SERVER['DOCUMENT_ROOT'] . "/security/keys/";
        $oldRoleFolder = ($role === 'Head') ? 'employee' : 'head'; // Previous role folder
        $newRoleFolder = ($role === 'Head') ? 'head' : 'employee'; // New role folder
        $oldFolderPath = $basePath . "$oldRoleFolder/$Id"; // Old folder path
        $newFolderPath = $basePath . "$newRoleFolder/$Id"; // New folder path

        // Check if the old folder exists
        if (file_exists($oldFolderPath) && is_dir($oldFolderPath)) {
            // Move the entire folder to the new location
            if (!rename($oldFolderPath, $newFolderPath)) {
                die("Failed to move folder from '$oldFolderPath' to '$newFolderPath'.");
            }
        } else {
            // If the folder doesn't exist, create the new folder
            if (!file_exists($newFolderPath)) {
                if (!mkdir($newFolderPath, 0755, true)) {
                    die("Failed to create folder '$newFolderPath'.");
                }
            }
        }

        // Optionally, log or create a new file in the folder for verification
        file_put_contents("$newFolderPath", "Folder moved successfully for user ID $Id.");


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
