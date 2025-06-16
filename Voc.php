<?php
// Start Session
session_start();

// Prüfen, ob Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

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

// Suchparameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Feedback-Nachrichten
$message = '';
$messageType = '';

if (isset($_GET['created']) && $_GET['created'] == 1) {
    $message = "Lernset erfolgreich erstellt!";
    $messageType = "success";
} elseif (isset($_GET['deleted']) && $_GET['deleted'] == 1) {
    $message = "Lernset erfolgreich gelöscht!";
    $messageType = "success";
}

// Lernsets laden
$where_conditions = ["l.is_active = 1"];
$params = [];
$types = "";

// Filter anwenden
if ($filter === 'custom') {
    $where_conditions[] = "l.type = 'custom' AND l.user_id = ?";
    $params[] = $user_id;
    $types .= "i";
} elseif ($filter === 'standard') {
    $where_conditions[] = "l.type = 'standard'";
} else {
    // Alle Lernsets: Standard + eigene Custom
    $where_conditions[] = "(l.type = 'standard' OR (l.type = 'custom' AND l.user_id = ?))";
    $params[] = $user_id;
    $types .= "i";
}

// Suchfilter
if (!empty($search)) {
    $where_conditions[] = "(l.name LIKE ? OR l.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

// SQL Query
$sql = "SELECT l.id, l.name, l.description, l.type, l.user_id, l.created_at, COUNT(v.id) as vocab_count 
        FROM lernsets l 
        LEFT JOIN vokabeln v ON l.id = v.lernset_id 
        WHERE " . implode(" AND ", $where_conditions) . "
        GROUP BY l.id 
        ORDER BY l.type ASC, l.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$lernsets = [];
while ($row = $result->fetch_assoc()) {
    $lernsets[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SprachenMeister - Bibliothek</title>
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
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 3rem 0;
            text-align: center;
        }
        
        .page-header h1 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .page-header .subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .search-section {
            background-color: white;
            border-radius: 15px;
            padding: 2rem;
            margin: -2rem auto 2rem;
            max-width: 800px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            position: relative;
            z-index: 10;
        }
        
        .search-form {
            display: flex;
            gap: 1rem;
            align-items: end;
        }
        
        .search-input {
            flex: 1;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.8rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(66, 85, 255, 0.25);
        }
        
        .btn-primary-custom {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            font-weight: 600;
            border-radius: 10px;
            padding: 0.8rem 2rem;
            white-space: nowrap;
        }
        
        .btn-primary-custom:hover {
            background-color: #3346e6;
            border-color: #3346e6;
            color: white;
        }
        
        .filter-tabs {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }
        
        .filter-tab {
            background-color: white;
            border: 2px solid #e9ecef;
            color: #666;
            border-radius: 25px;
            padding: 0.6rem 1.5rem;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .filter-tab:hover, .filter-tab.active {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            text-decoration: none;
        }
        
        .create-button {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 60px;
            height: 60px;
            background-color: var(--secondary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            text-decoration: none;
            color: white;
            font-size: 1.5rem;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .create-button:hover {
            background-color: #e67e00;
            transform: scale(1.1);
            color: white;
        }
        
        .lernsets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            padding: 2rem 0;
        }
        
        .lernset-card {
            background-color: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }
        
        .lernset-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .lernset-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .lernset-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .lernset-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            color: white;
        }
        
        .lernset-badge.standard {
            background-color: var(--primary-color);
        }
        
        .lernset-badge.custom {
            background-color: var(--secondary-color);
        }
        
        .lernset-description {
            color: #666;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }
        
        .lernset-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .lernset-vocab-count {
            color: #888;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .lernset-date {
            color: #aaa;
            font-size: 0.8rem;
        }
        
        .lernset-actions {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 0.5rem;
        }
        
        .action-btn {
            padding: 0.6rem;
            border-radius: 8px;
            text-decoration: none;
            text-align: center;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .action-btn.primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .action-btn.primary:hover {
            background-color: #3346e6;
            color: white;
        }
        
        .action-btn.secondary {
            background-color: var(--info-color);
            color: white;
        }
        
        .action-btn.secondary:hover {
            background-color: #138496;
            color: white;
        }
        
        .action-btn.warning {
            background-color: var(--warning-color);
            color: #856404;
        }
        
        .action-btn.warning:hover {
            background-color: #e0a800;
            color: #856404;
        }
        
        .action-btn.edit {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .action-btn.edit:hover {
            background-color: #e67e00;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #666;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            margin-bottom: 1rem;
        }
        
        .empty-state p {
            margin-bottom: 2rem;
        }
        
        .alert {
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 2rem;
        }
        
        .alert-success {
            background-color: #d4edda;
            border-color: var(--success-color);
            color: var(--success-color);
        }
        
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }
            
            .search-section {
                margin: -1rem 1rem 2rem;
                padding: 1.5rem;
            }
            
            .search-form {
                flex-direction: column;
                gap: 1rem;
            }
            
            .filter-tabs {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            
            .lernsets-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
                padding: 1rem;
            }
            
            .lernset-actions {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
            
            .create-button {
                bottom: 1rem;
                right: 1rem;
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
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
                        <a class="nav-link active" href="Voc.php">Bibliothek</a>
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
        <div class="container">
            <h1><i class="fas fa-book me-3"></i>Lernset-Bibliothek</h1>
            <div class="subtitle">Entdecke und verwalte deine Lernsets</div>
        </div>
    </div>

    <div class="container">
        <!-- Search Section -->
        <div class="search-section">
            <?php if (!empty($message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="GET" action="Voc.php" class="search-form">
                <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                <div class="search-input">
                    <label for="search" class="form-label">Lernsets durchsuchen</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Nach Name oder Beschreibung suchen...">
                </div>
                <button type="submit" class="btn btn-primary-custom">
                    <i class="fas fa-search me-2"></i>Suchen
                </button>
                <?php if (!empty($search)): ?>
                    <a href="Voc.php?filter=<?php echo htmlspecialchars($filter); ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="Voc.php?search=<?php echo urlencode($search); ?>&filter=all" 
               class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                <i class="fas fa-th-large me-1"></i>Alle Lernsets
            </a>
            <a href="Voc.php?search=<?php echo urlencode($search); ?>&filter=standard" 
               class="filter-tab <?php echo $filter === 'standard' ? 'active' : ''; ?>">
                <i class="fas fa-star me-1"></i>Standard
            </a>
            <a href="Voc.php?search=<?php echo urlencode($search); ?>&filter=custom" 
               class="filter-tab <?php echo $filter === 'custom' ? 'active' : ''; ?>">
                <i class="fas fa-user-edit me-1"></i>Meine Lernsets
            </a>
        </div>

        <!-- Lernsets Grid -->
        <?php if (empty($lernsets)): ?>
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h3>Keine Lernsets gefunden</h3>
                <?php if (!empty($search)): ?>
                    <p>Keine Lernsets entsprechen deiner Suche nach "<?php echo htmlspecialchars($search); ?>".</p>
                    <a href="Voc.php?filter=<?php echo htmlspecialchars($filter); ?>" class="btn btn-primary-custom">
                        <i class="fas fa-times me-2"></i>Suche zurücksetzen
                    </a>
                <?php elseif ($filter === 'custom'): ?>
                    <p>Du hast noch keine eigenen Lernsets erstellt.</p>
                    <a href="create_set.php" class="btn btn-primary-custom">
                        <i class="fas fa-plus me-2"></i>Erstes Lernset erstellen
                    </a>
                <?php else: ?>
                    <p>Keine Lernsets verfügbar.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="lernsets-grid">
                <?php foreach ($lernsets as $lernset): ?>
                    <div class="lernset-card" onclick="window.location='lernset.php?id=<?php echo $lernset['id']; ?>'">
                        <div class="lernset-header">
                            <div>
                                <div class="lernset-title"><?php echo htmlspecialchars($lernset['name']); ?></div>
                                <span class="lernset-badge <?php echo $lernset['type']; ?>">
                                    <?php echo $lernset['type'] === 'custom' ? 'Eigenes Set' : 'Standard'; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="lernset-description">
                            <?php echo htmlspecialchars($lernset['description'] ?: 'Keine Beschreibung verfügbar'); ?>
                        </div>
                        
                        <div class="lernset-stats">
                            <div class="lernset-vocab-count">
                                <i class="fas fa-list-ol"></i>
                                <?php echo $lernset['vocab_count']; ?> Vokabeln
                            </div>
                            <div class="lernset-date">
                                <?php echo date('d.m.Y', strtotime($lernset['created_at'])); ?>
                            </div>
                        </div>
                        
                        <div class="lernset-actions">
                            <a href="karteikarten.php?id=<?php echo $lernset['id']; ?>" 
                               class="action-btn primary" 
                               onclick="event.stopPropagation();"
                               title="Karteikarten lernen">
                                <i class="fas fa-clone me-1"></i>Karten
                            </a>
                            <a href="schreiben.php?id=<?php echo $lernset['id']; ?>" 
                               class="action-btn secondary" 
                               onclick="event.stopPropagation();"
                               title="Schreibübung">
                                <i class="fas fa-keyboard me-1"></i>Schreiben
                            </a>
                            <a href="test.php?id=<?php echo $lernset['id']; ?>" 
                               class="action-btn warning" 
                               onclick="event.stopPropagation();"
                               title="Test machen">
                                <i class="fas fa-check me-1"></i>Test
                            </a>
                            <?php if ($lernset['type'] === 'custom' && $lernset['user_id'] == $user_id): ?>
                                <a href="edit_set.php?id=<?php echo $lernset['id']; ?>" 
                                   class="action-btn edit" 
                                   onclick="event.stopPropagation();"
                                   title="Lernset bearbeiten">
                                    <i class="fas fa-edit me-1"></i>Bearbeiten
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Create Button -->
    <a href="create_set.php" class="create-button" title="Neues Lernset erstellen">
        <i class="fas fa-plus"></i>
    </a>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Search on Enter key
        document.getElementById('search').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.closest('form').submit();
            }
        });
        
        // Clear search when X is clicked
        document.querySelectorAll('.btn-outline-secondary').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                window.location.href = this.href;
            });
        });
    </script>
</body>
</html>