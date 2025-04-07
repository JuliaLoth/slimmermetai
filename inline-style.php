<?php
// Een versie van de homepage met inline CSS als tijdelijke oplossing
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slimmer met AI | Praktische AI-tools voor Nederlandse professionals</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Basis stijlen */
        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f9fafc;
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 1150px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header */
        header {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px 0;
        }
        
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        
        .logo a {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #333;
        }
        
        .logo-text {
            font-weight: bold;
            font-size: 1.2rem;
            margin-left: 10px;
        }
        
        .nav-links {
            display: flex;
            gap: 20px;
        }
        
        .nav-links a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            padding: 5px 10px;
            transition: all 0.3s ease;
        }
        
        .nav-links a:hover {
            color: #5852f2;
        }
        
        /* Hero section */
        .page-hero {
            padding: 80px 0;
            background: linear-gradient(135deg, #5852f2 0%, #db2777 100%);
            color: white;
            text-align: center;
        }
        
        .hero-content {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .hero-content h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }
        
        .hero-content p {
            font-size: 1.2rem;
            margin-bottom: 30px;
        }
        
        .hero-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        
        /* Knoppen */
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .btn-primary {
            background-color: white;
            color: #5852f2;
        }
        
        .btn-primary:hover {
            background-color: #f0f0f0;
        }
        
        .btn-secondary {
            border: 2px solid white;
            color: white;
            background: transparent;
        }
        
        .btn-secondary:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        /* Content secties */
        .section {
            padding: 60px 0;
        }
        
        /* Footer */
        footer {
            background-color: #333;
            color: white;
            padding: 40px 0 20px;
        }
        
        .footer-bottom {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
    </style>
</head>
<body>
    <header role="banner">
        <div class="container">
            <nav class="navbar" role="navigation" aria-label="Hoofdnavigatie">
                <div class="logo">
                    <a href="index.php">
                        <img src="images/Logo.svg" alt="Slimmer met AI logo" width="50">
                        <span class="logo-text">Slimmer met AI</span>
                    </a>
                </div>
                <div class="nav-links">
                    <a href="index.php" aria-current="page">Home</a>
                    <a href="tools.php">Tools</a>
                    <a href="e-learnings.php">Cursussen</a>
                    <a href="over-mij.php">Over Mij</a>
                    <a href="nieuws.php">Nieuws</a>
                </div>
            </nav>
        </div>
    </header>

    <main id="main-content" role="main">
        <section class="page-hero">
            <div class="container">
                <div class="hero-content">
                    <h1>Slimmer met AI</h1>
                    <p>Praktische AI-tools en e-learnings voor Nederlandse professionals</p>
                    <div class="hero-buttons">
                        <a href="tools.php" class="btn btn-primary">Bekijk Tools</a>
                        <a href="e-learnings.php" class="btn btn-secondary">Ontdek E-learnings</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="container">
                <h2>Welkom bij Slimmer met AI</h2>
                <p>Hier vind je praktische tools en cursussen om slimmer te werken met AI.</p>
                
                <div style="margin-top: 40px;">
                    <h3>Opmerking over CSS styling</h3>
                    <p>Dit is een tijdelijke pagina met inline CSS styling. De normale website heeft meer functionaliteit en een uitgebreidere styling via externe CSS bestanden.</p>
                    <p>Als je deze pagina ziet, betekent dit dat er een probleem is met het laden van het externe CSS bestand. Gebruik de debugging tools om het probleem op te lossen.</p>
                    <p><a href="css-debug.php" style="color: #5852f2; font-weight: bold;">Open CSS Debug Tool</a> | <a href="fix-permissions.php" style="color: #5852f2; font-weight: bold;">Controleer Bestandsrechten</a></p>
                </div>
            </div>
        </section>
    </main>

    <footer role="contentinfo">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Slimmer met AI. Alle rechten voorbehouden.</p>
            </div>
        </div>
    </footer>
</body>
</html> 