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
        // Passwort in Klartext speichern (NICHT empfohlen für Produktivumgebungen!)
        $insert_stmt = $conn->prepare("INSERT INTO account (username, email, password) VALUES (?, ?, ?)");
        $insert_stmt->bind_param("sss", $username, $email, $password);

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
    <!-- Font Awesome für Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            padding: 2.5rem;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            position: relative;
        }
        
        .back-button {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: none;
            border: none;
            color: #6c757d;
            font-size: 1.2rem;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .back-button:hover {
            color: #4255ff;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
            margin-top: 1rem;
        }
        
        .register-header h2 {
            color: #4255ff;
            font-weight: bold;
        }
        
        .btn-primary {
            background-color: #4255ff;
            border-color: #4255ff;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: #3245e6;
            border-color: #3245e6;
        }
        
        .form-control:focus {
            border-color: #4255ff;
            box-shadow: 0 0 0 0.2rem rgba(66, 85, 255, 0.25);
        }
        
        .login-link {
            text-align: center;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <!-- Zurück-Button -->
        <button class="back-button" onclick="window.location.href='index.php'" title="Zurück zur Startseite">
            <i class="fas fa-arrow-left"></i>
        </button>
        
        <div class="register-header">
            <h2>SprachMeister</h2>
            <p>Erstelle dein Konto und beginne deine Lernreise</p>
        </div>
        
        <?php if (isset($register_error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($register_error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="mb-3">
                <label for="username" class="form-label">Benutzername</label>
                <input type="text" class="form-control" id="username" name="username" required 
                       placeholder="Dein Benutzername"
                       value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">E-Mail-Adresse</label>
                <input type="email" class="form-control" id="email" name="email" required 
                       placeholder="Deine E-Mail-Adresse"
                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Passwort</label>
                <input type="password" class="form-control" id="password" name="password" required minlength="6" 
                       placeholder="Mindestens 6 Zeichen">
            </div>
            <div class="d-grid">
                <button type="submit" name="register" class="btn btn-primary btn-lg">Registrieren</button>
            </div>
            
            <div class="login-link">
                <p class="mt-3">Bereits ein Konto? <a href="login.php" class="text-decoration-none">Jetzt anmelden</a></p>
            </div>
        </form>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>