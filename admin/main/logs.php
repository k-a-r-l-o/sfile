<?php
session_start();

// Check if session variables are set
if (!isset($_SESSION['admin_role'], $_SESSION['admin_token'], $_SESSION['admin_user_id'])) {
    header("Location: ../login?error=session_expired");
}

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
            `name` LIKE :search OR 
            `activity` LIKE :search OR 
            `email_address` LIKE :search
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
            `name` LIKE :search OR 
            `activity` LIKE :search OR 
            `email_address` LIKE :search
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
