<?php
session_start();

// Check if the session contains a user ID
if (!isset($_SESSION['admin_role'], $_SESSION['admin_token'], $_SESSION['admin_user_id'])) {
    header("Location: ../../login?error=session_expired");
    exit;
}

// Caesar Cipher Shift Key
define('SHIFT_KEY', 24); // Adjust the shift key as needed

/**
 * Encrypt a string using the Caesar cipher.
 *
 * @param string $input The string to encrypt.
 * @return string The encrypted string.
 */
function caesarEncrypt($input)
{
    $result = '';
    foreach (str_split($input) as $char) {
        if (ctype_alpha($char)) {
            $offset = ctype_upper($char) ? 65 : 97;
            $result .= chr(((ord($char) - $offset + SHIFT_KEY) % 26) + $offset);
        } else {
            $result .= $char; // Non-alphabetic characters are not shifted
        }
    }
    return $result;
}

/**
 * Decrypt a string using the Caesar cipher.
 *
 * @param string $input The string to decrypt.
 * @return string The decrypted string.
 */
function caesarDecrypt($input)
{
    $result = '';
    foreach (str_split($input) as $char) {
        if (ctype_alpha($char)) {
            $offset = ctype_upper($char) ? 65 : 97;
            $result .= chr(((ord($char) - $offset - SHIFT_KEY + 26) % 26) + $offset);
        } else {
            $result .= $char; // Non-alphabetic characters are not shifted
        }
    }
    return $result;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../../../config/config.php';

    // Retrieve and encrypt form data
    $email = $_POST['email'] ?? '';
    $firstname = $_POST['firstname'] ?? '';
    $lastname = $_POST['lastname'] ?? '';

    if (empty($email) || empty($firstname) || empty($lastname)) {
        header("Location: add-admin?error=missing_fields");
        exit;
    }

    try {
        $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Encrypt sensitive data
        $encryptedEmail = caesarEncrypt($email);
        $encryptedFirstname = caesarEncrypt($firstname);
        $encryptedLastname = caesarEncrypt($lastname);

        // Check if email already exists
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM tb_admin_userdetails WHERE user_email = :email AND user_status = '1'");
        $checkStmt->bindParam(':email', $encryptedEmail);
        $checkStmt->execute();
        $emailExists = $checkStmt->fetchColumn();

        if ($emailExists > 0) {
            header("Location: add-admin?error=email_exists");
            exit;
        }

        // Start a transaction
        $pdo->beginTransaction();
        $role = 'Administrator';

        // Insert into tb_admin_userdetails
        $stmt = $pdo->prepare("INSERT INTO tb_admin_userdetails (user_fname, user_lname, user_email, user_role) 
                                VALUES (:fname, :lname, :email, :role)");
        $stmt->bindParam(':fname', $encryptedFirstname);
        $stmt->bindParam(':lname', $encryptedLastname);
        $stmt->bindParam(':email', $encryptedEmail);
        $stmt->bindParam(':role', $role);
        $stmt->execute();

        // Fetch the inserted user ID
        $user_id = $pdo->lastInsertId();

        // Generate and hash the password
        $password = $lastname . '_' . preg_replace("/[^a-zA-Z0-9]/", "", $user_id);
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert into tb_admin_logindetails
        $loginStmt = $pdo->prepare("INSERT INTO tb_admin_logindetails (password, user_id) 
                                    VALUES (:password, :user_id)");
        $loginStmt->bindParam(':password', $hashed_password);
        $loginStmt->bindParam(':user_id', $user_id);
        $loginStmt->execute();

        // Commit the transaction
        $pdo->commit();

        // Logging and redirection code remains the same...

        echo json_encode(["success" => true]);
        header("Location: add-admin?error=none");
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(["error" => "An error occurred: " . $e->getMessage()]);
        header("Location: add-admin?error=server_error");
        exit;
    }
}
header("Location: ../");
exit;
