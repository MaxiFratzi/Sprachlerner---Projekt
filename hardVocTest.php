<?php
// Start Session
session_start();

// Prüfen, ob Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Benutzername aus der Session holen
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Benutzer';

// Datenbankverbindung herstellen
$servername = "localhost";
$dbUsername = "root";
$dbPassword = "root"; 
$dbName = "vokabeln"; 

$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);

// Verbindung überprüfen
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

// Anzahl der verfügbaren Vokabeln abrufen
$sql = "SELECT COUNT(*) as total FROM woerterh"; // Hard vocabulary table
$result = $conn->query($sql);
$total_vocab = 0;

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $total_vocab = $row['total'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SprachenMeister - Fortgeschrittenen Vokabeltest</title>
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
            background-color: #f4f4f4;
        }
        
        .navbar {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .library-header {
            background-color: var(--primary-color);
            color: white;
            padding: 2rem 0;
            text-align: center;
        }
        
        .library-content {
            max-width: 800px;
            margin: 2rem auto;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        
        .form-range::-webkit-slider-thumb {
            background: var(--primary-color);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 10px 25px;
            font-weight: 600;
            border-radius: 50px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container">
            <a class="navbar-brand" href="main.php">SprachenMeister</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <form class="d-flex mx-auto mb-2 mb-lg-0">
                    <input class="form-control me-2" type="search" placeholder="Nach Vokabeln suchen" style="width: 250px; border-radius: 20px;">
                </form>
                <div class="ms-auto">
                    <div class="dropdown">
                        <div class="user-avatar dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user"></i>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><span class="dropdown-item-text">Angemeldet als <strong><?php echo htmlspecialchars($username); ?></strong></span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="account.php"><i class="fas fa-user-cog me-2"></i>Mein Account</a></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Ausloggen</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Library Header -->
    <div class="library-header">
        <h1>Fortgeschrittenen Vokabeltest starten</h1>
        <p class="mt-2">Wähle die Anzahl der Vokabeln für deinen Test</p>
    </div>

    <!-- Library Content -->
    <div class="library-content">
        <?php if ($total_vocab < 5): ?>
            <div class="text-center py-5">
                <i class="fas fa-exclamation-circle fa-3x text-warning mb-3"></i>
                <h3>Nicht genügend Vokabeln</h3>
                <p class="text-muted">Es müssen mindestens 5 Vokabeln im Lernset vorhanden sein, um einen Test zu starten.</p>
                <a href="hardVoc.php" class="btn btn-primary mt-3">Zurück zur Übersicht</a>
            </div>
        <?php else: ?>
            <form action="hardVocTestRun.php" method="POST">
                <div class="mb-4">
                    <label for="vocabCount" class="form-label">Anzahl der Testfragen: <span id="vocabCountValue">10</span></label>
                    <input type="range" class="form-range" id="vocabCount" name="vocabCount" 
                        min="5" max="<?php echo $total_vocab; ?>" value="10" 
                        oninput="document.getElementById('vocabCountValue').textContent = this.value">
                    <div class="d-flex justify-content-between small text-muted">
                        <span>Min: 5</span>
                        <span>Max: <?php echo $total_vocab; ?></span>
                    </div>
                </div>
                
                <div class="text-center mt-5">
                    <a href="hardVoc.php" class="btn btn-outline-secondary me-2">Abbrechen</a>
                    <button type="submit" class="btn btn-primary">Test starten <i class="fas fa-arrow-right ms-2"></i></button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <!-- Bootstrap and JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>