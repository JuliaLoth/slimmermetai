<?php
// Directe testpagina zonder includes
echo '<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test - Slimmer met AI</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
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
            </div>
        </section>
    </main>

    <footer role="contentinfo">
        <div class="container">
            <p>&copy; ' . date('Y') . ' Slimmer met AI. Alle rechten voorbehouden.</p>
        </div>
    </footer>
</body>
</html>';
?> 