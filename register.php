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

// Registrierung verarbeiten
if (isset($_POST['register'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    // Überprüfen, ob Benutzername oder E-Mail bereits existieren
    $check_stmt = $conn->prepare("SELECT * FROM account WHERE username = ? OR email = ?");
    $check_stmt->bind_param("ss", $username, $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $register_error = "Benutzername oder E-Mail bereits vorhanden";
    } else {
        // Passwort hashen
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Benutzer in Datenbank einfügen
        $insert_stmt = $conn->prepare("INSERT INTO account (username, email, password) VALUES (?, ?, ?)");
        $insert_stmt->bind_param("sss", $username, $email, $hashed_password);

        if ($insert_stmt->execute()) {
            // Automatisches Login nach Registrierung
            $user_id = $conn->insert_id;
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;

            // Weiterleitung zur Hauptseite
            header("Location: main.php");
            exit();
        } else {
            $register_error = "Fehler bei der Registrierung: " . $conn->error;
        }

        $insert_stmt->close();
    }

    $check_stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SprachMeister - Registrierung</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f4f4;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .register-container {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .btn-primary {
            background-color: #4255ff;
            border-color: #4255ff;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2 class="text-center mb-4">SprachMeister Registrierung</h2>
        
        <?php if (isset($register_error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($register_error) ?></div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="mb-3">
                <label for="username" class="form-label">Benutzername</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">E-Mail</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Passwort</label>
                <input type="password" class="form-control" id="password" name="password" required minlength="6">
            </div>
            <div class="d-grid">
                <button type="submit" name="register" class="btn btn-primary">Registrieren</button>
            </div>
        </form>
        <div class="text-center mt-3">
            <p>Bereits ein Konto? <a href="login.php">Jetzt anmelden</a></p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>