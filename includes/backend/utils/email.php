<?php
/**
 * Email utility voor SlimmerMetAI
 * Zorgt voor het versturen van emails via SMTP of PHP mail()
 */

require_once dirname(dirname(__FILE__)) . '/config/db.php';

/**
 * Verstuurt een email via SMTP of PHP mail()
 * 
 * @param string $to Ontvanger email
 * @param string $subject Onderwerp
 * @param string $body HTML inhoud
 * @param array $options Extra opties zoals cc, bcc, replyTo
 * @return bool True bij succes, false bij fout
 */
function sendEmail($to, $subject, $body, $options = []) {
    // Haal SMTP instellingen uit .env
    $smtpHost = getEnv('SMTP_HOST');
    $smtpPort = getEnv('SMTP_PORT');
    $smtpUser = getEnv('SMTP_USER');
    $smtpPass = getEnv('SMTP_PASSWORD');
    $fromEmail = getEnv('EMAIL_FROM', 'noreply@slimmermetai.com');
    
    // Controleer of we SMTP moeten gebruiken
    $useSmtp = !empty($smtpHost) && !empty($smtpPort) && !empty($smtpUser) && !empty($smtpPass);
    
    // Bereid headers voor
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = 'From: ' . $fromEmail;
    
    // Voeg extra headers toe indien opgegeven
    if (!empty($options['cc'])) {
        $headers[] = 'Cc: ' . $options['cc'];
    }
    
    if (!empty($options['bcc'])) {
        $headers[] = 'Bcc: ' . $options['bcc'];
    }
    
    if (!empty($options['replyTo'])) {
        $headers[] = 'Reply-To: ' . $options['replyTo'];
    }
    
    // Combineeer headers
    $headersString = implode("\r\n", $headers);
    
    // Probeer email te versturen
    try {
        if ($useSmtp) {
            return sendSmtpEmail($to, $subject, $body, $headersString, $smtpHost, $smtpPort, $smtpUser, $smtpPass);
        } else {
            return mail($to, $subject, $body, $headersString);
        }
    } catch (Exception $e) {
        error_log('Error sending email: ' . $e->getMessage());
        return false;
    }
}

/**
 * Verstuurt een email via SMTP
 * Eenvoudige implementatie zonder extra libraries
 * 
 * @param string $to Ontvanger email
 * @param string $subject Onderwerp
 * @param string $body HTML inhoud
 * @param string $headers Email headers
 * @param string $host SMTP host
 * @param int $port SMTP port
 * @param string $username SMTP username
 * @param string $password SMTP password
 * @return bool True bij succes, false bij fout
 */
function sendSmtpEmail($to, $subject, $body, $headers, $host, $port, $username, $password) {
    // In een productie-omgeving zou je hier een volledige SMTP implementatie gebruiken
    // zoals PHPMailer of Swift Mailer. Dit is een simpele simulatie.
    
    error_log("[SMTP] Would send email to $to with subject: $subject");
    error_log("[SMTP] Using SMTP: $host:$port");
    
    // Voor nu gebruiken we alsnog mail() als fallback
    return mail($to, $subject, $body, $headers);
}

/**
 * Verstuurt een welkomst email aan een nieuwe gebruiker
 * 
 * @param string $to Ontvanger email
 * @param string $name Naam van de gebruiker
 * @param string $verificationUrl URL voor email verificatie
 * @return bool True bij succes, false bij fout
 */
function sendWelcomeEmail($to, $name, $verificationUrl) {
    $subject = 'Welkom bij SlimmerMetAI!';
    
    $body = '
    <html>
    <head>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
            }
            .header {
                background-color: #4A6CF7;
                color: white;
                padding: 20px;
                text-align: center;
            }
            .content {
                padding: 20px;
                background-color: #f9f9f9;
            }
            .button {
                display: inline-block;
                padding: 10px 20px;
                background-color: #4A6CF7;
                color: white;
                text-decoration: none;
                border-radius: 4px;
                margin-top: 20px;
            }
            .footer {
                text-align: center;
                margin-top: 20px;
                font-size: 12px;
                color: #666;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Welkom bij SlimmerMetAI</h1>
            </div>
            <div class="content">
                <p>Beste ' . htmlspecialchars($name) . ',</p>
                <p>Bedankt voor je registratie bij SlimmerMetAI! We zijn blij dat je erbij bent.</p>
                <p>Om je account te activeren en toegang te krijgen tot al onze diensten, moet je je e-mailadres verifiëren.</p>
                <p><a href="' . htmlspecialchars($verificationUrl) . '" class="button">E-mail Verifiëren</a></p>
                <p>Of kopieer en plak deze link in je browser:</p>
                <p>' . htmlspecialchars($verificationUrl) . '</p>
                <p>Als je vragen hebt, kun je altijd contact met ons opnemen door te reageren op deze e-mail.</p>
                <p>Met vriendelijke groet,<br>Het SlimmerMetAI Team</p>
            </div>
            <div class="footer">
                <p>© ' . date('Y') . ' SlimmerMetAI. Alle rechten voorbehouden.</p>
                <p>Je ontvangt deze e-mail omdat je je hebt geregistreerd op onze website.</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    return sendEmail($to, $subject, $body);
}

