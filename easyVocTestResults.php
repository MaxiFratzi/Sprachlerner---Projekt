<?php
// Start Session
session_start();

// Prüfen, ob Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Prüfen, ob Testantworten und Testvokabeln vorhanden sind
if (!isset($_POST['answers']) || !isset($_SESSION['test_vocabulary'])) {
    header("Location: easyVocTest.php");
    exit();
}

$answers = $_POST['answers'];
$testVocabulary = $_SESSION['test_vocabulary'];

// Benutzername aus der Session holen
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Benutzer';

// Ergebnisse auswerten
$results = array();
$correctCount = 0;
$totalCount = count($testVocabulary);

foreach ($testVocabulary as $vocab) {
    $id = $vocab['id'];
    $isCorrect = false;
    
    if (isset($answers[$id])) {
        // Normalisierung der Antworten für den Vergleich (Kleinbuchstaben, Trimmen)
        $userAnswer = strtolower(trim($answers[$id]));
        $correctAnswer = strtolower(trim($vocab['deutsch']));
        
        // Überprüfen, ob die Antwort richtig ist
        $isCorrect = ($userAnswer === $correctAnswer);
        
        if ($isCorrect) {
            $correctCount++;
        }
    }
    
    $results[] = array(
        'id' => $id,
        'englisch' => $vocab['englisch'],
        'deutsch' => $vocab['deutsch'],
        'userAnswer' => isset($answers[$id]) ? $answers[$id] : '',
        'isCorrect' => $isCorrect
    );
}

// Berechnung der Erfolgsquote
$successRate = ($totalCount > 0) ? round(($correctCount / $totalCount) * 100) : 0;

// Vokabeln als gelernt markieren
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

// Heutiges Datum
$today = date('Y-m-d');

// Prüfen, ob bereits ein Eintrag für heute existiert
$stmt = $conn->prepare("SELECT count FROM vocabulary_tracking WHERE user_id = ? AND learn_date = ?");
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Vorhandenen Eintrag aktualisieren
    $row = $result->fetch_assoc();
    $new_count = $row['count'] + $correctCount;
    
    $update_stmt = $conn->prepare("UPDATE vocabulary_tracking SET count = ? WHERE user_id = ? AND learn_date = ?");
    $update_stmt->bind_param("iis", $new_count, $user_id, $today);
    $update_stmt->execute();
    $update_stmt->close();
} else {
    // Neuen Eintrag erstellen
    $insert_stmt = $conn->prepare("INSERT INTO vocabulary_tracking (user_id, learn_date, count) VALUES (?, ?, ?)");
    $insert_stmt->bind_param("isi", $user_id, $today, $correctCount);
    $insert_stmt->execute();
    $insert_stmt->close();
}

$stmt->close();
$conn->close();

// Testvokabeln aus der Session entfernen
unset($_SESSION['test_vocabulary']);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SprachenMeister - Testergebnisse</title>
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
            --danger-color: #dc3545;
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
        }
        
        .results-summary {
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding: 2rem;
            margin-bottom: 2rem;
            background-color: #f8f9fa;
            border-radius: 10px;
        }
        
        .score-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background-color: #f0f4ff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .score-percent {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .score-label {
            font-size: 1rem;
            color: #666;
        }
        
        .result-stats {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.1rem;
        }
        
        .stat-item i.fa-check {
            color: var(--success-color);
        }
        
        .stat-item i.fa-times {
            color: var(--danger-color);
        }
        
        .result-list-header {
            padding: 1rem 0;
            border-bottom: 1px solid #e0e0e0;
            margin-bottom: 1.5rem;
        }
        
        .vocab-item {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .vocab-item.correct {
            background-color: rgba(40, 167, 69, 0.1);
            border-left: 4px solid var(--success-color);
        }
        
        .vocab-item.incorrect {
            background-color: rgba(220, 53, 69, 0.1);
            border-left: 4px solid var(--danger-color);
        }
        
        .vocab-english {
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .vocab-german {
            color: #666;
        }
        
        .user-answer {
            margin-top: 0.5rem;
            font-style: italic;
        }
        
        .user-answer.correct {
            color: var(--success-color);
        }
        
        .user-answer.incorrect {
            color: var(--danger-color);
            text-decoration: line-through;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
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
        <h1>Dein Testergebnis</h1>
        <p class="mt-2">Hier siehst du, wie gut du abgeschnitten hast</p>
    </div>

    <!-- Library Content -->
    <div class="library-content">
        <div class="results-summary">
            <div class="score-circle">
                <div class="score-percent"><?php echo $successRate; ?>%</div>
                <div class="score-label">Erfolgsquote</div>
            </div>
            <div class="result-stats">
                <div class="stat-item">
                    <i class="fas fa-check fa-lg"></i>
                    <span><strong><?php echo $correctCount; ?></strong> richtige Antworten</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-times fa-lg"></i>
                    <span><strong><?php echo $totalCount - $correctCount; ?></strong> falsche Antworten</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-list fa-lg"></i>
                    <span><strong><?php echo $totalCount; ?></strong> Vokabeln insgesamt</span>
                </div>
            </div>
        </div>
        
        <div class="result-list-header">
            <h3>Deine Antworten im Detail</h3>
        </div>
        
        <div class="result-list">
            <?php foreach ($results as $result): ?>
                <div class="vocab-item <?php echo $result['isCorrect'] ? 'correct' : 'incorrect'; ?>">
                    <div class="vocab-english"><?php echo htmlspecialchars($result['englisch']); ?></div>
                    <div class="vocab-german">Korrekte Übersetzung: <?php echo htmlspecialchars($result['deutsch']); ?></div>
                    <div class="user-answer <?php echo $result['isCorrect'] ? 'correct' : 'incorrect'; ?>">
                        Deine Antwort: <?php echo htmlspecialchars($result['userAnswer']); ?>
                        <?php if ($result['isCorrect']): ?>
                            <i class="fas fa-check ms-2"></i>
                        <?php else: ?>
                            <i class="fas fa-times ms-2"></i>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-4">
            <a href="easyVoc.php" class="btn btn-outline-secondary me-2">Zurück zur Übersicht</a>
            <a href="easyVocTest.php" class="btn btn-primary">Neuen Test starten</a>
        </div>
    </div>

    <!-- Bootstrap and JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>