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
$dbUsername = "root"; // Standardmäßig "root" bei XAMPP
$dbPassword = "root"; // Standardmäßig leer bei XAMPP
$dbName = "vokabeln"; // Hier Datenbankname anpassen falls nötig

$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);

// Verbindung überprüfen
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

// Vokabeln aus der Datenbank abrufen
$sql = "SELECT id, englisch, deutsch FROM woertere";
$result = $conn->query($sql);

$vocabularies = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $vocabularies[] = $row;
    }
}

$conn->close();

// Falls keine Vokabeln gefunden wurden
if (count($vocabularies) == 0) {
    $noVocabularies = true;
} else {
    $noVocabularies = false;
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SprachenMeister - Karteikarten</title>
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
            min-height: 400px;
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

        /* Flashcard Styles */
        .flashcard-container {
            perspective: 1000px;
            width: 100%;
            max-width: 500px;
            height: 300px;
            margin: 0 auto;
        }

        .flashcard {
            width: 100%;
            height: 100%;
            position: relative;
            transition: transform 0.6s;
            transform-style: preserve-3d;
            cursor: pointer;
        }

        .flashcard.flipped {
            transform: rotateY(180deg);
        }

        .flashcard-front, .flashcard-back {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            font-size: 2rem;
            font-weight: bold;
            text-align: center;
        }

        .flashcard-front {
            background-color: #f0f4ff;
            color: var(--primary-color);
            z-index: 2;
        }

        .flashcard-back {
            background-color: #f4f8ff;
            color: #333;
            transform: rotateY(180deg);
        }

        .card-hint {
            font-size: 1rem;
            color: #888;
            position: absolute;
            bottom: 10px;
            width: 100%;
            text-align: center;
        }

        .flashcard-controls {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .progress-text {
            font-size: 1.1rem;
            color: #666;
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
        <h1>Karteikarten Lernen</h1>
        <p class="mt-2">Klicke auf die Karte zum Umdrehen</p>
    </div>

    <!-- Library Content -->
    <div class="library-content">
        <?php if ($noVocabularies): ?>
            <div class="text-center py-5">
                <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                <h3>Keine Vokabeln gefunden</h3>
                <p class="text-muted">Es wurden keine Vokabeln in der Datenbank gefunden.</p>
                <a href="easyVoc.php" class="btn btn-primary mt-3">Zurück zur Übersicht</a>
            </div>
        <?php else: ?>
            <div class="flashcard-container">
                <div id="flashcard" class="flashcard">
                    <div class="flashcard-front">
                        <span id="frontText"></span>
                        <div class="card-hint">Klicke zum Umdrehen</div>
                    </div>
                    <div class="flashcard-back">
                        <span id="backText"></span>
                        <div class="card-hint">Klicke zum Umdrehen</div>
                    </div>
                </div>
            </div>
            <div class="flashcard-controls">
                <button id="prevButton" class="btn btn-outline-primary"><i class="fas fa-chevron-left me-2"></i>Zurück</button>
                <div class="progress-text">Karte <span id="currentCard">1</span> von <span id="totalCards"><?php echo count($vocabularies); ?></span></div>
                <button id="nextButton" class="btn btn-primary">Weiter<i class="fas fa-chevron-right ms-2"></i></button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap and JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if (!$noVocabularies): ?>
    <script>
        // Vokabeln als JavaScript Array
        const vocabularies = <?php echo json_encode($vocabularies); ?>;
        let currentIndex = 0;
        let isFlipped = false;
        
        const flashcard = document.getElementById('flashcard');
        const frontText = document.getElementById('frontText');
        const backText = document.getElementById('backText');
        const currentCardSpan = document.getElementById('currentCard');
        const nextButton = document.getElementById('nextButton');
        const prevButton = document.getElementById('prevButton');
        
        // Karte initialisieren
        updateCard();
        
        // Karte umdrehen bei Klick
        flashcard.addEventListener('click', () => {
            flashcard.classList.toggle('flipped');
            isFlipped = !isFlipped;
        });
        
        // Nächste Karte
        nextButton.addEventListener('click', () => {
            if (currentIndex < vocabularies.length - 1) {
                currentIndex++;
                resetCard();
                updateCard();
            }
        });
        
        // Vorherige Karte
        prevButton.addEventListener('click', () => {
            if (currentIndex > 0) {
                currentIndex--;
                resetCard();
                updateCard();
            }
        });
        
        // Karte aktualisieren
        function updateCard() {
            frontText.textContent = vocabularies[currentIndex].englisch;
            backText.textContent = vocabularies[currentIndex].deutsch;
            currentCardSpan.textContent = currentIndex + 1;
            
            // Prüfen ob es die erste oder letzte Karte ist
            prevButton.disabled = currentIndex === 0;
            nextButton.disabled = currentIndex === vocabularies.length - 1;
        }
        
        // Karte zurücksetzen (nicht umgedreht)
        function resetCard() {
            if (isFlipped) {
                flashcard.classList.remove('flipped');
                isFlipped = false;
            }
        }
    </script>
    <?php endif; ?>
</body>
</html>