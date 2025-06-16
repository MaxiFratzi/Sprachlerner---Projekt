<?php
// Start Session
session_start();

// Prüfen, ob Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Set-ID aus URL-Parameter holen
if (!isset($_GET['set_id']) || !is_numeric($_GET['set_id'])) {
    header("Location: library.php");
    exit();
}

$set_id = intval($_GET['set_id']);
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

// Set-Informationen laden und Berechtigung prüfen
$set_stmt = $conn->prepare("SELECT name, description, user_id FROM learning_sets WHERE id = ?");
$set_stmt->bind_param("i", $set_id);
$set_stmt->execute();
$set_result = $set_stmt->get_result();

if ($set_result->num_rows === 0) {
    header("Location: library.php");
    exit();
}

$set_data = $set_result->fetch_assoc();
$set_stmt->close();

// Alle Vokabeln des Sets laden
$vocab_stmt = $conn->prepare("SELECT id, german_word, english_word FROM vocabulary WHERE set_id = ? ORDER BY RAND()");
$vocab_stmt->bind_param("i", $set_id);
$vocab_stmt->execute();
$vocab_result = $vocab_stmt->get_result();

$vocabularies = [];
while ($row = $vocab_result->fetch_assoc()) {
    $vocabularies[] = $row;
}
$vocab_stmt->close();

// Falls keine Vokabeln vorhanden sind
if (empty($vocabularies)) {
    $conn->close();
    header("Location: library.php?error=no_vocab");
    exit();
}

