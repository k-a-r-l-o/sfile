<?php
session_start();
require_once 'config/database.php'; // Include your database class

$error = ''; // Initialize the error variable
$email = ''; // Initialize the email variable
$password = ''; // Initialize the password variable

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and retrieve user input
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        try {
            // Create a database instance and open a connection
            $db = new database();
            $conn = $db->getConnection();

            // Prepare and execute the SQL query
            $stmt = $conn->prepare(
                "SELECT l.password, u.user_id, u.user_role, l.user_status
                 FROM tb_admin_logindetails l
                 JOIN tb_admin_userdetails u ON l.user_id = u.user_id
                 WHERE u.user_email = :email"
            );
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            // Fetch the result
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user != null) {
                if ($user['user_status'] == "Online") {
                    $error = "Your account is already logged in. Please log out from other devices.";
                } elseif (password_verify($password, $user['password'])) {
                    // Update user status to indicate they are logged in
                    $updateStatusStmt = $conn->prepare("UPDATE tb_admin_logindetails SET user_status = 'Onlin' WHERE user_id = :user_id");
                    $updateStatusStmt->execute([':user_id' => $user['user_id']]);

                    // Log successful login using the user_id
                    $logStmt = $conn->prepare("INSERT INTO tb_logs (doer, log_action) VALUES (:doer, :action)");
                    $logStmt->execute([
                        ':doer' => $user['user_id'],
                        ':action' => 'Successfully logged in'
                    ]);

                    // Set session variables for the logged-in user
                    $_SESSION['email'] = $email;
                    $_SESSION['role'] = $user['user_role'];

                    // Redirect based on user role
                    if ($user['user_role'] === 'Administrator') {
                        header("Location: src/roles/admin/");
                        exit();
                    } else {
                        header("Location: src/roles/client/");
                        exit();
                    }
                    exit();
                } else {
                    // Log failed login attempt using the user_id
                    $logStmt = $conn->prepare("INSERT INTO tb_logs (doer, log_action) VALUES (:doer, :action)");
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
