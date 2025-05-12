<?php
// Start Session
session_start();

// Database connection
$host = "localhost";
$username = "root"; // Ändere nach Bedarf
$password = "root"; // Ändere nach Bedarf
$dbname = "vokabeln"; // Angepasster Datenbankname gemäß neuem Schema

// Verbindung zur Datenbank herstellen
$conn = new mysqli($host, $username, $password, $dbname);

// Verbindung überprüfen
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

// Benutzerdaten abrufen
$accid = $_SESSION['accid'];
$sql = "SELECT username FROM account WHERE accid = $accid";
$result = $conn->query($sql);
$user = $result->fetch_assoc();

// Schwierigkeitsgrad des Benutzers abrufen
$difficulty_sql = "SELECT schwierigkeit FROM benutzer WHERE accid = $accid";
$difficulty_result = $conn->query($difficulty_sql);
$difficulty = $difficulty_result->fetch_assoc();

// Lernfortschritt abrufen - nutzt benutzerVok Tabelle
// Da das Schema etwas anders ist, implementieren wir eine einfache Zählung
$progress_sql = "SELECT 
                COUNT(CASE WHEN lernstufe = 1 THEN 1 END) as easy_count,
                COUNT(CASE WHEN lernstufe = 2 THEN 1 END) as medium_count,
                COUNT(CASE WHEN lernstufe = 3 THEN 1 END) as hard_count
                FROM benutzerVok 
                WHERE vid IN (SELECT vid FROM benutzerVok WHERE lernstufe IS NOT NULL)";
$progress_result = $conn->query($progress_sql);
$progress = $progress_result->fetch_assoc();

// Vokabeln je nach Schwierigkeitsgrad laden
$user_difficulty = $difficulty ? $difficulty['schwierigkeit'] : 'E'; // Standardmäßig leicht, falls keine Einstellung

$vocab_table = "woerter" . $user_difficulty;
$vocab_sql = "SELECT * FROM $vocab_table ORDER BY id LIMIT 10";
$vocab_result = $conn->query($vocab_sql);

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
            margin-bottom: 20px;
        }
        
        .main-content {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
        }
        
        .vocab-card {
            background-color: var(--light-blue);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
        }
        
        .stats-card {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .level-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 20px;
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
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">SprachMeister</a>
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
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="difficultyDropdown" role="button" data-bs-toggle="dropdown">
                            Schwierigkeit
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Leicht (E)</a></li>
                            <li><a class="dropdown-item" href="#">Mittel (M)</a></li>
                            <li><a class="dropdown-item" href="#">Schwer (H)</a></li>
                        </ul>
                    </li>
                </ul>
                <form class="d-flex mx-auto mb-2 mb-lg-0">
                    <input class="form-control me-2" type="search" placeholder="Vokabeln suchen" style="width: 250px; border-radius: 20px;">
                </form>
                <div class="d-flex">
                    <div class="dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                            <?php echo $user['username']; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#">Mein Profil</a></li>
                            <li><a class="dropdown-item" href="#">Einstellungen</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Abmelden</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="sidebar">
                    <h5>Mein Fortschritt</h5>
                    <div class="progress mb-3" style="height: 20px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo isset($progress['easy_count']) ? min(100, ($progress['easy_count'] / 10) * 100) : 0; ?>%" 
                            aria-valuenow="<?php echo isset($progress['easy_count']) ? $progress['easy_count'] : 0; ?>" aria-valuemin="0" aria-valuemax="10">
                            Leicht: <?php echo isset($progress['easy_count']) ? $progress['easy_count'] : 0; ?>
                        </div>
                    </div>
                    <div class="progress mb-3" style="height: 20px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo isset($progress['medium_count']) ? min(100, ($progress['medium_count'] / 10) * 100) : 0; ?>%" 
                            aria-valuenow="<?php echo isset($progress['medium_count']) ? $progress['medium_count'] : 0; ?>" aria-valuemin="0" aria-valuemax="10">
                            Mittel: <?php echo isset($progress['medium_count']) ? $progress['medium_count'] : 0; ?>
                        </div>
                    </div>
                    <div class="progress mb-4" style="height: 20px;">
                        <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo isset($progress['hard_count']) ? min(100, ($progress['hard_count'] / 10) * 100) : 0; ?>%" 
                            aria-valuenow="<?php echo isset($progress['hard_count']) ? $progress['hard_count'] : 0; ?>" aria-valuemin="0" aria-valuemax="10">
                            Schwer: <?php echo isset($progress['hard_count']) ? $progress['hard_count'] : 0; ?>
                        </div>
                    </div>
                    
                    <h5 class="mt-4">Aktionen</h5>
                    <div class="list-group">
                        <a href="#" class="list-group-item list-group-item-action">
                            <i class="fas fa-plus-circle me-2"></i> Neue Vokabeln lernen
                        </a>
                        <a href="#" class="list-group-item list-group-item-action">
                            <i class="fas fa-sync-alt me-2"></i> Wiederholung starten
                        </a>
                        <a href="#" class="list-group-item list-group-item-action">
                            <i class="fas fa-graduation-cap me-2"></i> Quiz starten
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Main Content Area -->
            <div class="col-lg-9">
                <div class="main-content">
                    <h3>Willkommen zurück, <?php echo $user['username']; ?>!</h3>
                    <p>Aktuelle Schwierigkeit: 
                        <span class="badge <?php 
                            if ($user_difficulty == 'E') echo 'bg-success';
                            else if ($user_difficulty == 'M') echo 'bg-warning';
                            else echo 'bg-danger';
                        ?>"><?php 
                            if ($user_difficulty == 'E') echo 'Leicht';
                            else if ($user_difficulty == 'M') echo 'Mittel';
                            else echo 'Schwer';
                        ?></span>
                    </p>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <h4>Deine aktuellen Vokabeln</h4>
                            
                            <?php if ($vocab_result && $vocab_result->num_rows > 0): ?>
                                <div class="row">
                                    <?php while($vocab = $vocab_result->fetch_assoc()): ?>
                                        <div class="col-md-6 col-lg-4">
                                            <div class="vocab-card">
                                                <h5><?php echo $vocab['englisch']; ?></h5>
                                                <p class="mb-0"><?php echo $vocab['deutsch']; ?></p>
                                                <?php if (isset($vocab['bild']) && !empty($vocab['bild'])): ?>
                                                    <div class="mt-2">
                                                        <small class="text-muted">Bild: <?php echo $vocab['bild']; ?></small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    Keine Vokabeln gefunden für deine aktuelle Schwierigkeitsstufe.
                                </div>
                            <?php endif; ?>
                            
                            <div class="text-center mt-3">
                                <a href="#" class="btn btn-primary">Alle Vokabeln anzeigen</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <h4>Lernfortschritt</h4>
                            <div class="alert alert-primary">
                                <i class="fas fa-info-circle me-2"></i> Setze dir ein Ziel und lerne regelmäßig für bessere Ergebnisse!
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="stats-card">
                                        <h6>Gelernte Vokabeln</h6>
                                        <h3><?php echo ($progress['easy_count'] ?? 0) + ($progress['medium_count'] ?? 0) + ($progress['hard_count'] ?? 0); ?></h3>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stats-card">
                                        <h6>Heutiges Ziel</h6>
                                        <h3>20 Vokabeln</h3>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stats-card">
                                        <h6>Lerntage in Folge</h6>
                                        <h3>3 Tage</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container">
            <div class="text-center">
                <p>&copy; 2025 SprachMeister. Alle Rechte vorbehalten.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap and JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>