# Mappenstructuur SlimmerMetAI.com

Deze documentatie beschrijft de mappenstructuur van de SlimmerMetAI.com website, geoptimaliseerd voor Antagonist hosting.

## Overzicht

```
/
├── .env                 # Productie configuratie (niet in git)
├── .env.example        # Template voor andere ontwikkelaars
├── .htaccess          # Apache configuratie voor root
├── public_html/       # Publieke bestanden
│   ├── index.php     # Hoofdpagina
│   ├── css/          # Stylesheets
│   ├── js/           # JavaScript bestanden
│   ├── images/       # Afbeeldingen
│   ├── uploads/      # Gebruikersuploads
│   └── includes/     # Publieke includes (header.php, footer.php)
├── includes/          # Core PHP includes en configuratie (NIET publiek toegankelijk)
│   ├── config.php    # Configuratie
│   ├── db.php        # Database functies
│   ├── functions.php # Algemene functies
│   ├── backend/      # Backend-gerelateerde code
│   ├── database/     # Database migraties en seeding
│   ├── e-learning/   # E-learning specifieke code
│   └── stripe/       # Stripe integratie code
├── components/        # Herbruikbare componenten
│   ├── header.php    # Header component
│   ├── footer.php    # Footer component
│   ├── head.php      # Head component
│   └── meta-tags.php # Meta tags component
└── api/              # API endpoints
    ├── auth/         # Authenticatie endpoints
    ├── products/     # Product endpoints
    └── orders/       # Order endpoints
```

## Uitleg

1. **Root-niveau bestanden:**
   - `.env`: Bevat omgevingsvariabelen zoals database credentials en API keys
   - `.env.example`: Template voor het .env bestand (zonder echte credentials)
   - `.htaccess`: Apache configuratie voor beveiliging en routing
   - `README.md`: Projectdocumentatie

2. **public_html:** Bevat alle publiek toegankelijke bestanden
   - `index.php`: De hoofdingang van de website
   - `css/`, `js/`, `images/`: Asset mappen
   - `uploads/`: Voor gebruikersuploads
   - `includes/`: Bevat publieke includes zoals header.php en footer.php die direct HTML genereren

3. **includes:** Bevat core code die NIET direct toegankelijk moet zijn
   - `config.php`: Configuratievariabelen
   - `db.php`: Database connectie en functies
   - `functions.php`: Algemene hulpfuncties
   - Kernfuncties en klassen van de website

4. **components:** Bevat herbruikbare UI-componenten
   - Header, footer, en andere UI-elementen
   - Deze worden geïnclude in de pagina's

5. **api:** Bevat API endpoints
   - Gegroepeerd per functionaliteit (auth, products, etc.)
   - Gebruikt JWT voor authenticatie

## Belangrijke punten

1. **Scheiding van verantwoordelijkheden:**
   - `/includes/`: Bevat de core functionaliteit, niet direct bereikbaar vanaf het web
   - `/public_html/includes/`: Bevat bestanden die specifiek zijn voor de publieke website, zoals header.php en footer.php
   - `/public_html/`: Bevat alle bestanden die via de webserver direct bereikbaar zijn

2. **Bestandsplaatsing:**
   - Plaats nieuwe PHP klassen en kernfunctionaliteit in `/includes/` en subdirectories
   - Plaats nieuwe publieke pagina's in `/public_html/`
   - Vermijd dubbele bestanden in verschillende mappen

## Hoe dit werkt

1. Alle HTTP requests komen binnen via `/public_html/index.php` of direct naar specifieke bestanden in `public_html/`
2. De `.htaccess` in de root beschermt de `/includes/` map tegen directe toegang
3. Configuratie wordt geladen uit het `.env` bestand via `includes/config.php`
4. Componenten (header, footer) worden geladen via `include_public()` functie

## Beveiliging

- Gevoelige bestanden staan buiten de `public_html` map
- Database credentials staan alleen in het `.env` bestand
- JWT wordt gebruikt voor API authenticatie
- XSS- en CSRF-bescherming is ingebouwd

## Ontwikkeling

1. Kopieer `.env.example` naar `.env` en vul de juiste waarden in
2. Gebruik `includes/db.php` voor alle database interacties
3. Plaats nieuwe API endpoints in de juiste submap onder `/api/` 