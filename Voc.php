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

// Verfügbare Lernsets abrufen
$sql = "SELECT id, name, description, type FROM lernsets WHERE is_active = 1 ORDER BY type DESC, name ASC";
$result = $conn->query($sql);

$lernsets = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Anzahl der Vokabeln für jedes Lernset abrufen
        $count_sql = "SELECT COUNT(*) as vocab_count FROM vokabeln WHERE lernset_id = ?";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param("i", $row['id']);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count_row = $count_result->fetch_assoc();
        
        $row['vocab_count'] = $count_row['vocab_count'];
        $lernsets[] = $row;
        $count_stmt->close();
    }
}

$conn->close();

// Aktuell ausgewähltes Lernset aus URL Parameter oder Session
$selected_lernset = null;
if (isset($_GET['lernset'])) {
    $selected_lernset = (int)$_GET['lernset'];
    $_SESSION['selected_lernset'] = $selected_lernset;
} elseif (isset($_SESSION['selected_lernset'])) {
    $selected_lernset = $_SESSION['selected_lernset'];
} elseif (!empty($lernsets)) {
    // Standardmäßig das erste Lernset auswählen
    $selected_lernset = $lernsets[0]['id'];
    $_SESSION['selected_lernset'] = $selected_lernset;
}

// Informationen zum ausgewählten Lernset
$selected_lernset_info = null;
foreach ($lernsets as $lernset) {
    if ($lernset['id'] == $selected_lernset) {
        $selected_lernset_info = $lernset;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SprachenMeister - Easy Vokabeln</title>
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
            max-width: 900px;
            margin: 2rem auto;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        
        .learning-options {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 30px;
        }
        
        .option-card {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 2rem;
            flex: 1;
            min-width: 280px;
            text-align: center;
            transition: transform 0.3s;
            cursor: pointer;
        }
        
        .option-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .option-icon {
            font-size: 4rem;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .option-card h3 {
            font-weight: 600;
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #333;
        }
        
        .option-card p {
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 10px 25px;
            font-weight: 600;
            border-radius: 50px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
        .dropdown-toggle::after {
            display: none;
        }
        
        .lernset-selector {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .lernset-card {
            background-color: white;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .lernset-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }
        
        .lernset-card.active {
            border-color: var(--primary-color);
            background-color: rgba(66, 85, 255, 0.1);
        }
        
        .lernset-type-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
        
        .selected-lernset-info {
            background-color: rgba(66, 85, 255, 0.1);
            border: 1px solid rgba(66, 85, 255, 0.2);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
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
        <h1>Easy Vokabeln</h1>
        <p class="mt-2">Wähle dein Lernset und deine bevorzugte Lernmethode</p>
    </div>

    <!-- Library Content -->
    <div class="library-content">
        <?php if (empty($lernsets)): ?>
            <div class="text-center py-5">
                <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                <h3>Keine Lernsets gefunden</h3>
                <p class="text-muted">Es wurden keine aktiven Lernsets gefunden.</p>
            </div>
        <?php else: ?>
            <!-- Lernset Auswahl -->
            <div class="lernset-selector">
                <h5 class="mb-3"><i class="fas fa-book me-2"></i>Lernset auswählen</h5>
                <div class="row">
                    <?php foreach ($lernsets as $lernset): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="lernset-card <?php echo ($lernset['id'] == $selected_lernset) ? 'active' : ''; ?>" 
                                 onclick="selectLernset(<?php echo $lernset['id']; ?>)">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($lernset['name']); ?></h6>
                                    <span class="badge <?php echo ($lernset['type'] == 'standard') ? 'bg-primary' : 'bg-secondary'; ?> lernset-type-badge">
                                        <?php echo ucfirst($lernset['type']); ?>
                                    </span>
                                </div>
                                <?php if (!empty($lernset['description'])): ?>
                                    <p class="text-muted small mb-2"><?php echo htmlspecialchars($lernset['description']); ?></p>
                                <?php endif; ?>
                                <small class="text-muted">
                                    <i class="fas fa-book-open me-1"></i><?php echo $lernset['vocab_count']; ?> Vokabeln
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if ($selected_lernset_info): ?>
                <!-- Ausgewähltes Lernset Info -->
                <div class="selected-lernset-info">
                    <h6><i class="fas fa-check-circle me-2"></i>Ausgewähltes Lernset: <?php echo htmlspecialchars($selected_lernset_info['name']); ?></h6>
                    <p class="mb-0 small text-muted">
                        <?php echo $selected_lernset_info['vocab_count']; ?> Vokabeln verfügbar
                        <?php if (!empty($selected_lernset_info['description'])): ?>
                            - <?php echo htmlspecialchars($selected_lernset_info['description']); ?>
                        <?php endif; ?>
                    </p>
                </div>

                <!-- Lernmethoden -->
                <h4 class="mb-4 text-center">Wie möchtest du lernen?</h4>
                
                <div class="learning-options">
                    <div class="option-card" onclick="startLearning('karten')">
                        <div class="option-icon">
                            <i class="fas fa-clone"></i>
                        </div>
                        <h3>Karteikarten</h3>
                        <p>Lerne mit digitalen Karteikarten. Dreh die Karte um, um die Antwort zu sehen.</p>
                        <button class="btn btn-primary">Karteikarten starten</button>
                    </div>
                    
                    <div class="option-card" onclick="startLearning('schreiben')">
                        <div class="option-icon">
                            <i class="fas fa-pencil-alt"></i>
                        </div>
                        <h3>Schreiben</h3>
                        <p>Lerne durch aktives Schreiben der Vokabeln für ein besseres Gedächtnis.</p>
                        <button class="btn btn-primary">Schreibübung starten</button>
                    </div>

                    <div class="option-card" onclick="startLearning('test')">
                        <div class="option-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3>Teste dein Wissen</h3>
                        <p>Überprüfe deine Vokabelkenntnisse mit einem Test und sieh deine Fortschritte.</p>
                        <button class="btn btn-primary">Test starten</button>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <p class="text-muted">Bitte wähle ein Lernset aus, um zu beginnen.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
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
    
    <script>
        function selectLernset(lernsetId) {
            // URL Parameter setzen und Seite neu laden
            window.location.href = '?lernset=' + lernsetId;
        }
        
        function startLearning(method) {
            <?php if ($selected_lernset): ?>
                const lernsetId = <?php echo $selected_lernset; ?>;
                let url = '';
                
                switch(method) {
                    case 'karten':
                        url = 'easyVocKarten.php?lernset=' + lernsetId;
                        break;
                    case 'schreiben':
                        url = 'easyVocSchreiben.php?lernset=' + lernsetId;
                        break;
                    case 'test':
                        url = 'easyVocTest.php?lernset=' + lernsetId;
                        break;
                }
                
                if (url) {
                    window.location.href = url;
                }
            <?php else: ?>
                alert('Bitte wähle zuerst ein Lernset aus.');
            <?php endif; ?>
        }
    </script>
</body>
</html>