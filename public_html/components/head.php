<?php
// Helper functie voor asset URLs als deze nog niet bestaat
if (!function_exists('asset_url')) {
    function asset_url($path) {
        return '//' . $_SERVER['HTTP_HOST'] . '/' . ltrim($path, '/');
    }
}

// Standaardwaarden voor metadata
$page_title = $page_title ?? 'Slimmer met AI';
$page_description = $page_description ?? 'Praktische AI-tools en e-learnings voor Nederlandse professionals. Werk slimmer, niet harder.';
$page_keywords = $page_keywords ?? 'AI, artificial intelligence, kunstmatige intelligentie, e-learning, tools, Nederland, Nederlands, AI tools';
$page_author = $page_author ?? 'Slimmer met AI';
$canonical_url = $canonical_url ?? 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

// Voeg meta tags toe
include_once 'meta-tags.php';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <!-- Meta tags -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($page_keywords); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($page_author); ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>">
    
    <!-- Title -->
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo asset_url('images/favicon.ico'); ?>" type="image/x-icon">
    <link rel="shortcut icon" href="<?php echo asset_url('images/favicon.ico'); ?>" type="image/x-icon">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo asset_url('css/style.css'); ?>">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- JavaScript -->
    <script src="<?php echo asset_url('js/main.js'); ?>" defer></script>
</head>
<body>
    <div class="preloader">
        <div class="loader"></div>
    </div>
    
    <a href="#main-content" class="skip-link">Direct naar inhoud</a> 