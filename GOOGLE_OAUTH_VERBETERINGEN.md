# Google OAuth API Verbeteringen - SlimmerMetAI.com

Dit document beschrijft de verbeteringen die zijn doorgevoerd in de Google OAuth implementatie voor de SlimmerMetAI.com website.

## Doorgevoerde Verbeteringen

De volgende verbeteringen zijn geïmplementeerd om de Google OAuth integratie veiliger, robuuster en toekomstbestendiger te maken:

### 1. Beveiligingsverbeteringen

- **Verplaatste gevoelige gegevens naar .env bestand**:
  - Google Client Secret staat nu in .env in plaats van in config.php
  - Configuratie voor verschillende omgevingen (productie/ontwikkeling)

- **Geïmplementeerde PKCE (Proof Key for Code Exchange)**:
  - Beschermt tegen onderschepping van de autorisatiecode
  - Verbeterde beveiliging voor de gehele OAuth-flow

- **Verbeterde HTTP headers voor beveiliging**:
  - Strict-Transport-Security
  - X-Content-Type-Options
  - X-Frame-Options
  - Referrer-Policy

- **Verbeterde CSRF-bescherming**:
  - State parameter heeft nu een time-to-live (10 minuten)
  - Betere validatie van de state parameter

### 2. Architectuurverbeteringen

- **Nieuwe GoogleAuthService klasse**:
  - Centraliseert alle Google OAuth functionaliteit
  - Betere scheiding van verantwoordelijkheden
  - Eenvoudiger onderhoud en testen

- **Implementatie van juiste HTTP client**:
  - Vervanging van file_get_contents() door cURL
  - Betere foutafhandeling en foutrapportage

- **Database tabellen voor token management**:
  - oauth_tokens tabel voor het opslaan van Google tokens
  - login_attempts tabel voor het bijhouden van inlogpogingen

### 3. Functionaliteitsverbeteringen

- **Token refresh mechanisme**:
  - Mogelijkheid om Google tokens te vernieuwen wanneer ze verlopen
  - Opslaan van refresh tokens voor langdurige toegang

- **Betere gebruikerservaring**:
  - Ondersteuning voor redirects na succesvolle login
  - Duidelijkere foutmeldingen

- **Incrementele autorisatie**:
  - Vraagt alleen om de specifieke rechten die nodig zijn
  - Betere compliance met Google's OAuth best practices

### 4. Code Kwaliteit

- **Verbeterde foutafhandeling**:
  - Gedetailleerde logging
  - Gebruikersvriendelijke foutmeldingen
  - Consistente error responses

- **Betere code organisatie**:
  - Herbruikbare functies
  - Duidelijke documentatie
  - Logische structuur

## Database Wijzigingen

De volgende database tabellen zijn toegevoegd/bijgewerkt:

1. **oauth_tokens** - Voor het opslaan van Google OAuth tokens:
   ```sql
   CREATE TABLE IF NOT EXISTS oauth_tokens (
       id INT AUTO_INCREMENT PRIMARY KEY,
       user_id INT NOT NULL,
       provider VARCHAR(32) NOT NULL,
       refresh_token TEXT,
       access_token TEXT,
       expires_at DATETIME,
       created_at DATETIME NOT NULL,
       updated_at DATETIME NOT NULL,
       FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
       INDEX (user_id, provider)
   );
   ```

2. **login_attempts** - Voor het bijhouden van inlogpogingen:
   ```sql
   CREATE TABLE IF NOT EXISTS login_attempts (
       id INT AUTO_INCREMENT PRIMARY KEY,
       email VARCHAR(255) NOT NULL,
       ip_address VARCHAR(45) NOT NULL,
       user_agent TEXT,
       success TINYINT(1) NOT NULL DEFAULT 0,
       created_at DATETIME NOT NULL,
       INDEX (email, success)
   );
   ```

## Configuratie

De .env configuratie is bijgewerkt en bevat nu de volgende Google-gerelateerde variabelen:

```
# Google OAuth Configuratie (Productie)
GOOGLE_CLIENT_ID=625906341722-2eohq5a55sl4a8511h6s20dbsicuknku.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=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

# Google OAuth Configuratie (Development)
GOOGLE_CLIENT_ID_DEV=your-development-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET_DEV=your-development-client-secret
```

## Gewijzigde Bestanden

1. **api/helpers/GoogleAuthService.php** (NIEUW)
   - Nieuwe service klasse die alle Google OAuth functionaliteit centraliseert

2. **api/config.php** (GEWIJZIGD)
   - Verbeterde configuratie die gevoelige gegevens uit de code haalt
   - Toevoeging van veilige HTTP helper functie
   - Verbeterde CSRF-bescherming met timeout

3. **.env** (GEWIJZIGD)
   - Toevoeging van Google OAuth configuratie
   - Ondersteuning voor verschillende omgevingen

4. **api/auth/google.php** (GEWIJZIGD)
   - Gebruikt nu de GoogleAuthService
   - Implementeert PKCE voor betere beveiliging

5. **api/auth/google-callback.php** (GEWIJZIGD)
   - Gebruikt nu de GoogleAuthService
   - Betere foutafhandeling en logging

6. **api/auth/google-token.php** (GEWIJZIGD)
   - Gebruikt nu de GoogleAuthService
   - Verbeterde validatie van tokens

## Testen

Om te controleren of de implementatie correct werkt:

1. Probeer in te loggen met een Google-account
2. Controleer of nieuwe gebruikers correct worden geregistreerd
3. Controleer of bestaande gebruikers correct worden ingelogd
4. Controleer de database tabellen (oauth_tokens en login_attempts)
5. Test de token refresh functionaliteit
6. Test wat er gebeurt als je de autorisatie annuleert

## Wat Te Doen Bij Problemen

Als er problemen optreden met de nieuwe implementatie:

1. Controleer de error logs (apache/nginx logs en PHP error logs)
2. Controleer de database tabellen op eventuele fouten
3. Verifieer of de Google Client ID en Client Secret correct zijn
4. Controleer of alle paden en URL's correct zijn geconfigureerd
5. Voer een test uit in een ontwikkelomgeving met debug modus ingeschakeld

## Volgende Stappen

Voor verdere verbeteringen:

1. Implementeer Google OAuth Consent Screen aanpassingen
2. Implementeer meer fijnmazige scope toegang
3. Configureer offline toegang voor langdurige toegang tot Google API's
4. Implementeer automatische token vernieuwing via AJAX
5. Voeg ondersteuning toe voor meerdere OAuth providers (Microsoft, Facebook, etc.)

## Referenties

- [Google OAuth 2.0 voor Webserver-toepassingen](https://developers.google.com/identity/protocols/oauth2/web-server)
- [PKCE Extension](https://datatracker.ietf.org/doc/html/rfc7636)
- [OAuth 2.0 Best Practices](https://developers.google.com/identity/protocols/oauth2/resources/best-practices)
