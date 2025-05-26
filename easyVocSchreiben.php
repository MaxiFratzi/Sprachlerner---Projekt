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
$dbPassword = "root"; // Standardmäßig "root" bei XAMPP, anpassen falls nötig
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

// Überprüfen, ob ein Formular gesendet wurde
$message = "";
$isCorrect = false;
$currentWord = 0;
$totalCorrect = 0;
$maxWords = 48; // Gesamtanzahl der zu lernenden Wörter

// Session-Variablen zur Fortschrittsverfolgung initialisieren, falls nicht vorhanden
if (!isset($_SESSION['schreiben_progress'])) {
    $_SESSION['schreiben_progress'] = [
        'currentWord' => 0,
        'correctWords' => [],
        'incorrectWords' => [],
        'totalCorrect' => 0,
        'completed' => false
    ];
}

// Antwort überprüfen, wenn das Formular abgeschickt wurde
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['answer'])) {
    $answer = trim($_POST['answer']);
    $wordId = isset($_POST['word_id']) ? (int)$_POST['word_id'] : 0;
    
    // Das aktuelle Wort finden
    $currentWordData = null;
    foreach ($vocabularies as $vocab) {
        if ($vocab['id'] == $wordId) {
            $currentWordData = $vocab;
            break;
        }
    }
    
    if ($currentWordData) {
        // Überprüfen, ob die Antwort korrekt ist (Groß-/Kleinschreibung ignorieren)
        if (strtolower($answer) === strtolower($currentWordData['deutsch'])) {
            $isCorrect = true;
            $message = "Richtig!";
            
            // Aktuelles Wort als korrekt markieren
            if (!in_array($wordId, $_SESSION['schreiben_progress']['correctWords'])) {
                $_SESSION['schreiben_progress']['correctWords'][] = $wordId;
                $_SESSION['schreiben_progress']['totalCorrect']++;
            }
            
            // Falls das Wort vorher falsch war, aus der Liste der falschen Wörter entfernen
            $key = array_search($wordId, $_SESSION['schreiben_progress']['incorrectWords']);
            if ($key !== false) {
                array_splice($_SESSION['schreiben_progress']['incorrectWords'], $key, 1);
            }
            
            // Zum nächsten Wort oder zur Wiederholung eines falschen Wortes gehen
            if (count($_SESSION['schreiben_progress']['incorrectWords']) > 0) {
                // Wiederhole ein falsches Wort
                $randomIncorrectIndex = array_rand($_SESSION['schreiben_progress']['incorrectWords']);
                $_SESSION['schreiben_progress']['currentWord'] = $_SESSION['schreiben_progress']['incorrectWords'][$randomIncorrectIndex];
            } else {
                // Zum nächsten Wort
                $_SESSION['schreiben_progress']['currentWord'] = ($_SESSION['schreiben_progress']['currentWord'] + 1) % count($vocabularies);
            }
        } else {
            $isCorrect = false;
            $message = "Falsch! Die richtige Antwort wäre: " . $currentWordData['deutsch'];
            
            // Wort als falsch markieren, falls es noch nicht in der Liste ist
            if (!in_array($wordId, $_SESSION['schreiben_progress']['incorrectWords'])) {
                $_SESSION['schreiben_progress']['incorrectWords'][] = $wordId;
            }
        }
    }
    
    // Überprüfen, ob alle Wörter gelernt wurden
    if (count($_SESSION['schreiben_progress']['correctWords']) >= min($maxWords, count($vocabularies)) && 
        count($_SESSION['schreiben_progress']['incorrectWords']) == 0) {
        $_SESSION['schreiben_progress']['completed'] = true;
    }
}

// Aktuelles Wort bestimmen
$currentWord = $_SESSION['schreiben_progress']['currentWord'];
$totalCorrect = $_SESSION['schreiben_progress']['totalCorrect'];
$completed = $_SESSION['schreiben_progress']['completed'];

