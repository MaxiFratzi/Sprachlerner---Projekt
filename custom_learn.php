<?php
// Start Session
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "Vokabeln";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the learning set table name from URL
$set_table = isset($_GET['set']) ? $_GET['set'] : '';
$vocab_data = [];
$set_name = '';

if ($set_table) {
    // Sanitize table name
    $set_table = preg_replace('/[^a-zA-Z0-9_]/', '', $set_table);
    
    // Get set name from learning_sets table
    $name_result = $conn->query("SELECT set_name FROM learning_sets WHERE table_name = '$set_table'");
    if ($name_result && $name_result->num_rows > 0) {
        $name_row = $name_result->fetch_assoc();
        $set_name = $name_row['set_name'];
    }
    
    // Get vocabulary data from custom table
    $result = $conn->query("SELECT * FROM `$set_table` ORDER BY RAND()");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $vocab_data[] = $row;
        }
    }
}

if (empty($vocab_data)) {
    header("Location: library.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SprachenMeister - <?php echo htmlspecialchars($set_name); ?> Lernen</title>
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
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .learning-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .flashcard-container {
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 3rem;
            text-align: center;
            max-width: 600px;
            width: 100%;
            min-height: 400px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }
        
        .progress-bar-container {
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
        }
        
        .close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #6c757d;
            cursor: pointer;
        }
        
        .flashcard-content {
            margin: 2rem 0;
        }
        
        .flashcard-word {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .flashcard-definition {
            font-size: 1.5rem;
            color: #333;
            display: none;
        }
        
        .flashcard-controls {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn-flip {
            background-color: var(--secondary-color);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .btn-flip:hover {
            background-color: #ff7700;
            color: white;
        }
        
        .btn-next {
            background-color: var(--primary-color);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            display: none;
        }
        
        .btn-next:hover {
            background-color: #3545ff;
            color: white;
        }
        
        .card-counter {
            position: absolute;
            bottom: 20px;
            right: 20px;
            color: #6c757d;
            font-weight: 600;
        }
        
        .completion-screen {
            display: none;
            text-align: center;
        }
        
        .completion-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="learning-container">
        <div class="flashcard-container">
            <!-- Progress Bar -->
            <div class="progress-bar-container">
                <div class="progress">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 0%"></div>
                </div>
            </div>
            
            <!-- Close Button -->
            <button class="close-btn" onclick="window.location.href='library.php'">
                <i class="fas fa-times"></i>
            </button>
            
            <!-- Flashcard Content -->
            <div class="flashcard-content" id="flashcardContent">
                <div class="flashcard-word" id="flashcardWord"></div>
                <div class="flashcard-definition" id="flashcardDefinition"></div>
            </div>
            
            <!-- Controls -->
            <div class="flashcard-controls">
                <button class="btn btn-flip" id="flipBtn" onclick="flipCard()">
                    <i class="fas fa-sync-alt"></i> Umdrehen
                </button>
                <button class="btn btn-next" id="nextBtn" onclick="nextCard()">
                    <i class="fas fa-arrow-right"></i> Weiter
                </button>
            </div>
            
            <!-- Card Counter -->
            <div class="card-counter" id="cardCounter"></div>
            
            <!-- Completion Screen -->
            <div class="completion-screen" id="completionScreen">
                <div class="completion-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <h2>Glückwunsch!</h2>
                <p>Sie haben alle Karten von "<?php echo htmlspecialchars($set_name); ?>" durchgelernt!</p>
                <button class="btn btn-primary" onclick="restartLearning()">
                    <i class="fas fa-redo"></i> Nochmal lernen
                </button>
                <a href="library.php" class="btn btn-secondary ms-2">
                    <i class="fas fa-home"></i> Zur Bibliothek
                </a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Vocabulary data from PHP
        const vocabData = <?php echo json_encode($vocab_data); ?>;
        let currentIndex = 0;
        let isFlipped = false;
        
        function updateCard() {
            if (currentIndex >= vocabData.length) {
                showCompletion();
                return;
            }
            
            const current = vocabData[currentIndex];
            document.getElementById('flashcardWord').textContent = current.begriff;
            document.getElementById('flashcardDefinition').textContent = current.definition;
            document.getElementById('cardCounter').textContent = `${currentIndex + 1} / ${vocabData.length}`;
            
            // Update progress bar
            const progress = ((currentIndex + 1) / vocabData.length) * 100;
            document.querySelector('.progress-bar').style.width = progress + '%';
            
            // Reset card state
            isFlipped = false;
            document.getElementById('flashcardDefinition').style.display = 'none';
            document.getElementById('flipBtn').style.display = 'inline-block';
            document.getElementById('nextBtn').style.display = 'none';
        }
        
        function flipCard() {
            if (!isFlipped) {
                document.getElementById('flashcardDefinition').style.display = 'block';
                document.getElementById('flipBtn').style.display = 'none';
                document.getElementById('nextBtn').style.display = 'inline-block';
                isFlipped = true;
            }
        }
        
        function nextCard() {
            currentIndex++;
            updateCard();
        }
        
        function showCompletion() {
            document.getElementById('flashcardContent').style.display = 'none';
            document.querySelector('.flashcard-controls').style.display = 'none';
            document.querySelector('.card-counter').style.display = 'none';
            document.getElementById('completionScreen').style.display = 'block';
        }
        
        function restartLearning() {
            currentIndex = 0;
            document.getElementById('flashcardContent').style.display = 'block';
            document.querySelector('.flashcard-controls').style.display = 'flex';
            document.querySelector('.card-counter').style.display = 'block';
            document.getElementById('completionScreen').style.display = 'none';
            
            // Shuffle the array for variety
            for (let i = vocabData.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [vocabData[i], vocabData[j]] = [vocabData[j], vocabData[i]];
            }
            
            updateCard();
        }
        
        // Initialize the first card
        updateCard();
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.code === 'Space') {
                e.preventDefault();
                if (!isFlipped) {
                    flipCard();
                } else {
                    nextCard();
                }
            } else if (e.code === 'Escape') {
                window.location.href = 'library.php';
            }
        });
    </script>
</body>
</html>