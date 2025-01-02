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
function generateAESKey() {
    return openssl_random_pseudo_bytes(32); // 256-bit AES key
}

function encryptFile($filePath, $aesKey, $iv) {
    $fileData = file_get_contents($filePath);
    return openssl_encrypt($fileData, 'aes-256-cbc', $aesKey, OPENSSL_RAW_DATA, $iv);
}

function getUniqueFileName($directory, $fileName) {
    $filePath = $directory . $fileName;
    $fileInfo = pathinfo($fileName);
    $baseName = $fileInfo['filename'];
    $extension = isset($fileInfo['extension']) ? '.' . $fileInfo['extension'] : '';
    $counter = 1;

    while (file_exists($filePath)) {
        $filePath = $directory . $baseName . " ($counter)" . $extension;
        $fileName = $baseName . " ($counter)" . $extension;
        $counter++;
    }

    return $fileName;
}

function formatFileSize($bytes) {
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

    // Ensure the uploaded file has a unique name
    $uniqueFileName = getUniqueFileName($uploadDir, basename($file['name']));
    $filePath = $uploadDir . $uniqueFileName;

    // Move the uploaded file to the desired location
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        die(json_encode(['status' => 'error', 'message' => 'Failed to move uploaded file.']));
    }

    // Generate AES key and IV
    $aesKey = generateAESKey();
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));

    // Encrypt the file using AES-256-CBC
    $encryptedData = encryptFile($filePath, $aesKey, $iv);
    $encryptedFilePath = $filePath . '.enc';

    // Save the encrypted file
    if (false === file_put_contents($encryptedFilePath, $encryptedData)) {
        die(json_encode(['status' => 'error', 'message' => 'Failed to save encrypted file.']));
    }

    // Encrypt the AES key using the public key
    $encryptedAESKey = '';
    if (!openssl_public_encrypt($aesKey, $encryptedAESKey, $publicKey)) {
        die(json_encode(['status' => 'error', 'message' => 'Failed to encrypt AES key.']));
    }

    // Prepare metadata for the encrypted file
    $metadata = [
        'iv' => base64_encode($iv),
        'encrypted_key' => base64_encode($encryptedAESKey),
        'file_name' => $uniqueFileName,
        'upload_time' => date('Y-m-d H:i:s'),
    ];

    // Save metadata as a JSON file
    $metadataFilePath = $encryptedFilePath . '.meta';
    if (false === file_put_contents($metadataFilePath, json_encode($metadata))) {
        die(json_encode(['status' => 'error', 'message' => 'Failed to save metadata.']));
    }

    // Output success message
    echo json_encode([
        'status' => 'success',
        'message' => 'File uploaded and encrypted successfully.',
        'file_name' => $uniqueFileName,
        'encrypted_file_path' => $encryptedFilePath,
        'metadata_path' => $metadataFilePath,
        'formatted_size' => formatFileSize($file['size']),
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}
?>
