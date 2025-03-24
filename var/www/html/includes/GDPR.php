<?php
/**
 * GDPR Class
 * Beheert AVG/GDPR compliance functies
 */
class GDPR {
    private $db;
    private static $instance = null;
    
    /**
     * Private constructor voor Singleton pattern
     */
    private function __construct() {
        // Laad database verbinding
        require_once 'Database.php';
        $this->db = Database::getInstance();
    }
    
    /**
     * Get GDPR instance (Singleton pattern)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Sla toestemming op van de gebruiker
     */
    public function recordConsent($userId, $type, $details = '') {
        $consentData = [
            'user_id' => $userId,
            'consent_type' => $type,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->insert('user_consents', $consentData);
    }
    
    /**
     * Controleer of de gebruiker toestemming heeft gegeven
     */
    public function hasConsent($userId, $type) {
        $consent = $this->db->getRow(
            "SELECT id FROM user_consents WHERE user_id = ? AND consent_type = ? ORDER BY timestamp DESC LIMIT 1",
            [$userId, $type]
        );
        
        return !empty($consent);
    }
    
    /**
     * Trek toestemming in
     */
    public function revokeConsent($userId, $type) {
        return $this->db->update(
            'user_consents',
            ['revoked' => 1, 'revoked_at' => date('Y-m-d H:i:s')],
            'user_id = ? AND consent_type = ?',
            [$userId, $type]
        );
    }
    
    /**
     * Exporteer gebruikersgegevens (recht op inzage)
     */
    public function exportUserData($userId) {
        $userData = [];
        
        // Basisgegevens van de gebruiker
        $userData['profile'] = $this->db->getRow(
            "SELECT id, name, email, created_at, last_login FROM users WHERE id = ?",
            [$userId]
        );
        
        // Toestemmingsgegevens
        $userData['consents'] = $this->db->getRows(
            "SELECT consent_type, details, timestamp, revoked, revoked_at FROM user_consents WHERE user_id = ?",
            [$userId]
        );
        
        // Inloggeschiedenis
        $userData['login_history'] = $this->db->getRows(
            "SELECT ip_address, user_agent, created_at, success FROM auth_log WHERE email = ? ORDER BY created_at DESC LIMIT 50",
            [$userData['profile']['email']]
        );
        
        // Activiteiten (voorbeeld - pas aan naar je specifieke tabellen)
        $userData['activities'] = $this->db->getRows(
            "SELECT action, details, created_at FROM user_activities WHERE user_id = ? ORDER BY created_at DESC LIMIT 100",
            [$userId]
        );
        
        return $userData;
    }
    
    /**
     * Verwijder gebruiker en alle gegevens (recht op vergetelheid)
     */
    public function deleteUserData($userId) {
        try {
            $this->db->beginTransaction();
            
            // Haal gebruikersemail op voor het verwijderen van gerelateerde gegevens
            $user = $this->db->getRow("SELECT email FROM users WHERE id = ?", [$userId]);
            
            if (!$user) {
                throw new Exception("Gebruiker niet gevonden");
            }
            
            // Verwijder gerelateerde gegevens (pas aan naar je specifieke tabellen)
            $this->db->delete('user_consents', 'user_id = ?', [$userId]);
            $this->db->delete('user_preferences', 'user_id = ?', [$userId]);
            $this->db->delete('user_activities', 'user_id = ?', [$userId]);
            
            // Anonimiseer auth logs
            $this->db->update(
                'auth_log',
                ['email' => 'geanonimiseerd', 'ip_address' => 'geanonimiseerd', 'user_agent' => 'geanonimiseerd'],
                'email = ?',
                [$user['email']]
            );
            
            // Verwijder of anonimiseer de gebruiker zelf
            $this->db->update(
                'users',
                [
                    'email' => 'verwijderd_' . bin2hex(random_bytes(8)) . '@geanonimiseerd.com',
                    'name' => 'Verwijderde gebruiker',
                    'password' => null,
                    'active' => 0,
                    'deleted' => 1,
                    'deleted_at' => date('Y-m-d H:i:s')
                ],
                'id = ?',
                [$userId]
            );
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            $this->logError('GDPR delete failure: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Genereer een privacyverklaring
     */
    public function getPrivacyStatement() {
        // Dit zou eigenlijk opgehaald moeten worden uit een database of CMS
        // Dit is slechts een voorbeeld
        return [
            'title' => 'Privacyverklaring',
            'last_updated' => '2023-03-15',
            'content' => '<p>Wij respecteren uw privacy en zetten ons in om uw persoonlijke gegevens te beschermen.</p>
                         <p>Deze privacyverklaring informeert u over hoe wij omgaan met uw persoonlijke gegevens wanneer u onze website bezoekt en vertelt u over uw privacyrechten.</p>
                         <h3>1. Welke gegevens we verzamelen</h3>
                         <p>We verzamelen en verwerken de volgende categorieën persoonlijke informatie:</p>
                         <ul>
                             <li>Identiteitsgegevens: naam, gebruikersnaam of identificator, geboortedatum</li>
                             <li>Contactgegevens: e-mailadres, telefoonnummer, adres</li>
                             <li>Technische gegevens: IP-adres, browsergegevens, locatiegegevens, besturingssysteem</li>
                         </ul>
                         <h3>2. Hoe we uw gegevens gebruiken</h3>
                         <p>We gebruiken uw persoonlijke gegevens alleen voor het doel waarvoor ze zijn verzameld, waaronder:</p>
                         <ul>
                             <li>Om u de diensten te leveren die u hebt aangevraagd</li>
                             <li>Om u te informeren over wijzigingen in onze diensten</li>
                             <li>Om onze website en diensten te verbeteren</li>
                         </ul>
                         <h3>3. Uw rechten</h3>
                         <p>Onder de AVG/GDPR heeft u verschillende rechten met betrekking tot uw persoonlijke gegevens, waaronder:</p>
                         <ul>
                             <li>Recht op inzage</li>
                             <li>Recht op rectificatie</li>
                             <li>Recht op vergetelheid</li>
                             <li>Recht op beperking van verwerking</li>
                             <li>Recht op dataportabiliteit</li>
                             <li>Recht van bezwaar</li>
                         </ul>'
        ];
    }
    
    /**
     * Genereer een cookieverklaring
     */
    public function getCookieStatement() {
        // Dit zou eigenlijk opgehaald moeten worden uit een database of CMS
        // Dit is slechts een voorbeeld
        return [
            'title' => 'Cookieverklaring',
            'last_updated' => '2023-03-15',
            'content' => '<p>Deze website maakt gebruik van cookies om uw ervaring te verbeteren terwijl u door de website navigeert.</p>
                         <h3>Wat zijn cookies?</h3>
                         <p>Cookies zijn kleine tekstbestanden die op uw apparaat worden opgeslagen wanneer u een website bezoekt.</p>
                         <h3>Welke cookies gebruiken we?</h3>
                         <p>We gebruiken de volgende typen cookies:</p>
                         <ul>
                             <li><strong>Noodzakelijke cookies:</strong> Deze cookies zijn essentieel voor het functioneren van de website.</li>
                             <li><strong>Voorkeurscookies:</strong> Deze cookies onthouden uw voorkeuren zodat we functies kunnen personaliseren.</li>
                             <li><strong>Analytische cookies:</strong> Deze cookies helpen ons te begrijpen hoe bezoekers onze website gebruiken.</li>
                         </ul>
                         <h3>Uw cookie-instellingen beheren</h3>
                         <p>U kunt uw browser instellen om cookies te weigeren, of om u te waarschuwen wanneer websites cookies proberen te plaatsen.</p>'
        ];
    }
    
    /**
     * Log GDPR gerelateerde fouten
     */
    private function logError($message) {
        if (defined('LOG_PATH')) {
            $logFile = LOG_PATH . 'gdpr_errors.log';
            $timestamp = date('Y-m-d H:i:s');
            $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
            
            // Maak de log map aan als deze niet bestaat
            if (!is_dir(LOG_PATH)) {
                mkdir(LOG_PATH, 0755, true);
            }
            
            // Schrijf naar het log bestand
            file_put_contents($logFile, $logMessage, FILE_APPEND);
        }
    }
}
?> 