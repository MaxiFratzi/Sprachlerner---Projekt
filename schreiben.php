<?php
// filepath: c:\xampp\htdocs\3BHWII\Sprachlerner - Projekt\schreiben.php
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
    <title>SprachenMeister - Schreibübung: <?php echo htmlspecialchars($lernset['name']); ?></title>
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
        
        .exercise-container {
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
        }
        
        .progress-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .progress-text {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .score {
            color: var(--success-color);
            font-weight: 600;
        }
        
        .progress-bar {
            height: 10px;
            border-radius: 5px;
            background-color: #e9ecef;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background-color: var(--success-color);
            border-radius: 5px;
            transition: width 0.3s ease;
        }
        
        .question-card {
            background-color: white;
            border-radius: 20px;
            padding: 3rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            text-align: center;
        }
        
        .question-text {
            font-size: 2.5rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .question-type {
            color: #666;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }
        
        .answer-input {
            width: 100%;
            max-width: 400px;
            margin: 0 auto 2rem;
            padding: 1rem;
            font-size: 1.5rem;
            text-align: center;
            border: 3px solid #e9ecef;
            border-radius: 15px;
            transition: all 0.3s ease;
        }
        
        .answer-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(66, 85, 255, 0.25);
            outline: none;
        }
        
        .answer-input.correct {
            border-color: var(--success-color);
            background-color: #d4edda;
        }
        
        .answer-input.incorrect {
            border-color: var(--danger-color);
            background-color: #f8d7da;
        }
        
        .feedback {
            margin-bottom: 2rem;
            padding: 1rem;
            border-radius: 10px;
            font-weight: 600;
        }
        
        .feedback.correct {
            background-color: #d4edda;
            color: var(--success-color);
            border: 2px solid var(--success-color);
        }
        
        .feedback.incorrect {
            background-color: #f8d7da;
            color: var(--danger-color);
            border: 2px solid var(--danger-color);
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
            margin: 0.5rem;
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
        
        .final-score {
            font-size: 2rem;
            font-weight: bold;
            color: var(--success-color);
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
            .question-text {
                font-size: 2rem;
            }
            
            .answer-input {
                font-size: 1.2rem;
            }
            
            .completion-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .question-card {
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
            <h1><i class="fas fa-keyboard me-3"></i>Schreibübung</h1>
            <div class="subtitle"><?php echo htmlspecialchars($lernset['name']); ?></div>
        </div>
    </div>

    <div class="container">
        <div class="exercise-container">
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
                <!-- Progress Card -->
                <div class="progress-card">
                    <div class="progress-info">
                        <span class="progress-text">
                            Frage <span id="currentQuestion">1</span> von <?php echo count($vocabularies); ?>
                        </span>
                        <span class="score">
                            Richtig: <span id="correctCount">0</span> / <span id="totalCount">0</span>
                        </span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" id="progressFill" style="width: 0%;"></div>
                    </div>
                </div>
                
                <!-- Exercise Interface -->
                <div id="exerciseUI">
                    <div class="question-card">
                        <div class="question-text" id="questionText">
                            <!-- Wird durch JavaScript gefüllt -->
                        </div>
                        <div class="question-type" id="questionType">
                            <!-- Wird durch JavaScript gefüllt -->
                        </div>
                        
                        <input type="text" class="answer-input" id="answerInput" 
                               placeholder="Deine Antwort..." autocomplete="off">
                        
                        <div class="feedback" id="feedback" style="display: none;">
                            <!-- Feedback wird hier angezeigt -->
                        </div>
                        
                        <div class="text-center">
                            <button class="control-btn" id="submitButton">
                                <i class="fas fa-check"></i> Antwort prüfen
                            </button>
                            <button class="control-btn secondary" id="nextButton" style="display: none;">
                                Weiter <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Abschlussbildschirm -->
                <div class="completion-screen" id="completionScreen">
                    <i class="fas fa-trophy"></i>
                    <h2>Übung abgeschlossen!</h2>
                    <div class="final-score" id="finalScore">
                        <!-- Finales Ergebnis -->
                    </div>
                    <p>Gut gemacht! Du hast alle Fragen bearbeitet.</p>
                    <div class="completion-actions">
                        <button class="control-btn" id="restartButton">
                            <i class="fas fa-redo"></i> Nochmal üben
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
        let correctAnswers = 0;
        let totalAnswered = 0;
        let isAnswered = false;
        let currentDirection = 'de_to_foreign'; // 'de_to_foreign' oder 'foreign_to_de'
        
        const questionText = document.getElementById('questionText');
        const questionType = document.getElementById('questionType');
        const answerInput = document.getElementById('answerInput');
        const feedback = document.getElementById('feedback');
        const submitButton = document.getElementById('submitButton');
        const nextButton = document.getElementById('nextButton');
        const currentQuestionSpan = document.getElementById('currentQuestion');
        const correctCountSpan = document.getElementById('correctCount');
        const totalCountSpan = document.getElementById('totalCount');
        const progressFill = document.getElementById('progressFill');
        const exerciseUI = document.getElementById('exerciseUI');
        const completionScreen = document.getElementById('completionScreen');
        const finalScore = document.getElementById('finalScore');
        const restartButton = document.getElementById('restartButton');
        
        // Erste Frage initialisieren
        updateQuestion();
        
        // Event Listeners
        answerInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !isAnswered) {
                checkAnswer();
            } else if (e.key === 'Enter' && isAnswered) {
                nextQuestion();
            }
        });
        
        submitButton.addEventListener('click', checkAnswer);
        nextButton.addEventListener('click', nextQuestion);
        restartButton.addEventListener('click', restartExercise);
        
        function updateQuestion() {
            const vocab = vocabularies[currentIndex];
            
            // Zufällige Richtung wählen
            currentDirection = Math.random() < 0.5 ? 'de_to_foreign' : 'foreign_to_de';
            
            if (currentDirection === 'de_to_foreign') {
                questionText.textContent = vocab.deutsch;
                questionType.textContent = 'Übersetze ins Fremdsprachige:';
            } else {
                questionText.textContent = vocab.fremdsprache;
                questionType.textContent = 'Übersetze ins Deutsche:';
            }
            
            currentQuestionSpan.textContent = currentIndex + 1;
            answerInput.value = '';
            answerInput.focus();
            isAnswered = false;
            
            // UI zurücksetzen
            answerInput.className = 'answer-input';
            feedback.style.display = 'none';
            submitButton.style.display = 'inline-flex';
            nextButton.style.display = 'none';
            
            updateProgress();
        }
        
        function checkAnswer() {
            if (isAnswered) return;
            
            const userAnswer = answerInput.value.trim().toLowerCase();
            const vocab = vocabularies[currentIndex];
            const correctAnswer = currentDirection === 'de_to_foreign' ? 
                vocab.fremdsprache.toLowerCase() : vocab.deutsch.toLowerCase();
            
            isAnswered = true;
            totalAnswered++;
            
            if (userAnswer === correctAnswer) {
                // Richtige Antwort
                correctAnswers++;
                answerInput.className = 'answer-input correct';
                feedback.className = 'feedback correct';
                feedback.innerHTML = '<i class="fas fa-check-circle me-2"></i>Richtig!';
            } else {
                // Falsche Antwort
                answerInput.className = 'answer-input incorrect';
                feedback.className = 'feedback incorrect';
                feedback.innerHTML = `<i class="fas fa-times-circle me-2"></i>Falsch! Richtig wäre: <strong>${currentDirection === 'de_to_foreign' ? vocab.fremdsprache : vocab.deutsch}</strong>`;
            }
            
            feedback.style.display = 'block';
            submitButton.style.display = 'none';
            nextButton.style.display = 'inline-flex';
            
            // Statistiken aktualisieren
            correctCountSpan.textContent = correctAnswers;
            totalCountSpan.textContent = totalAnswered;
            
            nextButton.focus();
        }
        
        function nextQuestion() {
            if (currentIndex < vocabularies.length - 1) {
                currentIndex++;
                updateQuestion();
            } else {
                showCompletionScreen();
            }
        }
        
        function updateProgress() {
            const progress = ((currentIndex + 1) / vocabularies.length) * 100;
            progressFill.style.width = progress + '%';
        }
        
        function showCompletionScreen() {
            const percentage = totalAnswered > 0 ? Math.round((correctAnswers / totalAnswered) * 100) : 0;
            finalScore.textContent = `${correctAnswers} von ${totalAnswered} richtig (${percentage}%)`;
            
            exerciseUI.style.display = 'none';
            completionScreen.style.display = 'block';
        }
        
        function restartExercise() {
            currentIndex = 0;
            correctAnswers = 0;
            totalAnswered = 0;
            isAnswered = false;
            
            // Vokabeln mischen
            for (let i = vocabularies.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [vocabularies[i], vocabularies[j]] = [vocabularies[j], vocabularies[i]];
            }
            
            correctCountSpan.textContent = '0';
            totalCountSpan.textContent = '0';
            
            exerciseUI.style.display = 'block';
            completionScreen.style.display = 'none';
            
            updateQuestion();
        }
    </script>
    <?php endif; ?>
</body>
</html>