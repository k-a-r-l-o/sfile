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
    return openssl_random_pseudo_bytes(32);
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

function generateUserRSAKeys($userId) {
    $seed = hash('sha256', $userId, true);
    srand(hexdec(substr(bin2hex($seed), 0, 8)));

    $config = [
        "private_key_bits" => 2048,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    ];

    $privateKeyResource = openssl_pkey_new($config);
    openssl_pkey_export($privateKeyResource, $privateKey);

    $publicKeyDetails = openssl_pkey_get_details($privateKeyResource);
    $publicKey = $publicKeyDetails['key'];

    $secureKeyDir = __DIR__ . '/../../src/roles/client/keys/';
    if (!is_dir($secureKeyDir)) {
        mkdir($secureKeyDir, 0700, true);
    }

    file_put_contents($secureKeyDir . 'private_key_' . $userId . '.pem', $privateKey);
    file_put_contents($secureKeyDir . 'public_key_' . $userId . '.pem', $publicKey);

    return ['private_key' => $privateKey, 'public_key' => $publicKey];
}

function encryptAESKeyWithPublicKey($aesKey, $publicKey) {
    $publicKeyResource = openssl_pkey_get_public($publicKey);
    $encryptedAESKey = '';
    if (openssl_public_encrypt($aesKey, $encryptedAESKey, $publicKeyResource)) {
        return $encryptedAESKey;
    }
    return false;
}

// Main Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        die(json_encode(['status' => 'error', 'message' => 'File upload error.']));
    }

    $uniqueFileName = getUniqueFileName($uploadDir, basename($file['name']));
    $filePath = $uploadDir . $uniqueFileName;

    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        die(json_encode(['status' => 'error', 'message' => 'Failed to move uploaded file.']));
    }

    $aesKey = generateAESKey();
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encryptedData = encryptFile($filePath, $aesKey, $iv);
    $encryptedFilePath = $filePath . '.enc';

    file_put_contents($encryptedFilePath, $encryptedData);

    $keys = generateUserRSAKeys($userId);
    $encryptedAESKey = encryptAESKeyWithPublicKey($aesKey, $keys['public_key']);

    if (!$encryptedAESKey) {
        die(json_encode(['status' => 'error', 'message' => 'Failed to encrypt AES key.']));
    }

    $metadata = [
        'iv' => base64_encode($iv),
        'encrypted_key' => base64_encode($encryptedAESKey),
        'file_name' => $uniqueFileName,
        'upload_time' => date('Y-m-d H:i:s'),
    ];
    file_put_contents($encryptedFilePath . '.meta', json_encode($metadata));

    $formattedSize = formatFileSize($file['size']);

    try {
        $stmt = $pdo->prepare("INSERT INTO tb_client_uploaded_files (user_id, file_name, file_size, upload_date, file_status) VALUES (:user_id, :file_name, :file_size, :upload_date, :file_status)");
        $stmt->execute([
            ':user_id' => $userId,
            ':file_name' => $uniqueFileName,
            ':file_size' => $formattedSize,
            ':upload_date' => date('Y-m-d H:i:s'),
            ':file_status' => 1,
        ]);
        echo json_encode(['status' => 'success', 'message' => 'File encrypted and stored successfully.', 'file_name' => $uniqueFileName]);
    } catch (PDOException $e) {
        die(json_encode(['status' => 'error', 'message' => 'Failed to save file metadata: ' . $e->getMessage()]));
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}
?>
