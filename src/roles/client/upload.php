<?php
// Upload directory
$uploadDir = 'uploads/';

// Generate AES-256 key
function generateAESKey() {
    return openssl_random_pseudo_bytes(32); // 256-bit key
}

// Encrypt file using AES-256
function encryptFile($filePath, $aesKey, $iv) {
    $fileData = file_get_contents($filePath);
    $encryptedData = openssl_encrypt($fileData, 'aes-256-cbc', $aesKey, OPENSSL_RAW_DATA, $iv);
    return $encryptedData;
}

// Encrypt AES key using RSA-2048
function encryptAESKey($aesKey, $publicKeyPath) {
    $publicKey = file_get_contents($publicKeyPath);
    openssl_public_encrypt($aesKey, $encryptedKey, $publicKey);
    return $encryptedKey;
}

// Save encrypted file and metadata
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $filePath = $uploadDir . basename($file['name']);
    move_uploaded_file($file['tmp_name'], $filePath);

    // Generate AES key and IV
    $aesKey = generateAESKey();
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));

    // Encrypt the file
    $encryptedData = encryptFile($filePath, $aesKey, $iv);
    $encryptedFilePath = $uploadDir . basename($file['name']) . '.enc';
    file_put_contents($encryptedFilePath, $encryptedData);

    // Encrypt AES key using RSA public key
    $publicKeyPath = 'keys/public_key.pem'; // Path to RSA public key
    $encryptedAESKey = encryptAESKey($aesKey, $publicKeyPath);

    // Save metadata
    $metadata = [
        'iv' => base64_encode($iv),
        'encrypted_key' => base64_encode($encryptedAESKey),
        'file_name' => basename($file['name']),
        'upload_time' => date('Y-m-d H:i:s'),
    ];
    file_put_contents($encryptedFilePath . '.meta', json_encode($metadata));

    echo json_encode(['status' => 'success', 'message' => 'File encrypted and stored successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}
?>
