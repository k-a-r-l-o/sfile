<?php
// Start session
session_start();

// Check if the session contains a user ID
if (!isset($_SESSION['client_role'], $_SESSION['client_token'], $_SESSION['client_user_id'])) {
    header("Location: ../../login?error=session_expired");
    exit;
} else {
    if ($_SESSION['client_role'] == 'Head') {
        header("Location: ../head/");
        exit;
    }
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

require_once '../../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the email is provided in the POST data
    if (!isset($_POST['fileid']) || empty($_POST['fileid'])) {
        header("Location: my-files?error=invalid_request");
        exit;
    }
    if (!isset($_POST['filenameInput']) || empty($_POST['filenameInput'])) {
        header("Location: my-files?error=invalid_request");
        exit;
    }
    if (isset($_POST['email']) && !empty($_POST['email'])) {
        $email = trim($_POST['email']);

        try {
            $db = new Database();
            $conn = $db->getConnection();

            // Check if there are any users with the role 'Head' and status 1
            $stmt = $conn->prepare(
                "SELECT user_email 
                FROM tb_client_userdetails 
                WHERE user_role = 'Head' AND user_status = 1"
            );
            $stmt->execute();

            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $validUser = null;

            // Loop through all users to find a match
            foreach ($users as $user) {
                if (aesDecrypt($user['user_email']) === $email) {
                    $validUser = $user;
                    break;
                }
            }

            if ($validUser) {
                header("Location: verify-password?email=$email&id=" . $_POST['fileid'] . "&file=" . $_POST['filenameInput']);
            } else {
                header("Location: share-file?id=" . $_POST['fileid'] . "&file=" . $_POST['filenameInput'] . "&error=email_not_found");
            }
        } catch (PDOException $e) {
            //header("Location: share-file?error=server_error");
            echo $e->getMessage();
            exit;
        }
    } else {
        header("Location: my-files?error=invalid_request");
        exit;
    }
} else {
    header("Location: my-files?error=invalid_request");
}
