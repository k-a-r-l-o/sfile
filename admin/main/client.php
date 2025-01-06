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

    // Define the base SQL query
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
            tb_client_userdetails u
        LEFT JOIN 
            tb_client_logindetails l 
        ON 
            u.user_id = l.user_id
        WHERE 
            u.user_status = 1
    ";

    // Capture the filter parameters from the query string
    $filters = [];
    if (isset($_GET['role']) && !empty($_GET['role'])) {
        $filters['role'] = $_GET['role'];
        $sql .= " AND user_role = :role";
    }

    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $filters['status'] = $_GET['status'];
        $sql .= " AND l.user_status = :status";
    }

    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $filters['search'] = "%" . $_GET['search'] . "%";
        $sql .= " AND (
            u.user_id LIKE :search OR 
            u.user_fname LIKE :search OR 
            u.user_lname LIKE :search OR 
            u.user_email LIKE :search
        )";
    }

    // Pagination logic
    $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? intval($_GET['limit']) : 10; // Default limit: 10
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1; // Default page: 1
    $offset = ($page - 1) * $limit;

    $sql .= " LIMIT :limit OFFSET :offset";

    // Prepare the statement
    $stmt = $pdo->prepare($sql);

    // Bind parameters for filters
    if (isset($filters['role'])) {
        $stmt->bindParam(':role', $filters['role'], PDO::PARAM_STR);
    }
    if (isset($filters['status'])) {
        $stmt->bindParam(':status', $filters['status'], PDO::PARAM_STR);
    }
    if (isset($filters['search'])) {
        $stmt->bindParam(':search', $filters['search'], PDO::PARAM_STR);
    }

    // Bind pagination parameters
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

    // Execute the query
    $stmt->execute();

    // Fetch the results
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get the total count of records for pagination metadata
    $countSql = "
        SELECT COUNT(*) as total 
        FROM tb_client_userdetails u
        LEFT JOIN tb_client_logindetails l 
        ON u.user_id = l.user_id
        WHERE u.user_status = 1
    ";

    if (isset($filters['role'])) {
        $countSql .= " AND user_role = :role";
    }
    if (isset($filters['status'])) {
        $countSql .= " AND l.user_status = :status";
    }

    if (isset($filters['search'])) {
        $countSql .= " AND (
            u.user_id LIKE :search OR 
            u.user_fname LIKE :search OR 
            u.user_lname LIKE :search OR 
            u.user_email LIKE :search
        )";
    }

    $countStmt = $pdo->prepare($countSql);
    if (isset($filters['role'])) {
        $countStmt->bindParam(':role', $filters['role'], PDO::PARAM_STR);
    }
    if (isset($filters['status'])) {
        $countStmt->bindParam(':status', $filters['status'], PDO::PARAM_STR);
    }
    if (isset($filters['search'])) {
        $countStmt->bindParam(':search', $filters['search'], PDO::PARAM_STR);
    }
    $countStmt->execute();
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Prepare the response with metadata
    $response = [
        "data" => $users,
        "pagination" => [
            "current_page" => $page,
            "per_page" => $limit,
            "total_records" => $total,
            "total_pages" => ceil($total / $limit)
        ]
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
}

exit;
