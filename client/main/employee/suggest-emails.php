<?php
// Start session
session_start();

// Check if the session contains a user ID
if (!isset($_SESSION['client_role'], $_SESSION['client_token'], $_SESSION['client_user_id'])) {
    header("Location: ../../login?error=session_expired");
} else {
    if ($_SESSION['client_role'] == 'Head') {
        header("Location: ../head/");
    }
}

require_once '../../../config/database.php';

if (isset($_GET['query'])) {
    $query = trim($_GET['query']);

    try {
        $db = new Database();
        $conn = $db->getConnection();

        // Fetch emails matching the query
        $stmt = $conn->prepare(
            "SELECT user_email 
             FROM tb_client_userdetails 
             WHERE user_email LIKE :query AND user_role = 'Head' AND user_status = 1 
             LIMIT 10"
        );
        $likeQuery = "%$query%";
        $stmt->bindParam(':query', $likeQuery, PDO::PARAM_STR);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'data' => array_column($results, 'user_email'),
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage(),
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'No query provided.',
    ]);
}
