<?php
/**
 * Algemene functies voor SlimmerMetAI.com
 */

// Voorkom direct toegang tot dit bestand
if (!defined('SITE_ROOT')) {
    die('Direct toegang tot dit bestand is niet toegestaan.');
}

/**
 * Redirect naar een bepaalde URL
 *
 * @param string $url De URL om naar te redirecten
 * @return void
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Flash berichten instellen en weergeven
 */
function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $flash_message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash_message;
    }
    return null;
}

function display_flash_message() {
    $flash_message = get_flash_message();
    if ($flash_message) {
        $type = $flash_message['type'];
        $message = $flash_message['message'];
        echo "<div class='alert alert-{$type}'>{$message}</div>";
    }
}

/**
 * CSRF bescherming
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

/**
 * Wachtwoord functies
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
}

function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

function is_password_strong($password) {
    // Minimaal 8 tekens, 1 hoofdletter, 1 kleine letter, 1 cijfer en 1 speciaal teken
    $uppercase = preg_match('@[A-Z]@', $password);
    $lowercase = preg_match('@[a-z]@', $password);
    $number = preg_match('@[0-9]@', $password);
    $special = preg_match('@[^\w]@', $password);
    
    return strlen($password) >= PASSWORD_MIN_LENGTH && $uppercase && $lowercase && $number && $special;
}

/**
 * Gebruiker functionaliteit
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        set_flash_message('danger', 'Je moet ingelogd zijn om deze pagina te bekijken.');
        redirect(SITE_URL . '/login.php');
    }
}

function get_user_by_id($id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    return $stmt->fetch();
}

function get_user_by_email($email) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    return $stmt->fetch();
}

function log_user_activity($user_id, $activity_type, $activity_data = null) {
    global $pdo;
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    
    $stmt = $pdo->prepare("
        INSERT INTO user_activity (user_id, activity_type, activity_data, ip_address)
        VALUES (:user_id, :activity_type, :activity_data, :ip_address)
    ");
    
    return $stmt->execute([
        'user_id' => $user_id,
        'activity_type' => $activity_type,
        'activity_data' => $activity_data ? json_encode($activity_data) : null,
        'ip_address' => $ip_address
    ]);
}

/**
 * Paginatie functie
 */
function paginate($total_items, $items_per_page, $current_page, $url_pattern) {
    $total_pages = ceil($total_items / $items_per_page);
    $current_page = max(1, min($current_page, $total_pages));
    
    $pagination = '<div class="pagination">';
    
    // Vorige pagina link
    if ($current_page > 1) {
        $prev_page = $current_page - 1;
        $pagination .= '<a href="' . sprintf($url_pattern, $prev_page) . '" class="page-item">&laquo; Vorige</a>';
    } else {
        $pagination .= '<span class="page-item disabled">&laquo; Vorige</span>';
    }
    
    // Paginanummers
    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $current_page + 2);
    
    if ($start_page > 1) {
        $pagination .= '<a href="' . sprintf($url_pattern, 1) . '" class="page-item">1</a>';
        if ($start_page > 2) {
            $pagination .= '<span class="page-item ellipsis">...</span>';
        }
    }
    
    for ($i = $start_page; $i <= $end_page; $i++) {
        if ($i == $current_page) {
            $pagination .= '<span class="page-item active">' . $i . '</span>';
        } else {
            $pagination .= '<a href="' . sprintf($url_pattern, $i) . '" class="page-item">' . $i . '</a>';
        }
    }
    
    if ($end_page < $total_pages) {
        if ($end_page < $total_pages - 1) {
            $pagination .= '<span class="page-item ellipsis">...</span>';
        }
        $pagination .= '<a href="' . sprintf($url_pattern, $total_pages) . '" class="page-item">' . $total_pages . '</a>';
    }
    
    // Volgende pagina link
    if ($current_page < $total_pages) {
        $next_page = $current_page + 1;
        $pagination .= '<a href="' . sprintf($url_pattern, $next_page) . '" class="page-item">Volgende &raquo;</a>';
    } else {
        $pagination .= '<span class="page-item disabled">Volgende &raquo;</span>';
    }
    
    $pagination .= '</div>';
    
    return $pagination;
}

/**
 * Helper functies
 */
function format_date($date, $format = 'd-m-Y H:i') {
    return date($format, strtotime($date));
}

function truncate_text($text, $length = 100, $ellipsis = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    $text = substr($text, 0, $length);
    $text = substr($text, 0, strrpos($text, ' '));
    return $text . $ellipsis;
}

function slugify($text) {
    // Vervang niet-alfanumerieke tekens met een streepje
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    // Transliteratie
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    // Verwijder overige ongewenste tekens
    $text = preg_replace('~[^-\w]+~', '', $text);
    // Trim streepjes aan begin en eind
    $text = trim($text, '-');
    // Verwijder dubbele streepjes
    $text = preg_replace('~-+~', '-', $text);
    // Naar kleine letters
    $text = strtolower($text);
    
    if (empty($text)) {
        return 'n-a';
    }
    
    return $text;
}

function get_file_extension($filename) {
    return pathinfo($filename, PATHINFO_EXTENSION);
}

function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function is_ajax_request() {
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
}

/**
 * Formulierverwerking
 */
function validate_required_fields($fields, $data) {
    $errors = [];
    
    foreach ($fields as $field) {
        if (empty($data[$field])) {
            $errors[$field] = 'Dit veld is verplicht.';
        }
    }
    
    return $errors;
}

function sanitize_form_data($data) {
    $sanitized = [];
    
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $sanitized[$key] = sanitize_form_data($value);
        } else {
            $sanitized[$key] = sanitize_input($value);
        }
    }
    
    return $sanitized;
}

/**
 * Debug functie
 */
function debug($var, $die = false) {
    if (DEBUG_MODE) {
        echo '<pre>';
        print_r($var);
        echo '</pre>';
        
        if ($die) {
            die();
        }
    }
} 