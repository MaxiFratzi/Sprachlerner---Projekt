<?php
// Start Session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "Not logged in";
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Check if it's a valid tracking request
if (!isset($_POST['learned']) || $_POST['learned'] != '1') {
    echo "Invalid request";
    exit();
}

// Database connection
$servername = "localhost";
$dbUsername = "root";
$dbPassword = "root"; // Match with your main.php
$dbName = "vokabeln"; // Match with your main.php

$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);

// Check connection
if ($conn->connect_error) {
    echo "Connection failed: " . $conn->connect_error;
    exit();
}

// Get today's date
$today = date('Y-m-d');

// Create table if it doesn't exist
$createTableSQL = "CREATE TABLE IF NOT EXISTS vocabulary_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    learn_date DATE NOT NULL,
    count INT NOT NULL DEFAULT 0,
    UNIQUE KEY user_date (user_id, learn_date)
)";

if (!$conn->query($createTableSQL)) {
    echo "Error creating table: " . $conn->error;
    exit();
}

// Check if entry exists for today
$stmt = $conn->prepare("SELECT count FROM vocabulary_tracking WHERE user_id = ? AND learn_date = ?");
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Update existing entry
    $row = $result->fetch_assoc();
    $new_count = $row['count'] + 1;
    
    $update_stmt = $conn->prepare("UPDATE vocabulary_tracking SET count = ? WHERE user_id = ? AND learn_date = ?");
    $update_stmt->bind_param("iis", $new_count, $user_id, $today);
    $update_stmt->execute();
    
    echo "Updated: " . $new_count;
    $update_stmt->close();
} else {
    // Create new entry
    $insert_stmt = $conn->prepare("INSERT INTO vocabulary_tracking (user_id, learn_date, count) VALUES (?, ?, 1)");
    $insert_stmt->bind_param("is", $user_id, $today);
    $insert_stmt->execute();
    
    echo "Created new entry: 1";
    $insert_stmt->close();
}

$stmt->close();
$conn->close();
?>