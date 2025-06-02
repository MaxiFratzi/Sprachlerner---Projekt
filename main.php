<?php
// Start Session
session_start();

// Prüfen, ob Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Benutzername und ID aus der Session holen
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Benutzer';
$user_id = $_SESSION['user_id'];

// Datenbankverbindung herstellen
$servername = "localhost";
$dbUsername = "root"; 
$dbPassword = "root"; // Oder "root" je nach Konfiguration
$dbName = "vokabeln"; 

$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);

// Verbindung überprüfen
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

// Suchfunktion
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$learning_sets = [];

// Alle verfügbaren Lernsets definieren (du kannst diese auch aus der Datenbank laden)
$all_sets = [
    ['name' => 'Easy', 'terms' => 48, 'file' => 'easyVoc.php', 'description' => 'Einfache Vokabeln für Anfänger'],
    ['name' => 'Medium', 'terms' => 48, 'file' => 'mediumVoc.php', 'description' => 'Mittelschwere Vokabeln'],
    ['name' => 'Hard', 'terms' => 48, 'file' => 'hardVoc.php', 'description' => 'Schwere Vokabeln für Fortgeschrittene'],
    // Hier kannst du weitere Sets hinzufügen
];

// Suche durchführen
if (!empty($search_query)) {
    foreach ($all_sets as $set) {
        if (stripos($set['name'], $search_query) !== false || 
            stripos($set['description'], $search_query) !== false) {
            $learning_sets[] = $set;
        }
    }
} else {
    $learning_sets = $all_sets;
}

// Heutiges Datum
$today = date('Y-m-d');

// Gelernte Vokabeln heute abrufen
$stmt = $conn->prepare("SELECT count FROM vocabulary_tracking WHERE user_id = ? AND learn_date = ?");
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$result = $stmt->get_result();

$vocab_count = 0;
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $vocab_count = $row['count'];
}

$stmt->close();

// Calculate learning streak (days in a row with at least 5 vocabularies learned)
$streak = 0;
$minimum_vocab = 5; // Minimum vocabulary required to maintain streak
$checkDate = $today;
$streakRunning = true;

$streakStmt = $conn->prepare("SELECT count FROM vocabulary_tracking WHERE user_id = ? AND learn_date = ? AND count >= ?");
$streakStmt->bind_param("isi", $user_id, $checkDate, $minimum_vocab);

// Loop backwards through days until we find a day with fewer than 5 words learned
while ($streakRunning) {
    $streakStmt->execute();
    $streakResult = $streakStmt->get_result();
    
    if ($streakResult->num_rows > 0) {
        // Day found with at least 5 words learned - increment streak
        $streak++;
        // Move to the previous day
        $checkDate = date('Y-m-d', strtotime($checkDate . ' -1 day'));
    } else {
        // Day found with less than 5 words learned - streak broken
        $streakRunning = false;
    }
}

$streakStmt->close();

// Calculate average success rate from all tests
$success_stmt = $conn->prepare("SELECT AVG(success_rate) as avg_rate FROM test_results WHERE user_id = ?");
$success_stmt->bind_param("i", $user_id);
$success_stmt->execute();
$success_result = $success_stmt->get_result();

$success_rate = 0;
if ($success_result->num_rows > 0) {
    $success_row = $success_result->fetch_assoc();
    if ($success_row['avg_rate'] !== null) {
        $success_rate = round($success_row['avg_rate']);
    }
}
$success_stmt->close();

// Handle case with no tests taken
$tests_taken = false;
$count_stmt = $conn->prepare("SELECT COUNT(*) as test_count FROM test_results WHERE user_id = ?");
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
if ($count_result->num_rows > 0) {
    $count_row = $count_result->fetch_assoc();
    $tests_taken = ($count_row['test_count'] > 0);
}
$count_stmt->close();

