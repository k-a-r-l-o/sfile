<?php
// Start session
session_start();

// Check if the session contains a user ID
if (!isset($_SESSION['client_role'], $_SESSION['client_token'], $_SESSION['client_user_id'])) {
    header("Location: ../../login?error=session_expired");
    exit;
} else {
    if ($_SESSION['client_role'] == 'Head') {
        header("Location: ../head/");
        exit;
    }
}

require_once __DIR__ . '/../../../config/config.php';

// Constants
$uploadDir = 'uploads/';
$userId = $_SESSION['client_user_id'];

try {
    // Initialize PDO connection
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]));
}

// Helper Functions
function generateAESKey(): string
{
    return openssl_random_pseudo_bytes(32); // 256-bit AES key
}

function encryptFile(string $filePath, string $aesKey, string $iv): string
{
    $fileData = file_get_contents($filePath);
    if ($fileData === false) {
        throw new RuntimeException('Failed to read file data for encryption.');
    }
    $encryptedData = openssl_encrypt($fileData, 'aes-256-cbc', $aesKey, OPENSSL_RAW_DATA, $iv);
    if ($encryptedData === false) {
        throw new RuntimeException('Failed to encrypt file data.');
    }
    return $encryptedData;
}

function getUniqueFileName(PDO $pdo, string $userId, string $fileName): string
{
    $fileInfo = pathinfo($fileName);
    $baseName = $fileInfo['filename'];
    $extension = isset($fileInfo['extension']) ? '.' . $fileInfo['extension'] : '';
    $uniqueName = $baseName . $extension;

    $counter = 1;

    // Check for existing files with the same name in the database
    while (true) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_files WHERE owner_id = :owner_id AND name = :name");
        $stmt->bindParam(':owner_id', $userId);
        $stmt->bindParam(':name', $uniqueName);
        $stmt->execute();

        if ($stmt->fetchColumn() == 0) {
            break; // No duplicate found, use this name
        }

        // Append a counter to the file name
        $uniqueName = $baseName . " ($counter)" . $extension;
        $counter++;
    }

    return $uniqueName;
}

function formatFileSize(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

// Main Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        die(json_encode(['status' => 'error', 'message' => 'File upload error.']));
    }

    // Retrieve user's public key
    $userPublicKeyPath = __DIR__ . '/../../../security/keys/employee/' . $userId . '/public_key.pem';
    if (!file_exists($userPublicKeyPath)) {
        die(json_encode(['status' => 'error', 'message' => 'Public key not found for user ID: ' . $userId]));
    }

    $publicKey = file_get_contents($userPublicKeyPath);
    if ($publicKey === false) {
        die(json_encode(['status' => 'error', 'message' => 'Failed to read public key.']));
    }

    // Create a directory structure based on the user ID
    $folderPath = $_SERVER['DOCUMENT_ROOT'] . "/client/main/employee/uploads/$userId"; // Absolute path
    if (!is_dir($folderPath) && !mkdir($folderPath, 0755, true)) {
        die(json_encode(['status' => 'error', 'message' => "Failed to create folder '$folderPath'."]));
    }

    // Calculate SHA-256 hash for file integrity
    $tempFilePath = $file['tmp_name'];
    $fileHash = hash_file('sha256', $tempFilePath);
    if ($fileHash === false) {
        die(json_encode(['status' => 'error', 'message' => 'Failed to calculate file hash.']));
    }

    // Ensure the uploaded file has a unique name
    $uniqueFileName = getUniqueFileName($pdo, $userId, $file['name']);
    $filePath = $folderPath . '/' . $uniqueFileName;

    // Generate AES key and IV
    $aesKey = generateAESKey();
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));

    // Encrypt the file using AES-256-CBC
    $encryptedData = encryptFile($tempFilePath, $aesKey, $iv);
    $encryptedFilePath = $folderPath . '/' . $uniqueFileName . '.enc';

    // Save the encrypted file
    if (file_put_contents($encryptedFilePath, $encryptedData) === false) {
        die(json_encode(['status' => 'error', 'message' => 'Failed to save encrypted file.']));
    }

    // Encrypt the AES key using the public key
    $encryptedAESKey = '';
    if (!openssl_public_encrypt($aesKey, $encryptedAESKey, $publicKey)) {
        die(json_encode(['status' => 'error', 'message' => 'Failed to encrypt AES key.']));
    }

    $formattedSize = formatFileSize($file['size']);

    // Prepare metadata for the encrypted file
    $metadata = [
        'iv' => base64_encode($iv),
        'encrypted_key' => base64_encode($encryptedAESKey),
        'file_name' => $uniqueFileName,
        'upload_time' => date('Y-m-d H:i:s'),
        'sha256_hash' => $fileHash,
    ];

    // Save metadata as a JSON file
    $metadataFilePath = $folderPath . '/' . $uniqueFileName . '.enc.meta';
    if (file_put_contents($metadataFilePath, json_encode($metadata, JSON_PRETTY_PRINT)) === false) {
        die(json_encode(['status' => 'error', 'message' => 'Failed to save metadata.']));
    }

    // Delete the original file
    if (file_exists($tempFilePath)) {
        unlink($tempFilePath);
    }

    $fileDate = date('Y-m-d H:i:s');

    // Insert file information into the database
    try {
        $stmt = $pdo->prepare("INSERT INTO tb_files (owner_id, name, size, hashed_key, created_at) VALUES (:owner_id, :name, :size, :hashed_key, :created_at)");
        $stmt->bindParam(':owner_id', $userId);
        $stmt->bindParam(':name', $uniqueFileName);
        $stmt->bindParam(':size', $formattedSize);
        $stmt->bindParam(':hashed_key', $fileHash);
        $stmt->bindParam(':created_at', $fileDate);
        $stmt->execute();
    } catch (PDOException $e) {
        die(json_encode(['status' => 'error', 'message' => 'Failed to insert file information into database: ' . $e->getMessage()]));
    }

    // Output success message
    echo json_encode([
        'status' => 'success',
        'message' => 'File uploaded and encrypted successfully.',
        'file_name' => $uniqueFileName,
        'encrypted_file_path' => $encryptedFilePath,
        'metadata_path' => $metadataFilePath,
        'formatted_size' => $formattedSize,
        'sha256_hash' => $fileHash,
    ]);

    // Fetch the current user's email and role
    $doerUserId = $_SESSION['client_user_id'];
    $userStmt = $pdo->prepare("
            SELECT user_email, user_role 
            FROM tb_client_userdetails 
            WHERE user_id = :user_id AND user_status = 1
        ");
    $userStmt->bindParam(':user_id', $doerUserId);
    $userStmt->execute();
    $userDetails = $userStmt->fetch(PDO::FETCH_ASSOC);
    $logRole = $userDetails['user_role'] ?? 'Unknown';

    // Log the user addition
    $logAction = "Uploaded file/s successfully";
    $logdate = date('Y-m-d H:i:s');
    $logStmt = $pdo->prepare("
        INSERT INTO tb_logs (doer, log_date, role, log_action) 
        VALUES (:doer, :log_date, :role, :action)
    ");
    $logStmt->execute([
        ':doer' => $doerUserId,
        ':log_date' => $logdate,
        ':role' => $logRole,
        ':action' => $logAction
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}
