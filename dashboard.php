<?php
// Start Session
session_start();

// Database connection
$host = "localhost";
$username = "root"; // Ändere nach Bedarf
$password = "root"; // Ändere nach Bedarf
$dbname = "vokabeln"; // Deine Datenbankname

// Verbindung zur Datenbank herstellen
$conn = new mysqli($host, $username, $password, $dbname);

// Verbindung überprüfen
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

// Benutzerdaten abrufen
$user_id = $_SESSION['user_id'];    
$sql = "SELECT username FROM account WHERE accid = $user_id";
$result = $conn->query($sql);
$user = $result->fetch_assoc();

// Lernfortschritt abrufen
$progress_sql = "SELECT 
                COUNT(CASE WHEN vocabulary_level = 'easy' THEN 1 END) as easy_count,
                COUNT(CASE WHEN vocabulary_level = 'medium' THEN 1 END) as medium_count,
                COUNT(CASE WHEN vocabulary_level = 'hard' THEN 1 END) as hard_count
                FROM learning_progress 
                WHERE accid = $user_id";
$progress_result = $conn->query($progress_sql);
$progress = $progress_result->fetch_assoc();

// Lernsets abrufen
$sets_sql = "SELECT * FROM learning_sets WHERE accid = $user_id ORDER BY created_at DESC LIMIT 3";
$sets_result = $conn->query($sets_sql);

// Quiz-Ergebnisse abrufen
$quiz_sql = "SELECT * FROM quiz_results WHERE accid = $user_id ORDER BY completed_at DESC LIMIT 3";
$quiz_result = $conn->query($quiz_sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SprachMeister</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Fontawesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4255ff;
            --secondary-color: #ff8a00;
            --light-blue: #b1f4ff;
            --pink: #ffb1f4;
            --orange: #ffcf8a;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .navbar {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .sidebar {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
        }
        
        .main-content {
            padding: 20px;
        }
        
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: rgba(66, 85, 255, 0.1);
            border-bottom: none;
            font-weight: 600;
        }
        
        .level-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .level-easy {
            background-color: #d4edda;
            color: #155724;
        }
        
        .level-medium {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .level-hard {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .progress {
            height: 10px;
            border-radius: 5px;
        }
        
        .feature-box {
            background-color: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
        }
        
        .feature-box:hover {
            transform: translateY(-5px);
        }
        
        .feature-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 8px 20px;
            font-weight: 600;
            border-radius: 50px;
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 8px 20px;
            font-weight: 600;
            border-radius: 50px;
        }