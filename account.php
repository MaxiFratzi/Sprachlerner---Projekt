<?php
session_start();

// Prüfen, ob Benutzer nicht eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

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

// Aktuellen Benutzer laden
$current_user_id = $_SESSION['user_id'];

// Account aktualisieren
if (isset($_POST['update_account'])) {
    $new_username = $conn->real_escape_string($_POST['username']);
    $new_email = $conn->real_escape_string($_POST['email']);
    
    // Vorbereitetes Statement für Sicherheit
    $update_stmt = $conn->prepare("UPDATE account SET username = ?, email = ? WHERE accid = ?");
    
    // Optional: Passwortänderung
    if (!empty($_POST['new_password'])) {
        // Passwort-Validierung
        if (strlen($_POST['new_password']) < 6) {
            $error_message = "Das Passwort muss mindestens 6 Zeichen lang sein.";
        } else {
            $new_password = $_POST['new_password'];
            $update_stmt = $conn->prepare("UPDATE account SET username = ?, email = ?, password = ? WHERE accid = ?");
            $update_stmt->bind_param("sssi", $new_username, $new_email, $new_password, $current_user_id);
        }
    } else {
        $update_stmt->bind_param("ssi", $new_username, $new_email, $current_user_id);
    }
    
    if (!isset($error_message) && $update_stmt->execute()) {
        // Aktualisiere Session-Daten
        $_SESSION['username'] = $new_username;
        $success_message = "Accountdaten erfolgreich aktualisiert!";
    } elseif (!isset($error_message)) {
        $error_message = "Fehler bei der Aktualisierung: " . $conn->error;
    }
    $update_stmt->close();
}

// Account löschen
if (isset($_POST['delete_account'])) {
    $delete_stmt = $conn->prepare("DELETE FROM account WHERE accid = ?");
    $delete_stmt->bind_param("i", $current_user_id);
    
    if ($delete_stmt->execute()) {
        // Logout und Weiterleitung
        session_destroy();
        header("Location: login.php");
        exit();
    } else {
        $error_message = "Fehler beim Löschen des Accounts: " . $conn->error;
    }
    $delete_stmt->close();
}

// Benutzerdaten laden
$user_stmt = $conn->prepare("SELECT username, email FROM account WHERE accid = ?");
$user_stmt->bind_param("i", $current_user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$user_stmt->close();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SprachMeister - Account</title>
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
            background-color: #f4f6f9;
        }
        
        .account-container {
            max-width: 600px;
            margin: 2rem auto;
            background-color: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        
        .form-label {
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="account-container">
            <h2 class="text-center mb-4">Mein Account</h2>
            
            <?php 
            if (isset($success_message)) {
                echo '<div class="alert alert-success">' . htmlspecialchars($success_message) . '</div>';
            }
            if (isset($error_message)) {
                echo '<div class="alert alert-danger">' . htmlspecialchars($error_message) . '</div>';
            }
            ?>
            
            <form method="post" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">Benutzername</label>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?= htmlspecialchars($user_data['username']) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">E-Mail</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?= htmlspecialchars($user_data['email']) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="new_password" class="form-label">Neues Passwort (optional)</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" minlength="6">
                    <small class="form-text text-muted">Mindestens 6 Zeichen. Leer lassen, um aktuelles Passwort zu behalten.</small>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" name="update_account" class="btn btn-primary">Änderungen speichern</button>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                        Account löschen
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Account Löschen Modal -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Account löschen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Bist du sicher, dass du deinen Account löschen möchtest? Diese Aktion kann nicht rückgängig gemacht werden.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <form method="post" action="">
                        <button type="submit" name="delete_account" class="btn btn-danger">Ja, Account löschen</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap and JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Datenbankverbindung schließen
$conn->close();
?>