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

// Handle form submission
if ($_POST && isset($_POST['set_title']) && isset($_POST['words']) && isset($_POST['translations'])) {
    $set_title = trim($_POST['set_title']);
    $words = $_POST['words'];
    $translations = $_POST['translations'];
    
    if (!empty($set_title) && !empty(array_filter($words)) && !empty(array_filter($translations))) {
        // Sanitize table name (remove special characters and spaces)
        $table_name = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($set_title));
        $table_name = 'lernset_' . $table_name;
        
        // Create new table for the learning set
        $create_table_sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            begriff VARCHAR(255) NOT NULL,
            definition VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($create_table_sql) === TRUE) {
            // Insert word pairs into the new table
            $insert_sql = "INSERT INTO `$table_name` (begriff, definition) VALUES (?, ?)";
            $stmt = $conn->prepare($insert_sql);
            
            for ($i = 0; $i < count($words); $i++) {
                if (!empty(trim($words[$i])) && !empty(trim($translations[$i]))) {
                    $stmt->bind_param("ss", trim($words[$i]), trim($translations[$i]));
                    $stmt->execute();
                }
            }
            
            // Also add entry to a master table to track all learning sets
            $master_table_sql = "CREATE TABLE IF NOT EXISTS learning_sets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                set_name VARCHAR(255) NOT NULL,
                table_name VARCHAR(255) NOT NULL,
                word_count INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $conn->query($master_table_sql);
            
            // Insert into master table
            $word_count = count(array_filter($words, function($word) { return !empty(trim($word)); }));
            $insert_master_sql = "INSERT INTO learning_sets (set_name, table_name, word_count) VALUES (?, ?, ?)";
            $stmt_master = $conn->prepare($insert_master_sql);
            $stmt_master->bind_param("ssi", $set_title, $table_name, $word_count);
            $stmt_master->execute();
            
            // Redirect to library with success message
            header("Location: library.php?success=1");
            exit();
        } else {
            $error_message = "Fehler beim Erstellen des Lernsets: " . $conn->error;
        }
    } else {
        $error_message = "Bitte füllen Sie alle Felder aus.";
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SprachenMeister - Neues Lernset erstellen</title>
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
        
        .create-header {
            background-color: var(--primary-color);
            color: white;
            padding: 2rem 0;
            text-align: center;
        }
        
        .create-content {
            max-width: 800px;
            margin: 2rem auto;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        
        .word-pair {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            align-items: center;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 10px;
            position: relative;
        }
        
        .word-pair input {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 0.75rem;
            font-size: 1rem;
        }
        
        .word-pair input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(66, 85, 255, 0.25);
        }
        
        .delete-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 0.8rem;
        }
        
        .delete-btn:hover {
            background: #c82333;
        }
        
        .add-pair-btn {
            background-color: var(--secondary-color);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            margin: 1rem 0;
            cursor: pointer;
        }
        
        .add-pair-btn:hover {
            background-color: #ff7700;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 10px 25px;
            font-weight: 600;
            border-radius: 50px;
        }
        
        .title-input {
            width: 100%;
            padding: 1rem;
            font-size: 1.2rem;
            font-weight: 600;
            border: 2px solid #ddd;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        
        .title-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(66, 85, 255, 0.25);
        }
        
        .create-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
        }
        
        .word-labels {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 0 1rem;
        }
        
        .word-labels span {
            flex: 1;
            font-weight: 600;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container">
            <a class="navbar-brand" href="index.php">SprachenMeister</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <form class="d-flex mx-auto mb-2 mb-lg-0">
                    <input class="form-control me-2" type="search" placeholder="Nach Übungstests suchen" style="width: 250px; border-radius: 20px;">
                </form>
            </div>
        </div>
    </nav>

    <!-- Create Header -->
    <div class="create-header">
        <h1>Neues Lernset erstellen</h1>
    </div>

    <!-- Create Content -->
    <div class="create-content">
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="createSetForm">
            <!-- Title Input -->
            <input type="text" name="set_title" class="title-input" placeholder="Titel des Lernsets eingeben" required>
            
            <!-- Word Labels -->
            <div class="word-labels">
                <span>BEGRIFF</span>
                <span>DEFINITION</span>
            </div>
            
            <!-- Word Pairs Container -->
            <div id="wordPairsContainer">
                <!-- Initial word pairs -->
                <div class="word-pair">
                    <input type="text" name="words[]" placeholder="Begriff eingeben" required>
                    <input type="text" name="translations[]" placeholder="Definition eingeben" required>
                    <button type="button" class="delete-btn" onclick="deleteWordPair(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                
                <div class="word-pair">
                    <input type="text" name="words[]" placeholder="Begriff eingeben">
                    <input type="text" name="translations[]" placeholder="Definition eingeben">
                    <button type="button" class="delete-btn" onclick="deleteWordPair(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            
            <!-- Add New Pair Button -->
            <button type="button" class="add-pair-btn" onclick="addWordPair()">
                <i class="fas fa-plus"></i> Neues Wörterpaar
            </button>
            
            <!-- Actions -->
            <div class="create-actions">
                <a href="library.php" class="btn btn-secondary">Abbrechen</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check"></i> Erstellen
                </button>
            </div>
        </form>
    </div>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>SprachenMeister</h5>
                    <p>Lerne Sprachen einfach und effektiv mit unserem interaktiven Sprachentrainer.</p>
                </div>
                <div class="col-md-3">
                    <h5>Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-decoration-none">Über uns</a></li>
                        <li><a href="#" class="text-decoration-none">Hilfe & FAQ</a></li>
                        <li><a href="#" class="text-decoration-none">Datenschutz</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-decoration-none">Kontaktformular</a></li>
                        <li><a href="#" class="text-decoration-none">Support</a></li>
                    </ul>
                </div>
            </div>
            <div class="text-center mt-4">
                <p>&copy; 2025 SprachenMeister. Alle Rechte vorbehalten.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap and JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function addWordPair() {
            const container = document.getElementById('wordPairsContainer');
            const newPair = document.createElement('div');
            newPair.className = 'word-pair';
            newPair.innerHTML = `
                <input type="text" name="words[]" placeholder="Begriff eingeben">
                <input type="text" name="translations[]" placeholder="Definition eingeben">
                <button type="button" class="delete-btn" onclick="deleteWordPair(this)">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            container.appendChild(newPair);
        }
        
        function deleteWordPair(button) {
            const container = document.getElementById('wordPairsContainer');
            const wordPairs = container.getElementsByClassName('word-pair');
            
            // Prevent deletion if only one pair remains
            if (wordPairs.length > 1) {
                button.parentElement.remove();
            } else {
                alert('Es muss mindestens ein Wörterpaar vorhanden sein.');
            }
        }
        
        // Form validation
        document.getElementById('createSetForm').addEventListener('submit', function(e) {
            const title = document.querySelector('input[name="set_title"]').value.trim();
            const words = document.querySelectorAll('input[name="words[]"]');
            const translations = document.querySelectorAll('input[name="translations[]"]');
            
            let hasValidPair = false;
            for (let i = 0; i < words.length; i++) {
                if (words[i].value.trim() !== '' && translations[i].value.trim() !== '') {
                    hasValidPair = true;
                    break;
                }
            }
            
            if (!title) {
                alert('Bitte geben Sie einen Titel für das Lernset ein.');
                e.preventDefault();
                return;
            }
            
            if (!hasValidPair) {
                alert('Bitte füllen Sie mindestens ein vollständiges Wörterpaar aus.');
                e.preventDefault();
                return;
            }
        });
    </script>
</body>
</html>