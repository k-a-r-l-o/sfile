<?php
session_start();

// Check if session variables are set
if (!isset($_SESSION['admin_role'], $_SESSION['admin_token'], $_SESSION['admin_user_id'])) {
    header("Location: ../login?error=session_expired");
}

//Encrypt and decrypt functions
// Load the key and IV from the .meta file
$metaFilePath = "../../security/key.meta";

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
// Include the configuration file
require_once __DIR__ . '/../../config/config.php';

try {
    // Establish a PDO connection
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch all user details
    $sql = "
        SELECT 
            u.user_id, 
            u.user_fname, 
            u.user_lname, 
            u.user_email, 
            u.user_role, 
            u.user_status, 
            l.user_status
        FROM 
            tb_admin_userdetails u
        LEFT JOIN 
            tb_admin_logindetails l 
        ON 
            u.user_id = l.user_id
        WHERE 
            u.user_status = 1
    ";

    // Execute the query
    $stmt = $pdo->query($sql);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Decrypt the data
    foreach ($users as &$user) {
        $user['user_fname'] = aesDecrypt($user['user_fname']);
        $user['user_lname'] = aesDecrypt($user['user_lname']);
        $user['user_email'] = aesDecrypt($user['user_email']);
    }

    // Filter results if a search query is provided
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $searchTerm = strtolower(trim($_GET['search']));
        $users = array_filter($users, function ($user) use ($searchTerm) {
            return stripos($user['user_id'], $searchTerm) !== false ||
                stripos($user['user_fname'], $searchTerm) !== false ||
                stripos($user['user_lname'], $searchTerm) !== false ||
                stripos($user['user_email'], $searchTerm) !== false;
        });
    }

    // Pagination logic
    $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? intval($_GET['limit']) : 10; // Default limit: 10
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1; // Default page: 1
    $offset = ($page - 1) * $limit;

    $total = count($users);
    $users = array_slice($users, $offset, $limit);

    // Prepare the response with metadata
    $response = [
        "data" => array_values($users), // Reset array keys for JSON consistency
        "pagination" => [
            "current_page" => $page,
            "per_page" => $limit,
            "total_records" => $total,
            "total_pages" => ceil($total / $limit),
        ],
    ];

    // Return the result as JSON
    header('Content-Type: application/json');
    echo json_encode($response);

    // Close the connection
    $pdo = null;
} catch (PDOException $e) {
    // Log the error and return a JSON error message
    error_log("Error: " . $e->getMessage());
    echo json_encode(["error" => "An error occurred while fetching users"]);
    exit;
}
