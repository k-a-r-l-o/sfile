<?php
session_start();
require_once '../../config/database.php';
require '../../vendor/autoload.php'; // For PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//Encrypt and decrypt functions
// Load the key and IV from the .meta file
$metaFilePath = "../../security/key.meta";

// Check if the file exists
if (!file_exists($metaFilePath)) {
    throw new Exception("Key meta file does not exist at $metaFilePath");
}

// Decode the JSON data from the file
$keys = json_decode(file_get_contents($metaFilePath), true);

// Check if JSON decoding was successful
if ($keys === null) {
    throw new Exception("Error decoding JSON from $metaFilePath");
}

// Check if both the 'key' and 'iv' fields are present
if (!isset($keys['key']) || !isset($keys['iv'])) {
    throw new Exception("Key or IV missing in the meta file");
}

// Decode the base64-encoded key and IV
$key = base64_decode($keys['key'], true);
$iv = base64_decode($keys['iv'], true);

// Validate the decoded key and IV lengths
if ($key === false || strlen($key) !== 32) {
    throw new Exception("Invalid AES key. Ensure it is 256 bits (32 bytes) base64 encoded.");
}

if ($iv === false || strlen($iv) !== 16) {
    throw new Exception("Invalid AES IV. Ensure it is 128 bits (16 bytes) base64 encoded.");
}

// Define the constants for key and IV
define('AES_KEY', $key);
define('AES_IV', $iv);


function aesEncrypt($input)
{
    // Add random padding to make the plaintext longer
    $padding = bin2hex(random_bytes(32)); // 64 characters of padding
    $paddedInput = $input . "::" . $padding;

    // Encrypt the padded input
    $encrypted = openssl_encrypt(
        $paddedInput,
        'AES-256-CBC',
        AES_KEY,
        0,
        AES_IV
    );

    // Base64-encode the encrypted string
    return base64_encode($encrypted);
}

function aesDecrypt($input)
{
    // Decode and decrypt the input
    $decrypted = openssl_decrypt(
        base64_decode($input),
        'AES-256-CBC',
        AES_KEY,
        0,
        AES_IV
    );

    // Remove the padding if it exists
    if ($decrypted !== false && strpos($decrypted, "::") !== false) {
        list($originalData,) = explode("::", $decrypted, 2);
        return $originalData;
    }

    return $decrypted;
}
// End of encrypt and decrypt functions

function sendLoginVerificationEmail($email, $token)
{
    $mail = new PHPMailer(true);
    try {
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'kocornejo00294@usep.edu.ph';
        $mail->Password = 'gubz tazq acwf ecny';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('kocornejo00294@usep.edu.ph', 'SecureFile');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'SECUREFILE - Verify Your Login';
        $mail->Body = "<!DOCTYPE html>
        <html lang=\"en\">
        <head>
            <meta charset=\"utf-8\" />
            <meta content=\"width=device-width, initial-scale=1.0\" name=\"viewport\" />
            <title>SecureFile</title>
        </head>

        <body style=\"font-family: 'Roboto', Arial, sans-serif; max-width: 600px margin: 0; padding: 0;\">
            <div style=\"max-width: 600px; border-radius: 10px; margin: auto; padding: 20px; background: #f9f9f9;\">
                <img
                    src='https://sfile.site/assets/img/light-logo.png'
                    alt='SecureFile'
                    style=\"width: 50%; max-width: 150px; margin-bottom: 20px;\"
                />
                <br />
                <main>
                <section style=\"background: #ffffff; border-radius: 10px; padding: 20px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);\">
                <div style=\"text-align: center;\">
                    
                    <h3 style=\"margin: 0 0 10px; font-size: 24px; color: #333;\">Verify Your Login Attempt</h3>
                    <p style=\"color: #666; font-size: 14px; line-height: 1.6;\">
                    We noticed a login attempt to your account. For your security,
                    please verify it by clicking the button below:
                    </p>

                    <a href='https://sfile.site/admin/session/verify-login.php?token=$token'
                    style=\"display: inline-block; margin-top: 20px; padding: 10px 20px; background: #007bff; color: #ffffff; text-decoration: none; border-radius: 5px; font-size: 16px;\"
                    >
                    Verify Login
                    </a>

                    <p style=\"margin-top: 20px; font-size: 14px; color: #666; line-height: 1.6;\">
                    This link will expire in <strong>10 minutes</strong>. If you
                    didn't attempt to log in, please secure your account by
                    changing your password immediately.
                    </p>
                </div>
                </section>

                <footer style=\"text-align: center; margin-top: 20px; font-size: 12px; color: #999;\">
                <p>
                    You received this email because a login attempt was made for your
                    account at SECUREFILE. If you didn't request this, please ignore
                    this email.
                </p>
                </footer>
            </main>
            </div>
        </body>
        </html>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email error: " . $mail->ErrorInfo);
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        try {
            $db = new Database();
            $conn = $db->getConnection();

            // Fetch all active users
            $stmt = $conn->prepare(
                "SELECT l.password, u.user_id, u.user_role, l.user_status, u.user_email
                 FROM tb_admin_logindetails l
                 JOIN tb_admin_userdetails u ON l.user_id = u.user_id
                 WHERE u.user_status = 1"
            );
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $validUser = null;

            // Loop through all users to find a match
            foreach ($users as $user) {
                if (aesDecrypt($user['user_email']) === $email && password_verify($password, $user['password'])) {
                    $validUser = $user;
                    break;
                }
            }

            if ($validUser) {
                // Check if user is already logged in
                if ($validUser['user_status'] === "Online") {
                    header("Location: ../login?error=already_logged_in");
                    exit;
                }

                // Generate and store the token for the valid user
                $tokenPlain = bin2hex(random_bytes(16));
                $tokenHash = password_hash($tokenPlain, PASSWORD_DEFAULT);
                $tokenExpiration = date('Y-m-d H:i:s', time() + 600);

                $updateTokenStmt = $conn->prepare(
                    "UPDATE tb_admin_logindetails 
                     SET token = :token, token_expiration = :expiration 
                     WHERE user_id = :user_id"
                );
                $updateTokenStmt->execute([
                    ':token' => $tokenHash,
                    ':expiration' => $tokenExpiration,
                    ':user_id' => $validUser['user_id']
                ]);

                // Send verification email
                if (sendLoginVerificationEmail($email, $tokenPlain)) {
                    //header("Location: ../verification-link-sent?email=$email");
                    header("Location: ./verify-login.php?token=$tokenPlain");
                    exit;
                } else {
                    header("Location: ../login?error=email_failed");
                    exit;
                }
            } else {
                header("Location: ../login?error=invalid_credentials");
                exit;
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            echo $e->getMessage();
            header("Location: ../login?error=server_error");
            exit;
        }
    } else {
        header("Location: ../login?error=missing_fields");
        exit;
    }
}

header("Location: ../login");
