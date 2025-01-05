<?php
session_start();

// Check if session variables are set
if (!isset($_SESSION['client_role'], $_SESSION['client_token'], $_SESSION['client_user_id'])) {
    header("Location: ../../login?error=session_expired");
} else {
    if ($_SESSION['client_role'] == 'Employee') {
        header("Location: ../employee/");
    }
}

// Include the configuration file
require_once __DIR__ . '/../../../config/config.php';

try {
    // Establish a PDO connection
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get the owner ID from query parameters
    $ownerId = $_GET['id'] ?? null;

    // Check if the current session user ID is available
    $sessionUserId = $_SESSION['client_user_id'];

    if (!$ownerId && !$sessionUserId) {
        echo json_encode(["error" => "Missing required parameters."]);
        exit;
    }

    // Define the base SQL query with ownership and sharing logic
    $sql = "
        SELECT 
            f.file_id,
            f.name,
            f.size,
            f.created_at,
            fs.shared_at,
            f.owner_id
        FROM tb_files f
        INNER JOIN tb_shared_files fs ON f.file_id = fs.file_id AND fs.shared_to = :session_user_id
        WHERE f.status = 1
    ";

    // Filters based on ownership or sharing
    if ($ownerId) {
        $sql .= " AND f.owner_id = :owner_id";
    } else {
        $sql .= " AND fs.shared_to = :session_user_id";
    }

    // Capture filters from query parameters
    $filters = [];
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $filters['search'] = "%" . $_GET['search'] . "%";
        $sql .= " AND f.name LIKE :search";
    }

    if (isset($_GET['time-filter']) && !empty($_GET['time-filter'])) {
        $timeFilter = $_GET['time-filter'];
        if ($timeFilter === "Week") {
            $sql .= " AND f.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
        } elseif ($timeFilter === "Month") {
            $sql .= " AND f.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        } elseif ($timeFilter === "Year") {
            $sql .= " AND f.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        }
    }

    // Pagination logic
    $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? intval($_GET['limit']) : 10;
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
    $offset = ($page - 1) * $limit;

    $sql .= " LIMIT :limit OFFSET :offset";

    // Prepare the statement
    $stmt = $pdo->prepare($sql);

    // Bind parameters
    $stmt->bindParam(':session_user_id', $sessionUserId, PDO::PARAM_STR);
    if ($ownerId) {
        $stmt->bindParam(':owner_id', $ownerId, PDO::PARAM_STR);
    }
    if (isset($filters['search'])) {
        $stmt->bindParam(':search', $filters['search'], PDO::PARAM_STR);
    }
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

    // Execute the query
    $stmt->execute();

    // Fetch the results
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get the total count of records for pagination metadata
    $countSql = "
        SELECT COUNT(*) as total
        FROM tb_files f
        INNER JOIN tb_shared_files fs ON f.file_id = fs.file_id AND fs.shared_to = :session_user_id
        WHERE f.status = 1
    ";

    if ($ownerId) {
        $countSql .= " AND f.owner_id = :owner_id";
    } else {
        $countSql .= " AND fs.shared_to = :session_user_id";
    }

    if (isset($filters['search'])) {
        $countSql .= " AND f.name LIKE :search";
    }

    $countStmt = $pdo->prepare($countSql);
    $countStmt->bindParam(':session_user_id', $sessionUserId, PDO::PARAM_STR);
    if ($ownerId) {
        $countStmt->bindParam(':owner_id', $ownerId, PDO::PARAM_STR);
    }
    if (isset($filters['search'])) {
        $countStmt->bindParam(':search', $filters['search'], PDO::PARAM_STR);
    }
    $countStmt->execute();
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Prepare the response with metadata
    $response = [
        "data" => $files,
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
    echo json_encode(["error" => "An error occurred while fetching files"]);
    exit;
}
