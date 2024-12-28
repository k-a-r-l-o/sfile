<?php
session_start();
require_once 'config/database.php';
require 'vendor/autoload.php'; // For PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = '';
$email = '';
$password = '';

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

        // Email content
        $mail->setFrom('kocornejo00294@usep.edu.ph', 'SecureFile');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Login';
        $mail->Body = "
            <p>We noticed a login attempt for your account. Click the link below to verify it:</p>
            <a href='https://sfile.site/session/verify-login.php?token=$token'>Verify Login</a>
            <p>This link will expire in 10 minutes.</p>
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
            $db = new database();
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
                // Check if the user is already logged in
                if ($user['user_status'] === "Online") {
                    $error = "Your account is already logged in. Please log out from other devices.";
                } elseif (password_verify($password, $user['password'])) {
                    // Generate token and expiration
                    $token = bin2hex(random_bytes(16));
                    $tokenExpiration = date('Y-m-d H:i:s', time() + 600); // Token valid for 10 minutes

                    // Update token and expiration in the database
                    $updateTokenStmt = $conn->prepare(
                        "UPDATE tb_admin_logindetails SET token = :token, token_expiration = :expiration WHERE user_id = :user_id"
                    );
                    $updateTokenStmt->execute([
                        ':token' => $token,
                        ':expiration' => $tokenExpiration,
                        ':user_id' => $user['user_id']
                    ]);

                    // Send verification email
                    if (sendLoginVerificationEmail($email, $token)) {
                        $error = "A verification link has been sent to your email. Please verify your login.";
                    } else {
                        $error = "Failed to send verification email. Please try again.";
                    }
                } else {
                    // Log failed login attempt
                    $logStmt = $conn->prepare(
                        "INSERT INTO tb_logs (doer, log_action) VALUES (:doer, :action)"
                    );
                    $logStmt->execute([
                        ':doer' => $user['user_id'],
                        ':action' => 'Failed login attempt'
                    ]);

                    $error = "Incorrect email or password. Please try again.";
                }
            } else {
                $error = "Incorrect email or password. Please try again.";
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = "An error occurred while processing your request. Please try again later.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <title>Login Form</title>
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
    <div class="container">
        <input type="checkbox" id="flip">
        <div class="cover">
            <div class="front">
                <div class="text">
                    <span class="text-1">Your files, your security.<br>Access with confidence.</span>
                    <span class="text-2">Welcome to SecureFile.</span>
                </div>
            </div>
            <div class="back">
                <img class="backImg" src="logInimg.jpg" alt="">
            </div>
        </div>
        <div class="forms">
            <div class="logo-section">
                <img src="assets\img\logo.png" alt="Logo" class="logo">
                <div class="securefile-text">
                    <span class="secure">Secure</span><span class="file">File</span>
                </div>
            </div>

            <div class="form-content">
                <div class="login-form">
                    <div class="title">Login</div>
                    <form action="" method="POST">
                        <div class="input-boxes">
                            <div class="input-box">
                                <i class="fas fa-envelope"></i>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" id="email" placeholder="Enter your email" required>
                            </div>
                            <div class="input-box">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="password" value="<?php echo htmlspecialchars($password); ?>" id="password" placeholder="Enter your password" required>
                                <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
                            </div>

                            <?php if (!empty($error)): ?>
                                <div id="error-message" class="error-message" style="color: red; font-size: 12px; text-align:center"> <?php echo $error; ?> </div>
                            <?php endif; ?>

                            <div class="text"><a href="#">Forgot password?</a></div>
                            <div class="button input-box">
                                <input type="submit" value="Sign in">
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById("password");
            const toggleIcon = document.querySelector(".toggle-password");

            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                toggleIcon.classList.remove("fa-eye");
                toggleIcon.classList.add("fa-eye-slash");
            } else {
                passwordInput.type = "password";
                toggleIcon.classList.remove("fa-eye-slash");
                toggleIcon.classList.add("fa-eye");
            }
        }

        // Hide error message after 5 seconds
        window.onload = function() {
            const errorMessage = document.getElementById("error-message");
            if (errorMessage) {
                setTimeout(function() {
                    errorMessage.style.display = 'none';
                }, 5000);

                // Clear the error message if the user starts typing
                const inputs = document.querySelectorAll("input");
                inputs.forEach(function(input) {
                    input.addEventListener('input', function() {
                        errorMessage.style.display = 'none';
                    });
                });
            }
        };
    </script>
</body>

</html>