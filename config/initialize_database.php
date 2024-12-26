<?php

require_once __DIR__ . '/config.php';

try {
    echo "<h2>Starting database connection...</h2>";
    $pdo = new PDO("mysql:host=" . DB_SERVER, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    echo "<h2 style='color: red;'>Database connection failed: " . $e->getMessage() . "</h2>";
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
    exit;
}

// Select the database
try {
    echo "<h2>Selecting database...</h2>";
    $pdo->exec("USE " . DB_NAME);
} catch (PDOException $e) {
    error_log("Error selecting database: " . $e->getMessage());
    echo "<h2 style='color: red;'>Error selecting database: " . $e->getMessage() . "</h2>";
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
        user_status TINYINT(1) DEFAULT 1
    ) AUTO_INCREMENT=2024000;");
    error_log('Table tb_admin_userdetails created successfully.');
} catch (PDOException $e) {
    error_log("Error creating table tb_admin_userdetails: " . $e->getMessage());
    echo "<h2 style='color: red;'>Error creating table tb_admin_userdetails: " . $e->getMessage() . "</h2>";
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
        user_status TINYINT(1) DEFAULT 1
    );");
    error_log('Table tb_client_userdetails created successfully.');
} catch (PDOException $e) {
    error_log("Error creating table tb_client_userdetails: " . $e->getMessage());
    echo "<h2 style='color: red;'>Error creating table tb_client_userdetails: " . $e->getMessage() . "</h2>";
    exit;
}

// Create `tb_admin_logindetails` table
try {
    echo "<h2>Creating table tb_admin_logindetails...</h2>";
    $pdo->exec("CREATE TABLE IF NOT EXISTS tb_admin_logindetails (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        password VARCHAR(255) NOT NULL,
        user_status VARCHAR(255) NULL,
        user_log VARCHAR(255) NULL,
        FOREIGN KEY (user_id) REFERENCES tb_admin_userdetails(user_id) ON DELETE CASCADE
    );");
    error_log('Table tb_admin_logindetails created successfully.');
} catch (PDOException $e) {
    error_log("Error creating table tb_admin_logindetails: " . $e->getMessage());
    echo "<h2 style='color: red;'>Error creating table tb_admin_logindetails: " . $e->getMessage() . "</h2>";
    exit;
}

// Create `tb_client_logindetails` table
try {
    echo "<h2>Creating table tb_client_logindetails...</h2>";
    $pdo->exec("CREATE TABLE IF NOT EXISTS tb_client_logindetails (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        password VARCHAR(255) NOT NULL,
        user_status VARCHAR(255) NULL,
        user_log VARCHAR(255) NULL,
        FOREIGN KEY (user_id) REFERENCES tb_client_userdetails(user_id) ON DELETE CASCADE
    );");
    error_log('Table tb_client_logindetails created successfully.');
} catch (PDOException $e) {
    error_log("Error creating table tb_client_logindetails: " . $e->getMessage());
    echo "<h2 style='color: red;'>Error creating table tb_client_logindetails: " . $e->getMessage() . "</h2>";
    exit;
}

// Create `tb_logs` table
try {
    echo "<h2>Creating table tb_logs...</h2>";
    $pdo->exec("CREATE TABLE IF NOT EXISTS tb_logs (
        log_id INT AUTO_INCREMENT PRIMARY KEY,
        log_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        doer VARCHAR(255) NOT NULL,
        log_action VARCHAR(255) NOT NULL
    );");
    error_log('Table tb_logs created successfully.');
} catch (PDOException $e) {
    error_log("Error creating table tb_logs: " . $e->getMessage());
    echo "<h2 style='color: red;'>Error creating table tb_logs: " . $e->getMessage() . "</h2>";
    exit;
}

// Default admin information
$user_id = 2024000;
$user_fname = 'Default';
$user_lname = 'Admin';
$user_email = 'admin@example.com';
$user_role = 'Administrator';
$password = $user_lname . '_' . preg_replace("/[^a-zA-Z0-9]/", "", $user_id);

// Insert default admin into `tb_admin_userdetails`
try {
    echo "<h2>Inserting default administrator into tb_admin_userdetails...</h2>";
    $stmt = $pdo->prepare("INSERT INTO tb_admin_userdetails (user_id, user_fname, user_lname, user_email, user_role, user_status)
        VALUES (:user_id, :user_fname, :user_lname, :user_email, :user_role, 1)
        ON DUPLICATE KEY UPDATE user_id=user_id;");
    $stmt->execute([
        ':user_id' => $user_id,
        ':user_fname' => $user_fname,
        ':user_lname' => $user_lname,
        ':user_email' => $user_email,
        ':user_role' => $user_role
    ]);
    error_log('Default administrator added successfully.');
} catch (PDOException $e) {
    error_log("Error inserting default admin into tb_admin_userdetails: " . $e->getMessage());
    echo "<h2 style='color: red;'>Error inserting default admin into tb_admin_userdetails: " . $e->getMessage() . "</h2>";
    exit;
}

// Insert default admin login details into `tb_admin_logindetails`
try {
    echo "<h2>Inserting login details for default administrator into tb_admin_logindetails...</h2>";
    $stmt = $pdo->prepare("INSERT INTO tb_admin_logindetails (password, user_id)
        VALUES (:password, :user_id)
        ON DUPLICATE KEY UPDATE user_id=user_id;");
    $stmt->execute([
        ':password' => password_hash($password, PASSWORD_DEFAULT),
        ':user_id' => $user_id
    ]);
    error_log('Login details for default administrator added successfully.');
} catch (PDOException $e) {
    error_log("Error inserting login details into tb_admin_logindetails: " . $e->getMessage());
    echo "<h2 style='color: red;'>Error inserting login details into tb_admin_logindetails: " . $e->getMessage() . "</h2>";
    exit;
}

// Log the default admin setup
try {
    echo "<h2>Logging default administrator setup...</h2>";
    $logStmt = $pdo->prepare("INSERT INTO tb_logs (doer, log_action) VALUES (:doer, :action)");
    $logStmt->execute([
        ':doer' => 'Administrator', 
        ':action' => 'Default administrator added'
    ]);
    error_log('Log for default admin action added successfully.');
} catch (PDOException $e) {
    error_log("Error logging default admin action: " . $e->getMessage());
    echo "<h2 style='color: red;'>Error logging default admin action: " . $e->getMessage() . "</h2>";
    exit;
}

// Close the connection
$pdo = null;

// Display success message
echo "<h2 style='color: green;'>Database and tables created successfully!</h2>";