// Continue with database close and the rest of your code...
$conn->close();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SprachenMeister - Lernzentrum</title>
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
            height: calc(100vh - 60px);
            width: 250px;
            position: fixed;
            top: 60px;
            left: 0;
            padding: 20px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-header {
            font-weight: bold;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 15px;
        }
        
        .sidebar-menu a {
            color: #333;
            text-decoration: none;
            font-size: 16px;
            display: block;
            padding: 8px 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .sidebar-menu a:hover {
            background-color: #f0f4ff;
            color: var(--primary-color);
        }
        
        .sidebar-menu a.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .content {
            margin-left: 250px;
            padding: 30px;
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
        
        .welcome-section {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .welcome-section h1 {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .stats-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            width: 30%;
            text-align: center;
        }
        
        .stat-card h3 {
            color: var(--primary-color);
            font-size: 2rem;
            margin-bottom: 5px;
        }
        
        .stat-card p {
            color: #666;
            margin-bottom: 0;
        }
        
        .recent-sets {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .recent-sets h2 {
            margin-bottom: 20px;
            font-size: 1.5rem;
            color: #333;
        }
        
        .set-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .set-card {
            background-color: #f0f4ff;
            border-radius: 12px;
            padding: 15px;
            border-left: 5px solid var(--primary-color);
            transition: transform 0.3s;
        }
        
        .set-card:hover {
            transform: translateY(-5px);
        }
        
        .set-card h4 {
            font-size: 1.1rem;
            margin-bottom: 8px;
        }
        
        .set-card p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .set-card .description {
            color: #888;
            font-size: 0.85rem;
            margin-bottom: 15px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .search-form {
            position: relative;
        }
        
        .search-form input {
            border-radius: 20px;
            padding-left: 45px;
        }
        
        .search-form .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }
        
        .no-results {
            text-align: center;
            color: #666;
            font-style: italic;
            margin: 20px 0;
        }
        
        .search-results-info {
            margin-bottom: 20px;
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand ms-4" href="main.php">SprachenMeister</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <form class="d-flex mx-auto mb-2 mb-lg-0 search-form" method="GET" action="main.php">
                    <i class="fas fa-search search-icon"></i>
                    <input class="form-control me-2" type="search" name="search" 
                           placeholder="Nach Lernsets suchen..." 
                           value="<?php echo htmlspecialchars($search_query); ?>"
                           style="width: 300px; border-radius: 20px;">
                    <button class="btn btn-outline-primary" type="submit" style="border-radius: 20px;">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                <div class="ms-auto me-4">
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

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            Lernzentrum
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="main.php" class="active">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="library.php">
                    <i class="fas fa-book"></i> Bibliothek
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="content" style="margin-top: 60px;">
        <div class="welcome-section">
            <h1>Willkommen zurück, <?php echo htmlspecialchars($username); ?>!</h1>
            <p>Bereit zum Lernen? Hier ist dein Fortschritt.</p>
        </div>
        
        <div class="stats-container">
            <div class="stat-card">
                <h3><?php echo $vocab_count; ?></h3>
                <p>Gelernte Vokabeln heute</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $streak; ?></h3>
                <p>Tage in Folge gelernt</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $tests_taken ? $success_rate : '-'; ?>%</h3>
                <p>Erfolgsquote</p>
            </div>
        </div>
        
        <div class="recent-sets">
            <h2>
                <?php if (!empty($search_query)): ?>
                    Suchergebnisse für "<?php echo htmlspecialchars($search_query); ?>"
                <?php else: ?>
                    Verfügbare Lernsets
                <?php endif; ?>
            </h2>
            
            <?php if (!empty($search_query)): ?>
                <div class="search-results-info">
                    <?php echo count($learning_sets); ?> Ergebnis(se) gefunden
                    <a href="main.php" class="ms-2 text-decoration-none">
                        <i class="fas fa-times"></i> Suche zurücksetzen
                    </a>
                </div>
            <?php endif; ?>
            
            <?php if (empty($learning_sets)): ?>
                <div class="no-results">
                    <i class="fas fa-search fa-2x mb-3"></i>
                    <p>Keine Lernsets gefunden für "<?php echo htmlspecialchars($search_query); ?>"</p>
                    <a href="main.php" class="btn btn-outline-primary">Alle Sets anzeigen</a>
                </div>
            <?php else: ?>
                <div class="set-grid">
                    <?php foreach ($learning_sets as $set): ?>
                        <div class="set-card">
                            <h4><?php echo htmlspecialchars($set['name']); ?></h4>
                            <p><?php echo $set['terms']; ?> Begriffe</p>
                            <div class="description"><?php echo htmlspecialchars($set['description']); ?></div>
                            <a href="<?php echo htmlspecialchars($set['file']); ?>" class="btn btn-primary btn-sm">
                                Weiter lernen
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap and JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Optional: Auto-submit bei Enter-Taste
        document.querySelector('input[name="search"]').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.closest('form').submit();
            }
        });
        
        // Optional: Suchfeld fokussieren mit Strg+K
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'k') {
                e.preventDefault();
                document.querySelector('input[name="search"]').focus();
            }
        });
    </script>
</body>
</html>