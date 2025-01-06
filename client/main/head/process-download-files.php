<?php
// Start session
session_start();

// Check if the session contains a user ID
if (!isset($_SESSION['client_role'], $_SESSION['client_token'], $_SESSION['client_user_id'])) {
    header("Location: ../../login?error=session_expired");
    exit;
} else {
    if ($_SESSION['client_role'] == 'Employee') {
        header("Location: ../employee/");
        exit;
    }
}

require_once '../../../config/config.php';

$userId = $_SESSION['client_user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ownerId = $_POST['userid'];
    $fileName = $_POST['filenameInput'];
    $id = $_POST['fileid'];

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
    if (isset($_POST['password'])) {
        $enteredPassword = $_POST['password'];

        if (isset($fileName) && isset($id) && isset($ownerId)) {
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
                    $privateKeyPath = $_SERVER['DOCUMENT_ROOT'] . "/security/keys/head/$userId";

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

                    $folderPath = $_SERVER['DOCUMENT_ROOT'] . "/client/main/employee/uploads/$ownerId";
                    $metadataFilePath = "$folderPath/$fileName.enc.meta";

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
                    // Prepare and execute the SQL statement to fetch the hashed password
                    $stmt = $pdo->prepare("SELECT * FROM tb_shared_files WHERE file_id = :file_id");
                    $stmt->bindParam(':file_id', $id, PDO::PARAM_INT);
                    $stmt->execute();

                    // Fetch the hashed password from the database
                    $eKey = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Decrypt the AES key using the private key
                    $encryptedAESKey = $eKey['encrypted_key'];
                    $decryptedAESKey = '';
                    $decryptionSuccess = openssl_private_decrypt($encryptedAESKey, $decryptedAESKey, $res);

                    if (!$decryptionSuccess) {
                        $response = [
                            'status' => 'error',
                            'message' => 'Failed to decrypt AES key.'
                        ];
                    }

                    // Decrypt the file using the decrypted AES key and IV
                    //$iv = base64_decode($metadata['iv']);
                    $iv = base64_decode($eKey['iv']);
                    $encryptedFilePath = "$folderPath/$fileName.enc";

                    // Read the encrypted file
                    $encryptedData = file_get_contents($encryptedFilePath);

                    // Decrypt the file using AES-256-CBC
                    $decryptedData = openssl_decrypt($encryptedData, 'aes-256-cbc', $decryptedAESKey, OPENSSL_RAW_DATA, $iv);

                    if ($decryptedData !== false) {
                        // Determine the MIME type based on the file extension
                        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
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
                        header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
                        header('Content-Length: ' . strlen($decryptedData));
                        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
                        header('Expires: 0');

                        // Ensure no output before headers
                        if (ob_get_length()) {
                            ob_end_clean();
                        }

                        echo $decryptedData;

                        exit;
                    } else {
                        // Return error if decryption fails
                        header('Content-Type: application/json');
                        echo json_encode([
                            'status' => 'error',
                            'message' => 'Failed to decrypt the file.'
                        ]);

                        exit;
                    }

                    // Return success message
                    $response = [
                        'status' => 'success',
                        'message' => 'File decrypted successfully.',
                        'decrypted_file_path' => $decryptedFilePath
                    ];
                    header('window.location.href = "employee-files?error=none";');
                } else {
                    $response = [
                        'status' => 'invalid',
                        'message' => 'Invalid password.'
                    ];
                    header('window.location.href = "verify-password?id=' . $_POST['fileid'] . '&file=' . $_POST['filenameInput'] . '&email=' . $_POST['email'] . '&error=password_not_match";');
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

    header('Content-Type: application/json');

    // Add this line at the end of your PHP script to ensure a response is always sent
    if (!isset($response)) {
        $response = [
            'status' => 'error',
            'message' => 'An unknown error occurred.'
        ];
    }

    echo json_encode($response);
    exit;
} else {
    header("Location: employee-files");
    exit;
}
