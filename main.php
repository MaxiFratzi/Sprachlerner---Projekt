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
$dbPassword = "root"; 
$dbName = "vokabeln"; 

$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);

// Verbindung überprüfen
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

// Dashboard-Statistiken abrufen
$stats = [];

// Gesamtanzahl verfügbare Lernsets (Standard + eigene)
$total_sets_sql = "SELECT COUNT(*) as total FROM lernsets WHERE is_active = 1 AND (type = 'standard' OR user_id = ?)";
$stmt = $conn->prepare($total_sets_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_sets'] = $result->fetch_assoc()['total'];

// Eigene Lernsets
$custom_sets_sql = "SELECT COUNT(*) as total FROM lernsets WHERE type = 'custom' AND user_id = ? AND is_active = 1";
$stmt = $conn->prepare($custom_sets_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['custom_sets'] = $result->fetch_assoc()['total'];

// Gesamtanzahl Vokabeln (aus verfügbaren Lernsets)
$total_vocab_sql = "SELECT COUNT(v.id) as total 
                    FROM vokabeln v 
                    JOIN lernsets l ON v.lernset_id = l.id 
                    WHERE l.is_active = 1 AND (l.type = 'standard' OR l.user_id = ?)";
$stmt = $conn->prepare($total_vocab_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_vocabularies'] = $result->fetch_assoc()['total'];

// Letzte 5 Lernsets (für Quick Access)
$recent_sets_sql = "SELECT l.id, l.name, l.description, l.type, COUNT(v.id) as vocab_count 
                    FROM lernsets l 
                    LEFT JOIN vokabeln v ON l.id = v.lernset_id 
                    WHERE l.is_active = 1 AND (l.type = 'standard' OR l.user_id = ?)
                    GROUP BY l.id 
                    ORDER BY l.created_at DESC 
                    LIMIT 5";
$stmt = $conn->prepare($recent_sets_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$recent_sets = [];
while ($row = $result->fetch_assoc()) {
    $recent_sets[] = $row;
}

// Beliebteste Lernsets (Standard-Sets mit meisten Vokabeln)
$popular_sets_sql = "SELECT l.id, l.name, l.description, COUNT(v.id) as vocab_count 
                     FROM lernsets l 
                     LEFT JOIN vokabeln v ON l.id = v.lernset_id 
                     WHERE l.type = 'standard' AND l.is_active = 1
                     GROUP BY l.id 
                     ORDER BY vocab_count DESC 
                     LIMIT 3";
$result = $conn->query($popular_sets_sql);

$popular_sets = [];
while ($row = $result->fetch_assoc()) {
    $popular_sets[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SprachenMeister - Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Fontawesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        .welcome-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        
        .welcome-content {
            text-align: center;
        }
        
        .welcome-content h1 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .welcome-content .subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }
        
        .quick-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .quick-action-btn {
            background-color: rgba(255, 255, 255, 0.2);
            border: 2px solid white;
            color: white;
            border-radius: 50px;
            padding: 0.8rem 2rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .quick-action-btn:hover {
            background-color: white;
            color: var(--primary-color);
            transform: translateY(-2px);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-card.primary .stat-icon {
            color: var(--primary-color);
        }
        
        .stat-card.success .stat-icon {
            color: var(--success-color);
        }
        
        .stat-card.info .stat-icon {
            color: var(--info-color);
        }
        
        .stat-card.warning .stat-icon {
            color: var(--warning-color);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stat-card.primary .stat-number {
            color: var(--primary-color);
        }
        
        .stat-card.success .stat-number {
            color: var(--success-color);
        }
        
        .stat-card.info .stat-number {
            color: var(--info-color);
        }
        
        .stat-card.warning .stat-number {
            color: var(--warning-color);
        }
        
        .stat-label {
            color: #666;
            font-weight: 600;
        }
        
        .dashboard-section {
            background-color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f8f9fa;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }
        
        .section-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        .section-link:hover {
            color: #3346e6;
            text-decoration: underline;
        }
        
        .lernset-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .lernset-card {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }
        
        .lernset-card:hover {
            transform: translateY(-3px);
            border-color: var(--primary-color);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .lernset-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .lernset-title {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--primary-color);
            margin-bottom: 0.3rem;
        }
        
        .lernset-badge {
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 10px;
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
            font-size: 0.9rem;
            margin-bottom: 1rem;
            line-height: 1.4;
        }
        
        .lernset-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .lernset-vocab-count {
            color: #888;
            font-size: 0.9rem;
        }
        
        .lernset-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm-custom {
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .empty-state {
            text-align: center;
            color: #666;
            padding: 3rem;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            margin-bottom: 1rem;
        }
        
        .empty-state p {
            margin-bottom: 2rem;
        }
        
        @media (max-width: 768px) {
            .welcome-content h1 {
                font-size: 2rem;
            }
            
            .quick-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1rem;
            }
            
            .stat-card {
                padding: 1.5rem;
            }
            
            .lernset-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-section {
                padding: 1.5rem;
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
                        <a class="nav-link active" href="main.php">Dashboard</a>
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

    <!-- Welcome Section -->
    <div class="welcome-section">
        <div class="container">
            <div class="welcome-content">
                <h1>Willkommen zurück, <?php echo htmlspecialchars($username); ?>!</h1>
                <div class="subtitle">Bereit für deine nächste Lerneinheit?</div>
                
                <div class="quick-actions">
                    <a href="library.php" class="quick-action-btn">
                        <i class="fas fa-book"></i>
                        <span>Bibliothek</span>
                    </a>
                    <a href="create_set.php" class="quick-action-btn">
                        <i class="fas fa-plus"></i> Neues Lernset
                    </a>
                    <?php if (!empty($recent_sets)): ?>
                        <a href="lernset.php?id=<?php echo $recent_sets[0]['id']; ?>" class="quick-action-btn">
                            <i class="fas fa-play"></i> Weiterlernen
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-number"><?php echo $stats['total_sets']; ?></div>
                <div class="stat-label">Verfügbare Lernsets</div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="fas fa-user-edit"></i>
                </div>
                <div class="stat-number"><?php echo $stats['custom_sets']; ?></div>
                <div class="stat-label">Eigene Lernsets</div>
            </div>
            
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="fas fa-list"></i>
                </div>
                <div class="stat-number"><?php echo $stats['total_vocabularies']; ?></div>
                <div class="stat-label">Gesamt Vokabeln</div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="fas fa-fire"></i>
                </div>
                <div class="stat-number"><?php echo count($recent_sets); ?></div>
                <div class="stat-label">Kürzlich verwendet</div>
            </div>
        </div>

        <!-- Recent Learning Sets -->
        <?php if (!empty($recent_sets)): ?>
        <div class="dashboard-section">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-clock me-2"></i>Kürzlich verwendet</h2>
                <a href="library.php" class="section-link">Alle anzeigen <i class="fas fa-arrow-right"></i></a>
            </div>
            
            <div class="lernset-grid">
                <?php foreach ($recent_sets as $set): ?>
                    <div class="lernset-card" onclick="window.location='lernset.php?id=<?php echo $set['id']; ?>'">
                        <div class="lernset-header">
                            <div>
                                <div class="lernset-title"><?php echo htmlspecialchars($set['name']); ?></div>
                                <span class="lernset-badge <?php echo $set['type']; ?>">
                                    <?php echo ucfirst($set['type']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="lernset-description">
                            <?php echo htmlspecialchars($set['description'] ?: 'Keine Beschreibung verfügbar'); ?>
                        </div>
                        <div class="lernset-stats">
                            <span class="lernset-vocab-count">
                                <i class="fas fa-list-ol me-1"></i><?php echo $set['vocab_count']; ?> Vokabeln
                            </span>
                            <div class="lernset-actions">
                                <button class="btn btn-primary btn-sm-custom" onclick="event.stopPropagation(); window.location='karteikarten.php?id=<?php echo $set['id']; ?>'">
                                    <i class="fas fa-clone me-1"></i>Karten
                                </button>
                                <button class="btn btn-outline-primary btn-sm-custom" onclick="event.stopPropagation(); window.location='test.php?id=<?php echo $set['id']; ?>'">
                                    <i class="fas fa-check me-1"></i>Test
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Popular Learning Sets -->
        <?php if (!empty($popular_sets)): ?>
        <div class="dashboard-section">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-star me-2"></i>Beliebte Standard-Lernsets</h2>
                <a href="library.php" class="section-link">Alle anzeigen <i class="fas fa-arrow-right"></i></a>
            </div>
            
            <div class="lernset-grid">
                <?php foreach ($popular_sets as $set): ?>
                    <div class="lernset-card" onclick="window.location='lernset.php?id=<?php echo $set['id']; ?>'">
                        <div class="lernset-header">
                            <div>
                                <div class="lernset-title"><?php echo htmlspecialchars($set['name']); ?></div>
                                <span class="lernset-badge standard">Standard</span>
                            </div>
                        </div>
                        <div class="lernset-description">
                            <?php echo htmlspecialchars($set['description'] ?: 'Keine Beschreibung verfügbar'); ?>
                        </div>
                        <div class="lernset-stats">
                            <span class="lernset-vocab-count">
                                <i class="fas fa-list-ol me-1"></i><?php echo $set['vocab_count']; ?> Vokabeln
                            </span>
                            <div class="lernset-actions">
                                <button class="btn btn-primary btn-sm-custom" onclick="event.stopPropagation(); window.location='karteikarten.php?id=<?php echo $set['id']; ?>'">
                                    <i class="fas fa-clone me-1"></i>Karten
                                </button>
                                <button class="btn btn-outline-primary btn-sm-custom" onclick="event.stopPropagation(); window.location='test.php?id=<?php echo $set['id']; ?>'">
                                    <i class="fas fa-check me-1"></i>Test
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Empty State für neue Benutzer -->
        <?php if (empty($recent_sets) && $stats['custom_sets'] == 0): ?>
        <div class="dashboard-section">
            <div class="empty-state">
                <i class="fas fa-rocket"></i>
                <h3>Willkommen bei SprachenMeister!</h3>
                <p>Du hast noch keine Lernsets erstellt oder verwendet. Starte jetzt mit dem Lernen!</p>
                <div class="d-flex justify-content-center gap-2 flex-wrap">
                    <a href="library.php" class="btn btn-primary">
                        <i class="fas fa-book me-2"></i>Zur Bibliothek
                    </a>
                    <a href="create_set.php" class="btn btn-outline-primary">
                        <i class="fas fa-plus me-2"></i>Eigenes Lernset erstellen
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Animation für Statistik-Karten beim Laden
        document.addEventListener('DOMContentLoaded', function() {
            const statNumbers = document.querySelectorAll('.stat-number');
            
            statNumbers.forEach(function(element) {
                const finalValue = parseInt(element.textContent);
                let currentValue = 0;
                const increment = Math.ceil(finalValue / 20);
                
                const timer = setInterval(function() {
                    currentValue += increment;
                    if (currentValue >= finalValue) {
                        currentValue = finalValue;
                        clearInterval(timer);
                    }
                    element.textContent = currentValue;
                }, 50);
            });
        });
        
        // Hover-Effekte für Lernset-Karten
        document.querySelectorAll('.lernset-card').forEach(function(card) {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-3px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>