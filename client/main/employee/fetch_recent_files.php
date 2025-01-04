<?php
// Start session
session_start();

if (!isset($_SESSION['client_user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../../../config/config.php';

// Constants
$userId = $_SESSION['client_user_id'];

try {
    // Initialize PDO connection
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query to fetch files uploaded in the last 4 hours
    $stmt = $pdo->prepare("
        SELECT name, size, created_at 
        FROM tb_files 
        WHERE owner_id = :owner_id 
        AND created_at >= NOW() - INTERVAL 4 HOUR
        ORDER BY created_at DESC
    ");
    $stmt->bindParam(':owner_id', $userId);
    $stmt->execute();

    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'files' => $files]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to fetch recent files: ' . $e->getMessage()]);
}
?>
