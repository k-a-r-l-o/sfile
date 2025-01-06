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

require_once __DIR__ . '/../../config/config.php';

try {
    // Establish PDO connection
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Base SQL query
    $sql = "
        SELECT * FROM (
            SELECT * FROM view_admin_activity_logs
            UNION ALL
            SELECT * FROM view_client_activity_logs
            UNION ALL
            SELECT * FROM view_system_generated_logs
        ) AS logs
        WHERE 1=1
    ";

    // Filters
    $filters = [];
    if (isset($_GET['role']) && !empty($_GET['role'])) {
        $filters['role'] = $_GET['role'];
        $sql .= " AND `role` = :role";
    }

    if (isset($_GET['time-filter']) && !empty($_GET['time-filter'])) {
        $filters = $_GET['time-filter'];
        if ($filters === "thisWeek") {
            $sql .= " AND `date_time` >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
        } elseif ($filters === "thisMonth") {
            $sql .= " AND `date_time` >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        } elseif ($filters === "thisYear") {
            $sql .= " AND YEAR(`date_time`) = YEAR(NOW())";
        }
    }

    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $filters['search'] = "%" . $_GET['search'] . "%";
        $sql .= " AND (
            aesDecrypt(`name`) LIKE :search OR 
            `activity` LIKE :search OR 
            aesDecrypt(`email_address`) LIKE :search
        )";
    }

    // Pagination
    $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? intval($_GET['limit']) : 10;
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
    $offset = ($page - 1) * $limit;

    $sql .= " ORDER BY `date_time` DESC, `log_id` DESC";
    $sql .= " LIMIT :limit OFFSET :offset";

    // Prepare the statement
    $stmt = $pdo->prepare($sql);

    // Bind parameters
    if (isset($filters['role'])) {
        $stmt->bindParam(':role', $filters['role'], PDO::PARAM_STR);
    }
    if (isset($filters['search'])) {
        $stmt->bindParam(':search', $filters['search'], PDO::PARAM_STR);
    }
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

    // Execute and fetch results
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Decrypt the relevant fields
    foreach ($logs as &$log) {
        if ($log['fname'] == $log['lname']) {
            $log['fname'] = aesDecrypt($log['fname']);
            $log['lname'] = " ";
        } else {
            $log['fname'] = aesDecrypt($log['fname']);
            $log['lname'] = aesDecrypt($log['lname']);
        }
        if ($log['fname'] == "System") {
            $log['email_address'] = " N/A";
        } else {
            $log['email_address'] = aesDecrypt($log['email_address']);
        }
    }

    // Count total records
    $countSql = "
        SELECT COUNT(*) AS total FROM (
            SELECT * FROM view_admin_activity_logs
            UNION ALL
            SELECT * FROM view_client_activity_logs
            UNION ALL
            SELECT * FROM view_system_generated_logs
        ) AS logs
        WHERE 1=1
    ";

    if (isset($filters['role'])) {
        $countSql .= " AND `role` = :role";
    }
    if (isset($filters['search'])) {
        $countSql .= " AND (
            aesDecrypt(`name`) LIKE :search OR 
            `activity` LIKE :search OR 
            aesDecrypt(`email_address`) LIKE :search
        )";
    }

    $countStmt = $pdo->prepare($countSql);
    if (isset($filters['role'])) {
        $countStmt->bindParam(':role', $filters['role'], PDO::PARAM_STR);
    }
    if (isset($filters['search'])) {
        $countStmt->bindParam(':search', $filters['search'], PDO::PARAM_STR);
    }
    $countStmt->execute();
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Prepare the response
    $response = [
        "data" => $logs,
        "pagination" => [
            "current_page" => $page,
            "per_page" => $limit,
            "total_records" => $total,
            "total_pages" => ceil($total / $limit),
        ],
    ];

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(["error" => "An error occurred while fetching logs"]);
}
exit;
