<?php
session_start();

// Check if the session contains a user ID
if (!isset($_SESSION['admin_role'], $_SESSION['admin_token'], $_SESSION['admin_user_id'])) {
    header("Location: ../../login?error=session_expired");
    exit;
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../../../config/config.php';

    // Retrieve and encrypt form data
    $email = $_POST['email'] ?? '';
    $firstname = $_POST['firstname'] ?? '';
    $lastname = $_POST['lastname'] ?? '';

    if (empty($email) || empty($firstname) || empty($lastname)) {
        header("Location: add-admin?error=missing_fields");
        exit;
    }

    try {
        $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Encrypt sensitive data
        $encryptedEmail = aesEncrypt($email);
        $encryptedFirstname = aesEncrypt($firstname);
        $encryptedLastname = aesEncrypt($lastname);

        // Check if email already exists
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM tb_admin_userdetails WHERE user_email = :email AND user_status = '1'");
        $checkStmt->bindParam(':email', $encryptedEmail);
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
        $stmt->bindParam(':fname', $encryptedFirstname);
        $stmt->bindParam(':lname', $encryptedLastname);
        $stmt->bindParam(':email', $encryptedEmail);
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

        // Logging and redirection code remains the same...

        echo json_encode(["success" => true]);
        header("Location: add-admin?error=none");
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(["error" => "An error occurred: " . $e->getMessage()]);
        header("Location: add-admin?error=server_error");
        exit;
    }
}
header("Location: ../");
exit;
