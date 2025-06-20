<?php
// Start Session
session_start();

// Prüfen, ob Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Prüfen, ob Lernset-ID übergeben wurde
if (!isset($_GET['set_id']) || empty($_GET['set_id'])) {
    header("Location: main.php");
    exit();
}

$set_id = (int)$_GET['set_id'];

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

// Lernset-Informationen abrufen
$stmt = $conn->prepare("SELECT name, sprache1, sprache2 FROM lernsets WHERE id = ?");
$stmt->bind_param("i", $set_id);
$stmt->execute();
$lernset_result = $stmt->get_result();

if ($lernset_result->num_rows == 0) {
    $conn->close();
    header("Location: main.php");
    exit();
}

$lernset = $lernset_result->fetch_assoc();
$stmt->close();

// Vokabeln aus der Datenbank abrufen
$stmt = $conn->prepare("SELECT id, wort_sprache1, wort_sprache2 FROM vokabeln WHERE lernset_id = ?");
$stmt->bind_param("i", $set_id);
$stmt->execute();
$result = $stmt->get_result();

$vocabularies = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $vocabularies[] = $row;
    }
}

$stmt->close();
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

        .lernset-info {
            text-align: center;
            margin-bottom: 2rem;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 10px;
        }

        .language-labels {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: #666;
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
        <h4><?php echo htmlspecialchars($lernset['name']); ?></h4>
        <p class="mt-2">Klicke auf die Karte zum Umdrehen</p>
    </div>

    <!-- Library Content -->
    <div class="library-content">
        <?php if ($noVocabularies): ?>
            <div class="text-center py-5">
                <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                <h3>Keine Vokabeln gefunden</h3>
                <p class="text-muted">Es wurden keine Vokabeln in diesem Lernset gefunden.</p>
                <a href="voc.php?set_id=<?php echo $set_id; ?>" class="btn btn-primary mt-3">Zurück zur Übersicht</a>
            </div>
        <?php else: ?>
            <!-- Lernset Info -->
            <div class="lernset-info">
                <h5><?php echo htmlspecialchars($lernset['name']); ?></h5>
                <div class="language-labels">
                    <span><strong><?php echo htmlspecialchars($lernset['sprache1']); ?></strong></span>
                    <span><strong><?php echo htmlspecialchars($lernset['sprache2']); ?></strong></span>
                </div>
            </div>

            <!-- Completion Screen (initially hidden) -->
            <div id="completionScreen" class="text-center py-5" style="display: none;">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <h2>Lernset abgeschlossen!</h2>
                <p class="text-muted mb-4">Du hast alle Vokabeln in diesem Set durchgearbeitet.</p>
                <div class="d-flex justify-content-center gap-3">
                    <button id="restartButton" class="btn btn-success">
                        <i class="fas fa-redo me-2"></i>Lernset erneut lernen
                    </button>
                    <a href="voc.php?set_id=<?php echo $set_id; ?>" class="btn btn-primary">
                        <i class="fas fa-th-large me-2"></i>Zurück zur Übersicht
                    </a>
                </div>
            </div>

            <!-- Flashcard UI (initially visible) -->
            <div id="flashcardUI">
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
                    <div>
                        <button id="prevButton" class="btn btn-outline-primary">
                            <i class="fas fa-chevron-left me-2"></i>Zurück
                        </button>
                        <a href="voc.php?set_id=<?php echo $set_id; ?>" class="btn btn-outline-secondary ms-2">
                            <i class="fas fa-th-large me-2"></i>Zurück zur Übersicht
                        </a>
                    </div>
                    <div class="progress-text">Karte <span id="currentCard">1</span> von <span id="totalCards"><?php echo count($vocabularies); ?></span></div>
                    <button id="nextButton" class="btn btn-primary">Weiter<i class="fas fa-chevron-right ms-2"></i></button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap and JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if (!$noVocabularies): ?>
    <script>
        // Vokabeln als JavaScript Array
        const vocabularies = <?php echo json_encode($vocabularies); ?>;
        const setId = <?php echo $set_id; ?>;
        let currentIndex = 0;
        let isFlipped = false;
        let isCompleted = false;
        
        const flashcard = document.getElementById('flashcard');
        const frontText = document.getElementById('frontText');
        const backText = document.getElementById('backText');
        const currentCardSpan = document.getElementById('currentCard');
        const nextButton = document.getElementById('nextButton');
        const prevButton = document.getElementById('prevButton');
        const flashcardUI = document.getElementById('flashcardUI');
        const completionScreen = document.getElementById('completionScreen');
        const restartButton = document.getElementById('restartButton');
        
        // Karte initialisieren
        updateCard();
        
        // Karte umdrehen bei Klick
        flashcard.addEventListener('click', () => {
            flashcard.classList.toggle('flipped');
            isFlipped = !isFlipped;
            
            // Track vocabulary when card is flipped to see answer
            if (isFlipped) {
                trackLearnedVocab();
            }
        });
        
        // Vocabulary tracking function
        function trackLearnedVocab() {
            // Create form data
            const formData = new FormData();
            formData.append('learned', '1');
            formData.append('set_id', setId);
            formData.append('vocab_id', vocabularies[currentIndex].id);
            
            // Send AJAX request
            fetch('track_vocabulary.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                console.log('Vocabulary tracked:', data);
            })
            .catch(error => {
                console.error('Error tracking vocabulary:', error);
            });
        }
        
        // Nächste Karte
        nextButton.addEventListener('click', () => {
            if (currentIndex < vocabularies.length - 1) {
                currentIndex++;
                resetCard();
                updateCard();
            } else if (!isCompleted) {
                // Wenn die letzte Karte erreicht ist und der Button nochmal gedrückt wird
                showCompletionScreen();
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
        
        // Erneut lernen Button
        restartButton.addEventListener('click', () => {
            currentIndex = 0;
            isCompleted = false;
            resetCard();
            updateCard();
            showFlashcardUI();
        });
        
        // Karte aktualisieren
        function updateCard() {
            frontText.textContent = vocabularies[currentIndex].wort_sprache1;
            backText.textContent = vocabularies[currentIndex].wort_sprache2;
            currentCardSpan.textContent = currentIndex + 1;
            
            // Prüfen ob es die erste oder letzte Karte ist
            prevButton.disabled = currentIndex === 0;
        }
        
        // Karte zurücksetzen (nicht umgedreht)
        function resetCard() {
            if (isFlipped) {
                flashcard.classList.remove('flipped');
                isFlipped = false;
            }
        }
        
        // Abschlussbildschirm anzeigen
        function showCompletionScreen() {
            isCompleted = true;
            flashcardUI.style.display = 'none';
            completionScreen.style.display = 'block';
        }
        
        // Karteikarten-UI anzeigen
        function showFlashcardUI() {
            flashcardUI.style.display = 'block';
            completionScreen.style.display = 'none';
        }
    </script>
    <?php endif; ?>
</body>
</html>