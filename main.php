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
$current_username = $_SESSION['username'];

// Account aktualisieren
if (isset($_POST['update_account'])) {
    $new_username = $conn->real_escape_string($_POST['username']);
    $new_email = $conn->real_escape_string($_POST['email']);
    
    // Vorbereitetes Statement für Sicherheit
    $update_stmt = $conn->prepare("UPDATE account SET username = ?, email = ? WHERE accid = ?");
    
    // Optional: Passwortänderung
    if (!empty($_POST['new_password'])) {
        $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $update_stmt = $conn->prepare("UPDATE account SET username = ?, email = ?, password = ? WHERE accid = ?");
        $update_stmt->bind_param("sssi", $new_username, $new_email, $new_password, $current_user_id);
    } else {
        $update_stmt->bind_param("ssi", $new_username, $new_email, $current_user_id);
    }
    
    if ($update_stmt->execute()) {
        // Aktualisiere Session-Daten
        $_SESSION['username'] = $new_username;
        $current_username = $new_username;
        $success_message = "Accountdaten erfolgreich aktualisiert!";
    } else {
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

// Logout-Funktion
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SprachenMeister - Lernsets</title>
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
        }
        
        .navbar {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .learning-sets-section {
            padding: 2rem 0;
        }
        
        .learning-set-card {
            border-radius: 20px;
            overflow: hidden;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .learning-set-card:hover {
            transform: scale(1.03);
        }
        
        .learning-set-header {
            background-color: var(--primary-color);
            color: white;
            padding: 1.5rem;
        }
        
        .learning-set-body {
            padding: 1.5rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 10px 25px;
            font-weight: 600;
            border-radius: 50px;
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 10px 25px;
            font-weight: 600;
            border-radius: 50px;
        }
        
        .language-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            background-color: var(--light-blue);
            color: var(--primary-color);
            font-weight: bold;
            margin-right: 10px;
        }
        
        .difficulty-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            background-color: var(--orange);
            color: white;
            font-weight: bold;
        }
        
        .user-dropdown {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container">
            <a class="navbar-brand" href="main.php">SprachMeister</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="toolsDropdown" role="button" data-bs-toggle="dropdown">
                            Lerntools
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Karteikarten</a></li>
                            <li><a class="dropdown-item" href="#">Übungstests</a></li>
                            <li><a class="dropdown-item" href="#">Lernspiele</a></li>
                        </ul>
                    </li>
                </ul>
                <form class="d-flex mx-auto mb-2 mb-lg-0">
                    <input class="form-control me-2" type="search" placeholder="Lernsets durchsuchen" style="width: 250px; border-radius: 20px;">
                </form>
                <div class="d-flex align-items-center">
                    <div class="dropdown">
                        <button class="btn btn-link nav-link dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-2"></i><?= htmlspecialchars($current_username) ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <a class="dropdown-item" href="account.php">
                                    <i class="fas fa-user me-2"></i>Account
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="?logout=1">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Profil Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Mein Profil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="fas fa-user-circle fa-5x mb-3 text-muted"></i>
                    <h4><?= htmlspecialchars($current_username) ?></h4>
                    <p class="text-muted">Mitglied seit: 
                        <?php 
                        // Hole Registrierungsdatum aus Datenbank
                        $profile_stmt = $conn->prepare("SELECT DATE(registration_date) as reg_date FROM account WHERE accid = ?");
                        $profile_stmt->bind_param("i", $current_user_id);
                        $profile_stmt->execute();
                        $profile_result = $profile_stmt->get_result();
                        $profile_data = $profile_result->fetch_assoc();
                        echo $profile_data ? htmlspecialchars($profile_data['reg_date']) : 'Unbekannt';
                        $profile_stmt->close();
                        ?>
                    </p>
                    <div class="row mt-4">
                        <div class="col">
                            <h5>0</h5>
                            <small class="text-muted">Lernsets</small>
                        </div>
                        <div class="col">
                            <h5>0</h5>
                            <small class="text-muted">Fortschritt</small>
                        </div>
                        <div class="col">
                            <h5>0</h5>
                            <small class="text-muted">Sprachen</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Einstellungen Modal -->
    <div class="modal fade" id="settingsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Accounteinstellungen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php 
                    if (isset($success_message)) {
                        echo '<div class="alert alert-success">' . htmlspecialchars($success_message) . '</div>';
                    }
                    if (isset($error_message)) {
                        echo '<div class="alert alert-danger">' . htmlspecialchars($error_message) . '</div>';
                    }
                    
                    // Aktuellen Benutzer laden
                    $user_stmt = $conn->prepare("SELECT username, email FROM account WHERE accid = ?");
                    $user_stmt->bind_param("i", $current_user_id);
                    $user_stmt->execute();
                    $user_result = $user_stmt->get_result();
                    $user_data = $user_result->fetch_assoc();
                    $user_stmt->close();
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
                            <small class="form-text text-muted">Mindestens 6 Zeichen</small>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" name="update_account" class="btn btn-primary">Änderungen speichern</button>
                            <button type="submit" name="delete_account" class="btn btn-danger" onclick="return confirm('Bist du sicher, dass du deinen Account löschen möchtest? Diese Aktion kann nicht rückgängig gemacht werden.')">Account löschen</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Learning Sets Section -->
    <section class="learning-sets-section">
        <div class="container">
            <h1 class="text-center mb-5">Unsere Lernsets</h1>
            
            <div class="row">
                <!-- Lernset 1 -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="learning-set-card">
                        <div class="learning-set-header">
                            <h3>Deutsch für Anfänger</h3>
                            <div class="mt-2">
                                <span class="language-badge">Deutsch</span>
                                <span class="difficulty-badge">Anfänger</span>
                            </div>
                        </div>
                        <div class="learning-set-body">
                            <p>Lerne die Grundlagen der deutschen Sprache mit unseren interaktiven Übungen.</p>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    <small class="text-muted">180 Vokabeln</small>
                                    </div>
                                <a href="#" class="btn btn-outline-primary btn-sm">Vorschau</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lernset 2 -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="learning-set-card">
                        <div class="learning-set-header">
                            <h3>Business Englisch</h3>
                            <div class="mt-2">
                                <span class="language-badge">Englisch</span>
                                <span class="difficulty-badge">Fortgeschritten</span>
                            </div>
                        </div>
                        <div class="learning-set-body">
                            <p>Perfektioniere deine Englischkenntnisse für den Geschäftsalltag.</p>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    <small class="text-muted">250 Vokabeln</small>
                                </div>
                                <a href="#" class="btn btn-outline-primary btn-sm">Vorschau</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lernset 3 -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="learning-set-card">
                        <div class="learning-set-header">
                            <h3>Französisch Reise</h3>
                            <div class="mt-2">
                                <span class="language-badge">Französisch</span>
                                <span class="difficulty-badge">Mittelstufe</span>
                            </div>
                        </div>
                        <div class="learning-set-body">
                            <p>Vokabeln und Redewendungen für deinen nächsten Urlaub in Frankreich.</p>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    <small class="text-muted">120 Vokabeln</small>
                                </div>
                                <a href="#" class="btn btn-outline-primary btn-sm">Vorschau</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Weitere Lernsets -->
                <div class="col-12 text-center mt-4">
                    <button class="btn btn-primary">Mehr Lernsets anzeigen</button>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>SprachMeister</h5>
                    <p>Lerne Sprachen einfach und effektiv mit unserem interaktiven Sprachentrainer.</p>
                </div>
                <div class="col-md-3">
                    <h5>Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-decoration-none">Über uns</a></li>
                        <li><a href="#" class="text-decoration-none">Hilfe & FAQ</a></li>
                        <li><a href="#" class="text-decoration-none">Datenschutz</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Kontakt</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-decoration-none">Kontaktformular</a></li>
                        <li><a href="#" class="text-decoration-none">Support</a></li>
                    </ul>
                </div>
            </div>
            <div class="text-center mt-4">
                <p>&copy; 2025 SprachMeister. Alle Rechte vorbehalten.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap and JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Datenbankverbindung schließen
$conn->close();
?>