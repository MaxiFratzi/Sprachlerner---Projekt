<?php
// filepath: c:\xampp\htdocs\3BHWII\Sprachlerner - Projekt\create_set.php
// Start Session
session_start();

// Prüfen, ob Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
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

// Variablen für Formular und Feedback
$setName = '';
$setDescription = '';
$vocabularies = [];
$message = '';
$messageType = '';

// Formular verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $setName = trim($_POST['setName'] ?? '');
    $setDescription = trim($_POST['setDescription'] ?? '');
    $vocabularies_post = $_POST['vocabularies'] ?? [];
    
    // Validierung
    $errors = [];
    
    if (empty($setName)) {
        $errors[] = "Lernset-Name ist erforderlich.";
    }
    
    if (strlen($setName) > 255) {
        $errors[] = "Lernset-Name darf maximal 255 Zeichen lang sein.";
    }
    
    if (strlen($setDescription) > 500) {
        $errors[] = "Beschreibung darf maximal 500 Zeichen lang sein.";
    }
    
    // Vokabeln validieren
    $validVocabularies = [];
    foreach ($vocabularies_post as $vocab) {
        $deutsch = trim($vocab['deutsch'] ?? '');
        $fremdsprache = trim($vocab['fremdsprache'] ?? '');
        
        if (!empty($deutsch) && !empty($fremdsprache)) {
            if (strlen($deutsch) <= 255 && strlen($fremdsprache) <= 255) {
                $validVocabularies[] = [
                    'deutsch' => $deutsch,
                    'fremdsprache' => $fremdsprache
                ];
            } else {
                $errors[] = "Deutsch und Fremdsprache dürfen jeweils maximal 255 Zeichen lang sein.";
            }
        }
    }
    
    if (empty($validVocabularies)) {
        $errors[] = "Mindestens eine Vokabel ist erforderlich.";
    }
    
    // Wenn keine Fehler, Lernset erstellen
    if (empty($errors)) {
        $conn->begin_transaction();
        
        try {
            // Lernset in Datenbank einfügen
            $stmt = $conn->prepare("INSERT INTO lernsets (name, description, type, user_id, is_active, created_at, updated_at) VALUES (?, ?, 'custom', ?, 1, NOW(), NOW())");
            $stmt->bind_param("ssi", $setName, $setDescription, $user_id);
            $stmt->execute();
            
            $lernset_id = $conn->insert_id;
            
            // Vokabeln einfügen
            $vocab_stmt = $conn->prepare("INSERT INTO vokabeln (lernset_id, deutsch, fremdsprache, created_at) VALUES (?, ?, ?, NOW())");
            
            foreach ($validVocabularies as $vocab) {
                $vocab_stmt->bind_param("iss", $lernset_id, $vocab['deutsch'], $vocab['fremdsprache']);
                $vocab_stmt->execute();
            }
            
            $conn->commit();
            
            // Zur Bibliothek weiterleiten
            header("Location: library.php?created=1");
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Fehler beim Erstellen des Lernsets: " . $e->getMessage();
            $messageType = "error";
        }
    } else {
        $message = implode("<br>", $errors);
        $messageType = "error";
        
        // Eingaben für das Formular beibehalten
        $vocabularies = $validVocabularies;
    }
}

