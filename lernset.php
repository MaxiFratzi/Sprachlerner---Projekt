<?php
// filepath: c:\xampp\htdocs\3BHWII\Sprachlerner - Projekt\lernset.php
// Start Session
session_start();

// Prüfen, ob Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Prüfen, ob Lernset-ID übergeben wurde
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: library.php");
    exit();
}

$lernset_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
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

// Lernset laden und prüfen, ob der Benutzer Zugriff hat
$sql = "SELECT * FROM lernsets WHERE id = ? AND (type = 'standard' OR user_id = ?) AND is_active = 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $lernset_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: library.php");
    exit();
}

$lernset = $result->fetch_assoc();

// Vokabeln aus der Datenbank laden
$vocab_sql = "SELECT * FROM vokabeln WHERE lernset_id = ? ORDER BY id ASC";
$vocab_stmt = $conn->prepare($vocab_sql);
$vocab_stmt->bind_param("i", $lernset_id);
$vocab_stmt->execute();
$vocab_result = $vocab_stmt->get_result();

$vocabularies = [];
while($row = $vocab_result->fetch_assoc()) {
    $vocabularies[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SprachenMeister - <?php echo htmlspecialchars($lernset['name']); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Fontawesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4255ff;
            --secondary-color: #ff8a00;
            --success-color: #28a745;
            --info-color: #17a2b8;
            --warning-color: #ffc107;
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
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background-color: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .user-avatar:hover {
            background-color: #3346e6;
        }
        
        .page-header {
            background-color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .page-header h1 {
            color: var(--primary-color);
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .page-header .subtitle {
            color: #666;
            font-size: 1.1rem;
        }
        
        .lernset-info {
            background-color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .lernset-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            color: white;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        .lernset-badge.standard {
            background-color: var(--primary-color);
        }
        
        .lernset-badge.custom {
            background-color: var(--secondary-color);
        }
        
        .learning-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .learning-card {
            background-color: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }
        
        .learning-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            text-decoration: none;
            color: inherit;
        }
        
        .learning-card .icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .learning-card.karteikarten .icon {
            color: var(--primary-color);
        }
        
        .learning-card.schreiben .icon {
            color: var(--info-color);
        }
        
        .learning-card.test .icon {
            color: var(--warning-color);
        }
        
        .learning-card h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .learning-card p {
            color: #666;
            margin-bottom: 1.5rem;
        }
        
        .learning-card .btn {
            width: 100%;
        }
        
        .vocabulary-preview {
            background-color: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .vocabulary-preview h3 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
        }
        
        .vocab-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .vocab-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            margin-bottom: 0.5rem;
            background-color: #f8f9fa;
            border-radius: 10px;
            transition: background-color 0.3s ease;
        }
        
        .vocab-item:hover {
            background-color: #e9ecef;
        }
        
        .vocab-deutsch {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .vocab-fremdsprache {
            color: #666;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .back-actions {
            margin-bottom: 2rem;
        }
        
        .btn-primary-custom {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            font-weight: 600;
            border-radius: 10px;
            padding: 0.8rem 2rem;
        }
        
        .btn-primary-custom:hover {
            background-color: #3346e6;
            border-color: #3346e6;
            color: white;
        }
        
        .btn-secondary-custom {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            color: white;
            font-weight: 600;
            border-radius: 10px;
            padding: 0.8rem 2rem;
        }
        
        .btn-secondary-custom:hover {
            background-color: #e67e00;
            border-color: #e67e00;
            color: white;
        }
        
        @media (max-width: 768px) {
            .learning-options {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
            
            .back-actions {
                text-align: center;
            }
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
                        <a class="nav-link" href="library.php">Bibliothek</a>
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

    <!-- Page Header -->
    <div class="page-header">
        <div class="container text-center">
            <h1><i class="fas fa-book me-3"></i><?php echo htmlspecialchars($lernset['name']); ?></h1>
            <div class="subtitle"><?php echo count($vocabularies); ?> Vokabeln • <?php echo htmlspecialchars($lernset['description'] ?: 'Keine Beschreibung verfügbar'); ?></div>
        </div>
    </div>

    <div class="container">
        <!-- Back Navigation -->
        <div class="back-actions">
            <a href="library.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Zurück zur Bibliothek
            </a>
            <?php if ($lernset['type'] === 'custom' && $lernset['user_id'] == $user_id): ?>
                <a href="edit_set.php?id=<?php echo $lernset_id; ?>" class="btn btn-secondary-custom ms-2">
                    <i class="fas fa-edit me-2"></i>Bearbeiten
                </a>
            <?php endif; ?>
        </div>

        <!-- Lernset Info -->
        <div class="lernset-info">
            <span class="lernset-badge <?php echo $lernset['type']; ?>">
                <?php echo $lernset['type'] === 'custom' ? 'Eigenes Lernset' : 'Standard-Lernset'; ?>
            </span>
            <h2><?php echo htmlspecialchars($lernset['name']); ?></h2>
            <p class="mb-2"><?php echo htmlspecialchars($lernset['description'] ?: 'Keine Beschreibung verfügbar'); ?></p>
            <small class="text-muted">
                <i class="fas fa-calendar me-1"></i>
                Erstellt am <?php echo date('d.m.Y', strtotime($lernset['created_at'])); ?>
            </small>
        </div>

        <?php if (empty($vocabularies)): ?>
            <!-- Keine Vokabeln vorhanden -->
            <div class="vocabulary-preview">
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Keine Vokabeln vorhanden</h3>
                    <p>In diesem Lernset sind noch keine Vokabeln vorhanden.</p>
                    <?php if ($lernset['type'] === 'custom' && $lernset['user_id'] == $user_id): ?>
                        <a href="edit_set.php?id=<?php echo $lernset_id; ?>" class="btn btn-primary-custom">
                            <i class="fas fa-plus me-2"></i>Vokabeln hinzufügen
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Lernoptionen -->
            <div class="learning-options">
                <a href="karteikarten.php?id=<?php echo $lernset_id; ?>" class="learning-card karteikarten">
                    <div class="icon">
                        <i class="fas fa-clone"></i>
                    </div>
                    <h3>Karteikarten</h3>
                    <p>Lerne mit interaktiven Karteikarten in beide Richtungen</p>
                    <button class="btn btn-primary-custom">
                        <i class="fas fa-play me-2"></i>Starten
                    </button>
                </a>
                
                <a href="schreiben.php?id=<?php echo $lernset_id; ?>" class="learning-card schreiben">
                    <div class="icon">
                        <i class="fas fa-keyboard"></i>
                    </div>
                    <h3>Schreibübung</h3>
                    <p>Übe durch Tippen der korrekten Übersetzungen</p>
                    <button class="btn btn-primary-custom">
                        <i class="fas fa-play me-2"></i>Starten
                    </button>
                </a>
                
                <a href="test.php?id=<?php echo $lernset_id; ?>" class="learning-card test">
                    <div class="icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <h3>Test</h3>
                    <p>Teste dein Wissen mit Multiple-Choice Fragen</p>
                    <button class="btn btn-primary-custom">
                        <i class="fas fa-play me-2"></i>Starten
                    </button>
                </a>
            </div>

            <!-- Vokabel-Vorschau -->
            <div class="vocabulary-preview">
                <h3><i class="fas fa-list me-2"></i>Vokabeln (<?php echo count($vocabularies); ?>)</h3>
                <div class="vocab-list">
                    <?php foreach ($vocabularies as $vocab): ?>
                        <div class="vocab-item">
                            <span class="vocab-deutsch"><?php echo htmlspecialchars($vocab['deutsch']); ?></span>
                            <span class="vocab-fremdsprache"><?php echo htmlspecialchars($vocab['fremdsprache']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>