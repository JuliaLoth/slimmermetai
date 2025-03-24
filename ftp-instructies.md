# FTP-uploaden van SlimmerMetAI.com

## Voorbereiding

1. Zorg ervoor dat je een FTP-client hebt geïnstalleerd, zoals:
   - FileZilla (gratis en beschikbaar voor Windows, macOS en Linux)
   - WinSCP (Windows)
   - Cyberduck (macOS)

2. Je hebt de volgende FTP-gegevens nodig van je hostingprovider:
   - FTP-server/hostname (bijv. ftp.slimmermetai.com)
   - Gebruikersnaam
   - Wachtwoord
   - Poort (meestal 21 voor FTP of 22 voor SFTP)

## Mappenstructuur

Upload de bestanden in de volgende structuur naar je webserver:

```
/public_html/ of /www/ of /htdocs/ (afhankelijk van je hostingprovider)
├── assets/
│   ├── css/         # Alle CSS-bestanden
│   ├── js/          # Alle JavaScript-bestanden
│   ├── images/      # Alle afbeeldingen
│   └── fonts/       # Lettertypen
├── database/        # Database scripts
├── includes/        # PHP includes (functies, configuratie)
├── partials/        # Herbruikbare HTML-componenten
├── uploads/         # Map voor gebruikersuploads (leeg, maar met schrijfrechten)
└── diverse PHP-bestanden (index.php, login.php, etc.)
```

## Stapsgewijze instructies

1. **Verbind met de FTP-server**
   - Open je FTP-client
   - Voer de gegevens in (server, gebruikersnaam, wachtwoord, poort)
   - Klik op "Verbinden" of "Connect"

2. **Navigeer naar de hoofdmap van je website**
   - Dit is meestal `/public_html/`, `/www/` of `/htdocs/`
   - Vraag je hostingprovider als je niet zeker weet wat de hoofdmap is

3. **Upload de bestanden**
   - Sleep alle bestanden en mappen uit het ZIP-bestand naar je webserver
   - Of gebruik de upload-functie van je FTP-client

4. **Controleer de rechten (belangrijk!)**
   - De map `uploads/` en submappen moeten schrijfrechten hebben (chmod 755 of 775)
   - Bestanden in het algemeen: chmod 644
   - PHP-bestanden: chmod 644
   - Configuratiebestanden: chmod 600 (voor extra beveiliging)

5. **Maak de database aan**
   - Log in op het databasebeheersysteem van je hosting (meestal phpMyAdmin)
   - Maak een nieuwe database aan
   - Importeer het bestand `database/db_structure.sql`

6. **Configureer de databaseverbinding**
   - Open `includes/config.php`
   - Werk de databasegegevens bij:
     ```php
     define('DB_HOST', 'localhost'); // meestal 'localhost'
     define('DB_NAME', 'jouw_database_naam');
     define('DB_USER', 'jouw_database_gebruiker');
     define('DB_PASS', 'jouw_database_wachtwoord');
     ```

7. **Test je website**
   - Open je website in een browser om te controleren of alles werkt
   - Log in met de standaard beheerderaccount:
     - E-mail: admin@slimmermetai.com
     - Wachtwoord: Admin123!
   - Verander direct het wachtwoord van de beheerder na inloggen!

## Problemen oplossen

1. **"500 Internal Server Error"**
   - Controleer de logbestanden van je webserver
   - Controleer of de PHP-versie op je server 7.4 of hoger is
   - Controleer of alle bestanden correct zijn geüpload

2. **Database-verbindingsproblemen**
   - Controleer of je databasegegevens correct zijn ingevuld in `includes/config.php`
   - Controleer of de database bestaat en is geïmporteerd

3. **Bestandsrechten**
   - Als sommige functies niet werken, controleer dan of de bestandsrechten correct zijn ingesteld
   - De map `uploads/` en submappen moeten schrijfbaar zijn

## Veiligheidsmaatregelen na uploaden

1. Wijzig de standaard beheeraccount-gegevens
2. Schakel DEBUG_MODE uit in `includes/config.php` door deze op `false` te zetten
3. Zorg ervoor dat foutmeldingen niet openbaar worden weergegeven
4. Overweeg om een SSL-certificaat te installeren voor HTTPS
5. Maak regelmatig backups van je website en database

## Contacteer voor hulp

Als je problemen ondervindt bij het uploaden of configureren van je website, neem dan contact op met:

- E-mail: support@slimmermetai.com 