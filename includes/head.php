<?php
$page_title = isset($page_title) ? $page_title . ' | Slimmer met AI' : 'Slimmer met AI';
$page_description = isset($page_description) ? $page_description : 'Praktische AI-tools en e-learnings voor Nederlandse professionals. Werk slimmer, niet harder.';

// Voeg meta tags toe
include_once 'meta-tags.php';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <meta name="description" content="<?php echo $page_description; ?>">
    <link rel="stylesheet" href="/css/style.css">
    <noscript>
        <style>
            .preloader { display: none !important; }
        </style>
    </noscript>

    <!-- Favicon -->
    <link rel="icon" href="/images/favicon.ico" type="image/x-icon">

    <!-- Scripts -->
    <script src="/js/main.js" defer></script>
</head>
<body>
    <div class="preloader">
        <div class="loader"></div>
    </div>
    
    <a href="#main-content" class="skip-link">Direct naar inhoud</a> 