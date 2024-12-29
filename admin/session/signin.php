<?php
session_start();
require_once '../../config/database.php';
require '../../vendor/autoload.php'; // For PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #ddd; border-radius: 8px;'>
                <div style='background-color: white; padding: 20px; text-align: center; border-bottom: 1px solid #ddd;'>
                    <img src='https://sfile.site/assets/img/logo.png' alt='SECUREFILE' style='max-width: 100px;'>
                </div>
                <div style='padding: 20px;'>
                    <h2 style='color: #333; text-align: center;'>Verify Your Login Attempt</h2>
                    <p style='color: #555; text-align: center;  text-decoration: none;'>We noticed a login attempt for your <strong>SECUREFILE</strong> account with an email of <strong>$email</strong>. For your security, please verify it by clicking the button below:</p>
                    <div style='text-align: center; margin: 20px 0;'>
                        <a href='https://sfile.site/admin/session/verify-login.php?token=$token' style='text-decoration: none; background-color: #050deb; color: #fff; padding: 12px 20px; border-radius: 5px; font-weight: bold; font-size: 14px;'>Verify Login</a>
                    </div>
                    <br/>
                    <p style='color: #555; text-align: center;'>This link will expire in <strong>10 minutes</strong>. If you didn’t attempt to log in, please secure your account by changing your password immediately.</p>
                    <br/>
                    <p style='font-size: 12px; text-align: center; color: #888; max-width: 600px;'>You received this email because a login attempt was made for your account at SECUREFILE. If you didn’t request this, please ignore this email.</p>
                </div>
            </div>
            
        ";

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

            $stmt = $conn->prepare(
                "SELECT l.password, u.user_id, u.user_role, l.user_status
                 FROM tb_admin_logindetails l
                 JOIN tb_admin_userdetails u ON l.user_id = u.user_id
                 WHERE u.user_email = :email"
            );
            $stmt->bindValue(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                if ($user['user_status'] === "Online") {
                    header("Location: ../login?error=already_logged_in");
                    exit;
                } elseif (password_verify($password, $user['password'])) {
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
                        ':user_id' => $user['user_id']
                    ]);

                    if (sendLoginVerificationEmail($email, $tokenPlain)) {
                        header("Location: verification-link-sent?email=$email");
                        exit;
                    } else {
                        header("Location: ../login?error=email_failed");
                        exit;
                    }
                } else {
                    header("Location: ../login?error=invalid_credentials");
                    exit;
                }
            } else {
                header("Location: ../login?error=user_not_found");
                exit;
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            header("Location: ../login?error=server_error");
            exit;
        }
    } else {
        header("Location: ../login?error=missing_fields");
        exit;
    }
}

header("Location: ../login");
