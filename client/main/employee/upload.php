<?php
// Start session
session_start();

if (!isset($_SESSION['client_user_id'])) {
    header('Location: login.php');
    exit;
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
function generateAESKey(): string {
    return openssl_random_pseudo_bytes(32); // 256-bit AES key
}

function encryptFile(string $filePath, string $aesKey, string $iv): string {
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

function getUniqueFileName(string $directory, string $fileName): string {
    $fileInfo = pathinfo($fileName);
    $baseName = $fileInfo['filename'];
    $extension = isset($fileInfo['extension']) ? '.' . $fileInfo['extension'] : '';
    $counter = 1;

    do {
        $uniqueName = $baseName . ($counter > 1 ? " ($counter)" : '') . $extension;
        $filePath = $directory . $uniqueName;
        $counter++;
    } while (file_exists($filePath));

    return $uniqueName;
}

function formatFileSize(int $bytes): string {
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

    // Ensure the uploaded file has a unique name
    $uniqueFileName = getUniqueFileName($folderPath . '/', $file['name']);
    $filePath = $folderPath . '/' . $uniqueFileName;

    // Move the uploaded file to a temporary location for processing
    $tempFilePath = $file['tmp_name'];

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
    ];

    // Save metadata as a JSON file
    $metadataFilePath = $folderPath . '/' . $uniqueFileName . '.enc.meta';
    if (file_put_contents($metadataFilePath, json_encode($metadata)) === false) {
        die(json_encode(['status' => 'error', 'message' => 'Failed to save metadata.']));
    }

    // Delete the original file
    if (file_exists($tempFilePath)) {
        unlink($tempFilePath);
    }

    // Insert file information into the database
    try {
        $stmt = $pdo->prepare("INSERT INTO tb_files (owner_id, name, size) VALUES (:owner_id, :name, :size)");
        $stmt->bindParam(':owner_id', $userId);
        $stmt->bindParam(':name', $uniqueFileName);
        $stmt->bindParam(':size', $formattedSize);
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
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}
?>
