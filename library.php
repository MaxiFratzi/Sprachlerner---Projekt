<?php
// Start Session
session_start();

// Prüfen, ob Benutzer eingeloggt ist (optional, je nach Ihren Anforderungen)
// if (!isset($_SESSION['user_id'])) {
//     header("Location: login.php");
//     exit();
// }

// Suchfunktion
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$learning_sets = [];

// Alle verfügbaren Lernsets definieren
$all_sets = [
    [
        'name' => 'Easy', 
        'terms' => 48, 
        'file' => 'easyVoc.php', 
        'description' => 'Einfache Vokabeln für Anfänger',
        'category' => 'Diese Woche'
    ],
    [
        'name' => 'Medium', 
        'terms' => 48, 
        'file' => 'mediumVoc.php', 
        'description' => 'Mittelschwere Vokabeln',
        'category' => 'Im April 2025'
    ],
    [
        'name' => 'Hard', 
        'terms' => 48, 
        'file' => 'hardVoc.php', 
        'description' => 'Schwere Vokabeln für Fortgeschrittene',
        'category' => 'Im März 2025'
    ],
    // Hier können Sie weitere Sets hinzufügen
];

// Suche durchführen
if (!empty($search_query)) {
    foreach ($all_sets as $set) {
        if (stripos($set['name'], $search_query) !== false || 
            stripos($set['description'], $search_query) !== false ||
            stripos($set['category'], $search_query) !== false) {
            $learning_sets[] = $set;
        }
    }
} else {
    $learning_sets = $all_sets;
}

// Sets nach Kategorien gruppieren
$grouped_sets = [];
foreach ($learning_sets as $set) {
    $grouped_sets[$set['category']][] = $set;
}
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
        }
        
        .library-set:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .library-set-info {
            flex-grow: 1;
        }
        
        .library-set-title {
            font-weight: 600;
            font-size: 1.1rem;
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
            padding: 10px 25px;
            font-weight: 600;
            border-radius: 50px;
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
        
        .search-results-info {
            margin-bottom: 20px;
            color: #666;
            font-size: 0.9rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .no-results {
            text-align: center;
            color: #666;
            font-style: italic;
            margin: 40px 0;
        }
        
        .navbar-search {
            position: relative;
        }
        
        .navbar-search .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            z-index: 2;
        }
        
        .navbar-search input {
            padding-left: 45px;
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
                <form class="d-flex mx-auto mb-2 mb-lg-0 navbar-search" method="GET" action="library.php">
                    <i class="fas fa-search search-icon"></i>
                    <input class="form-control me-2" type="search" name="search" 
                           placeholder="Nach Lernsets suchen..." 
                           value="<?php echo htmlspecialchars($search_query); ?>"
                           style="width: 300px; border-radius: 20px;">
                    <button class="btn btn-outline-primary" type="submit" style="border-radius: 20px;">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
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
            </ul>
        </div>
    </div>

    <!-- Library Content -->
    <div class="library-content">
        <!-- Interne Suchleiste -->
        <div class="search-bar">
            <form method="GET" action="library.php">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="form-control" name="search" 
                       placeholder="Karteikarten suchen..." 
                       value="<?php echo htmlspecialchars($search_query); ?>"
                       style="border-radius: 20px;">
            </form>
        </div>

        <?php if (!empty($search_query)): ?>
            <div class="search-results-info">
                <span>
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
                <p>Keine Lernsets gefunden für "<?php echo htmlspecialchars($search_query); ?>"</p>
                <a href="library.php" class="btn btn-outline-primary">Alle Sets anzeigen</a>
            </div>
        <?php else: ?>
            <?php foreach ($grouped_sets as $category => $sets): ?>
                <h4 class="mb-3 <?php echo ($category !== array_key_first($grouped_sets)) ? 'mt-4' : ''; ?>">
                    <?php echo htmlspecialchars($category); ?>
                </h4>
                <?php foreach ($sets as $set): ?>
                    <div class="library-set">
                        <div class="library-set-info">
                            <div class="library-set-title"><?php echo htmlspecialchars($set['name']); ?></div>
                            <div class="library-set-details"><?php echo $set['terms']; ?> Begriffe</div>
                            <div class="library-set-description"><?php echo htmlspecialchars($set['description']); ?></div>
                        </div>
                        <div>
                            <a href="<?php echo htmlspecialchars($set['file']); ?>" class="btn btn-primary">Lernen</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
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
        // Auto-submit bei Enter-Taste
        document.querySelectorAll('input[name="search"]').forEach(function(input) {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    this.closest('form').submit();
                }
            });
        });
        
        // Suchfeld fokussieren mit Strg+K
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'k') {
                e.preventDefault();
                document.querySelector('input[name="search"]').focus();
            }
        });
    </script>
</body>
</html>