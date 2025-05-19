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
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SprachenMeister - Lernzentrum</title>
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

        .sidebar {
            background-color: white;
            height: calc(100vh - 60px);
            width: 250px;
            position: fixed;
            top: 60px;
            left: 0;
            padding: 20px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-header {
            font-weight: bold;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 15px;
        }
        
        .sidebar-menu a {
            color: #333;
            text-decoration: none;
            font-size: 16px;
            display: block;
            padding: 8px 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .sidebar-menu a:hover {
            background-color: #f0f4ff;
            color: var(--primary-color);
        }
        
        .sidebar-menu a.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .content {
            margin-left: 250px;
            padding: 30px;
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
        
        .welcome-section {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .welcome-section h1 {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .stats-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            width: 30%;
            text-align: center;
        }
        
        .stat-card h3 {
            color: var(--primary-color);
            font-size: 2rem;
            margin-bottom: 5px;
        }
        
        .stat-card p {
            color: #666;
            margin-bottom: 0;
        }
        
        .recent-sets {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .recent-sets h2 {
            margin-bottom: 20px;
            font-size: 1.5rem;
            color: #333;
        }
        
        .set-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        
        .set-card {
            background-color: #f0f4ff;
            border-radius: 12px;
            padding: 15px;
            border-left: 5px solid var(--primary-color);
            transition: transform 0.3s;
        }
        
        .set-card:hover {
            transform: translateY(-5px);
        }
        
        .set-card h4 {
            font-size: 1.1rem;
            margin-bottom: 8px;
        }
        
        .set-card p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand ms-4" href="main.php">SprachenMeister</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <form class="d-flex mx-auto mb-2 mb-lg-0">
                    <input class="form-control me-2" type="search" placeholder="Nach Vokabeln suchen" style="width: 250px; border-radius: 20px;">
                </form>
                <div class="ms-auto me-4">
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

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            Lernzentrum
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="main.php" class="active">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="library.php">
                    <i class="fas fa-book"></i> Bibliothek
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="content" style="margin-top: 60px;">
        <div class="welcome-section">
            <h1>Willkommen zurück, <?php echo htmlspecialchars($username); ?>!</h1>
            <p>Bereit zum Lernen? Hier ist dein Fortschritt.</p>
        </div>
        
        <div class="stats-container">
            <div class="stat-card">
                <h3>0</h3>
                <p>Gelernte Vokabeln heute</p>
            </div>
            <div class="stat-card">
                <h3>0</h3>
                <p>Tage in Folge gelernt</p>
            </div>
            <div class="stat-card">
                <h3>0%</h3>
                <p>Erfolgsquote</p>
            </div>
        </div>
        
        <div class="recent-sets">
            <h2>Kürzlich gelernte Sets</h2>
            <div class="set-grid">
                <div class="set-card">
                    <h4>Easy</h4>
                    <p>50 Begriffe</p>
                    <a href="easyVoc.php" class="btn btn-primary btn-sm">Weiter lernen</a>
                </div>
                <div class="set-card">
                    <h4>Medium</h4>
                    <p>50 Begriffe</p>
                    <a href="mediumVoc.php" class="btn btn-primary btn-sm">Weiter lernen</a>
                </div>
                <div class="set-card">
                    <h4>Hard</h4>
                    <p>50 Begriffe</p>
                    <a href="hardVoc.php" class="btn btn-primary btn-sm">Weiter lernen</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap and JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>