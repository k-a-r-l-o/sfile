<?php
session_start();

// Check if the session contains a user ID
if (!isset($_SESSION['client_role'], $_SESSION['client_token'], $_SESSION['client_user_id'])) {
    header("Location: ../../login?error=session_expired");
    exit;
}

// Redirect Head role to their dashboard
if ($_SESSION['client_role'] == 'Head') {
    header("Location: ../head/");
    exit;
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
            file_id,
            name AS filename,
            size AS file_size,
            created_at AS uploaded_at
        FROM 
            tb_files
        WHERE 
            status = 1 AND owner_id = :owner_id
    ";

    // Apply filters
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = "%" . $_GET['search'] . "%";
        $sql .= " AND name LIKE :search";
    }

    if (isset($_GET['time-filter']) && !empty($_GET['time-filter'])) {
        $timeFilter = $_GET['time-filter'];
        if ($timeFilter === "Week") {
            $sql .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
        } elseif ($timeFilter === "Month") {
            $sql .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        } elseif ($timeFilter === "Year") {
            $sql .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        }
    }

    // Pagination logic
    $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? intval($_GET['limit']) : 10; // Default limit: 10
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1; // Default page: 1
    $offset = ($page - 1) * $limit;

    $sql .= " LIMIT :limit OFFSET :offset";

    // Prepare the statement
    $stmt = $pdo->prepare($sql);

    // Bind parameters
    $stmt->bindParam(':owner_id', $_SESSION['client_user_id'], PDO::PARAM_INT);
    if (isset($search)) {
        $stmt->bindParam(':search', $search, PDO::PARAM_STR);
    }
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

    // Execute the query
    $stmt->execute();
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count total records for pagination
    $countSql = "
        SELECT COUNT(*) AS total 
        FROM tb_files
        WHERE status = 1 AND owner_id = :owner_id
    ";

    if (isset($search)) {
        $countSql .= " AND name LIKE :search";
    }

    $countStmt = $pdo->prepare($countSql);
    $countStmt->bindParam(':owner_id', $_SESSION['client_user_id'], PDO::PARAM_INT);
    if (isset($search)) {
        $countStmt->bindParam(':search', $search, PDO::PARAM_STR);
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
    echo json_encode(["error" => "An error occurred while fetching files."]);
    exit;
}