// Lernfortschritt verfolgen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['learned_count'])) {
    $learned_count = intval($_POST['learned_count']);
    $today = date('Y-m-d');
    
    // Prüfen, ob bereits ein Eintrag für heute existiert
    $check_stmt = $conn->prepare("SELECT count FROM vocabulary_tracking WHERE user_id = ? AND learn_date = ?");
    $check_stmt->bind_param("is", $user_id, $today);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Eintrag aktualisieren
        $current_row = $check_result->fetch_assoc();
        $new_count = $current_row['count'] + $learned_count;
        $update_stmt = $conn->prepare("UPDATE vocabulary_tracking SET count = ? WHERE user_id = ? AND learn_date = ?");
        $update_stmt->bind_param("iis", $new_count, $user_id, $today);
        $update_stmt->execute();
        $update_stmt->close();
    } else {
        // Neuen Eintrag erstellen
        $insert_stmt = $conn->prepare("INSERT INTO vocabulary_tracking (user_id, learn_date, count) VALUES (?, ?, ?)");
        $insert_stmt->bind_param("isi", $user_id, $today, $learned_count);
        $insert_stmt->execute();
        $insert_stmt->close();
    }
    $check_stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SprachenMeister - <?php echo htmlspecialchars($set_data['name']); ?></title>
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
        
        .learning-header {
            background-color: var(--primary-color);
            color: white;
            padding: 2rem 0;
            text-align: center;
        }
        
        .flashcard-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .flashcard {
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 3rem;
            text-align: center;
            min-height: 300px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            cursor: pointer;
            transition: transform 0.3s;
            margin-bottom: 2rem;
        }
        
        .flashcard:hover {
            transform: translateY(-5px);
        }
        
        .flashcard.flipped {
            background-color: var(--light-blue);
        }
        
        .word-front {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .word-back {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 1rem;
        }
        
        .instruction {
            color: #666;
            font-size: 1.1rem;
        }
        
        .progress-section {
            background-color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .progress-bar {
            height: 20px;
            border-radius: 10px;
        }
        
        .controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 800px;
            margin: 0 auto 2rem auto;
            padding: 0 1rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 50px;
        }
        
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 50px;
        }
        
        .btn-outline-secondary {
            padding: 10px 25px;
            font-weight: 600;
            border-radius: 50px;
        }
        
        .completion-modal {
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            border-radius: 20px;
        }
        
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container">
            <a class="navbar-brand" href="main.php">SprachenMeister</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="library.php">
                    <i class="fas fa-arrow-left me-2"></i>Zurück zur Bibliothek
                </a>
            </div>
        </div>
    </nav>

    <!-- Learning Header -->
    <div class="learning-header">
        <h1><?php echo htmlspecialchars($set_data['name']); ?></h1>
        <p><?php echo htmlspecialchars($set_data['description']); ?></p>
        <p><strong><?php echo count($vocabularies); ?> Vokabeln</strong> in diesem Set</p>
    </div>

    <!-- Progress Section -->
    <div class="container">
        <div class="progress-section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Lernfortschritt</h5>
                <span id="progress-text">0 / <?php echo count($vocabularies); ?></span>
            </div>
            <div class="progress">
                <div class="progress-bar bg-success" role="progressbar" id="progress-bar" 
                     style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                </div>
            </div>
        </div>
    </div>

    <!-- Flashcard Container -->
    <div class="flashcard-container">
        <div class="flashcard" id="flashcard" onclick="flipCard()">
            <div id="card-front">
                <div class="word-front" id="german-word"></div>
                <div class="instruction">Klicken zum Umdrehen</div>
            </div>
            <div id="card-back" class="hidden">
                <div class="word-back" id="english-word"></div>
                <div class="instruction">Klicken zum Zurückdrehen</div>
            </div>
        </div>
    </div>

    <!-- Controls -->
    <div class="controls">
        <button class="btn btn-outline-secondary" onclick="previousCard()" id="prev-btn" disabled>
            <i class="fas fa-arrow-left me-2"></i>Zurück
        </button>
        
        <div class="text-center">
            <button class="btn btn-success me-2" onclick="markAsLearned()" id="learned-btn">
                <i class="fas fa-check me-2"></i>Gelernt
            </button>
            <button class="btn btn-primary" onclick="nextCard()" id="next-btn">
                Weiter<i class="fas fa-arrow-right ms-2"></i>
            </button>
        </div>
        
        <button class="btn btn-outline-secondary" onclick="window.location.href='library.php'">
            <i class="fas fa-times me-2"></i>Beenden
        </button>
    </div>

    <!-- Completion Modal -->
    <div class="modal fade" id="completionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-trophy text-warning me-2"></i>Herzlichen Glückwunsch!
                    </h5>
                </div>
                <div class="modal-body text-center">
                    <p>Du hast alle Vokabeln in diesem Set durchgegangen!</p>
                    <p><strong id="learned-count-display">0</strong> Vokabeln als gelernt markiert.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" onclick="restartLearning()">
                        <i class="fas fa-redo me-2"></i>Nochmal lernen
                    </button>
                    <button type="button" class="btn btn-primary" onclick="window.location.href='library.php'">
                        <i class="fas fa-book me-2"></i>Zur Bibliothek
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap and JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Vokabel-Daten aus PHP
        const vocabularies = <?php echo json_encode($vocabularies); ?>;
        let currentIndex = 0;
        let isFlipped = false;
        let learnedCount = 0;
        let learnedCards = new Set();

        // Initiale Karte laden
        loadCard(currentIndex);

        function loadCard(index) {
            if (index >= vocabularies.length) {
                showCompletionModal();
                return;
            }

            const vocab = vocabularies[index];
            document.getElementById('german-word').textContent = vocab.german_word;
            document.getElementById('english-word').textContent = vocab.english_word;
            
            // Karte zurücksetzen
            isFlipped = false;
            document.getElementById('card-front').classList.remove('hidden');
            document.getElementById('card-back').classList.add('hidden');
            document.getElementById('flashcard').classList.remove('flipped');
            
            updateProgress();
            updateButtons();
        }

        function flipCard() {
            const cardFront = document.getElementById('card-front');
            const cardBack = document.getElementById('card-back');
            const flashcard = document.getElementById('flashcard');

            if (isFlipped) {
                cardFront.classList.remove('hidden');
                cardBack.classList.add('hidden');
                flashcard.classList.remove('flipped');
                isFlipped = false;
            } else {
                cardFront.classList.add('hidden');
                cardBack.classList.remove('hidden');
                flashcard.classList.add('flipped');
                isFlipped = true;
            }
        }

        function nextCard() {
            if (currentIndex < vocabularies.length - 1) {
                currentIndex++;
                loadCard(currentIndex);
            } else {
                showCompletionModal();
            }
        }

        function previousCard() {
            if (currentIndex > 0) {
                currentIndex--;
                loadCard(currentIndex);
            }
        }

        function markAsLearned() {
            const vocabId = vocabularies[currentIndex].id;
            if (!learnedCards.has(vocabId)) {
                learnedCards.add(vocabId);
                learnedCount++;
            }
            nextCard();
        }

        function updateProgress() {
            const progress = ((currentIndex) / vocabularies.length) * 100;
            document.getElementById('progress-bar').style.width = progress + '%';
            document.getElementById('progress-text').textContent = `${currentIndex} / ${vocabularies.length}`;
        }

        function updateButtons() {
            document.getElementById('prev-btn').disabled = (currentIndex === 0);
            document.getElementById('next-btn').style.display = 
                (currentIndex === vocabularies.length - 1) ? 'none' : 'inline-block';
        }

        function showCompletionModal() {
            document.getElementById('learned-count-display').textContent = learnedCount;
            
            // Lernfortschritt an Server senden
            if (learnedCount > 0) {
                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'learned_count=' + learnedCount
                });
            }
            
            const modal = new bootstrap.Modal(document.getElementById('completionModal'));
            modal.show();
        }

        function restartLearning() {
            currentIndex = 0;
            learnedCount = 0;
            learnedCards.clear();
            loadCard(currentIndex);
            
            const modal = bootstrap.Modal.getInstance(document.getElementById('completionModal'));
            modal.hide();
        }

        // Tastatur-Shortcuts
        document.addEventListener('keydown', function(event) {
            switch(event.key) {
                case ' ':
                case 'Enter':
                    event.preventDefault();
                    flipCard();
                    break;
                case 'ArrowRight':
                    event.preventDefault();
                    nextCard();
                    break;
                case 'ArrowLeft':
                    event.preventDefault();
                    previousCard();
                    break;
                case 'l':
                case 'L':
                    event.preventDefault();
                    markAsLearned();
                    break;
            }
        });
    </script>
</body>
</html>