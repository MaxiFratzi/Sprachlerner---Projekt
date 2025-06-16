<?php
// filepath: c:\xampp\htdocs\3BHWII\Sprachlerner - Projekt\karteikarten.php
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
$vocab_sql = "SELECT * FROM vokabeln WHERE lernset_id = ? ORDER BY RAND()";
$vocab_stmt = $conn->prepare($vocab_sql);
$vocab_stmt->bind_param("i", $lernset_id);
$vocab_stmt->execute();
$vocab_result = $vocab_stmt->get_result();

$vocabularies = [];
$noVocabularies = false;

if ($vocab_result->num_rows > 0) {
    while($row = $vocab_result->fetch_assoc()) {
        $vocabularies[] = [
            'id' => $row['id'],
            'deutsch' => $row['deutsch'],
            'fremdsprache' => $row['fremdsprache']
        ];
    }
} else {
    $noVocabularies = true;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SprachenMeister - Karteikarten: <?php echo htmlspecialchars($lernset['name']); ?></title>
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
            --danger-color: #dc3545;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        
        .navbar {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            background-color: white !important;
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
        
        .flashcard-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .progress-card {
            background-color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .progress-info {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .flashcard {
            width: 100%;
            height: 400px;
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            cursor: pointer;
            transition: transform 0.6s;
            transform-style: preserve-3d;
            position: relative;
            margin-bottom: 2rem;
        }
        
        .flashcard:hover {
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }
        
        .flashcard.flipped {
            transform: rotateY(180deg);
        }
        
        .flashcard-front,
        .flashcard-back {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            box-sizing: border-box;
            border: 3px solid #e9ecef;
        }
        
        .flashcard-front {
            background-color: white;
            color: var(--primary-color);
        }
        
        .flashcard-back {
            background-color: #f8f9fa;
            color: var(--secondary-color);
            transform: rotateY(180deg);
            border-color: var(--secondary-color);
        }
        
        .flashcard-text {
            font-size: 2.5rem;
            font-weight: 600;
            text-align: center;
            word-wrap: break-word;
            line-height: 1.3;
        }
        
        .card-type {
            position: absolute;
            top: 20px;
            left: 20px;
            background-color: var(--primary-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .flashcard-back .card-type {
            background-color: var(--secondary-color);
        }
        
        .flashcard-controls {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .control-btn {
            background-color: var(--primary-color);
            border: none;
            color: white;
            border-radius: 10px;
            padding: 1rem 2rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .control-btn:hover {
            background-color: #3346e6;
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }
        
        .control-btn:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
            transform: none;
        }
        
        .control-btn.secondary {
            background-color: var(--secondary-color);
        }
        
        .control-btn.secondary:hover {
            background-color: #e67e00;
        }
        
        .flip-hint {
            text-align: center;
            color: #666;
            margin-bottom: 2rem;
            font-size: 1rem;
            background-color: white;
            padding: 1rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .completion-screen {
            display: none;
            text-align: center;
            background-color: white;
            border-radius: 20px;
            padding: 4rem 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .completion-screen i {
            font-size: 4rem;
            color: var(--success-color);
            margin-bottom: 1rem;
        }
        
        .completion-screen h2 {
            color: var(--primary-color);
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .completion-screen p {
            color: #666;
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }
        
        .completion-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .no-vocab-message {
            text-align: center;
            background-color: white;
            border-radius: 20px;
            padding: 4rem 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .no-vocab-message i {
            font-size: 4rem;
            color: var(--warning-color);
            margin-bottom: 1rem;
        }
        
        .no-vocab-message h2 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .no-vocab-message p {
            color: #666;
            margin-bottom: 2rem;
        }
        
        .back-actions {
            background-color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .flashcard {
                height: 300px;
            }
            
            .flashcard-text {
                font-size: 1.8rem;
            }
            
            .flashcard-controls {
                flex-direction: column;
                align-items: center;
            }
            
            .completion-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .flashcard-front,
            .flashcard-back {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light">
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
            <h1><i class="fas fa-clone me-3"></i>Karteikarten</h1>
            <div class="subtitle"><?php echo htmlspecialchars($lernset['name']); ?></div>
        </div>
    </div>

    <div class="container">
        <div class="flashcard-container">
            <!-- Back Navigation -->
            <div class="back-actions">
                <a href="lernset.php?id=<?php echo $lernset_id; ?>" class="control-btn">
                    <i class="fas fa-arrow-left"></i> Zurück zum Lernset
                </a>
                <a href="library.php" class="control-btn secondary">
                    <i class="fas fa-book"></i> Zur Bibliothek
                </a>
            </div>

            <?php if ($noVocabularies): ?>
                <!-- Keine Vokabeln vorhanden -->
                <div class="no-vocab-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h2>Keine Vokabeln gefunden</h2>
                    <p>In diesem Lernset sind noch keine Vokabeln vorhanden.</p>
                    <?php if ($lernset['type'] === 'custom' && $lernset['user_id'] == $user_id): ?>
                        <a href="edit_set.php?id=<?php echo $lernset_id; ?>" class="control-btn">
                            <i class="fas fa-plus"></i> Vokabeln hinzufügen
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Progress Info -->
                <div class="progress-card">
                    <div class="progress-info">
                        Karte <span id="currentCard">1</span> von <?php echo count($vocabularies); ?>
                    </div>
                </div>
                
                <!-- Karteikarten Interface -->
                <div id="flashcardUI">
                    <div class="flashcard" id="flashcard">
                        <div class="flashcard-front">
                            <div class="card-type">Deutsch</div>
                            <div class="flashcard-text" id="frontText">
                                <!-- Wird durch JavaScript gefüllt -->
                            </div>
                        </div>
                        <div class="flashcard-back">
                            <div class="card-type">Fremdsprache</div>
                            <div class="flashcard-text" id="backText">
                                <!-- Wird durch JavaScript gefüllt -->
                            </div>
                        </div>
                    </div>
                    
                    <div class="flip-hint">
                        <i class="fas fa-hand-pointer me-2"></i>
                        Klicke auf die Karte, um sie umzudrehen
                        <br><small class="text-muted">Verwende auch die Pfeiltasten ← → und Leertaste</small>
                    </div>
                    
                    <div class="flashcard-controls">
                        <button class="control-btn" id="prevButton">
                            <i class="fas fa-chevron-left"></i> Vorherige
                        </button>
                        <button class="control-btn secondary" id="nextButton">
                            Nächste <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Abschlussbildschirm -->
                <div class="completion-screen" id="completionScreen">
                    <i class="fas fa-trophy"></i>
                    <h2>Gratulation!</h2>
                    <p>Du hast alle <?php echo count($vocabularies); ?> Karteikarten durchgeschaut!</p>
                    <div class="completion-actions">
                        <button class="control-btn" id="restartButton">
                            <i class="fas fa-redo"></i> Nochmal lernen
                        </button>
                        <a href="lernset.php?id=<?php echo $lernset_id; ?>" class="control-btn">
                            <i class="fas fa-arrow-left"></i> Zurück zum Lernset
                        </a>
                        <a href="library.php" class="control-btn secondary">
                            <i class="fas fa-book"></i> Zur Bibliothek
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap and JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if (!$noVocabularies): ?>
    <script>
        // Vokabeln als JavaScript Array
        const vocabularies = <?php echo json_encode($vocabularies); ?>;
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
        });
        
        // Nächste Karte
        nextButton.addEventListener('click', () => {
            if (currentIndex < vocabularies.length - 1) {
                resetCard();
                setTimeout(() => {
                    currentIndex++;
                    updateCard();
                }, 300);
            } else if (!isCompleted) {
                showCompletionScreen();
            }
        });
        
        // Vorherige Karte
        prevButton.addEventListener('click', () => {
            if (currentIndex > 0) {
                resetCard();
                setTimeout(() => {
                    currentIndex--;
                    updateCard();
                }, 300);
            }
        });
        
        // Neustart
        restartButton.addEventListener('click', () => {
            currentIndex = 0;
            isCompleted = false;
            resetCard();
            updateCard();
            showFlashcardUI();
        });
        
        // Karte aktualisieren
        function updateCard() {
            const vocab = vocabularies[currentIndex];
            frontText.textContent = vocab.deutsch;
            backText.textContent = vocab.fremdsprache;
            currentCardSpan.textContent = currentIndex + 1;
            
            // Button-Status aktualisieren
            prevButton.disabled = currentIndex === 0;
            
            if (currentIndex === vocabularies.length - 1) {
                nextButton.innerHTML = '<i class="fas fa-flag-checkered"></i> Fertig';
            } else {
                nextButton.innerHTML = 'Nächste <i class="fas fa-chevron-right"></i>';
            }
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
        
        // Keyboard controls
        document.addEventListener('keydown', function(e) {
            if (e.code === 'Space') {
                e.preventDefault();
                flashcard.click();
            } else if (e.code === 'ArrowLeft') {
                e.preventDefault();
                if (!prevButton.disabled) {
                    prevButton.click();
                }
            } else if (e.code === 'ArrowRight') {
                e.preventDefault();
                nextButton.click();
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>