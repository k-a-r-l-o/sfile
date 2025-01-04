<?php
session_start();

// Check if session variables are set
if (!isset($_SESSION['admin_role'], $_SESSION['admin_token'], $_SESSION['admin_user_id'])) {
    header("Location: ../login?error=session_expired");
    exit;
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
    if (!empty($_GET['role'])) {
        $filters['role'] = htmlspecialchars($_GET['role']);
        $sql .= " AND `role` = :role";
    }

    if (!empty($_GET['time-filter'])) {
        $timeFilter = $_GET['time-filter'];
        if ($timeFilter === "Week") {
            $sql .= " AND `date_time` >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
        } elseif ($timeFilter === "Month") {
            $sql .= " AND `date_time` >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        } elseif ($timeFilter === "Year") {
            $sql .= " AND `date_time` >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        }
    }

    if (!empty($_GET['search'])) {
        $filters['search'] = "%" . htmlspecialchars($_GET['search']) . "%";
        $sql .= " AND (
            `name` LIKE :search OR 
            `activity` LIKE :search OR 
            `email_address` LIKE :search
        )";
    }

    // Pagination
    $limit = max(1, intval($_GET['limit'] ?? 10));
    $page = max(1, intval($_GET['page'] ?? 1));
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
    $countSql = str_replace("*", "COUNT(*) AS total", $sql);
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
    echo json_encode(["error" => "Unable to fetch logs. Please try again later."]);
}
exit;
