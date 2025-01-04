<?php
// Start session
session_start();

if (!isset($_SESSION['client_user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../../../config/config.php';

$userId = $_SESSION['client_user_id'];

try {
    // Initialize PDO connection
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]));
}

// Retrieve the JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Check if the password field is set
if (isset($input['password'])) {
    $enteredPassword = $input['password'];
    $fileName = $input['fileName'];

    // Fetch file details from the database using file ID
    $stmt = $pdo->prepare("SELECT * FROM tb_files WHERE `name` = :name AND owner_id = :owner_id");
    $stmt->bindParam(':name', $fileName, PDO::PARAM_INT);
    $stmt->bindParam(':owner_id', $userId, PDO::PARAM_INT);
    $stmt->execute();

    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($file) {
        // Prepare and execute the SQL statement to fetch the hashed password
        $stmt = $pdo->prepare("SELECT password FROM tb_client_logindetails WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        // Fetch the hashed password from the database
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && isset($result['password'])) {
            $hashedPassword = $result['password'];

            // Verify the entered password against the hashed password
            if (password_verify($enteredPassword, $hashedPassword)) {
                // Password is correct
                $response = [
                    'status' => 'success',
                    'message' => 'Password verified successfully.'
                ];

                // Path to the user's folder where the private key is stored
                $privateKeyPath = $_SERVER['DOCUMENT_ROOT'] . "/security/keys/employee/$userId"; 

                // Path to the encrypted private key
                $encryptedPrivateKeyPath = "$privateKeyPath/private_key.enc";

                // Check if the file exists
                if (!file_exists($encryptedPrivateKeyPath)) {
                    $response = [
                        'status' => 'error',
                        'message' => 'File not exists'
                    ];
                }

                // Read the encrypted private key from the file
                $encryptedPrivateKey = file_get_contents($encryptedPrivateKeyPath);

                $passphrase = $enteredPassword; // The entered password used as the passphrase

                // Decrypt the private key using the passphrase
                $res = openssl_pkey_get_private($encryptedPrivateKey, $passphrase);

                if (!$res) {
                    //die("1Failed to decrypt private key: " . openssl_error_string());
                    $response = [
                        'status' => 'error',
                        'message' => 'Decryption failed'
                    ];
                }

                $folderPath = $_SERVER['DOCUMENT_ROOT'] . "/client/main/employee/uploads/$userId"; 
                $metadataFilePath = "$folderPath/{$file['name']}.enc.meta";

                // Check if the metadata file exists
                if (!file_exists($metadataFilePath)) {
                    $response = [
                        'status' => 'error',
                        'message' => 'Metadata file does not exist.'
                    ];
                }

                // Read the metadata from the file
                $metadata = json_decode(file_get_contents($metadataFilePath), true);
                if ($metadata === null) {
                    $response = [
                        'status' => 'error',
                        'message' => 'Failed to read metadata.'
                    ];
                }

                // Decrypt the AES key using the private key
                $encryptedAESKey = base64_decode($metadata['encrypted_key']);
                $decryptedAESKey = '';
                $decryptionSuccess = openssl_private_decrypt($encryptedAESKey, $decryptedAESKey, $res);

                if (!$decryptionSuccess) {
                    $response = [
                        'status' => 'error',
                        'message' => 'Failed to decrypt AES key.'
                    ];
                }

                // Decrypt the file using the decrypted AES key and IV
                $iv = base64_decode($metadata['iv']);
                $encryptedFilePath = "$folderPath/{$file['name']}.enc";

                // Read the encrypted file
                $encryptedData = file_get_contents($encryptedFilePath);

                // Decrypt the file using AES-256-CBC
                $decryptedData = openssl_decrypt($encryptedData, 'aes-256-cbc', $decryptedAESKey, OPENSSL_RAW_DATA, $iv);

                if ($decryptedData === false) {
                    $response = [
                        'status' => 'error',
                        'message' => 'Failed to decrypt the file.'
                    ];
                }   

                // Determine the MIME type based on the file extension
                $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $mimeType = match (strtolower($fileExtension)) {
                    'pdf' => 'application/pdf',
                    'doc', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'xls', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'txt' => 'text/plain',
                    'ppt', 'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    'png' => 'image/png',
                    'jpg', 'jpeg' => 'image/jpeg',
                    default => 'application/octet-stream', // Fallback for unknown types
                };

                // Set headers for file download
                header('Content-Type: ' . $mimeType);
                header('Content-Disposition: attachment; filename="' . basename($file['name']) . '"');
                header('Content-Length: ' . strlen($decryptedData));
                header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
                header('Expires: 0');

                // Clear output buffer and send the file
                ob_clean();
                flush();
                echo $decryptedData;
                exit;

                // Return success message
                $response = [
                    'status' => 'success',
                    'message' => 'File decrypted successfully.',
                    'decrypted_file_path' => $decryptedFilePath
                ];

            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Invalid password.'
                ];
            }

        } else {
            // User not found or password not set
            $response = [
                'status' => 'error',
                'message' => 'User not found or password not set.'
            ];
        }
    } else {
        $response = [
            'status' => 'error',
            'message' => 'File not found or invalid file ID.'
        ];
    }
    
} else {
    // Password field is missing
    $response = [
        'status' => 'error',
        'message' => 'Password field is missing.'
    ];
}

?>
