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

// Registrierungs-Verarbeitung
if (isset($_POST['register'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    
    // Prüfen, ob Email bereits existiert
    $check_sql = "SELECT accid FROM account WHERE email = '$email'";
    $result = $conn->query($check_sql);
    
    if ($result->num_rows > 0) {
        // E-Mail bereits registriert
        $_SESSION['register_error'] = "Diese E-Mail wird bereits verwendet!";
        header("Location: main.php");
        exit();
    } else {
        // Passwort hashen
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Neuen Benutzer speichern
        $sql = "INSERT INTO account (username, password, email) VALUES ('$username', '$hashed_password', '$email')";
        
        if ($conn->query($sql) === TRUE) {
            // Benutzer-ID für weitere Schritte
            $new_user_id = $conn->insert_id;
            
            // Optional: Erstelle einen Eintrag in der benutzer-Tabelle
            $default_level = "E"; // Standardmäßig einfacher Schwierigkeitsgrad
            $insert_benutzer = "INSERT INTO benutzer (schwierigkeit, accid) VALUES ('$default_level', $new_user_id)";
            $conn->query($insert_benutzer);
            
            // Erfolgreiche Registrierung
            $_SESSION['register_success'] = "Registrierung erfolgreich! Du kannst dich jetzt anmelden.";
            header("Location: main.php");
            exit();
        } else {
            // Fehler bei der Registrierung
            $_SESSION['register_error'] = "Fehler bei der Registrierung: " . $conn->error;
            header("Location: main.php");
            exit();
        }
    }
}

$conn->close();
?>