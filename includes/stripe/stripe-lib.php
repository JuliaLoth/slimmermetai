<?php
/**
 * Stripe Library voor Slimmer met AI
 * Dit bestand bevat herbruikbare Stripe-gerelateerde functies
 */

// Controleer of Composer Autoload beschikbaar is (voor lokale ontwikkeling)
$autoload_path = SITE_ROOT . '/vendor/autoload.php';
if (file_exists($autoload_path)) {
    require_once $autoload_path;
}

// Laad environment variabelen uit .env bestand als het bestaat
$env_path = dirname(dirname(dirname(__FILE__))) . '/.env';
if (file_exists($env_path)) {
    $env_vars = parse_ini_file($env_path);
    foreach ($env_vars as $key => $value) {
        if (!getenv($key)) {
            putenv("$key=$value");
        }
    }
}

// Stripe API sleutel instellen
$stripe_secret_key = getenv('STRIPE_SECRET_KEY') ?: 'sk_test_51R9P5k4PGPB9w5n1VCGJD30RWZgNCA2U5xyhCrEHZw46tPGShW8bNRMjbTxvKDUI3A1mclQvBYvywM1ZNU1jffIo00jKgorz1n';
$stripe_publishable_key = getenv('STRIPE_PUBLIC_KEY') ?: 'pk_test_51R9P5k4PGPB9w5n1not7EQ9Kh15WBNrFC0B09dHvKNN3Slf1dF32rFvQniEwPpeeAQstMGnLQFTblXXwN8QAGovO00S1D67hoD';
$stripe_webhook_secret = getenv('STRIPE_WEBHOOK_SECRET') ?: 'whsec_0u8wjjyPfMoEphZkbqQ2hlYmzY4cAp1W'; // Ingevuld met de zandbak webhook secret

// Log de API key (in debug modus)
if (getenv('DEBUG_MODE') === 'true') {
    error_log('Stripe API sleutel: ' . $stripe_secret_key);
}

// Initialiseer Stripe API indien de library beschikbaar is
if (class_exists('\Stripe\Stripe')) {
    \Stripe\Stripe::setApiKey($stripe_secret_key);
    error_log('Stripe SDK geïnitialiseerd met API sleutel');
} else {
    // Waarschuwing indien Stripe SDK niet beschikbaar is
    error_log("Waarschuwing: Stripe SDK niet gevonden. Gebruik fallback implementatie.");
}

/**
 * Maak een Stripe checkout sessie aan
 * 
 * @param array $sessionData Gegevens voor de sessie (line_items, success_url, cancel_url)
 * @return array Sessie details inclusief ID en URL
 */
function create_stripe_checkout_session($sessionData) {
    // Controleer of we de Stripe SDK hebben of moeten terugvallen op eigen implementatie
    if (class_exists('\Stripe\Stripe')) {
        return create_stripe_checkout_session_sdk($sessionData);
    } else {
        return create_stripe_checkout_session_fallback($sessionData);
    }
}

/**
 * Maak een checkout sessie aan met Stripe SDK
 */
function create_stripe_checkout_session_sdk($sessionData) {
    global $stripe_secret_key;
    
    try {
        // Creëer de checkout sessie via Stripe's SDK
        $session = \Stripe\Checkout\Session::create([
            'mode' => $sessionData['mode'] ?? 'payment',
            'line_items' => $sessionData['line_items'],
            'success_url' => $sessionData['success_url'],
            'cancel_url' => $sessionData['cancel_url'],
            'customer_email' => $sessionData['customer_email'] ?? null,
            'client_reference_id' => $sessionData['client_reference_id'] ?? null,
            'metadata' => $sessionData['metadata'] ?? null
        ]);
        
        // Sla sessiegegevens op in de database
        save_stripe_session_to_database($session);
        
        return [
            'id' => $session->id,
            'url' => $session->url
        ];
    } catch (\Exception $e) {
        error_log('Stripe API fout: ' . $e->getMessage());
        throw new Exception('Fout bij maken van Stripe checkout sessie: ' . $e->getMessage());
    }
}