// Das aktuelle Wort aus der Vokabelliste holen
$currentWordData = isset($vocabularies[$currentWord]) ? $vocabularies[$currentWord] : null;
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SprachenMeister - Vokabeln Schreiben</title>
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
            --success-color: #28a745;
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

        /* Schreiben UI Styles */
        .writing-container {
            max-width: 500px;
            margin: 0 auto;
            text-align: center;
        }

        .english-word {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 2rem;
        }

        .answer-input {
            width: 100%;
            padding: 12px;
            font-size: 1.2rem;
            border: 2px solid #ddd;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .answer-input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(66, 85, 255, 0.25);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 10px 25px;
            font-weight: 600;
            border-radius: 50px;
        }

        .progress-container {
            margin-top: 2rem;
        }

        .message-container {
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 1rem;
        }

        .message {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
        }

        .message.correct {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(40, 167, 69, 0.2);
        }

        .message.incorrect {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }
        
        /* Completion Screen Styles */
        .completion-screen {
            text-align: center;
            padding: 2rem;
        }
        
        .completion-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
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
        <h1>Vokabeln Schreiben</h1>
        <p class="mt-2">Übersetze die englischen Wörter ins Deutsche</p>
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
        <?php elseif ($completed): ?>
            <!-- Completion Screen -->
            <div id="completionScreen" class="completion-screen">
                <div class="d-flex justify-content-center mb-4">
                    <div style="width: 80px; height: 80px; background-color: #28a745; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-check fa-3x text-white"></i>
                    </div>
                </div>
                <h2>Lernset abgeschlossen!</h2>
                <p class="text-muted">Du hast alle Vokabeln in diesem Set durchgearbeitet.</p>
                <div class="d-flex justify-content-center gap-3 mt-4">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <input type="hidden" name="reset_progress" value="1">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-redo me-2"></i>Lernset erneut lernen
                        </button>
                    </form>
                    <a href="easyVoc.php" class="btn btn-primary">
                        <i class="fas fa-th-large me-2"></i>Zurück zur Übersicht
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Writing UI -->
            <div id="writingUI" class="writing-container">
                <div class="english-word">
                    <?php echo htmlspecialchars($currentWordData['englisch']); ?>
                </div>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" autocomplete="off">
                    <input type="hidden" name="word_id" value="<?php echo $currentWordData['id']; ?>">
                    <input type="text" name="answer" class="answer-input" placeholder="Tippe die deutsche Übersetzung" autofocus>
                    <button type="submit" class="btn btn-primary w-100">Überprüfen</button>
                </form>
                
                <div class="message-container">
                    <?php if (!empty($message)): ?>
                        <div class="message <?php echo $isCorrect ? 'correct' : 'incorrect'; ?>">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="progress-container">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Fortschritt</span>
                        <span><?php echo $totalCorrect; ?> von <?php echo min($maxWords, count($vocabularies)); ?> Wörtern</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-success" 
                             role="progressbar" 
                             style="width: <?php echo ($totalCorrect / min($maxWords, count($vocabularies))) * 100; ?>%" 
                             aria-valuenow="<?php echo $totalCorrect; ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="<?php echo min($maxWords, count($vocabularies)); ?>">
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap and JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Automatisch Focus auf das Eingabefeld setzen
        document.addEventListener('DOMContentLoaded', function() {
            const inputField = document.querySelector('.answer-input');
            if (inputField) {
                inputField.focus();
            }
            
            // Check if answer was correct from PHP and track it
            <?php if ($isCorrect): ?>
                trackCorrectAnswer();
            <?php endif; ?>
        });

        // Track vocabulary when answered correctly
        function trackCorrectAnswer() {
            // Create form data for Ajax request
            const formData = new FormData();
            formData.append('learned', '1');
            
            // Send AJAX request to tracking script
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
    </script>
</body>
</html>
<?php
// Das Fortschritts-Reset verarbeiten (wenn der "Lernset erneut lernen" Button geklickt wurde)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_progress'])) {
    // Zurücksetzen des Fortschritts
    $_SESSION['schreiben_progress'] = [
        'currentWord' => 0,
        'correctWords' => [],
        'incorrectWords' => [],
        'totalCorrect' => 0,
        'completed' => false
    ];
    
    // Zur selben Seite umleiten, um POST-Daten zu entfernen
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>