<?php
session_start();
require_once '../../../../config/database.php';

// Check if the session contains a user ID
if (!isset($_SESSION['client_role'], $_SESSION['client_token'], $_SESSION['client_user_id'])) {
  header("Location: ../../../login?error=session_expired");
} else {
  if ($_SESSION['client_role'] == 'Head') {
    header("Location: ../../head/");
  }
}


//Encrypt and decrypt functions
// Load the key and IV from the .meta file
$metaFilePath = "../../../../security/key.meta";

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

  $user['user_fname'] = aesDecrypt($user['user_fname']);
  $user['user_lname'] = aesDecrypt($user['user_lname']);
  $user['user_email'] = aesDecrypt($user['user_email']);
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