/**
 * Fallback functie voor het maken van een Stripe checkout sessie via directe API calls
 * (Gebruikt wanneer Stripe SDK niet beschikbaar is)
 */
function create_stripe_checkout_session_fallback($sessionData) {
    global $stripe_secret_key;
    
    $endpoint = 'https://api.stripe.com/v1/checkout/sessions';
    
    // Bereid data voor
    $data = [
        'mode' => $sessionData['mode'] ?? 'payment',
        'success_url' => $sessionData['success_url'],
        'cancel_url' => $sessionData['cancel_url']
    ];
    
    // Voeg line_items toe
    foreach ($sessionData['line_items'] as $index => $item) {
        $data["line_items[$index][price_data][currency]"] = $item['price_data']['currency'];
        $data["line_items[$index][price_data][product_data][name]"] = $item['price_data']['product_data']['name'];
        
        if (isset($item['price_data']['product_data']['description'])) {
            $data["line_items[$index][price_data][product_data][description]"] = $item['price_data']['product_data']['description'];
        }
        
        if (isset($item['price_data']['product_data']['images'])) {
            foreach ($item['price_data']['product_data']['images'] as $imageIndex => $image) {
                $data["line_items[$index][price_data][product_data][images][$imageIndex]"] = $image;
            }
        }
        
        $data["line_items[$index][price_data][unit_amount]"] = $item['price_data']['unit_amount'];
        $data["line_items[$index][quantity]"] = $item['quantity'];
    }
    
    // Voeg optionele velden toe
    if (isset($sessionData['customer_email'])) {
        $data['customer_email'] = $sessionData['customer_email'];
    }
    
    if (isset($sessionData['client_reference_id'])) {
        $data['client_reference_id'] = $sessionData['client_reference_id'];
    }
    
    if (isset($sessionData['metadata'])) {
        foreach ($sessionData['metadata'] as $key => $value) {
            $data["metadata[$key]"] = $value;
        }
    }
    
    // Voer API call uit
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $stripe_secret_key,
            'Content-Type: application/x-www-form-urlencoded'
        ]
    ]);
    
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    
    if ($err) {
        error_log('cURL Error: ' . $err);
        throw new Exception('Fout bij verbinden met Stripe API');
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['error'])) {
        error_log('Stripe API Error: ' . json_encode($result['error']));
        throw new Exception('Stripe API fout: ' . ($result['error']['message'] ?? 'Onbekende fout'));
    }
    
    // Sla sessiegegevens op in de database
    save_stripe_session_to_database($result);
    
    return [
        'id' => $result['id'],
        'url' => $result['url']
    ];
}

/**
 * Controleer de status van een betaling
 * 
 * @param string $sessionId Stripe sessie ID
 * @return array Sessie-informatie
 */
function check_stripe_payment_status($sessionId) {
    // Controleer of we de Stripe SDK hebben of terugvallen op eigen implementatie
    if (class_exists('\Stripe\Stripe')) {
        return check_stripe_payment_status_sdk($sessionId);
    } else {
        return check_stripe_payment_status_fallback($sessionId);
    }
}

/**
 * Controleer betaalstatus met Stripe SDK
 */
function check_stripe_payment_status_sdk($sessionId) {
    try {
        $session = \Stripe\Checkout\Session::retrieve($sessionId);
        return [
            'id' => $session->id,
            'status' => $session->payment_status,
            'amount_total' => $session->amount_total / 100, // Convert van centen naar euro
            'currency' => $session->currency
        ];
    } catch (\Exception $e) {
        error_log('Stripe API fout bij ophalen sessie: ' . $e->getMessage());
        throw new Exception('Fout bij ophalen van betalingsstatus: ' . $e->getMessage());
    }
}

/**
 * Fallback functie voor het controleren van betaalstatus via directe API calls
 */
