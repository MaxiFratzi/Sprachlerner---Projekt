<?php
// Start Session
session_start();

?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SprachenMeister - Lerne Sprachen interaktiv</title>
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
        }
        
        .navbar {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .hero-section {
            padding: 4rem 0;
            text-align: center;
        }
        
        h1 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 1.5rem;
        }
        
        .hero-text {
            max-width: 700px;
            margin: 0 auto 2rem auto;
        }
        
        .feature-card {
            border-radius: 20px;
            padding: 2rem;
            height: 100%;
            transition: transform 0.3s;
            margin-bottom: 20px;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
        }
        
        .learn-card {
            background-color: var(--light-blue);
        }
        
        .resources-card {
            background-color: var(--pink);
        }
        
        .flashcards-card {
            background-color: #c9c6ff;
        }
        
        .quiz-card {
            background-color: var(--orange);
        }
        
        .feature-image {
            max-width: 180px;
            margin-bottom: 1rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 10px 25px;
            font-weight: 600;
            border-radius: 50px;
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 10px 25px;
            font-weight: 600;
            border-radius: 50px;
        }
        
        .modal-content {
            border-radius: 15px;
        }
        
        .alert {
            margin-bottom: 20px;
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
                <div class="d-flex">
                    <a href="login.php" class="btn btn-outline-primary me-2">Anmelden</a>
                    <a href="register.php" class="btn btn-primary">Erstellen</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1>Wie möchtest du lernen?</h1>
            <p class="hero-text">Mit den interaktiven Karteikarten, Übungstests und Lernaktivitäten von SprachenMeister lernst du alles, was du willst.</p>
            <a href="register.php" class="btn btn-primary btn-lg mb-4">
                Kostenlos registrieren
            </a>
        </div>
    </section>

    <!-- Feature Cards -->
    <section class="container mb-5">
        <div class="row">
            <!-- Learn Card -->
            <div class="col-md-6 col-lg-3">
                <div class="feature-card learn-card">
                    <h3>Lernen</h3>
                    <div class="mt-3">
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" placeholder="Antwort eingeben">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Resources Card -->
            <div class="col-md-6 col-lg-3">
                <div class="feature-card resources-card">
                    <h3>Arbeitshilfen</h3>
                    <div class="mt-3">
                        <h5>Sprachstrukturen</h5>
                        <div class="d-flex mt-4">
                            <div>
                                <div class="mb-3">Gliederung</div>
                            </div>
                            <div class="ms-3">
                                <div class="mb-3">Kurzübersicht</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Flashcards Card -->
            <div class="col-md-6 col-lg-3">
                <div class="feature-card flashcards-card">
                    <h3>Karteikarten</h3>
                    <div class="card mt-4 mx-auto" style="width: 180px; height: 120px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
                        <div class="card-body text-center d-flex align-items-center justify-content-center">
                            <div>
                                <div class="fw-bold">Wort</div>
                                <div class="text-muted">Übersetzung</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quiz Card -->
            <div class="col-md-6 col-lg-3">
                <div class="feature-card quiz-card">
                    <h3>Übungstests</h3>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between mb-2">
                            <div>Note</div>
                            <div>Ergebnis</div>
                            <div>Zeit</div>
                        </div>
                        <div class="d-flex justify-content-between mb-4 fw-bold">
                            <div>84%</div>
                            <div>42/50</div>
                            <div>10 Min</div>
                        </div>
                        <div class="mt-2">
                            <div class="mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="quizOption" id="option1">
                                    <label class="form-check-label" for="option1">A. Option</label>
                                </div>
                            </div>
                            <div class="mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="quizOption" id="option2" checked>
                                    <label class="form-check-label" for="option2">B. Option</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

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
                    <h5>Kontakt</h5>
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
</body>
</html>