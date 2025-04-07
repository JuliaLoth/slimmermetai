<?php
// Meta tags voor SEO en sociale media
echo '<!-- Open Graph / Facebook -->';
echo '<meta property="og:type" content="website">';
echo '<meta property="og:url" content="' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" . '">';
echo '<meta property="og:title" content="' . $page_title . '">';
echo '<meta property="og:description" content="' . $page_description . '">';
echo '<meta property="og:image" content="/images/og-image.jpg">';

echo '<!-- Twitter -->';
echo '<meta property="twitter:card" content="summary_large_image">';
echo '<meta property="twitter:url" content="' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" . '">';
echo '<meta property="twitter:title" content="' . $page_title . '">';
echo '<meta property="twitter:description" content="' . $page_description . '">';
echo '<meta property="twitter:image" content="/images/og-image.jpg">';
?> 