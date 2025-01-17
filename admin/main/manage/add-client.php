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

        // Encrypt sensitive data
        $encryptedEmail = aesEncrypt($email);
        $encryptedFirstname = aesEncrypt($firstname);
        $encryptedLastname = aesEncrypt($lastname);

        // Check if email already exists
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM tb_client_userdetails WHERE user_email = :email AND user_status = '1'");
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

        // Insert into tb_client_logindetails
        $loginStmt = $pdo->prepare("INSERT INTO tb_client_logindetails (password, user_id) 
                                    VALUES (:password, :user_id)");
        $loginStmt->bindParam(':password', $hashed_password);
        $loginStmt->bindParam(':user_id', $user_id);
        $loginStmt->execute();

        // Commit the transaction
        $pdo->commit();

        $rolefolder = '';
        if ($role == 'Head') {
            $rolefolder = 'head';
        } else {
            $rolefolder = 'employee';
        }
        $folderPath = $_SERVER['DOCUMENT_ROOT'] . "/security/keys/$rolefolder"; // Absolute path
        $folderName = $user_id;
        $newFolderPath = rtrim($folderPath, '/') . "/" . $folderName;

        // Check if the folder exists, and create it if necessary
        if (!file_exists($newFolderPath)) {
            if (!mkdir($newFolderPath, 0755, true)) {
                die("Failed to create folder '$newFolderPath'.");
            }
        }

        $configargs = array(
            "config" => __DIR__ . '/../../../security/openssl.cnf',
            'private_key_bits' => 2048, // Key size: 2048 bits
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        );

        $res = openssl_pkey_new($configargs);
        if (!$res) {
            die("Failed to generate keys: " . openssl_error_string());
        }

        // Generate the private key and encrypt it using the user's password
        $privateKey = null;
        $passphrase = $password;  // Use the generated password to encrypt the private key

        // Encrypt the private key with the password (passphrase)
        if (!openssl_pkey_export($res, $privateKey, $passphrase, $configargs)) {
            die("Failed to export encrypted private key: " . openssl_error_string());
        }

        // Extract the public key
        $keyDetails = openssl_pkey_get_details($res);
        $publicKey = $keyDetails["key"];

        // Save the keys to the specific folder
        file_put_contents("$newFolderPath/private_key.enc", $privateKey);
        file_put_contents("$newFolderPath/public_key.pem", $publicKey);

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
        $logAction = "$role user $user_id added successfully.";
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
