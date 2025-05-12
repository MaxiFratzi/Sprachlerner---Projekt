<?php
session_start();

// Datenbankverbindung
$host = "localhost";
$username = "root";
$password = "root";
$dbname = "vokabeln";

// Verbindung zur Datenbank herstellen
$conn = new mysqli($host, $username, $password, $dbname);

// Verbindung überprüfen
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

// Login-Verarbeitung
if (isset($_POST['login'])) {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    
    // Email überprüfen
    $sql = "SELECT accid, username, password FROM account WHERE email = '$email'";
    $result = $conn->query($sql);
    
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        // Passwort überprüfen
        if (password_verify($password, $row['password'])) {
            // Erfolgreicher Login
            $_SESSION['user_id'] = $row['accid'];
            $_SESSION['username'] = $row['username'];
            
            // Weiterleitung zur Hauptseite
            header("Location: main.php");
            exit();
        } else {
            // Fehler: Falsches Passwort
            $_SESSION['login_error'] = "Falsches Passwort!";
            header("Location: main.php");
            exit();
        }
    } else {
        // Fehler: Benutzer nicht gefunden
        $_SESSION['login_error'] = "Benutzer nicht gefunden!";
        header("Location: main.php");
        exit();
    }
}

$conn->close();
?>