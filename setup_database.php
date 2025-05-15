<?php
// Database connection settings
$servername = "localhost";
$username = "root";  // Change this to your MySQL username
$password = "";      // Change this to your MySQL password

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS pasfotos";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully or already exists<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select the database
$conn->select_db("pasfotos");

// Create classes table
$sql = "CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Classes table created successfully<br>";
} else {
    echo "Error creating classes table: " . $conn->error . "<br>";
}

// Create students table
$sql = "CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    photo_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Students table created successfully<br>";
} else {
    echo "Error creating students table: " . $conn->error . "<br>";
}

// Check if classes table is empty
$result = $conn->query("SELECT COUNT(*) as count FROM classes");
$row = $result->fetch_assoc();

// Insert sample classes if the table is empty
if ($row['count'] == 0) {
    $sql = "INSERT INTO classes (class_name) VALUES 
    ('1A'), ('1B'), ('2A'), ('2B'), ('3A'), ('3B')";
    
    if ($conn->query($sql) === TRUE) {
        echo "Sample classes added successfully<br>";
    } else {
        echo "Error adding sample classes: " . $conn->error . "<br>";
    }
}

$conn->close();
echo "Database setup completed!";
?>
