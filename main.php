
<?php
// Beispiel: Benutzername aus der Session anzeigen
session_start();
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Gast';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vokabeltrainer - Startseite</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <div class="account-info">
            Angemeldet als: <strong><?= htmlspecialchars($username) ?></strong>
        </div>
    </header>
    <div class="container">
        <nav class="sidebar">
            <ul>
                <li><a href="main.php">Start</a></li>
                <li><a href="bibliothek.php">Bibliothek</a></li>
            </ul>
        </nav>
        <main class="content">
            <h1>Zuletzt verwendete Lernsets</h1>
            <div class="recent-sets">
                <!-- Beispiel für Lernsets -->
                <div class="set">Lernset 1</div>
                <div class="set">Lernset 2</div>
                <div class="set">Lernset 3</div>
            </div>
        </main>
    </div>
</body>
</html>