<?php
require_once __DIR__ . '/config.php';

//Encrypt and decrypt functions
// Load the key and IV from the .meta file
$metaFilePath = "../security/key.meta";

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


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        echo "<h2>Starting database connection...</h2>";
        $pdo = new PDO("mysql:host=" . DB_SERVER, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        echo "<h2 style='color: red;'>Database connection failed: " . $e->getMessage() . "</h2>";
        $errortext = $e->getMessage();
        header("Location: initialize_database?error=$errortext");
        exit;
    }

    // Create the database
    try {
        echo "<h2>Creating database...</h2>";
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
        error_log('Database created successfully or already exists.');
    } catch (PDOException $e) {
        error_log("Error creating database: " . $e->getMessage());
        echo "<h2 style='color: red;'>Error creating database: " . $e->getMessage() . "</h2>";
        $errortext = $e->getMessage();
        header("Location: initialize_database?error=$errortext");
        exit;
    }

    // Select the database
    try {
        echo "<h2>Selecting database...</h2>";
        $pdo->exec("USE " . DB_NAME);
    } catch (PDOException $e) {
        error_log("Error selecting database: " . $e->getMessage());
        echo "<h2 style='color: red;'>Error selecting database: " . $e->getMessage() . "</h2>";
        $errortext = $e->getMessage();
        header("Location: initialize_database?error=$errortext");
        exit;
    }

    // Create `tb_admin_userdetails` table
    try {
        echo "<h2>Creating table tb_admin_userdetails...</h2>";
        $pdo->exec("CREATE TABLE IF NOT EXISTS tb_admin_userdetails (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        user_fname VARCHAR(255) NOT NULL,
        user_lname VARCHAR(255) NOT NULL,
        user_email VARCHAR(255) NOT NULL,
        user_role VARCHAR(50) NOT NULL,
        user_img_url VARCHAR(255),
        user_status TINYINT(1) DEFAULT 1,
        INDEX idx_user_email (user_email)
    ) AUTO_INCREMENT=2024000;");
        error_log('Table tb_admin_userdetails created successfully.');
    } catch (PDOException $e) {
        error_log("Error creating table tb_admin_userdetails: " . $e->getMessage());
        echo "<h2 style='color: red;'>Error creating table tb_admin_userdetails: " . $e->getMessage() . "</h2>";
        $errortext = $e->getMessage();
        header("Location: initialize_database?error=$errortext");
        exit;
    }

    // Create `tb_client_userdetails` table
    try {
        echo "<h2>Creating table tb_client_userdetails...</h2>";
        $pdo->exec("CREATE TABLE IF NOT EXISTS tb_client_userdetails (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        user_fname VARCHAR(255) NOT NULL,
        user_lname VARCHAR(255) NOT NULL,
        user_email VARCHAR(255) NOT NULL,
        user_role VARCHAR(50) NOT NULL,
        user_img_url VARCHAR(255),
        user_status TINYINT(1) DEFAULT 1,
        INDEX idx_user_email (user_email)
    );");
        error_log('Table tb_client_userdetails created successfully.');
    } catch (PDOException $e) {
        error_log("Error creating table tb_client_userdetails: " . $e->getMessage());
        echo "<h2 style='color: red;'>Error creating table tb_client_userdetails: " . $e->getMessage() . "</h2>";
        $errortext = $e->getMessage();
        header("Location: initialize_database?error=$errortext");
        exit;
    }

    // Create `tb_admin_logindetails` table
    try {
        echo "<h2>Creating table tb_admin_logindetails...</h2>";
        $pdo->exec("CREATE TABLE IF NOT EXISTS tb_admin_logindetails (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        password VARCHAR(255) NOT NULL,
        user_status VARCHAR(255) NULL DEFAULT 'Offline',
        user_log DATETIME NULL,
        token VARCHAR(255) NULL,
        verified TINYINT(1) DEFAULT 0,
        token_expiration DATETIME NULL,
        FOREIGN KEY (user_id) REFERENCES tb_admin_userdetails(user_id) ON DELETE CASCADE
    );");
        error_log('Table tb_admin_logindetails created successfully.');
    } catch (PDOException $e) {
        error_log("Error creating table tb_admin_logindetails: " . $e->getMessage());
        echo "<h2 style='color: red;'>Error creating table tb_admin_logindetails: " . $e->getMessage() . "</h2>";
        $errortext = $e->getMessage();
        header("Location: initialize_database?error=$errortext");
        exit;
    }

    // Create `tb_client_logindetails` table
    try {
        echo "<h2>Creating table tb_client_logindetails...</h2>";
        $pdo->exec("CREATE TABLE IF NOT EXISTS tb_client_logindetails (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        password VARCHAR(255) NOT NULL,
        user_status VARCHAR(255) NULL DEFAULT 'Offline',
        user_log DATETIME NULL,
        token VARCHAR(255) NULL,
        verified TINYINT(1) DEFAULT 0,
        token_expiration DATETIME NULL, 
        FOREIGN KEY (user_id) REFERENCES tb_client_userdetails(user_id) ON DELETE CASCADE
    );");
        error_log('Table tb_client_logindetails created successfully.');
    } catch (PDOException $e) {
        error_log("Error creating table tb_client_logindetails: " . $e->getMessage());
        echo "<h2 style='color: red;'>Error creating table tb_client_logindetails: " . $e->getMessage() . "</h2>";
        $errortext = $e->getMessage();
        header("Location: initialize_database?error=$errortext");
        exit;
    }

    // Create `tb_logs` table
    try {
        echo "<h2>Creating table tb_logs...</h2>";
        $pdo->exec("CREATE TABLE IF NOT EXISTS tb_logs (
        log_id INT AUTO_INCREMENT PRIMARY KEY,
        log_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        doer VARCHAR(255) NOT NULL,
        role VARCHAR(50) NOT NULL,
        log_action VARCHAR(255) NOT NULL
    );");
        error_log('Table tb_logs created successfully.');
    } catch (PDOException $e) {
        error_log("Error creating table tb_logs: " . $e->getMessage());
        echo "<h2 style='color: red;'>Error creating table tb_logs: " . $e->getMessage() . "</h2>";
        $errortext = $e->getMessage();
        header("Location: initialize_database?error=$errortext");
        exit;
    }

    // User details for default admin and other users
    $users = [
        [
            'user_id' => 2024000,
            'user_fname' => 'Karl',
            'user_lname' => 'Cornejo',
            'user_email' => 'kocornejo00294@usep.edu.ph',
            'user_role' => 'Administrator',
        ],
        [
            'user_id' => 2024001,
            'user_fname' => 'Debbie Michelle',
            'user_lname' => 'Gerodias',
            'user_email' => 'dmbgerodias00151@usep.edu.ph',
            'user_role' => 'Administrator',
        ],
        [
            'user_id' => 2024002,
            'user_fname' => 'Precious Lyn',
            'user_lname' => 'Suico',
            'user_email' => 'plmsuico00102@usep.edu.ph',
            'user_role' => 'Administrator',
        ],
        [
            'user_id' => 2024003,
            'user_fname' => 'Christeline Jane',
            'user_lname' => 'Tabacon',
            'user_email' => 'cjmtabacon00103@usep.edu.ph',
            'user_role' => 'Administrator',
        ]
    ];

    foreach ($users as $user) {
        $password = $user['user_lname'] . '_' . preg_replace("/[^a-zA-Z0-9]/", "", $user['user_id']);

        // Insert user into `tb_admin_userdetails`
        try {
            echo "<h2>Inserting user: {$user['user_fname']} {$user['user_lname']} into tb_admin_userdetails...</h2>";
            $stmt = $pdo->prepare("INSERT INTO tb_admin_userdetails (user_id, user_fname, user_lname, user_email, user_role, user_status)
            VALUES (:user_id, :user_fname, :user_lname, :user_email, :user_role, 1)
            ON DUPLICATE KEY UPDATE user_id=user_id;");
            $stmt->execute([
                ':user_id' => $user['user_id'],
                ':user_fname' => aesEncrypt($user['user_fname']),
                ':user_lname' => aesEncrypt($user['user_lname']),
                ':user_email' => aesEncrypt($user['user_email']),
                ':user_role' => $user['user_role']
            ]);
            error_log("User {$user['user_fname']} {$user['user_lname']} added successfully.");
        } catch (PDOException $e) {
            error_log("Error inserting user {$user['user_fname']} {$user['user_lname']} into tb_admin_userdetails: " . $e->getMessage());
            echo "<h2 style='color: red;'>Error inserting user: " . $e->getMessage() . "</h2>";
            $errortext = $e->getMessage();
            header("Location: initialize_database?error=$errortext");
            exit;
        }

        // Insert login details into `tb_admin_logindetails`
        try {
            echo "<h2>Inserting login details for user: {$user['user_fname']} {$user['user_lname']}...</h2>";
            $stmt = $pdo->prepare("INSERT INTO tb_admin_logindetails (password, user_id)
            VALUES (:password, :user_id)
            ON DUPLICATE KEY UPDATE user_id=user_id;");
            $stmt->execute([
                ':password' => password_hash($password, PASSWORD_DEFAULT),
                ':user_id' => $user['user_id']
            ]);
            error_log("Login details for user {$user['user_fname']} {$user['user_lname']} added successfully.");
        } catch (PDOException $e) {
            error_log("Error inserting login details for user {$user['user_fname']} {$user['user_lname']}: " . $e->getMessage());
            echo "<h2 style='color: red;'>Error inserting login details: " . $e->getMessage() . "</h2>";
            $errortext = $e->getMessage();
            header("Location: initialize_database?error=$errortext");
            exit;
        }

        // Log the action
        try {
            echo "<h2>Logging action for user: {$user['user_fname']} {$user['user_lname']}...</h2>";
            $logStmt = $pdo->prepare("INSERT INTO tb_logs (doer, role, log_action) VALUES (:doer, :role, :action)");
            $logStmt->execute([
                ':doer' => aesEncrypt('System'),
                ':role' => 'System',
                ':action' => "Administrator user {$user['user_id']} added successfully."
            ]);
            error_log("Log for user {$user['user_fname']} {$user['user_lname']} added successfully.");
        } catch (PDOException $e) {
            error_log("Error logging action for user {$user['user_fname']} {$user['user_lname']}: " . $e->getMessage());
            echo "<h2 style='color: red;'>Error logging action: " . $e->getMessage() . "</h2>";
            $errortext = $e->getMessage();
            header("Location: initialize_database?error=$errortext");
            exit;
        }
    }

    try {
        echo "<h2>Creating table tb_files...</h2>";
        $pdo->exec("CREATE TABLE IF NOT EXISTS tb_files (
            file_id INT AUTO_INCREMENT PRIMARY KEY,
            owner_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            size VARCHAR(50) NOT NULL,
            status TINYINT(1) DEFAULT 1,
            hashed_key VARCHAR(255) NOT NULL,
            created_at DATETIME,
            updated_at DATETIME,
            UNIQUE (owner_id, name),
            FOREIGN KEY (owner_id) REFERENCES tb_client_userdetails(user_id) ON DELETE CASCADE
        );");
        error_log('Table tb_files created successfully.');
    } catch (PDOException $e) {
        error_log("Error creating table tb_files: " . $e->getMessage());
        echo "<h2 style='color: red;'>Error creating table tb_files: " . $e->getMessage() . "</h2>";
        $errortext = $e->getMessage();
        header("Location: initialize_database?error=$errortext");
        exit;
    }

    // Create `tb_shared_files` table
    try {
        echo "<h2>Creating table tb_shared_files...</h2>";
        $pdo->exec("CREATE TABLE IF NOT EXISTS tb_shared_files (
        file_id INT NOT NULL,
        shared_to INT NOT NULL,
        encrypted_key VARCHAR(255) NOT NULL,
        iv VARCHAR(255) NOT NULL,
        shared_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (file_id, shared_to),
        FOREIGN KEY (file_id) REFERENCES tb_files(file_id) ON DELETE CASCADE,
        FOREIGN KEY (shared_to) REFERENCES tb_client_userdetails(user_id) ON DELETE CASCADE
    );");
        error_log('Table tb_shared_files created successfully.');
    } catch (PDOException $e) {
        error_log("Error creating table tb_shared_files: " . $e->getMessage());
        echo "<h2 style='color: red;'>Error creating table tb_shared_files: " . $e->getMessage() . "</h2>";
        exit;
    }

    try {
        echo "<h2>Creating view view_admin_activity_logs...</h2>";
        $pdo->exec("
        CREATE VIEW view_admin_activity_logs AS
        SELECT 
            tb_logs.log_id AS `log_id`,
            tb_admin_userdetails.user_fname AS `lname`,
            tb_admin_userdetails.user_lname AS `fname`,
            tb_admin_userdetails.user_email AS `email_address`,
            tb_logs.log_date AS `date_time`,
            tb_logs.role AS `role`,
            tb_logs.log_action AS `activity`
        FROM 
            tb_logs
        JOIN 
            tb_admin_userdetails 
        ON 
            tb_logs.doer = tb_admin_userdetails.user_id
        WHERE 
            tb_logs.role = 'Administrator'
    ");
        error_log('View view_admin_activity_logs created successfully.');
    } catch (PDOException $e) {
        error_log("Error creating view view_admin_activity_logs: " . $e->getMessage());
        echo "<h2 style='color: red;'>Error creating view view_admin_activity_logs: " . $e->getMessage() . "</h2>";
        $errortext = $e->getMessage();
        header("Location: initialize_database?error=$errortext");
        exit;
    }

    try {
        echo "<h2>Creating view view_client_activity_logs...</h2>";
        $pdo->exec("
        CREATE VIEW view_client_activity_logs AS
        SELECT 
            tb_logs.log_id AS `log_id`,
            tb_client_userdetails.user_fname AS `lname`,
            tb_client_userdetails.user_lname AS `fname`,
            tb_client_userdetails.user_email AS `email_address`,
            tb_logs.log_date AS `date_time`,
            tb_logs.role AS `role`,
            tb_logs.log_action AS `activity`
        FROM 
            tb_logs
        JOIN 
            tb_client_userdetails 
        ON 
            tb_logs.doer = tb_client_userdetails.user_id
        WHERE 
            tb_logs.role IN ('Head', 'Employee')
    ");
        error_log('View view_client_activity_logs created successfully.');
    } catch (PDOException $e) {
        error_log("Error creating view view_client_activity_logs: " . $e->getMessage());
        echo "<h2 style='color: red;'>Error creating view view_client_activity_logs: " . $e->getMessage() . "</h2>";
        $errortext = $e->getMessage();
        header("Location: initialize_database?error=$errortext");
        exit;
    }


    try {
        echo "<h2>Creating view view_system_generated_logs...</h2>";
        $pdo->exec("
        CREATE VIEW view_system_generated_logs AS
        SELECT 
            tb_logs.log_id AS `log_id`,
            tb_logs.doer AS `fname`,
            tb_logs.doer AS `lname`,
            tb_logs.doer AS `email_address`,
            tb_logs.log_date AS `date_time`,
            tb_logs.role AS `role`,
            tb_logs.log_action AS `activity`    
        FROM 
            tb_logs
        WHERE 
            tb_logs.role NOT IN ('Administrator', 'Head', 'Employee')
    ");
        error_log('View view_system_generated_logs created successfully.');
    } catch (PDOException $e) {
        error_log("Error creating view view_system_generated_logs: " . $e->getMessage());
        echo "<h2 style='color: red;'>Error creating view view_system_generated_logs: " . $e->getMessage() . "</h2>";
        $errortext = $e->getMessage();
        header("Location: initialize_database?error=$errortext");
        exit;
    }

    // Close the connection
    $pdo = null;

    // Display success message
    echo "<h2 style='color: green;'>Database and tables created successfully!</h2>";
    header("Location: create-success");
    exit;
} else {
    header("Location: initialize_database");
    exit;
}
