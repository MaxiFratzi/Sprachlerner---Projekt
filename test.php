<?php
// filepath: c:\xampp\htdocs\3BHWII\Sprachlerner - Projekt\test.php
// Ähnlich wie schreiben.php, aber mit Multiple-Choice
// Alle Datenbankfelder von 'begriff'/'definition' zu 'deutsch'/'fremdsprache' ändern

// Start Session
session_start();

// Prüfen, ob Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Prüfen, ob Lernset-ID übergeben wurde
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: Voc.php");
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
    // Weiterleitung korrigieren
    header("Location: library.php");
    exit();
}

$lernset = $result->fetch_assoc();

// Vokabeln aus der Datenbank laden (zufällige Reihenfolge)
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
    <title>SprachenMeister - Test: <?php echo htmlspecialchars($lernset['name']); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Fontawesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4255ff;
            --secondary-color: #ff8a00;
            --success-color: #28a745;
            --error-color: #dc3545;
            --warning-color: #ffc107;
            --light-blue: #b1f4ff;
            --pink: #ffb1f4;
            --orange: #ffcf8a;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            margin: 0;
            padding: 0;
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
        
        .test-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 100px);
            padding: 2rem;
        }
        
        .test-wrapper {
            text-align: center;
            max-width: 900px;
            width: 100%;
        }
        
        .test-header {
            color: white;
            margin-bottom: 2rem;
        }
        
        .test-header h1 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .test-header .subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .progress-container {
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            padding: 0.8rem 1.5rem;
            color: white;
            margin-bottom: 2rem;
            display: inline-block;
        }
        
        .progress-bar-custom {
            background-color: rgba(255, 255, 255, 0.3);
            border-radius: 50px;
            height: 8px;
            width: 200px;
            margin: 0.5rem auto;
            overflow: hidden;
        }
        
        .progress-fill {
            background-color: white;
            height: 100%;
            width: 0%;
            transition: width 0.3s ease;
            border-radius: 50px;
        }
        
        .test-card {
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            padding: 3rem;
            margin-bottom: 2rem;
            min-height: 450px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f8f9fa;
        }
        
        .question-number {
            background-color: var(--primary-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: bold;
        }
        
        .question-type {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .question-text {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 2rem;
            line-height: 1.3;
        }
        
        .answer-options {
            display: grid;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .answer-option {
            background-color: #f8f9fa;
            border: 3px solid #e9ecef;
            border-radius: 15px;
            padding: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: left;
            font-size: 1.1rem;
            position: relative;
        }
        
        .answer-option:hover {
            border-color: var(--primary-color);
            background-color: #f0f7ff;
            transform: translateY(-2px);
        }
        
        .answer-option.selected {
            border-color: var(--primary-color);
            background-color: #e3f2fd;
        }
        
        .answer-option.correct {
            border-color: var(--success-color);
            background-color: #d4edda;
        }
        
        .answer-option.incorrect {
            border-color: var(--error-color);
            background-color: #f8d7da;
        }
        
        .answer-option.disabled {
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        .answer-letter {
            display: inline-block;
            width: 30px;
            height: 30px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            font-weight: bold;
            margin-right: 1rem;
        }
        
        .answer-option.correct .answer-letter {
            background-color: var(--success-color);
        }
        
        .answer-option.incorrect .answer-letter {
            background-color: var(--error-color);
        }
        
        .test-controls {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .control-btn {
            background-color: rgba(255, 255, 255, 0.2);
            border: 2px solid white;
            color: white;
            border-radius: 50px;
            padding: 0.8rem 2rem;
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
            background-color: white;
            color: var(--primary-color);
            transform: translateY(-2px);
        }
        
        .control-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .control-btn:disabled:hover {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            transform: none;
        }
        
        .btn-primary-custom {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            padding: 0.8rem 2rem;
            font-weight: 600;
            border-radius: 50px;
        }
        
        .btn-primary-custom:hover {
            background-color: #3346e6;
            border-color: #3346e6;
            color: white;
        }
        
        .btn-primary-custom:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .completion-screen {
            display: none;
            text-align: center;
            color: white;
            padding: 3rem 2rem;
        }
        
        .completion-screen h2 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .completion-screen p {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .completion-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .score-display {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 2rem;
            margin: 2rem 0;
            color: white;
        }
        
        .score-main {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .score-percentage {
            font-size: 4rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .score-grade {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .score-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 1rem;
            text-align: center;
        }
        
        .score-item {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 1.2rem;
        }
        
        .score-number {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .score-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .no-vocab-message {
            text-align: center;
            color: white;
            padding: 3rem 2rem;
        }
        
        .no-vocab-message h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .no-vocab-message p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .back-btn {
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
            margin: 0.5rem;
        }
        
        .back-btn:hover {
            background-color: white;
            color: var(--primary-color);
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .test-card {
                padding: 2rem;
                min-height: 400px;
            }
            
            .question-text {
                font-size: 1.5rem;
            }
            
            .answer-option {
                padding: 1rem;
                font-size: 1rem;
            }
            
            .test-header h1 {
                font-size: 2rem;
            }
            
            .test-controls {
                flex-direction: column;
                align-items: center;
            }
            
            .completion-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .score-percentage {
                font-size: 3rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="main.php">SprachenMeister</a>
            <div class="ms-auto">
                <a href="Voc.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Zurück zur Bibliothek
                </a>
            </div>
        </div>
    </nav>

    <?php if ($noVocabularies): ?>
        <!-- Keine Vokabeln vorhanden -->
        <div class="test-container">
            <div class="test-wrapper">
                <div class="no-vocab-message">
                    <h2><i class="fas fa-exclamation-triangle"></i></h2>
                    <h2>Keine Vokabeln gefunden</h2>
                    <p>In diesem Lernset sind noch keine Vokabeln vorhanden.</p>
                    <?php if ($lernset['type'] === 'custom'): ?>
                        <a href="edit_set.php?id=<?php echo $lernset_id; ?>" class="back-btn">
                            <i class="fas fa-plus"></i> Vokabeln hinzufügen
                        </a>
                    <?php endif; ?>
                    <!-- Zurück-Links korrigieren -->
                    <a href="library.php" class="control-btn secondary">
                        <i class="fas fa-book"></i> Zur Bibliothek
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Test Interface -->
        <div class="test-container">
            <div class="test-wrapper">
                <div class="test-header">
                    <h1><?php echo htmlspecialchars($lernset['name']); ?></h1>
                    <div class="subtitle">Wissenstest</div>
                </div>
                
                <div id="testUI">
                    <div class="progress-container">
                        <div>Frage <span id="currentQuestion">1</span> von <span id="totalQuestions"><?php echo min(count($vocabularies), 10); ?></span></div>
                        <div class="progress-bar-custom">
                            <div class="progress-fill" id="progressFill"></div>
                        </div>
                    </div>
                    
                    <div class="test-card">
                        <div class="question-header">
                            <div class="question-number" id="questionNumber">1</div>
                            <div class="question-type">Multiple Choice</div>
                        </div>
                        
                        <div class="question-text" id="questionText">
                            <!-- Wird durch JavaScript gefüllt -->
                        </div>
                        
                        <div class="answer-options" id="answerOptions">
                            <!-- Wird durch JavaScript gefüllt -->
                        </div>
                        
                        <button class="btn btn-primary-custom btn-lg" id="submitButton" onclick="submitAnswer()" disabled>
                            <i class="fas fa-check me-2"></i>Antwort bestätigen
                        </button>
                        
                        <button class="btn btn-primary-custom btn-lg" id="nextButton" onclick="nextQuestion()" style="display: none;">
                            <i class="fas fa-arrow-right me-2"></i>Nächste Frage
                        </button>
                    </div>
                    
                    <div class="test-controls">
                        <button class="control-btn" id="skipButton" onclick="skipQuestion()">
                            <i class="fas fa-forward"></i> Überspringen
                        </button>
                    </div>
                </div>
                
                <!-- Abschlussbildschirm -->
                <div class="completion-screen" id="completionScreen">
                    <h2><i class="fas fa-graduation-cap"></i></h2>
                    <h2>Test abgeschlossen!</h2>
                    <p>Du hast den Test erfolgreich beendet!</p>
                    
                    <div class="score-display">
                        <div class="score-main">
                            <div class="score-percentage" id="scorePercentage">0%</div>
                            <div class="score-grade" id="scoreGrade">-</div>
                        </div>
                        
                        <div class="score-grid">
                            <div class="score-item">
                                <div class="score-number" id="correctCount">0</div>
                                <div class="score-label">Richtig</div>
                            </div>
                            <div class="score-item">
                                <div class="score-number" id="incorrectCount">0</div>
                                <div class="score-label">Falsch</div>
                            </div>
                            <div class="score-item">
                                <div class="score-number" id="skippedCount">0</div>
                                <div class="score-label">Übersprungen</div>
                            </div>
                            <div class="score-item">
                                <div class="score-number" id="timeSpent">0:00</div>
                                <div class="score-label">Zeit</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="completion-actions">
                        <button class="control-btn" id="restartButton" onclick="restartTest()">
                            <i class="fas fa-redo"></i> Test wiederholen
                        </button>
                        <a href="lernset.php?id=<?php echo $lernset_id; ?>" class="control-btn">
                            <i class="fas fa-arrow-left"></i> Zurück zum Lernset
                        </a>
                        <!-- Navigation korrigieren -->
                        <li class="nav-item">
                            <a class="nav-link" href="library.php">Bibliothek</a>
                        </li>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Bootstrap and JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if (!$noVocabularies): ?>
    <script>
        // Vokabeln als JavaScript Array
        const allVocabularies = <?php echo json_encode($vocabularies); ?>;
        
        // Test-Konfiguration
        const MAX_QUESTIONS = 10;
        const testVocabularies = allVocabularies.slice(0, MAX_QUESTIONS);
        
        let currentIndex = 0;
        let selectedAnswer = null;
        let isAnswered = false;
        let isCompleted = false;
        let startTime = Date.now();
        
        // Statistiken
        let correctAnswers = 0;
        let incorrectAnswers = 0;
        let skippedAnswers = 0;
        
        // DOM-Elemente
        const questionText = document.getElementById('questionText');
        const answerOptions = document.getElementById('answerOptions');
        const submitButton = document.getElementById('submitButton');
        const nextButton = document.getElementById('nextButton');
        const skipButton = document.getElementById('skipButton');
        const currentQuestionSpan = document.getElementById('currentQuestion');
        const questionNumberSpan = document.getElementById('questionNumber');
        const progressFill = document.getElementById('progressFill');
        const testUI = document.getElementById('testUI');
        const completionScreen = document.getElementById('completionScreen');
        
        // Test starten
        loadQuestion();
        
        function loadQuestion() {
            if (currentIndex >= testVocabularies.length) {
                showCompletionScreen();
                return;
            }
            
            const currentVocab = testVocabularies[currentIndex];
            
            // Frage anzeigen
            questionText.textContent = `Was bedeutet "${currentVocab.deutsch}"?`;
            
            // Multiple Choice Optionen generieren
            const options = generateOptions(currentVocab);
            displayOptions(options);
            
            // UI aktualisieren
            currentQuestionSpan.textContent = currentIndex + 1;
            questionNumberSpan.textContent = currentIndex + 1;
            
            // Progress Bar aktualisieren
            const progress = ((currentIndex) / testVocabularies.length) * 100;
            progressFill.style.width = progress + '%';
            
            // Reset
            selectedAnswer = null;
            isAnswered = false;
            submitButton.disabled = true;
            submitButton.style.display = 'inline-block';
            nextButton.style.display = 'none';
        }
        
        function generateOptions(correctVocab) {
            const options = [correctVocab.fremdsprache];
            
            // Weitere Optionen aus anderen Vokabeln hinzufügen
            const otherVocabs = allVocabularies.filter(v => v.id !== correctVocab.id);
            
            while (options.length < 4 && otherVocabs.length > 0) {
                const randomIndex = Math.floor(Math.random() * otherVocabs.length);
                const randomDefinition = otherVocabs[randomIndex].fremdsprache;
                
                if (!options.includes(randomDefinition)) {
                    options.push(randomDefinition);
                }
                otherVocabs.splice(randomIndex, 1);
            }
            
            // Falls nicht genug Vokabeln vorhanden, mit Platzhaltern auffüllen
            while (options.length < 4) {
                options.push(`Option ${options.length + 1}`);
            }
            
            // Optionen mischen
            return shuffleArray(options);
        }
        
        function displayOptions(options) {
            answerOptions.innerHTML = '';
            
            options.forEach((option, index) => {
                const optionElement = document.createElement('div');
                optionElement.className = 'answer-option';
                optionElement.innerHTML = `
                    <span class="answer-letter">${String.fromCharCode(65 + index)}</span>
                    ${option}
                `;
                
                optionElement.addEventListener('click', () => selectOption(index, option));
                answerOptions.appendChild(optionElement);
            });
        }
        
        function selectOption(index, answer) {
            if (isAnswered) return;
            
            // Alle Optionen deselektieren
            document.querySelectorAll('.answer-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            
            // Ausgewählte Option markieren
            document.querySelectorAll('.answer-option')[index].classList.add('selected');
            
            selectedAnswer = answer;
            submitButton.disabled = false;
        }
        
        function submitAnswer() {
            if (isAnswered || selectedAnswer === null) return;
            
            isAnswered = true;
            const correctAnswer = testVocabularies[currentIndex].fremdsprache;
            const options = document.querySelectorAll('.answer-option');
            
            // Alle Optionen deaktivieren
            options.forEach(option => {
                option.classList.add('disabled');
                
                const optionText = option.textContent.trim().substring(1).trim();
                if (optionText === correctAnswer) {
                    option.classList.add('correct');
                } else if (optionText === selectedAnswer && selectedAnswer !== correctAnswer) {
                    option.classList.add('incorrect');
                }
            });
            
            // Statistiken aktualisieren
            if (selectedAnswer === correctAnswer) {
                correctAnswers++;
            } else {
                incorrectAnswers++;
            }
            
            // UI aktualisieren
            submitButton.style.display = 'none';
            nextButton.style.display = 'inline-block';
            skipButton.disabled = true;
        }
        
        function nextQuestion() {
            currentIndex++;
            skipButton.disabled = false;
            loadQuestion();
        }
        
        function skipQuestion() {
            if (!isAnswered) {
                skippedAnswers++;
            }
            nextQuestion();
        }
        
        function showCompletionScreen() {
            isCompleted = true;
            testUI.style.display = 'none';
            completionScreen.style.display = 'block';
            
            // Zeitberechnung
            const endTime = Date.now();
            const totalTime = Math.floor((endTime - startTime) / 1000);
            const minutes = Math.floor(totalTime / 60);
            const seconds = totalTime % 60;
            
            // Ergebnisse berechnen
            const totalAnswered = correctAnswers + incorrectAnswers;
            const percentage = totalAnswered > 0 ? Math.round((correctAnswers / totalAnswered) * 100) : 0;
            const grade = getGrade(percentage);
            
            // Ergebnisse anzeigen
            document.getElementById('scorePercentage').textContent = percentage + '%';
            document.getElementById('scoreGrade').textContent = grade;
            document.getElementById('correctCount').textContent = correctAnswers;
            document.getElementById('incorrectCount').textContent = incorrectAnswers;
            document.getElementById('skippedCount').textContent = skippedAnswers;
            document.getElementById('timeSpent').textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        }
        
        function getGrade(percentage) {
            if (percentage >= 90) return 'Ausgezeichnet!';
            if (percentage >= 80) return 'Sehr gut!';
            if (percentage >= 70) return 'Gut!';
            if (percentage >= 60) return 'Befriedigend';
            if (percentage >= 50) return 'Ausreichend';
            return 'Nicht bestanden';
        }
        
        function restartTest() {
            currentIndex = 0;
            selectedAnswer = null;
            isAnswered = false;
            isCompleted = false;
            startTime = Date.now();
            correctAnswers = 0;
            incorrectAnswers = 0;
            skippedAnswers = 0;
            
            // Vokabeln neu mischen
            shuffleArray(testVocabularies);
            
            testUI.style.display = 'block';
            completionScreen.style.display = 'none';
            skipButton.disabled = false;
            
            loadQuestion();
        }
        
        // Array mischen (Fisher-Yates shuffle)
        function shuffleArray(array) {
            for (let i = array.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [array[i], array[j]] = [array[j], array[i]];
            }
            return array;
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (isCompleted) return;
            
            if (e.code === 'KeyA' || e.code === 'Digit1') {
                e.preventDefault();
                selectOptionByIndex(0);
            } else if (e.code === 'KeyB' || e.code === 'Digit2') {
                e.preventDefault();
                selectOptionByIndex(1);
            } else if (e.code === 'KeyC' || e.code === 'Digit3') {
                e.preventDefault();
                selectOptionByIndex(2);
            } else if (e.code === 'KeyD' || e.code === 'Digit4') {
                e.preventDefault();
                selectOptionByIndex(3);
            } else if (e.code === 'Enter') {
                e.preventDefault();
                if (!isAnswered && selectedAnswer !== null) {
                    submitAnswer();
                } else if (isAnswered) {
                    nextQuestion();
                }
            } else if (e.code === 'Space') {
                e.preventDefault();
                if (!isAnswered) {
                    skipQuestion();
                }
            }
        });
        
        function selectOptionByIndex(index) {
            if (isAnswered) return;
            
            const options = document.querySelectorAll('.answer-option');
            if (options[index]) {
                options[index].click();
            }
        }
        
        // Vokabeln initial mischen
        shuffleArray(testVocabularies);
    </script>
    <?php endif; ?>
</body>
</html>