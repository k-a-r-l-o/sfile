<?php
session_start();

// Check if session variables are set
if (!isset($_SESSION['client_role'], $_SESSION['client_token'], $_SESSION['client_user_id'])) {
    header("Location: ../login?error=session_expired");
}

// Include the configuration file
require_once __DIR__ . '/../../../config/config.php';

try {
    // Establish a PDO connection
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Define the base SQL query
    $sql = "
        SELECT 
            name,
            size,
            created_at
        FROM 
            tb_files
        WHERE 
            status = 1 AND owner_id = :owner_id
    ";

    // Capture the filter parameters from the query string
    $filters = [];
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $filters['search'] = "%" . $_GET['search'] . "%";
        $sql .= " AND (
            `name` LIKE :search
        )";
    }

    if (isset($_GET['time-filter']) && !empty($_GET['time-filter'])) {
        $filters = $_GET['time-filter'];
        if ($filters === "Week") {
            $sql .= " AND `date_time` >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
        } elseif ($filters === "Month") {
            $sql .= " AND `date_time` >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        } elseif ($filters === "Year") {
            $sql .= " AND `date_time` >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        }
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

    $stmt->bindParam(':owner_id', $_SESSION['client_user_id'], PDO::PARAM_STR);
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
        FROM tb_files
        WHERE status = 1 AND owner_id = :owner_id
    ";

    /*
    if (isset($filters['role'])) {
        $countSql .= " AND user_role = :role";
    }
    if (isset($filters['status'])) {
        $countSql .= " AND l.user_status = :status";
    }*/

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
    $countStmt->bindParam(':owner_id', $_SESSION['client_user_id'], PDO::PARAM_STR);
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
