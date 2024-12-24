<?php

require_once __DIR__ . '\config.php';

try {
    // Create a new PDO instance to handle database creation
    $pdo = new PDO("mysql:host=" . DB_SERVER, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create the database if it does not exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    error_log('Database created successfully or already exists.');

    // Select the database
    $pdo->exec("USE " . DB_NAME);

    // Create `tb_userdetails` table
    $pdo->exec("CREATE TABLE IF NOT EXISTS tb_userdetails (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        user_fname VARCHAR(255) NOT NULL,
        user_lname VARCHAR(255) NOT NULL,
        user_email VARCHAR(255) NOT NULL,
        user_role VARCHAR(50) NOT NULL,
        user_img_url VARCHAR(255),
        user_status TINYINT(1) DEFAULT 1
    ) AUTO_INCREMENT=2024000;");
    error_log('Table tb_userdetails created successfully.');

    // Create `tb_logindetails` table
    $pdo->exec("CREATE TABLE IF NOT EXISTS tb_logindetails (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL,
        password VARCHAR(255) NOT NULL,
        FOREIGN KEY (user_id) REFERENCES tb_userdetails(user_id) ON DELETE CASCADE
    );");
    error_log('Table tb_logindetails created successfully.');

    // Create `tb_logs` table
    $pdo->exec("CREATE TABLE IF NOT EXISTS tb_logs (
        log_id INT AUTO_INCREMENT PRIMARY KEY,
        log_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        doer VARCHAR(255) NOT NULL,
        log_action VARCHAR(255) NOT NULL
    );");
    error_log('Table tb_logs created successfully.');

    // Default admin information
    $user_id = 2024000;
    $user_fname = 'Precious Lyn';
    $user_lname = 'Suico';
    $user_email = 'plmsuico00102@usep.edu.ph';
    $user_role = 'Administrator';

    // Generate the username by removing spaces and special characters from fname
    $username = $user_id . preg_replace("/[^a-zA-Z0-9]/", "", $user_fname); // Remove special characters and spaces

    // Generate the password by combining user_id and fname with an underscore
    $password = $user_id . '_' . preg_replace("/[^a-zA-Z0-9]/", "", $user_fname);

    // Insert default admin account into `tb_userdetails`
    $stmt = $pdo->prepare("
        INSERT INTO tb_userdetails (user_id, user_fname, user_lname, user_email, user_role, user_status)
        VALUES (:user_id, :user_fname, :user_lname, :user_email, :user_role, 1)
        ON DUPLICATE KEY UPDATE user_id=user_id;
    ");
    $stmt->execute([
        ':user_id' => $user_id,
        ':user_fname' => $user_fname,
        ':user_lname' => $user_lname,
        ':user_email' => $user_email,
        ':user_role' => $user_role
    ]);
    error_log('Default administrator added successfully.');

    // Insert the username and password into `tb_logindetails`
    $stmt = $pdo->prepare("
        INSERT INTO tb_logindetails (username, password, user_id)
        VALUES (:username, :password, :user_id)
        ON DUPLICATE KEY UPDATE username=username;
    ");
    $stmt->execute([
        ':username' => $username,
        ':password' => password_hash($password, PASSWORD_DEFAULT), // Store hashed password
        ':user_id' => $user_id
    ]);
    error_log('Login details for default administrator added successfully.');


    // Log entry for default admin setup
    $logStmt = $pdo->prepare("INSERT INTO tb_logs (doer, log_action) VALUES (:doer, :action)");
    $logStmt->execute([
        ':doer' => 'System',
        ':action' => 'Default administrator added'
    ]);
    error_log('Log for default admin action added successfully.');

    // Close the connection
    $pdo = null;

    // Display success message
    echo "<h2 style='color: green;'>Database and tables created successfully!</h2>";
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    // Display error message
    echo "<h2 style='color: red;'>Error creating database and tables: " . $e->getMessage() . "</h2>";
}
