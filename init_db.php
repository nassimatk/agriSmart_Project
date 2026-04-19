<?php
$host = "localhost";
$username = "root";
$password = ""; // Default XAMPP
$port = 3306;

// Create connection
$conn = new mysqli($host, $username, $password, "", $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS agrismart";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully\n";
} else {
    echo "Error creating database: " . $conn->error . "\n";
}

// Select database
$conn->select_db("agrismart");

// Create Users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    firstname VARCHAR(50) NOT NULL,
    lastname VARCHAR(50) NOT NULL,
    farm_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'user',
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table users created successfully\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// Create Plant Photos table
$sql = "CREATE TABLE IF NOT EXISTS plant_photos (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(6) UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    description TEXT,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table plant_photos created successfully\n";
} else {
    echo "Error creating plant_photos table: " . $conn->error . "\n";
}

// Create Sensor Readings table
$sql = "CREATE TABLE IF NOT EXISTS sensor_readings (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    air_temperature FLOAT,
    air_humidity FLOAT,
    soil_temperature FLOAT,
    soil_moisture FLOAT,
    light_intensity FLOAT,
    prediction VARCHAR(50),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table sensor_readings created successfully\n";
} else {
    echo "Error creating sensor_readings table: " . $conn->error . "\n";
}

// Insert admin user
$admin_password = password_hash("admin123", PASSWORD_DEFAULT);
$sql = "INSERT IGNORE INTO users (firstname, lastname, farm_name, email, password, role) 
        VALUES ('Admin', 'System', 'Admin HQ', 'admin@souss-massa.ma', '$admin_password', 'admin')";
if ($conn->query($sql) === TRUE) {
    echo "Admin user created successfully\n";
} else {
    echo "Error inserting admin: " . $conn->error . "\n";
}

$conn->close();
?>
