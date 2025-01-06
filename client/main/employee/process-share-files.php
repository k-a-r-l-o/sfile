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

require_once '../../../config/database.php';

$userId = $_SESSION['client_user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the email is provided in the POST data
    if (!isset($_POST['fileid']) || empty($_POST['fileid'])) {
        header("Location: my-files?error=invalid_request");
        exit;
    }
    if (!isset($_POST['filenameInput']) || empty($_POST['filenameInput'])) {
        header("Location: my-files?error=invalid_request");
        exit;
    }
    if (!isset($_POST['email']) || empty($_POST['email'])) {
        header("Location: my-files?error=invalid_request");
        exit;
    }
    if (isset($_POST['password']) && !empty($_POST['password'])) {
        $enteredPassword = $_POST['password'];
        $filename = $_POST['filenameInput'];
        $fileid = $_POST['fileid'];
        $enteredEmail = $_POST['email'];

        try {
            $db = new Database();
            $conn = $db->getConnection();

            // Verify the password
            $stmt = $conn->prepare(
                "SELECT password FROM tb_client_logindetails WHERE user_id = :user_id"
            );
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);


            if ($result && isset($result['password'])) {
                $hashedPassword = $result['password'];

                if (password_verify($enteredPassword, $hashedPassword)) {
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
                        echo json_encode($response);
                        header("Location: my-files?error=invalid_request");
                        exit;
                    }

                    // Read the encrypted private key from the file
                    $encryptedPrivateKey = file_get_contents($encryptedPrivateKeyPath);

                    $passphrase = $enteredPassword; // The entered password used as the passphrase

                    // Decrypt the private key using the passphrase
                    $res = openssl_pkey_get_private($encryptedPrivateKey, $passphrase);

                    if (!$res) {
                        $response = [
                            'status' => 'error',
                            'message' => 'Decryption failed'
                        ];
                        echo json_encode($response);
                        exit;
                    }

                    $folderPath = $_SERVER['DOCUMENT_ROOT'] . "/client/main/employee/uploads/$userId";
                    $metadataFilePath = "$folderPath/{$filename}.enc.meta";

                    // Check if the metadata file exists
                    if (!file_exists($metadataFilePath)) {
                        $response = [
                            'status' => 'error',
                            'message' => 'Metadata file does not exist.'
                        ];
                        echo json_encode($response);
                        exit;
                    }

                    // Read the metadata from the file
                    $metadata = json_decode(file_get_contents($metadataFilePath), true);
                    if ($metadata === null) {
                        $response = [
                            'status' => 'error',
                            'message' => 'Failed to read metadata.'
                        ];
                        echo json_encode($response);
                        exit;
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
                        echo json_encode($response);
                        exit;
                    }

                    // Fetch the user_id of the Head based on the entered email
                    $stmt = $conn->prepare("SELECT user_id FROM tb_client_userdetails WHERE user_role = 'Head' AND user_status = 1 AND user_email = :email");
                    $stmt->bindParam(':email', $enteredEmail, PDO::PARAM_STR);
                    $stmt->execute();

                    $headUserId = $stmt->fetch(PDO::FETCH_ASSOC)['user_id']; // Fetch the user_id of the Head

                    if (!$headUserId) {
                        $response = [
                            'status' => 'error',
                            'message' => 'Head not found for the given email.'
                        ];
                        echo json_encode($response);
                        exit;
                    }

                    // Build the path to the Head's public key file using the user_id
                    $headPublicKeyPath = __DIR__ . "/../../../security/keys/head/$headUserId/public_key.pem";

                    if (!file_exists($headPublicKeyPath)) {
                        $response = [
                            'status' => 'error',
                            'message' => 'Public key not found for Head with user ID: ' . $headUserId
                        ];
                        echo json_encode($response);
                        exit;
                    }

                    // Read the Head's public key
                    $headPublicKey = file_get_contents($headPublicKeyPath);
                    if ($headPublicKey === false) {
                        $response = [
                            'status' => 'error',
                            'message' => 'Failed to read Head\'s public key.'
                        ];
                        echo json_encode($response);
                        exit;
                    }

                    // Encrypt the AES key using the Head's public key
                    $encryptedAESKeyForHead = '';
                    $encryptionSuccess = openssl_public_encrypt($decryptedAESKey, $encryptedAESKeyForHead, $headPublicKey);

                    if (!$encryptionSuccess) {
                        $response = [
                            'status' => 'error',
                            'message' => 'Failed to encrypt AES key with Head\'s public key.'
                        ];
                        echo json_encode($response);
                        exit;
                    }

                    // Base64 encode the encrypted AES key for storage or transmission
                    $encryptedAESKeyForHead = base64_encode($encryptedAESKeyForHead);

                    // Insert the data into the database (tb_shared_files table)
                    $currentDateTime = date('Y-m-d H:i:s');
                    $stmt = $conn->prepare("INSERT INTO tb_shared_files (file_id, shared_to, encrypted_key, iv, shared_at) VALUES (:file_id, :shared_to, :encrypted_key, :iv, :shared_at)");
                    $stmt->bindParam(':file_id', $fileid, PDO::PARAM_INT);
                    $stmt->bindParam(':shared_to', $headUserId, PDO::PARAM_STR);
                    $stmt->bindParam(':encrypted_key', $encryptedAESKeyForHead, PDO::PARAM_STR);
                    $stmt->bindParam(':iv', $metadata['iv'], PDO::PARAM_STR);
                    $stmt->bindParam(':shared_at', $currentDateTime, PDO::PARAM_STR);

                    if ($stmt->execute()) {
                        $response = [
                            'status' => 'success',
                            'message' => 'AES key encrypted and stored in database successfully.',
                            'encrypted_key' => $encryptedAESKeyForHead
                        ];
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
                        $logAction = "Shared file $filename successfully";
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
                        header("Location: my-files?error=none");
                    } else {
                        $response = [
                            'status' => 'error',
                            'message' => 'Failed to store the encrypted AES key in the database.'
                        ];
                        header("Location: my-files?error=already_shared");
                    }
                    /*/ Path to save the encrypted AES key
                    $encryptedAESKeyFilePath = __DIR__ . "/../../../security/keys/head/$headUserId/encrypted_aes_key.enc";

                    // Save the encrypted AES key to the file
                    if (file_put_contents($encryptedAESKeyFilePath, $encryptedAESKeyForHead) === false) {
                        $response = [
                            'status' => 'error',
                            'message' => 'Failed to save the encrypted AES key to file.'
                        ];
                        echo json_encode($response);
                        exit;
                    }*/

                    echo json_encode($response);
                    exit;
                } else {
                    header("Location: verify-password?id=" . $_POST['fileid'] . "&file=" . $_POST['filenameInput'] . "&email=" . $_POST['email'] . "&error=password_not_match");
                }
            } else {
                header("Location: my-files?error=user_not_found");
            }
        } catch (PDOException $e) {
            //header("Location: my-files?error=database_error");
            echo json_encode(["error" => "An error occurred: " . $e->getMessage()]);
            exit;
        }
    }
} else {
    header("Location: my-files?error=invalid_request");
    exit;
}
