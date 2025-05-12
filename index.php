<?php
// Start Session
session_start();

// Prüfen, ob Benutzer bereits eingeloggt ist
if (isset($_SESSION['user_id'])) {
    // Wenn eingeloggt, zu dashboard.php weiterleiten
    header("Location: dashboard.php");
    exit();
}

// Database connection
$host = "localhost";
$username = "root"; // Ändere nach Bedarf
$password = "root"; // Ändere nach Bedarf
$dbname = "vokabeln"; // Geändert zu neuem Datenbanknamen

// Verbindung zur Datenbank herstellen
$conn = new mysqli($host, $username, $password, $dbname);

// Verbindung überprüfen
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

// Nachrichten-Variable initialisieren
$message = "";

// Login-Formular Verarbeitung
if (isset($_POST['login'])) {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    
    // Email überprüfen - angepasst an das neue Schema
    $sql = "SELECT accid, username, password FROM account WHERE email = '$email'";
    $result = $conn->query($sql);
    
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        // Passwort überprüfen (in der Produktion besser password_verify() verwenden)
        if (password_verify($password, $row['password'])) {
            // Erfolgreicher Login
            $_SESSION['user_id'] = $row['accid']; // Geändert von 'id' zu 'accid'
            $_SESSION['username'] = $row['username'];
            header("Location: dashboard.php");
            exit();
        } else {
            $message = "Falsches Passwort!";
        }
    } else {
        $message = "Benutzer nicht gefunden!";
    }
}

