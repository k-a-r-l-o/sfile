<?php
session_start();

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

$userId = $_SESSION['client_user_id'];

try {
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]));
}

$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['password'], $input['email'], $input['filename'])) {
    $enteredPassword = $input['password'];
    $enteredEmail = $input['email'];
    $fileName = $input['filename'];

    $stmt = $pdo->prepare("SELECT * FROM tb_files WHERE `name` = :name AND owner_id = :owner_id");
    $stmt->bindParam(':name', $fileName, PDO::PARAM_STR);
    $stmt->bindParam(':owner_id', $userId, PDO::PARAM_INT);
    $stmt->execute();

    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($file) {
        $stmt = $pdo->prepare("SELECT password FROM tb_client_logindetails WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && isset($result['password'])) {
            $hashedPassword = $result['password'];

            if (password_verify($enteredPassword, $hashedPassword)) {
                $response = [
                    'status' => 'success',
                    'message' => 'Password verified successfully.',
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
                    echo json_encode($response);
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
                $metadataFilePath = "$folderPath/{$file['name']}.enc.meta";

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
                $stmt = $pdo->prepare("SELECT user_id FROM tb_client_userdetails WHERE user_role = 'Head' AND user_email = :email");
                $stmt->bindParam(':email', $enteredEmail, PDO::PARAM_STR);
                $stmt->execute();

                $headUserId = $stmt->fetch(PDO::FETCH_ASSOC)['user_id'];

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

                // Final response
                $response = [
                    'status' => 'success',
                    'message' => 'AES key encrypted and saved successfully.',
                    'encrypted_key' => base64_encode($encryptedAESKeyForHead)
                ];

                echo json_encode($response);
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Wrong password input, please try again.'
                ];
                echo json_encode($response);
                exit;
            }
        } else {
            $response = [
                'status' => 'error',
                'message' => 'User not found or password not set.',
            ];
            echo json_encode($response);
        }
    } else {
        $response = [
            'status' => 'error',
            'message' => 'File not found or does not belong to the user.',
        ];
        echo json_encode($response);
    }
} else {
    $response = [
        'status' => 'error',
        'message' => 'Invalid input data.',
    ];
    echo json_encode($response);
}
?>
