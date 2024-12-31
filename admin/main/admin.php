<?php
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
            tb_admin_userdetails u
        LEFT JOIN 
            tb_admin_logindetails l 
        ON 
            u.user_id = l.user_id
        WHERE 
            1=1
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
        FROM tb_admin_userdetails u
        LEFT JOIN tb_admin_logindetails l 
        ON u.user_id = l.user_id
        WHERE 1=1
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