/**
 * Verstuurt een wachtwoord reset email
 * 
 * @param string $to Ontvanger email
 * @param string $name Naam van de gebruiker
 * @param string $resetUrl URL voor wachtwoord reset
 * @return bool True bij succes, false bij fout
 */
function sendPasswordResetEmail($to, $name, $resetUrl) {
    $subject = 'Wachtwoord Reset Instructies - SlimmerMetAI';
    
    $body = '
    <html>
    <head>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
            }
            .header {
                background-color: #4A6CF7;
                color: white;
                padding: 20px;
                text-align: center;
            }
            .content {
                padding: 20px;
                background-color: #f9f9f9;
            }
            .button {
                display: inline-block;
                padding: 10px 20px;
                background-color: #4A6CF7;
                color: white;
                text-decoration: none;
                border-radius: 4px;
                margin-top: 20px;
            }
            .footer {
                text-align: center;
                margin-top: 20px;
                font-size: 12px;
                color: #666;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Wachtwoord Reset</h1>
            </div>
            <div class="content">
                <p>Beste ' . htmlspecialchars($name) . ',</p>
                <p>We hebben een verzoek ontvangen om je wachtwoord te resetten. Klik op de onderstaande knop om een nieuw wachtwoord in te stellen:</p>
                <p><a href="' . htmlspecialchars($resetUrl) . '" class="button">Wachtwoord Resetten</a></p>
                <p>Of kopieer en plak deze link in je browser:</p>
                <p>' . htmlspecialchars($resetUrl) . '</p>
                <p>Als je geen wachtwoord reset hebt aangevraagd, kun je deze e-mail veilig negeren.</p>
                <p>Deze link is geldig voor 24 uur en kan maar één keer worden gebruikt.</p>
                <p>Met vriendelijke groet,<br>Het SlimmerMetAI Team</p>
            </div>
            <div class="footer">
                <p>© ' . date('Y') . ' SlimmerMetAI. Alle rechten voorbehouden.</p>
                <p>Je ontvangt deze e-mail omdat iemand een wachtwoord reset heeft aangevraagd voor je account.</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    return sendEmail($to, $subject, $body);
}

/**
 * Verstuurt een notificatie email
 * 
 * @param string $to Ontvanger email
 * @param string $name Naam van de gebruiker
 * @param string $subject Email onderwerp
 * @param string $message Email bericht
 * @param string $buttonText Knoptekst (optioneel)
 * @param string $buttonUrl Knop URL (optioneel)
 * @return bool True bij succes, false bij fout
 */
function sendNotificationEmail($to, $name, $subject, $message, $buttonText = '', $buttonUrl = '') {
    $buttonHtml = '';
    if (!empty($buttonText) && !empty($buttonUrl)) {
        $buttonHtml = '
        <p><a href="' . htmlspecialchars($buttonUrl) . '" class="button">' . htmlspecialchars($buttonText) . '</a></p>
        <p>Of kopieer en plak deze link in je browser:</p>
        <p>' . htmlspecialchars($buttonUrl) . '</p>
        ';
    }
    
    $body = '
    <html>
    <head>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
            }
            .header {
                background-color: #4A6CF7;
                color: white;
                padding: 20px;
                text-align: center;
            }
            .content {
                padding: 20px;
                background-color: #f9f9f9;
            }
            .button {
                display: inline-block;
                padding: 10px 20px;
                background-color: #4A6CF7;
                color: white;
                text-decoration: none;
                border-radius: 4px;
                margin-top: 20px;
            }
            .footer {
                text-align: center;
                margin-top: 20px;
                font-size: 12px;
                color: #666;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>' . htmlspecialchars($subject) . '</h1>
            </div>
            <div class="content">
                <p>Beste ' . htmlspecialchars($name) . ',</p>
                <p>' . $message . '</p>
                ' . $buttonHtml . '
                <p>Met vriendelijke groet,<br>Het SlimmerMetAI Team</p>
            </div>
            <div class="footer">
                <p>© ' . date('Y') . ' SlimmerMetAI. Alle rechten voorbehouden.</p>
                <p>Je ontvangt deze e-mail omdat je je hebt aangemeld voor notificaties.</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    return sendEmail($to, $subject, $body);
}
?> 