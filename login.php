<?php
session_start();
require_once 'config/database.php'; // Include your database class

$error = ''; // Initialize the error variable
$username = ''; // Initialize the username variable

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and retrieve user input
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        try {
            // Create a database instance and open a connection
            $db = new database();
            $conn = $db->getConnection();

            // Prepare and execute the SQL query
            $stmt = $conn->prepare(
                "SELECT l.username, l.password, u.user_id, u.user_role, u.user_status
                 FROM tb_logindetails l
                 JOIN tb_userdetails u ON l.user_id = u.user_id
                 WHERE l.username = :username AND u.user_status = 1"
            );
            $stmt->bindParam(':username', $username);
            $stmt->execute();

            // Fetch the result
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user != null) {
                if ($user && password_verify($password, $user['password'])) {
                    // Log successful login using the user_id
                    $logStmt = $conn->prepare("INSERT INTO tb_logs (doer, log_action) VALUES (:doer, :action)");
                    $logStmt->execute([
                        ':doer' => $user['user_id'],  // Use user_id, not username
                        ':action' => 'Successfully logged in'
                    ]);

                    // Set session variables for the logged-in user
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['user_role'];

                    // Check if the user is an Administrator
                    if ($user['user_role'] === 'Administrator') {
                        // Redirect to the administrator profile page
                        header("Location: /SECUREFILE/securefile/src/roles/admin/index.php");
                        exit();
                    } else {
                        // Redirect to the dashboard or another page for non-admin users
                        header("Location: /SECUREFILE/securefile/src/roles/client/index.php");
                        exit();
                    }
                } else {
                    // Log failed login attempt using the user_id
                    $logStmt = $conn->prepare("INSERT INTO tb_logs (doer, log_action) VALUES (:doer, :action)");
                    $logStmt->execute([
                        ':doer' => $user['user_id'],  // Use user_id, not username
                        ':action' => 'Failed login attempt'
                    ]);

                    $error = "Incorrect username or password. Please try again.";
                }
            } else {
                $error = "Incorrect username or password. Please try again.";
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
    <script src="https://accounts.google.com/gsi/client" async defer></script>
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
                <img src="logo.png" alt="Logo" class="logo">
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
                                <input type="text" name="username" text="<?php echo $username; ?>" id="username" placeholder="Enter your username" required>
                            </div>
                            <div class="input-box">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="password" id="password" placeholder="Enter your password" required>
                                <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
                            </div>

                            <?php if (!empty($error)): ?>
                                <div id="error-message" class="error-message" style="color: red; font-size: 12px; text-align:center"> <?php echo $error; ?> </div>
                            <?php endif; ?>

                            <div class="text"><a href="#">Forgot password?</a></div>
                            <div class="button input-box">
                                <input type="submit" value="Sign in">
                            </div>
                            <div id="g_id_onload"
                                data-client_id="YOUR_CLIENT_ID"
                                data-context="signin"
                                data-ux_mode="popup"
                                data-callback="handleCredentialResponse"
                                data-auto_prompt="false">
                            </div>
                            <div class="g_id_signin" data-type="standard"></div>
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
                    //errorMessage.style.visibility = 'hidden';
                }, 5000); // Hide after 5 seconds

                // Clear the error message if the user starts typing
                const inputs = document.querySelectorAll("input");
                inputs.forEach(function(input) {
                    input.addEventListener('input', function() {
                        errorMessage.style.display = 'none';
                        //errorMessage.style.visibility = 'hidden';
                    });
                });
            }
        };
    </script>

</body>

</html>