function check_stripe_payment_status_fallback($sessionId) {
    global $stripe_secret_key;
    
    $endpoint = 'https://api.stripe.com/v1/checkout/sessions/' . urlencode($sessionId);
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $stripe_secret_key
        ]
    ]);
    
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    
    if ($err) {
        error_log('cURL Error: ' . $err);
        throw new Exception('Fout bij verbinden met Stripe API');
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['error'])) {
        error_log('Stripe API Error: ' . json_encode($result['error']));
        throw new Exception('Stripe API fout: ' . ($result['error']['message'] ?? 'Onbekende fout'));
    }
    
    return [
        'id' => $result['id'],
        'status' => $result['payment_status'],
        'amount_total' => $result['amount_total'] / 100, // Convert van centen naar euro
        'currency' => $result['currency']
    ];
}

/**
 * Fallback functie om Stripe sessie op te slaan in een bestand als de database niet beschikbaar is
 * 
 * @param object|array $session De Stripe sessie
 * @return bool Success
 */
function save_stripe_session_to_file($session) {
    // Map maken als deze niet bestaat
    $sessions_dir = SITE_ROOT . '/logs/stripe_sessions';
    if (!file_exists($sessions_dir)) {
        if (!mkdir($sessions_dir, 0755, true)) {
            error_log('Kon geen logs/stripe_sessions directory aanmaken');
            return false;
        }
    }
    
    // Converteer naar array indien nodig
    $sessionData = is_array($session) ? $session : json_decode(json_encode($session), true);
    
    // Bestandsnaam gebaseerd op sessie ID
    $filename = $sessions_dir . '/' . $sessionData['id'] . '.json';
    
    // Sla sessie op als JSON
    if (file_put_contents($filename, json_encode($sessionData, JSON_PRETTY_PRINT))) {
        error_log('Stripe sessie opgeslagen in bestand: ' . $filename);
        return true;
    } else {
        error_log('Kon Stripe sessie niet opslaan in bestand: ' . $filename);
        return false;
    }
}

/**
 * Sla Stripe sessie op in de database
 * 
 * @param object|array $session De Stripe sessie
 * @return bool Success
 */
function save_stripe_session_to_database($session) {
    // Als er geen PDO-verbinding is, gebruik dan de bestandsfallback
    if (!isset($GLOBALS['pdo']) || !$GLOBALS['pdo']) {
        error_log('Geen databaseverbinding beschikbaar voor het opslaan van de Stripe sessie, valt terug op bestandsopslag');
        return save_stripe_session_to_file($session);
    }
    
    global $pdo;
    
    // Controleer of de tabel bestaat
    try {
        $check_table = $pdo->query("SHOW TABLES LIKE 'stripe_sessions'");
        if ($check_table->rowCount() == 0) {
            // Tabel bestaat niet, val terug op bestandsopslag
            error_log('Stripe sessions tabel bestaat niet, valt terug op bestandsopslag');
            return save_stripe_session_to_file($session);
        }
    } catch (Exception $e) {
        error_log('Kon niet controleren of stripe_sessions tabel bestaat: ' . $e->getMessage());
        return save_stripe_session_to_file($session);
    }
    
    // Converteer van Stripe object naar array indien nodig
    $sessionData = is_array($session) ? $session : json_decode(json_encode($session), true);
    
    try {
        // Bereid de query voor
        $query = "INSERT INTO stripe_sessions (
                    session_id, 
                    user_id, 
                    amount_total, 
                    currency, 
                    payment_status, 
                    status, 
                    created_at, 
                    metadata
                ) VALUES (
                    :session_id, 
                    :user_id, 
                    :amount_total, 
                    :currency, 
                    :payment_status, 
                    :status, 
                    NOW(), 
                    :metadata
                )";
        
        // Haal user_id uit de session metadata of client_reference_id
        $userId = null;
        if (isset($sessionData['client_reference_id']) && strpos($sessionData['client_reference_id'], 'user_') === 0) {
            $userId = substr($sessionData['client_reference_id'], 5);
        }
        
        // Bereid metadata voor
        $metadata = json_encode($sessionData['metadata'] ?? []);
        
        // Voer de query uit
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':session_id' => $sessionData['id'],
            ':user_id' => $userId,
            ':amount_total' => ($sessionData['amount_total'] ?? 0) / 100, // Van centen naar euro
            ':currency' => $sessionData['currency'] ?? 'eur',
            ':payment_status' => $sessionData['payment_status'] ?? 'unpaid',
            ':status' => $sessionData['status'] ?? 'open',
            ':metadata' => $metadata
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log('Database fout bij opslaan Stripe sessie: ' . $e->getMessage());
        // Als de database opslag mislukt, val terug op bestandsopslag
        return save_stripe_session_to_file($session);
    }
}

