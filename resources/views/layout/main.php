<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'SlimmerMetAI - Praktische AI-tools voor Nederlandse professionals' ?></title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Modern CSS via Vite build -->
    <?php
        use App\Infrastructure\Utils\Asset;
        $mainCss = Asset::css('main');
        if ($mainCss) {
            echo '<link rel="stylesheet" href="' . $mainCss . '">';
        }
    ?>
    
    <!-- CSRF Token for AJAX -->
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
</head>
<body>
    <?php include __DIR__ . '/partials/unified-header.php'; ?>
    <main id="main-content" role="main" style="padding-top: 80px;">
        <?= $content ?? '' ?>
    </main>
    <?php include __DIR__ . '/partials/footer.php'; ?>
    
    <!-- Modern JavaScript via Vite build -->
    <?php
        // Hoofd-bundle insluiten - bevat alle functionaliteit inclusief cart
        echo '<script type="module" src="' . Asset::url('main') . '"></script>'; 
        
        // Auth-specific JS voor login/register pagina's
        if (strpos($_SERVER['REQUEST_URI'], 'login') !== false || strpos($_SERVER['REQUEST_URI'], 'register') !== false) {
            $authJs = Asset::url('auth');
            if ($authJs) {
                echo '<script type="module" src="' . $authJs . '"></script>';
            }
        }
        
        // Cart en Stripe-specific JS voor winkelwagen en betaling pagina's
        if (strpos($_SERVER['REQUEST_URI'], 'betalen') !== false || strpos($_SERVER['REQUEST_URI'], 'winkelwagen') !== false) {
            $cartJs = Asset::url('cart');
            if ($cartJs) {
                echo '<script type="module" src="' . $cartJs . '"></script>';
            }
            
            $stripeJs = Asset::url('stripe-payment');
            if ($stripeJs) {
                echo '<script type="module" src="' . $stripeJs . '"></script>';
            }
        }
    ?>
</body>
</html> 