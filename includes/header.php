<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
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
                <a href="index.php" <?php echo ($current_page == 'index.php') ? 'aria-current="page"' : ''; ?>>Home</a>
                <a href="tools.php" <?php echo ($current_page == 'tools.php') ? 'aria-current="page"' : ''; ?>>Tools</a>
                <a href="e-learnings.php" <?php echo ($current_page == 'e-learnings.php') ? 'aria-current="page"' : ''; ?>>Cursussen</a>
                <a href="over-mij.php" <?php echo ($current_page == 'over-mij.php') ? 'aria-current="page"' : ''; ?>>Over Mij</a>
                <a href="nieuws.php" <?php echo ($current_page == 'nieuws.php') ? 'aria-current="page"' : ''; ?>>Nieuws</a>
            </div>
            <div class="auth-buttons">
                <a href="login.php" class="account-btn">
                    Account
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </a>
                <a href="winkelwagen.php" class="cart-button <?php echo ($current_page == 'winkelwagen.php') ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                    <span class="cart-count">0</span>
                </a>
            </div>
        </nav>
    </div>
</header> 