/**
 * Update betaalstatus van een sessie opgeslagen in een bestand
 * 
 * @param string $sessionId De Stripe sessie ID
 * @param string $status De nieuwe status
 * @return bool Success
 */
function update_stripe_session_in_file($sessionId, $status) {
    $sessions_dir = SITE_ROOT . '/logs/stripe_sessions';
    $filename = $sessions_dir . '/' . $sessionId . '.json';
    
    if (!file_exists($filename)) {
        error_log('Kon sessiebestand niet vinden om te updaten: ' . $filename);
        return false;
    }
    
    try {
        $sessionData = json_decode(file_get_contents($filename), true);
        if (!$sessionData) {
            error_log('Kon sessiegegevens niet lezen uit bestand: ' . $filename);
            return false;
        }
        
        // Update de status
        $sessionData['payment_status'] = $status;
        $sessionData['updated_at'] = date('Y-m-d H:i:s');
        
        // Sla de bijgewerkte gegevens op
        if (file_put_contents($filename, json_encode($sessionData, JSON_PRETTY_PRINT))) {
            error_log('Stripe sessie status bijgewerkt in bestand: ' . $filename);
            return true;
        } else {
            error_log('Kon Stripe sessie status niet bijwerken in bestand: ' . $filename);
            return false;
        }
    } catch (Exception $e) {
        error_log('Fout bij updaten van sessie in bestand: ' . $e->getMessage());
        return false;
    }
}

/**
 * Update betaalstatus van een sessie in de database
 * 
 * @param string $sessionId De Stripe sessie ID
 * @param string $status De nieuwe status
 * @return bool Success
 */
function update_stripe_session_status($sessionId, $status) {
    // Als er geen PDO-verbinding is, gebruik dan de bestandsfallback
    if (!isset($GLOBALS['pdo']) || !$GLOBALS['pdo']) {
        error_log('Geen databaseverbinding beschikbaar voor het updaten van de Stripe sessie, valt terug op bestandsupdate');
        return update_stripe_session_in_file($sessionId, $status);
    }
    
    global $pdo;
    
    try {
        // Controleer of de tabel bestaat
        $check_table = $pdo->query("SHOW TABLES LIKE 'stripe_sessions'");
        if ($check_table->rowCount() == 0) {
            // Tabel bestaat niet, val terug op bestandsupdate
            error_log('Stripe sessions tabel bestaat niet, valt terug op bestandsupdate');
            return update_stripe_session_in_file($sessionId, $status);
        }
        
        $query = "UPDATE stripe_sessions 
                  SET payment_status = :status, 
                      updated_at = NOW() 
                  WHERE session_id = :session_id";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':status' => $status,
            ':session_id' => $sessionId
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log('Database fout bij updaten Stripe sessie: ' . $e->getMessage());
        // Als database update mislukt, probeer bestandsupdate
        return update_stripe_session_in_file($sessionId, $status);
    }
}

/**
 * Verwerk Stripe webhook events
 * 
 * @param string $payload De webhook payload
 * @param string $sigHeader De Stripe-Signature header
 * @return array Resultaat van de verwerking
 */
