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
    <link rel="stylesheet" href="/css/style.css" integrity="sha384-FA5BD373839A7E5C87BC78DFFAFB85DAEC2E7B119085BE3B42D9F8D4D5E94DE3FBF662E585EC6219A1FA559C3374F437" crossorigin="anonymous">
    <noscript>
        <style>
            .preloader { display: none !important; }
        </style>
    </noscript>

    <!-- Favicon -->
    <link rel="icon" href="/images/favicon.ico" type="image/x-icon">

    <!-- Scripts -->
    <script src="/js/main.js" integrity="sha384-D431A0F1C9FA03B819022A4FA749CE15B42778E4B5190856540DCD109B3C06C147369CF24FF07F0B78F1B2ACD2E1686B" crossorigin="anonymous" defer></script>
</head>
<body>
    <div class="preloader">
        <div class="loader"></div>
    </div>
    
    <a href="#main-content" class="skip-link">Direct naar inhoud</a> 