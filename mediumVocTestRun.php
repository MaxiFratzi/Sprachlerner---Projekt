<?php
// Start Session
session_start();

// Prüfen, ob Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Prüfen, ob die Testparameter übermittelt wurden
if (!isset($_POST['vocabCount']) || !is_numeric($_POST['vocabCount']) || $_POST['vocabCount'] < 5) {
    header("Location: mediumVocTest.php");
    exit();
}

$vocabCount = intval($_POST['vocabCount']);

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

// Zufällige Vokabeln für den Test abrufen
$sql = "SELECT id, englisch, deutsch FROM woerterm ORDER BY RAND() LIMIT ?"; // Medium vocabulary table
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $vocabCount);
$stmt->execute();
$result = $stmt->get_result();

$testVocabulary = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $testVocabulary[] = $row;
    }
}

$conn->close();

// Falls nicht genügend Vokabeln gefunden wurden
if (count($testVocabulary) < $vocabCount) {
    header("Location: mediumVocTest.php");
    exit();
}

// Array mit Testvokabeln in der Session speichern
$_SESSION['test_vocabulary'] = $testVocabulary;
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SprachenMeister - Medium Vokabeltest</title>
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
        }
        
        .progress {
            height: 10px;
        }
        
        .vocab-card {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .vocab-english {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .answer-input {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 1.1rem;
            transition: all 0.3s;
        }
        
        .answer-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(66, 85, 255, 0.25);
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
        <h1>Medium Vokabeltest</h1>
        <p class="mt-2">Übersetze die englischen Wörter ins Deutsche</p>
    </div>

    <!-- Library Content -->
    <div class="library-content">
        <div class="mb-4">
            <div class="d-flex justify-content-between mb-2">
                <span>Fortschritt</span>
                <span><span id="currentQuestion">1</span> von <?php echo count($testVocabulary); ?></span>
            </div>
            <div class="progress">
                <div class="progress-bar bg-primary" id="progressBar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>
        
        <form id="testForm" action="mediumVocTestResults.php" method="POST">
            <?php foreach ($testVocabulary as $index => $vocab): ?>
                <div class="vocab-card" id="question-<?php echo $index; ?>" style="<?php echo $index > 0 ? 'display: none;' : ''; ?>">
                    <div class="vocab-english"><?php echo htmlspecialchars($vocab['englisch']); ?></div>
                    <div class="mb-3">
                        <label for="answer-<?php echo $index; ?>" class="form-label">Deutsche Übersetzung:</label>
                        <input type="text" class="form-control answer-input" id="answer-<?php echo $index; ?>" name="answers[<?php echo $vocab['id']; ?>]" autocomplete="off" required>
                    </div>
                    <div class="d-flex justify-content-between mt-4">
                        <?php if ($index > 0): ?>
                            <button type="button" class="btn btn-outline-secondary prev-button" data-index="<?php echo $index; ?>">
                                <i class="fas fa-chevron-left me-2"></i>Zurück
                            </button>
                        <?php else: ?>
                            <div></div>
                        <?php endif; ?>
                        
                        <?php if ($index < count($testVocabulary) - 1): ?>
                            <button type="button" class="btn btn-primary next-button" data-index="<?php echo $index; ?>">
                                Weiter<i class="fas fa-chevron-right ms-2"></i>
                            </button>
                        <?php else: ?>
                            <button type="submit" class="btn btn-success">
                                Test abschließen<i class="fas fa-check ms-2"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </form>
    </div>

    <!-- Bootstrap and JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const totalQuestions = <?php echo count($testVocabulary); ?>;
            let currentIndex = 0;
            
            // Automatisch Focus auf das erste Eingabefeld setzen
            document.querySelector('#answer-0').focus();
            
            // Event-Listener für "Weiter" Buttons
            document.querySelectorAll('.next-button').forEach(button => {
                button.addEventListener('click', function() {
                    const index = parseInt(this.getAttribute('data-index'));
                    const input = document.querySelector(`#answer-${index}`);
                    
                    // Validierung: Eingabefeld darf nicht leer sein
                    if (input.value.trim() === '') {
                        input.classList.add('is-invalid');
                        return;
                    }
                    
                    // Aktuelle Frage ausblenden
                    document.querySelector(`#question-${index}`).style.display = 'none';
                    
                    // Nächste Frage einblenden
                    document.querySelector(`#question-${index + 1}`).style.display = 'block';
                    document.querySelector(`#answer-${index + 1}`).focus();
                    
                    // Fortschrittsanzeige aktualisieren
                    currentIndex = index + 1;
                    updateProgress();
                });
            });
            
            // Event-Listener für "Zurück" Buttons
            document.querySelectorAll('.prev-button').forEach(button => {
                button.addEventListener('click', function() {
                    const index = parseInt(this.getAttribute('data-index'));
                    
                    // Aktuelle Frage ausblenden
                    document.querySelector(`#question-${index}`).style.display = 'none';
                    
                    // Vorherige Frage einblenden
                    document.querySelector(`#question-${index - 1}`).style.display = 'block';
                    document.querySelector(`#answer-${index - 1}`).focus();
                    
                    // Fortschrittsanzeige aktualisieren
                    currentIndex = index - 1;
                    updateProgress();
                });
            });
            
            // Fortschrittsanzeige aktualisieren
            function updateProgress() {
                const progressPercent = ((currentIndex + 1) / totalQuestions) * 100;
                document.getElementById('progressBar').style.width = `${progressPercent}%`;
                document.getElementById('progressBar').setAttribute('aria-valuenow', progressPercent);
                document.getElementById('currentQuestion').textContent = currentIndex + 1;
            }
            
            // Validierung bei Formular-Absendung
            document.getElementById('testForm').addEventListener('submit', function(e) {
                const inputs = document.querySelectorAll('.answer-input');
                let isValid = true;
                
                inputs.forEach(input => {
                    if (input.value.trim() === '') {
                        input.classList.add('is-invalid');
                        isValid = false;
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Bitte fülle alle Felder aus.');
                }
            });
            
            // Eingabefelder: Bei Eingabe den invalid-Status entfernen
            document.querySelectorAll('.answer-input').forEach(input => {
                input.addEventListener('input', function() {
                    this.classList.remove('is-invalid');
                });
            });
        });
    </script>
</body>
</html>