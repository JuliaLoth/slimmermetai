<<<<<<< HEAD
# SlimmerMetAI.com Website

Deze repository bevat de code voor de SlimmerMetAI.com website, inclusief de frontend en PHP API voor hosting op Antagonist.

## Mapstructuur

```
domains/slimmermetai.com/
├── database/             # Database scripts en migraties
│   └── setup.sql         # Database installatiebestand
├── public_html/          # Publiek toegankelijke bestanden (DocumentRoot)
│   ├── api/              # PHP API endpoints
│   │   ├── auth/         # Authenticatie endpoints
│   │   ├── users/        # Gebruiker gerelateerde endpoints
│   │   ├── config.php    # API configuratiebestand
│   │   └── index.php     # API index
│   ├── assets/           # Statische bestanden (CSS, JS, afbeeldingen)
│   ├── install.php       # Database installatiescript (verwijderen na gebruik!)
│   └── index.php         # Website hoofdpagina
└── uploads/              # Gebruikersuploads (buiten DocumentRoot voor veiligheid)
    └── profile_pictures/ # Profielfoto's
```

## Installatiestappen

### 1. Voorbereiding

1. Upload alle bestanden naar je webhosting via FTP
2. Zorg dat je beschikt over de volgende informatie:
   - MySQL database naam, gebruiker en wachtwoord
   - Domeinnaam waar de site gehost zal worden
   - SMTP server gegevens voor e-mailverificatie (optioneel)

### 2. Configuratie

1. Bewerk het bestand `public_html/api/config.php` en vul de juiste waarden in:
   - Database gegevens
   - Site URL en naam
   - SMTP server gegevens (optioneel)
   - JWT geheime sleutel (wijzig deze!)
   - reCAPTCHA sleutels (optioneel)

2. Zorg ervoor dat de volgende mappen schrijfrechten hebben (chmod 755):
   - `uploads/`
   - `uploads/profile_pictures/`

### 3. Database installatie

1. Open je browser en navigeer naar `https://jouwdomein.nl/install.php?key=slimmermetai_installatie_2023`
2. Volg de instructies op het scherm om de database te installeren
3. **BELANGRIJK**: Verwijder het bestand `install.php` na gebruik!

### 4. Admin toegang

Na installatie kun je inloggen met de volgende gegevens:

- E-mail: admin@slimmermetai.com
- Wachtwoord: Admin123!

**BELANGRIJK**: Wijzig dit wachtwoord direct na je eerste inlog!

## Beveiligingsinstellingen

Om de veiligheid van je site te waarborgen:

1. Zorg dat `debug_mode` op `false` staat in productie
2. Zorg dat de JWT geheime sleutel sterk en uniek is
3. Gebruik HTTPS voor alle verkeer
4. Zorg dat `uploads/` niet direct toegankelijk is via het web
5. Gebruik de ingebouwde CSRF-beveiliging voor alle formulieren
6. Activeer reCAPTCHA voor registratie en wachtwoord reset formulieren

## Caching

De API gebruikt standaard HTTP caching headers. Voor optimale prestaties:

1. Activeer gzip compressie in je webserver
2. Stel lange cache-tijden in voor statische assets
3. Overweeg een CDN voor wereldwijde prestaties

## Problemen oplossen

### API werkt niet

1. Controleer of mod_rewrite is ingeschakeld
2. Controleer of de .htaccess bestanden correct zijn geüpload
3. Controleer database inloggegevens in config.php

### E-mails worden niet verzonden

1. Controleer de SMTP instellingen in config.php
2. Controleer of je hosting PHP mail() toestaat of gebruik SMTP

### Bestandsuploads werken niet

1. Controleer of de uploads mappen de juiste schrijfrechten hebben
2. Controleer de upload_max_filesize instelling in PHP

## Stripe Integratie

De website bevat nu een volledige integratie met Stripe voor het verwerken van betalingen. Hieronder staat hoe je dit kunt gebruiken:

### Benodigde bestanden

1. **StripeHelper.php**: Een helper klasse die alle Stripe functionaliteit vereenvoudigt
2. **betalen.php**: Voorbeeld betalingspagina met Stripe Elements
3. **betaling-voltooid.php**: Pagina voor het afhandelen van voltooide betalingen
4. **stripe-webhook.php**: Handler voor Stripe webhooks

### Configuratie

De Stripe integratie gebruikt de volgende configuratiewaarden in het `.env` bestand:

```
STRIPE_SECRET_KEY=sk_test_...
STRIPE_PUBLIC_KEY=pk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_PRICE_PREMIUM=price_...
STRIPE_PRICE_BASIC=price_...
```

### Webhook instellen

Om de Stripe webhook te laten werken:

1. Log in op je Stripe dashboard
2. Ga naar Ontwikkelaars > Webhooks
3. Voeg een nieuwe webhook endpoint toe: `https://slimmermetai.com/stripe-webhook.php`
4. Selecteer de events die je wilt ontvangen (bijvoorbeeld: `payment_intent.succeeded`, `payment_intent.payment_failed`)
5. Kopieer de Signing Secret naar je `.env` bestand

### Voorbeeld gebruik

```php
// Inclusief de autoloader
require_once 'vendor/autoload.php';

// Gebruik de StripeHelper
$stripeHelper = new \SlimmerMetAI\StripeHelper();

// Maak een betaling aan
$intent = $stripeHelper->createPaymentIntent(49.95, "Premium Abonnement");
$clientSecret = $intent->client_secret;

// Gebruiken in je front-end
// Zie de betalen.php voor een volledig voorbeeld
```

## Licentie

Copyright © 2023 SlimmerMetAI.com. Alle rechten voorbehouden. 
=======
# slimmermetai.com
website
>>>>>>> 42f5d4acb36f48d16a32e8200a34a44170a87d36