function handle_stripe_webhook($payload, $sigHeader) {
    global $stripe_webhook_secret;
    
    // Debug logging
    error_log('Verwerken webhook met secret: ' . $stripe_webhook_secret);
    
    // Voer een controle uit op het webhook secret
    if (empty($stripe_webhook_secret) || $stripe_webhook_secret === 'whsec_12345') {
        error_log('Waarschuwing: Webhook secret is leeg of een placeholder. Stel een correct webhook secret in');
    }
    
    // Controleer of we de Stripe SDK hebben of terugvallen op eigen implementatie
    if (class_exists('\Stripe\Stripe')) {
        return handle_stripe_webhook_sdk($payload, $sigHeader);
    } else {
        // Fallback implementatie - in dit geval accepteren we gewoon de webhook
        // Echte implementatie zou handmatige JSON parsing en verificatie vereisen
        $data = json_decode($payload, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Ongeldige JSON in webhook payload: ' . json_last_error_msg());
            throw new Exception('Ongeldige webhook payload: JSON kon niet worden geparsed');
        }
        
        if (!isset($data['type'])) {
            error_log('Webhook payload mist eventtype');
            throw new Exception('Ongeldige webhook payload: type veld ontbreekt');
        }
        
        error_log('Webhook fallback verwerking voor type: ' . $data['type']);
        return handle_webhook_event($data);
    }
}

/**
 * Verwerk webhook met Stripe SDK
 */
function handle_stripe_webhook_sdk($payload, $sigHeader) {
    global $stripe_webhook_secret;
    
    try {
        error_log('Poging om Stripe webhook te verwerken via SDK');
        
        // Controleer of signin header aanwezig is
        if (empty($sigHeader)) {
            error_log('Stripe-Signature header ontbreekt in webhook request');
            throw new Exception('Stripe-Signature header ontbreekt in webhook request');
        }
        
        $event = \Stripe\Webhook::constructEvent(
            $payload, $sigHeader, $stripe_webhook_secret
        );
        
        error_log('Webhook geverifieerd. Event type: ' . $event->type);
        return handle_webhook_event($event);
    } catch (\Stripe\Exception\SignatureVerificationException $e) {
        error_log('Stripe webhook handtekening verificatie mislukt: ' . $e->getMessage());
        throw new Exception('Webhook handtekening verificatie mislukt: ' . $e->getMessage());
    } catch (\Exception $e) {
        error_log('Stripe webhook fout: ' . $e->getMessage());
        throw new Exception('Webhook fout: ' . $e->getMessage());
    }
}

/**
 * Verwerk webhook event
 */
function handle_webhook_event($event) {
    // Converteer van Stripe object naar array indien nodig
    $eventData = is_array($event) ? $event : json_decode(json_encode($event), true);
    
    $type = $eventData['type'];
    $object = $eventData['data']['object'];
    
    error_log('Webhook event verwerking voor type: ' . $type);
    
    // Log de object data voor debugging
    error_log('Webhook object data: ' . json_encode($object));
    
    switch ($type) {
        case 'checkout.session.completed':
            error_log('Checkout sessie voltooid: sessie ID ' . $object['id']);
            update_stripe_session_status($object['id'], 'paid');
            return ['success' => true, 'message' => 'Checkout sessie voltooid', 'session_id' => $object['id']];
            
        case 'payment_intent.succeeded':
            // Update voor integraties die PaymentIntents gebruiken
            if (isset($object['metadata']['checkout_session_id'])) {
                $session_id = $object['metadata']['checkout_session_id'];
                error_log('Betaling succesvol voor sessie: ' . $session_id);
                update_stripe_session_status($session_id, 'paid');
                return ['success' => true, 'message' => 'Betaling succesvol', 'session_id' => $session_id];
            }
            return ['success' => true, 'message' => 'Betaling succesvol, geen sessie ID gevonden'];
            
        case 'payment_intent.payment_failed':
            // Update voor integraties die PaymentIntents gebruiken
            if (isset($object['metadata']['checkout_session_id'])) {
                $session_id = $object['metadata']['checkout_session_id'];
                error_log('Betaling mislukt voor sessie: ' . $session_id);
                update_stripe_session_status($session_id, 'failed');
                return ['success' => true, 'message' => 'Betaling mislukt', 'session_id' => $session_id];
            }
            return ['success' => true, 'message' => 'Betaling mislukt, geen sessie ID gevonden'];
            
        default:
            error_log('Onbekend webhook event type: ' . $type);
            return ['success' => true, 'message' => 'Event ontvangen maar niet verwerkt: ' . $type];
    }
} 