// Registrierungs-Formular Verarbeitung
if (isset($_POST['register'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    
    // Prüfen, ob Email bereits existiert
    $check_sql = "SELECT accid FROM account WHERE email = '$email'";
    $result = $conn->query($check_sql);
    
    if ($result->num_rows > 0) {
        $message = "Diese E-Mail wird bereits verwendet!";
    } else {
        // Neuen Benutzer speichern - angepasst an das neue Schema
        $sql = "INSERT INTO account (username, password, email) VALUES ('$username', '$password', '$email')";
        
        if ($conn->query($sql) === TRUE) {
            // Optional: Erstelle auch einen Eintrag in der benutzer-Tabelle
            $new_user_id = $conn->insert_id;
            $default_level = "E"; // Standardmäßig einfacher Schwierigkeitsgrad
            $insert_benutzer = "INSERT INTO benutzer (schwierigkeit, accid) VALUES ('$default_level', $new_user_id)";
            $conn->query($insert_benutzer);
            
            $message = "Registrierung erfolgreich! Du kannst dich jetzt anmelden.";
        } else {
            $message = "Fehler bei der Registrierung: " . $conn->error;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SprachMeister - Lerne Sprachen interaktiv</title>
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
        
        .hero-section {
            padding: 4rem 0;
            text-align: center;
        }
        
        h1 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 1.5rem;
        }
        
        .hero-text {
            max-width: 700px;
            margin: 0 auto 2rem auto;
        }
        
        .feature-card {
            border-radius: 20px;
            padding: 2rem;
            height: 100%;
            transition: transform 0.3s;
            margin-bottom: 20px;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
        }
        
        .learn-card {
            background-color: var(--light-blue);
        }
        
        .resources-card {
            background-color: var(--pink);
        }
        
        .flashcards-card {
            background-color: #c9c6ff;
        }
        
        .quiz-card {
            background-color: var(--orange);
        }
        
        .feature-image {
            max-width: 180px;
            margin-bottom: 1rem;
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
        
        .modal-content {
            border-radius: 15px;
        }
        
        .alert {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container">
            <a class="navbar-brand" href="index.php">SprachMeister</a>
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
                    <input class="form-control me-2" type="search" placeholder="Nach Übungstests suchen" style="width: 250px; border-radius: 20px;">
                </form>
                <div class="d-flex">
                    <button class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#loginModal">Anmelden</button>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#registerModal">Erstellen</button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1>Wie möchtest du lernen?</h1>
            <p class="hero-text">Mit den interaktiven Karteikarten, Übungstests und Lernaktivitäten von SprachMeister lernst du alles, was du willst.</p>
            <button class="btn btn-primary btn-lg mb-4" data-bs-toggle="modal" data-bs-target="#registerModal">
                Kostenlos registrieren
            </button>
        </div>
    </section>

    <!-- Feature Cards -->
    <section class="container mb-5">
        <div class="row">
            <!-- Learn Card -->
            <div class="col-md-6 col-lg-3">
                <div class="feature-card learn-card">
                    <h3>Lernen</h3>
                    <div class="mt-3">
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" placeholder="Antwort eingeben">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Resources Card -->
            <div class="col-md-6 col-lg-3">
                <div class="feature-card resources-card">
                    <h3>Arbeitshilfen</h3>
                    <div class="mt-3">
                        <h5>Sprachstrukturen</h5>
                        <div class="d-flex mt-4">
                            <div>
                                <div class="mb-3">Gliederung</div>
                                <div>Wichtige Themen</div>
                            </div>
                            <div class="ms-3">
                                <div class="mb-3">Kurzübersicht</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Flashcards Card -->
            <div class="col-md-6 col-lg-3">
                <div class="feature-card flashcards-card">
                    <h3>Karteikarten</h3>
                    <div class="card mt-4 mx-auto" style="width: 180px; height: 120px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
                        <div class="card-body text-center d-flex align-items-center justify-content-center">
                            <div>
                                <div class="fw-bold">Wort</div>
                                <div class="text-muted">Übersetzung</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quiz Card -->
            <div class="col-md-6 col-lg-3">
                <div class="feature-card quiz-card">
                    <h3>Übungstests</h3>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between mb-2">
                            <div>Note</div>
                            <div>Ergebnis</div>
                            <div>Zeit</div>
                        </div>
                        <div class="d-flex justify-content-between mb-4 fw-bold">
                            <div>84%</div>
                            <div>42/50</div>
                            <div>10 Min</div>
                        </div>
                        <div class="mt-2">
                            <div class="mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="quizOption" id="option1">
                                    <label class="form-check-label" for="option1">A. Option</label>
                                </div>
                            </div>
                            <div class="mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="quizOption" id="option2" checked>
                                    <label class="form-check-label" for="option2">B. Option</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Anmelden</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (!empty($message) && isset($_POST['login'])): ?>
                        <div class="alert alert-danger"><?php echo $message; ?></div>
                    <?php endif; ?>
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="loginEmail" class="form-label">E-Mail</label>
                            <input type="email" class="form-control" id="loginEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="loginPassword" class="form-label">Passwort</label>
                            <input type="password" class="form-control" id="loginPassword" name="password" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" name="login" class="btn btn-primary">Anmelden</button>
                        </div>
                    </form>
                    <div class="text-center mt-3">
                        <p>Noch kein Konto? <a href="#" data-bs-toggle="modal" data-bs-target="#registerModal" data-bs-dismiss="modal">Jetzt registrieren</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div class="modal fade" id="registerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Neues Konto erstellen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (!empty($message) && isset($_POST['register'])): ?>
                        <div class="alert alert-<?php echo strpos($message, "erfolgreich") !== false ? "success" : "danger"; ?>"><?php echo $message; ?></div>
                    <?php endif; ?>
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="registerUsername" class="form-label">Benutzername</label>
                            <input type="text" class="form-control" id="registerUsername" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="registerEmail" class="form-label">E-Mail</label>
                            <input type="email" class="form-control" id="registerEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="registerPassword" class="form-label">Passwort</label>
                            <input type="password" class="form-control" id="registerPassword" name="password" required minlength="6">
                        </div>
                        <div class="d-grid">
                            <button type="submit" name="register" class="btn btn-primary">Registrieren</button>
                        </div>
                    </form>
                    <div class="text-center mt-3">
                        <p>Bereits ein Konto? <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal" data-bs-dismiss="modal">Jetzt anmelden</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

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