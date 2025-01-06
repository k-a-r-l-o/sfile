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
                    $publicKeyPath = "$privateKeyPath/public_key.pem";

                    // Check if the file exists
                    if (!file_exists($encryptedPrivateKeyPath)) {
                        header('Content-Type: application/json');
                        echo $response = [
                            'status' => 'error',
                            'message' => 'File not exists'
                        ];
                        exit;
                    }

                    // Read the encrypted private key from the file
                    $encryptedPrivateKey = file_get_contents($encryptedPrivateKeyPath);
                    $publickey = file_get_contents($publicKeyPath);

                    $passphrase = $enteredPassword; // The entered password used as the passphrase

                    // Decrypt the private key using the passphrase
                    $res = openssl_pkey_get_private($encryptedPrivateKey, $passphrase);

                    if (!$res) {
                        //die("1Failed to decrypt private key: " . openssl_error_string());
                        header('Content-Type: application/json');
                        echo $response = [
                            'status' => 'error',
                            'message' => 'Decryption failed'
                        ];
                        exit;
                    }

                    $folderPath = $_SERVER['DOCUMENT_ROOT'] . "/client/main/employee/uploads/$ownerId";
                    $metadataFilePath = "$folderPath/$fileName.enc.meta";

                    // Check if the metadata file exists
                    if (!file_exists($metadataFilePath)) {
                        header('Content-Type: application/json');
                        echo $response = [
                            'status' => 'error',
                            'message' => 'Metadata file does not exist.'
                        ];
                        exit;
                    }

                    // Read the metadata from the file
                    $metadata = json_decode(file_get_contents($metadataFilePath), true);
                    if ($metadata === null) {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'status' => 'error',
                            'message' => 'Failed to read metadata.'
                        ]);
                        exit;
                    }
                    // Prepare and execute the SQL statement to fetch the hashed password
                    $stmt = $pdo->prepare("SELECT * FROM tb_shared_files WHERE file_id = :file_id");
                    $stmt->bindParam(':file_id', $id, PDO::PARAM_INT);
                    $stmt->execute();

                    // Fetch the hashed password from the database
                    $eKey = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Decrypt the AES key using the private key
                    $encryptedAESKey = base64_decode($eKey['encrypted_key']);
                    $decryptedAESKey = '';
                    $decryptionSuccess = openssl_private_decrypt($encryptedAESKey, $decryptedAESKey, $res, OPENSSL_NO_PADDING);

                    if (!$decryptionSuccess) {
                        header('Content-Type: application/json');
                        echo openssl_error_string();
                        echo "\n";
                        echo json_encode([
                            'status' => 'error',
                            'message' => 'Failed to decrypt AES key.'
                        ]);
                        exit;
                    }

                    // Decrypt the file using the decrypted AES key and IV
                    //$iv = base64_decode($metadata['iv']);
                    $iv = base64_decode($eKey['iv']);
                    $encryptedFilePath = "$folderPath/$fileName.enc";

                    // Read the encrypted file
                    $encryptedData = file_get_contents($encryptedFilePath);

                    // Decrypt the file using AES-256-CBC
                    $decryptedData = openssl_decrypt($encryptedData, 'aes-256-cbc', $decryptedAESKey, OPENSSL_NO_PADDING, $iv);

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
                        // Fetch the current user's email and role
                        $doerUserId = $_SESSION['client_user_id'];
                        $userStmt = $conn->prepare("
                                SELECT user_email, user_role 
                                FROM tb_client_userdetails 
                                WHERE user_id = :user_id AND user_status = 1
                            ");
                        $userStmt->bindParam(':user_id', $doerUserId);
                        $userStmt->execute();
                        $userDetails = $userStmt->fetch(PDO::FETCH_ASSOC);
                        $logRole = $userDetails['user_role'] ?? 'Unknown';

                        // Log the user addition
                        $logAction = "Downloaded file $fileName successfully";
                        $logdate = date('Y-m-d H:i:s');
                        $logStmt = $conn->prepare("
                            INSERT INTO tb_logs (doer, log_date, role, log_action) 
                            VALUES (:doer, :log_date, :role, :action)
                        ");
                        $logStmt->execute([
                            ':doer' => $doerUserId,
                            ':log_date' => $logdate,
                            ':role' => $logRole,
                            ':action' => $logAction
                        ]);
                        // Return success message
                        $response = [
                            'status' => 'success',
                            'message' => 'File decrypted successfully.',
                            'decrypted_file_path' => $decryptedFilePath
                        ];
                        header('window.location.href = "employee-files?error=none";');
                        exit;
                    } else {
                        // Return error if decryption fails
                        header('Content-Type: application/json');
                        echo openssl_error_string();
                        echo json_encode([
                            'status' => 'error',
                            'message' => 'Failed to decrypt the file.'
                        ]);

                        exit;
                    }
                } else {
                    header('Content-Type: application/json');
                    $response = [
                        'status' => 'invalid',
                        'message' => 'Invalid password.'
                    ];
                    header('window.location.href = "verify-password?id=' . $_POST['fileid'] . '&file=' . $_POST['filenameInput'] . '&email=' . $_POST['email'] . '&error=password_not_match";');
                    exit;
                }
            } else {
                // User not found or password not set
                $response = [
                    'status' => 'error',
                    'message' => 'User not found or password not set.'
                ];
                header('window.location.href = "employee-files?error=1";');
                exit;
            }
        } else {
            $response = [
                'status' => 'error',
                'message' => 'File not found or invalid file ID.'
            ];
            header('window.location.href = "employee-files?error=2";');
            exit;
        }
    } else {
        // Password field is missing
        $response = [
            'status' => 'error',
            'message' => 'Password field is missing.'
        ];
        exit;
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