$conn->close();
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
            --success-color: #28a745;
            --error-color: #dc3545;
            --warning-color: #ffc107;
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
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 3rem 0;
            text-align: center;
        }
        
        .page-header h1 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .page-header .subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .main-content {
            max-width: 900px;
            margin: 3rem auto;
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 3rem;
        }
        
        .form-section {
            margin-bottom: 3rem;
        }
        
        .form-section h3 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            font-weight: 600;
            border-bottom: 2px solid #f8f9fa;
            padding-bottom: 0.5rem;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.8rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(66, 85, 255, 0.25);
        }
        
        .vocabulary-container {
            border: 2px solid #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .vocabulary-item {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .vocabulary-item:hover {
            background-color: #e9ecef;
        }
        
        .vocabulary-number {
            position: absolute;
            top: -10px;
            left: 15px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .vocabulary-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }
        
        .btn-remove {
            background-color: var(--error-color);
            border-color: var(--error-color);
            color: white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }
        
        .btn-remove:hover {
            background-color: #c82333;
            border-color: #c82333;
        }
        
        .btn-add-vocab {
            background-color: var(--success-color);
            border-color: var(--success-color);
            color: white;
            font-weight: 600;
            border-radius: 50px;
            padding: 0.8rem 2rem;
            margin-top: 1rem;
        }
        
        .btn-add-vocab:hover {
            background-color: #218838;
            border-color: #218838;
            color: white;
        }
        
        .btn-primary-custom {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            font-weight: 600;
            border-radius: 50px;
            padding: 1rem 2.5rem;
            font-size: 1.1rem;
        }
        
        .btn-primary-custom:hover {
            background-color: #3346e6;
            border-color: #3346e6;
            color: white;
        }
        
        .alert {
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 2rem;
        }
        
        .alert-success {
            background-color: #d4edda;
            border-color: var(--success-color);
            color: var(--success-color);
        }
        
        .alert-danger {
            background-color: #f8d7da;
            border-color: var(--error-color);
            color: var(--error-color);
        }
        
        .form-actions {
            text-align: center;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 2px solid #f8f9fa;
        }
        
        .back-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            margin-right: 2rem;
        }
        
        .back-link:hover {
            color: #3346e6;
            text-decoration: underline;
        }
        
        .vocabulary-counter {
            background-color: var(--light-blue);
            color: var(--primary-color);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin: 2rem 1rem;
                padding: 2rem;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
            
            .vocabulary-inputs {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
            
            .btn-remove {
                width: 100%;
                border-radius: 10px;
                height: auto;
                padding: 0.5rem;
            }
            
            .form-actions {
                text-align: center;
            }
            
            .back-link {
                display: block;
                margin-bottom: 1rem;
                margin-right: 0;
            }
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
        <h1><i class="fas fa-plus me-3"></i>Neues Lernset erstellen</h1>
        <div class="subtitle">Erstelle dein eigenes Vokabel-Lernset</div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $messageType === 'error' ? 'alert-danger' : 'alert-success'; ?>">
                <i class="fas <?php echo $messageType === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'; ?> me-2"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="create_set.php" id="createSetForm">
            <!-- Lernset-Details -->
            <div class="form-section">
                <h3><i class="fas fa-info-circle me-2"></i>Lernset-Details</h3>
                
                <div class="mb-3">
                    <label for="setName" class="form-label">Lernset-Name *</label>
                    <input type="text" class="form-control" id="setName" name="setName" 
                           value="<?php echo htmlspecialchars($setName); ?>" 
                           placeholder="z.B. Englisch Grundwortschatz" required maxlength="255">
                </div>
                
                <div class="mb-3">
                    <label for="setDescription" class="form-label">Beschreibung</label>
                    <textarea class="form-control" id="setDescription" name="setDescription" 
                              rows="3" placeholder="Beschreibe dein Lernset..." maxlength="500"><?php echo htmlspecialchars($setDescription); ?></textarea>
                    <div class="form-text">Optional - Maximal 500 Zeichen</div>
                </div>
            </div>

            <!-- Vokabeln -->
            <div class="form-section">
                <h3><i class="fas fa-book me-2"></i>Vokabeln</h3>
                
                <div class="vocabulary-counter">
                    <i class="fas fa-list-ol me-1"></i>
                    <span id="vocabCount">0</span> Vokabeln
                </div>
                
                <div class="vocabulary-container">
                    <div id="vocabularyList">
                        <!-- Vokabeln werden hier dynamisch hinzugefügt -->
                        <?php if (!empty($vocabularies)): ?>
                            <?php foreach ($vocabularies as $index => $vocab): ?>
                            <div class="vocabulary-item" id="vocab-<?php echo $index + 1; ?>">
                                <div class="vocabulary-number"><?php echo $index + 1; ?></div>
                                <div class="vocabulary-inputs">
                                    <div>
                                        <label class="form-label">Deutsch</label>
                                        <input type="text" class="form-control" 
                                               name="vocabularies[<?php echo $index + 1; ?>][deutsch]" 
                                               value="<?php echo htmlspecialchars($vocab['deutsch']); ?>" 
                                               placeholder="z.B. Haus" 
                                               maxlength="255" 
                                               onchange="validateForm()">
                                    </div>
                                    <div>
                                        <label class="form-label">Fremdsprache</label>
                                        <input type="text" class="form-control" 
                                               name="vocabularies[<?php echo $index + 1; ?>][fremdsprache]" 
                                               value="<?php echo htmlspecialchars($vocab['fremdsprache']); ?>" 
                                               placeholder="z.B. house" 
                                               maxlength="255" 
                                               onchange="validateForm()">
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-remove" 
                                                onclick="removeVocabulary(<?php echo $index + 1; ?>)" 
                                                title="Vokabel entfernen">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <button type="button" class="btn btn-add-vocab" onclick="addVocabulary()">
                        <i class="fas fa-plus me-2"></i>Vokabel hinzufügen
                    </button>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <a href="library.php" class="back-link">
                    <i class="fas fa-arrow-left me-2"></i>Zurück zur Bibliothek
                </a>
                <button type="submit" class="btn btn-primary-custom" id="submitButton" disabled>
                    <i class="fas fa-save me-2"></i>Lernset erstellen
                </button>
            </div>
        </form>
    </div>

    <!-- Bootstrap and JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let vocabularyCount = <?php echo count($vocabularies); ?>;
        
        // Beim Laden der Seite erste Vokabel hinzufügen wenn leer
        document.addEventListener('DOMContentLoaded', function() {
            if (vocabularyCount === 0) {
                addVocabulary();
            } else {
                updateVocabCounter();
                validateForm();
            }
        });
        
        function addVocabulary(deutsch = '', fremdsprache = '') {
            vocabularyCount++;
            
            const vocabularyList = document.getElementById('vocabularyList');
            const vocabularyItem = document.createElement('div');
            vocabularyItem.className = 'vocabulary-item';
            vocabularyItem.id = `vocab-${vocabularyCount}`;
            
            vocabularyItem.innerHTML = `
                <div class="vocabulary-number">${vocabularyCount}</div>
                <div class="vocabulary-inputs">
                    <div>
                        <label class="form-label">Deutsch</label>
                        <input type="text" class="form-control" 
                               name="vocabularies[${vocabularyCount}][deutsch]" 
                               value="${deutsch}" 
                               placeholder="z.B. Haus" 
                               maxlength="255" 
                               onchange="validateForm()">
                    </div>
                    <div>
                        <label class="form-label">Fremdsprache</label>
                        <input type="text" class="form-control" 
                               name="vocabularies[${vocabularyCount}][fremdsprache]" 
                               value="${fremdsprache}" 
                               placeholder="z.B. house" 
                               maxlength="255" 
                               onchange="validateForm()">
                    </div>
                    <div>
                        <button type="button" class="btn btn-remove" 
                                onclick="removeVocabulary(${vocabularyCount})" 
                                title="Vokabel entfernen">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            
            vocabularyList.appendChild(vocabularyItem);
            updateVocabCounter();
            validateForm();
            
            // Fokus auf ersten Input setzen
            vocabularyItem.querySelector('input[type="text"]').focus();
        }
        
        function removeVocabulary(id) {
            const vocabularyItem = document.getElementById(`vocab-${id}`);
            if (vocabularyItem) {
                vocabularyItem.remove();
                updateVocabNumbers();
                updateVocabCounter();
                validateForm();
            }
        }
        
        function updateVocabNumbers() {
            const vocabularyItems = document.querySelectorAll('.vocabulary-item');
            vocabularyItems.forEach((item, index) => {
                const numberElement = item.querySelector('.vocabulary-number');
                if (numberElement) {
                    numberElement.textContent = index + 1;
                }
            });
        }
        
        function updateVocabCounter() {
            const count = document.querySelectorAll('.vocabulary-item').length;
            document.getElementById('vocabCount').textContent = count;
        }
        
        function validateForm() {
            const setName = document.getElementById('setName').value.trim();
            const vocabularyItems = document.querySelectorAll('.vocabulary-item');
            
            let hasValidVocab = false;
            vocabularyItems.forEach(item => {
                const deutsch = item.querySelector('input[name*="[deutsch]"]').value.trim();
                const fremdsprache = item.querySelector('input[name*="[fremdsprache]"]').value.trim();
                
                if (deutsch && fremdsprache) {
                    hasValidVocab = true;
                }
            });
            
            const submitButton = document.getElementById('submitButton');
            submitButton.disabled = !setName || !hasValidVocab;
        }
        
        // Form-Validierung bei Eingabe
        document.getElementById('setName').addEventListener('input', validateForm);
        
        // Enter-Taste für neue Vokabel
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.matches('input[name*="vocabularies"]')) {
                e.preventDefault();
                
                // Prüfen ob beide Felder der aktuellen Vokabel ausgefüllt sind
                const currentItem = e.target.closest('.vocabulary-item');
                const deutsch = currentItem.querySelector('input[name*="[deutsch]"]').value.trim();
                const fremdsprache = currentItem.querySelector('input[name*="[fremdsprache]"]').value.trim();
                
                if (deutsch && fremdsprache) {
                    addVocabulary();
                } else {
                    // Zum nächsten leeren Feld springen
                    const nextInput = currentItem.querySelector('input[name*="[fremdsprache]"]');
                    if (nextInput && !nextInput.value.trim()) {
                        nextInput.focus();
                    }
                }
            }
        });
        
        // Formular-Submission verhindern wenn nicht gültig
        document.getElementById('createSetForm').addEventListener('submit', function(e) {
            const submitButton = document.getElementById('submitButton');
            if (submitButton.disabled) {
                e.preventDefault();
                alert('Bitte fülle mindestens den Lernset-Namen und eine vollständige Vokabel aus.');
            }
        });
    </script>
</body>
</html>