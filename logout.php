<?php
// Start the session
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abgemeldet</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Fontawesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logout-container {
            background: #fff;
            padding: 2.5rem 3rem;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            text-align: center;
        }
        .logout-container h1 {
            font-size: 2rem;
            margin-bottom: 1.5rem;
            color: #4255ff;
        }
        .logout-container a {
            display: inline-block;
            margin-top: 1rem;
            padding: 0.5rem 1.5rem;
            background: #4255ff;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-size: 1rem;
            transition: background 0.2s;
        }
        .logout-container a:hover {
            background: #2d3bbd;
        }
        .logout-icon {
            font-size: 3rem;
            color: #4255ff;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logout-icon">
            <i class="fas fa-sign-out-alt"></i>
        </div>
        <h1>Du wurdest abgemeldet!</h1>
        <a href="login.php">Zur Login-Page</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>