<?php
// Start Session
session_start();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SprachenMeister - Deine Bibliothek</title>
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
        
        .library-tabs {
            display: flex;
            justify-content: center;
            margin-top: 1rem;
            gap: 1rem;
        }
        
        .library-tabs .nav-link {
            color: white;
            font-weight: 600;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
        }
        
        .library-tabs .nav-link.active {
            background-color: white;
            color: var(--primary-color);
        }
        
        .library-content {
            max-width: 800px;
            margin: 2rem auto;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        
        .library-set {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: transform 0.3s;
        }
        
        .library-set:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .library-set-info {
            flex-grow: 1;
        }
        
        .library-set-title {
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .library-set-details {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 10px 25px;
            font-weight: 600;
            border-radius: 50px;
        }
        
        .search-bar {
            margin-bottom: 1.5rem;
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

    <!-- Library Header -->
    <div class="library-header">
        <h1>Deine Bibliothek</h1>
        <div class="library-tabs">
            <ul class="nav">
                <li class="nav-item">
                    <a class="nav-link active" href="#lernsets">Lernsets</a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Library Content -->
    <div class="library-content">
        <div class="search-bar">
            <input type="text" class="form-control" placeholder="Karteikarten suchen" style="border-radius: 20px;">
        </div>

        <!-- Diese Woche -->
        <h4 class="mb-3">Diese Woche</h4>
        <div class="library-set">
            <div class="library-set-info">
                <div class="library-set-title">Easy</div>
                <div class="library-set-details">50 Begriffe</div>
            </div>
            <div>
                <button class="btn btn-primary">Lernen</button>
            </div>
        </div>

        <!-- Im April 2025 -->
        <h4 class="mt-4 mb-3">Im April 2025</h4>
        <div class="library-set">
            <div class="library-set-info">
                <div class="library-set-title">Medium</div>
                <div class="library-set-details">50 Begriffe</div>
            </div>
            <div>
                <button class="btn btn-primary">Lernen</button>
            </div>
        </div>

        <!-- Im März 2025 -->
        <h4 class="mt-4 mb-3">Im März 2025</h4>
        <div class="library-set">
            <div class="library-set-info">
                <div class="library-set-title">Hard</div>
                <div class="library-set-details">50 Begriffe</div>
            </div>
            <div>
                <button class="btn btn-primary">Lernen</button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>SprachMeister</h5>
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
                    <h5>Kontakt</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-decoration-none">Kontaktformular</a></li>
                        <li><a href="#" class="text-decoration-none">Support</a></li>
                    </ul>
                </div>
            </div>
            <div class="text-center mt-4">
                <p>&copy; 2025 SprachMeister. Alle Rechte vorbehalten.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap and JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>