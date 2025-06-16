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
$user_id = $_SESSION['user_id'];

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

// Suchfunktion
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$learning_sets = [];

// Alle Lernsets aus der Datenbank laden
$sql = "SELECT l.id, l.name, l.description, l.type, l.user_id, COUNT(v.id) as vocab_count 
        FROM lernsets l 
        LEFT JOIN vokabeln v ON l.id = v.lernset_id 
        WHERE l.is_active = 1 
        AND (l.type = 'standard' OR l.user_id = ?)
        GROUP BY l.id 
        ORDER BY l.type DESC, l.name ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$all_sets = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $all_sets[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'type' => $row['type'],
            'terms' => $row['vocab_count'],
            'file' => 'lernset.php?id=' . $row['id'] // Neue einheitliche Lernset-Seite
        ];
    }
}

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

$conn->close();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SprachenMeister - Deine Bibliothek</title>
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
        
        .library-tabs {
            display: flex;
            justify-content: center;
            margin-top: 1rem;
            gap: 1rem;
        }
        
        .library-tabs .nav-link {
            color: white;
            font-weight: 600;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .library-tabs .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .library-tabs .nav-link.active {
            background-color: white;
            color: var(--primary-color);
        }
        
        .library-content {
            max-width: 800px;
            margin: 2rem auto;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        
        .library-set {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: transform 0.3s;
            cursor: pointer;
        }
        
        .library-set:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .library-set-info {
            flex-grow: 1;
        }
        
        .library-set-title {
            font-weight: 600;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.3rem;
        }
        
        .library-set-details {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .library-set-description {
            color: #888;
            font-size: 0.85rem;
            margin-top: 5px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 8px 20px;
            font-weight: 600;
            border-radius: 50px;
            font-size: 0.9rem;
        }
        
        .custom-set-badge {
            background-color: var(--secondary-color);
            color: white;
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 10px;
        }
        
        .standard-set-badge {
            background-color: var(--primary-color);
            color: white;
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 10px;
        }
        
        .library-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .search-bar {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .search-bar input {
            padding-left: 45px;
        }
        
        .search-bar .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            z-index: 2;
        }
        
        .no-results {
            text-align: center;
            color: #666;
            font-style: italic;
            margin: 40px 0;
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
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="main.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="library.php">Bibliothek</a>
                    </li>
                </ul>
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
        <h1>Deine Bibliothek</h1>
        <div class="library-tabs">
            <ul class="nav">
                <li class="nav-item">
                    <a class="nav-link active" href="#lernsets">Lernsets</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="create_set.php">
                        <i class="fas fa-plus"></i> Neues Lernset
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Library Content -->
    <div class="library-content">
        <!-- Suchleiste -->
        <div class="search-bar">
            <form method="GET" action="library.php">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="form-control" name="search" 
                       placeholder="Lernsets suchen..." 
                       value="<?php echo htmlspecialchars($search_query); ?>"
                       style="border-radius: 20px;">
            </form>
        </div>

        <?php if (!empty($search_query)): ?>
            <div class="search-results-info mb-3">
                <span class="text-muted">
                    <?php echo count($learning_sets); ?> Ergebnis(se) gefunden für "<?php echo htmlspecialchars($search_query); ?>"
                </span>
                <a href="library.php" class="text-decoration-none">
                    <i class="fas fa-times"></i> Suche zurücksetzen
                </a>
            </div>
        <?php endif; ?>

        <?php if (empty($learning_sets)): ?>
            <div class="no-results">
                <i class="fas fa-search fa-2x mb-3"></i>
                <p>Keine Lernsets gefunden<?php if (!empty($search_query)): ?> für "<?php echo htmlspecialchars($search_query); ?>"<?php endif; ?></p>
                <a href="library.php" class="btn btn-outline-primary">Alle Sets anzeigen</a>
            </div>
        <?php else: ?>
            <h4 class="mb-3">Alle Lernsets</h4>
            <?php foreach ($learning_sets as $set): ?>
                <div class="library-set" onclick="window.location='<?php echo htmlspecialchars($set['file']); ?>'">
                    <div class="library-set-info">
                        <div class="library-set-title">
                            <?php echo htmlspecialchars($set['name']); ?>
                            <?php if ($set['type'] === 'custom'): ?>
                                <span class="custom-set-badge">Custom</span>
                            <?php else: ?>
                                <span class="standard-set-badge">Standard</span>
                            <?php endif; ?>
                        </div>
                        <div class="library-set-details"><?php echo $set['terms']; ?> Begriffe</div>
                        <div class="library-set-description"><?php echo htmlspecialchars($set['description']); ?></div>
                    </div>
                    <div class="library-actions">
                        <a href="<?php echo htmlspecialchars($set['file']); ?>" class="btn btn-primary" onclick="event.stopPropagation();">
                            <i class="fas fa-book-open me-1"></i> Lernen
                        </a>
                        <?php if ($set['type'] === 'custom'): ?>
                            <a href="edit_set.php?id=<?php echo $set['id']; ?>" class="btn btn-outline-primary btn-sm" onclick="event.stopPropagation();">
                                <i class="fas fa-edit"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <!-- Button zum Erstellen eines neuen Lernsets -->
        <div class="text-center mt-4">
            <a href="create_set.php" class="btn btn-success">
                <i class="fas fa-plus me-2"></i> Neues Lernset erstellen
            </a>
        </div>
    </div>

    <!-- Bootstrap and JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-submit bei Enter-Taste
        document.querySelector('input[name="search"]').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.closest('form').submit();
            }
        });
    </script>
</body>
</html>