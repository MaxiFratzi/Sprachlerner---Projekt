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
$login_error = "";
if (isset($_POST['login'])) {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    // Prepared Statement für zusätzliche Sicherheit
    $stmt = $conn->prepare("SELECT accid, username, password FROM account WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Passwort direkt vergleichen (NICHT für Produktiveinsatz!)
        if ($password === $user['password']) {
            // Erfolgreiches Login
            $_SESSION['user_id'] = $user['accid'];
            $_SESSION['username'] = $user['username'];
            
            // Weiterleitung zur Hauptseite
            header("Location: main.php");
            exit();
        } else {
            $login_error = "Ungültige E-Mail oder Passwort";
        }
    } else {
        $login_error = "Ungültige E-Mail oder Passwort";
    }

    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SprachMeister - Anmelden</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f4f7;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            background-color: white;
            padding: 2.5rem;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h2 {
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
        
        .forgot-password {
            text-align: right;
            margin-bottom: 1rem;
        }
        
        .register-link {
            text-align: center;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2>SprachMeister</h2>
            <p>Melde dich an, um deine Lernreise fortzusetzen</p>
        </div>
        
        <?php if (!empty($login_error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($login_error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="mb-3">
                <label for="email" class="form-label">E-Mail-Adresse</label>
                <input type="email" class="form-control" id="email" name="email" required 
                       placeholder="Deine E-Mail-Adresse" 
                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Passwort</label>
                <input type="password" class="form-control" id="password" name="password" required 
                       placeholder="Dein Passwort">
            </div>
            
            <div class="forgot-password">
                <a href="#" class="text-decoration-none text-muted">Passwort vergessen?</a>
            </div>
            
            <div class="d-grid">
                <button type="submit" name="login" class="btn btn-primary btn-lg">Anmelden</button>
            </div>
            
            <div class="register-link">
                <p class="mt-3">Noch kein Konto? <a href="register.php" class="text-decoration-none">Registrieren</a></p>
            </div>
        </form>
    </div>

    <!-- Bootstrap JS und Dependency -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>