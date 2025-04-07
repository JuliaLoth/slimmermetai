# SlimmerMetAI.com Installatie Checklist

## Voorbereidingen

- [ ] Maak een database aan op je Antagonist hostingaccount
- [ ] Noteer de database naam, gebruiker en wachtwoord
- [ ] Zorg voor een domeinnaam waarop de website gehost zal worden
- [ ] Zorg ervoor dat je FTP-toegang hebt tot de hostingaccount

## Uploaden van bestanden

- [ ] Upload alle bestanden naar de hostingaccount via FTP
- [ ] Zorg ervoor dat de mappenstructuur correct is:
  - `domains/slimmermetai.com/database/` (niet publiek toegankelijk)
  - `domains/slimmermetai.com/public_html/` (DocumentRoot)
  - `domains/slimmermetai.com/uploads/` (niet publiek toegankelijk)

## Configuratie

- [ ] Bewerk het bestand `public_html/api/config.php` met de juiste waarden:
  - Database instellingen
  - Site URL en naam
  - JWT geheime sleutel
  - reCAPTCHA sleutels (aanbevolen)
  - E-mail instellingen

## Instellen van bestandsrechten

- [ ] Zet de rechten van `uploads/` en submappen op 755 (schrijfbaar)
- [ ] Zet de rechten van bestaande bestanden in `uploads/` op 644
- [ ] Controleer of de webserver kan schrijven naar de uploads mappen

## Database installatie

- [ ] Open een webbrowser en navigeer naar `https://jouwdomein.nl/install.php?key=slimmermetai_installatie_2023`
- [ ] Controleer of alle tabellen correct zijn aangemaakt
- [ ] Verwijder het `install.php` bestand na succesvolle installatie

## Verificatie van de installatie

- [ ] Open een webbrowser en navigeer naar `https://jouwdomein.nl/api/check.php?key=slimmermetai_check_2023`
- [ ] Controleer of alle systeem- en databasechecks succesvol zijn
- [ ] Verifieer dat alle API endpoints correct zijn ingesteld
- [ ] Verifieer dat de bestandsrechten correct zijn ingesteld

## Beveiliging

- [ ] Controleer of `debug_mode` op `false` staat in productie
- [ ] Controleer of de JWT geheime sleutel sterk en uniek is
- [ ] Zorg dat HTTPS is geconfigureerd voor alle verkeer
- [ ] Controleer of de `uploads/` directory niet direct toegankelijk is via het web
- [ ] Verwijder of beveilig het `check.php` bestand met een sterke sleutel

## Eerste inlog

- [ ] Log in met de standaard admin account (email: `admin@slimmermetai.com`, wachtwoord: `Admin123!`)
- [ ] Wijzig direct het wachtwoord van de admin account
- [ ] Voeg eventueel extra gebruikersaccounts toe

## Laatste checks

- [ ] Test het registreren van een nieuwe gebruiker
- [ ] Test het inloggen met een bestaande gebruiker
- [ ] Test het uploaden van profielfoto's
- [ ] Test het resetten van wachtwoorden
- [ ] Test de e-mailverificatie

## Aangepast voor Antagonist hosting

- [ ] Controleer of de .htaccess bestanden correct werken
- [ ] Controleer of URL rewrites correct werken
- [ ] Zorg dat PHP versie 7.4 of hoger is ingesteld
- [ ] Controleer of de GD en PDO extensies beschikbaar zijn

## Na de migratie van Node.js naar PHP

- [ ] Test of alle endpoints dezelfde respons geven als de oude Node.js API
- [ ] Controleer of bestaande gebruikersaccounts nog steeds werken
- [ ] Controleer of de frontend nog steeds correct werkt met de nieuwe API
- [ ] Controleer of sessies correct werken (ingelogd blijven) 