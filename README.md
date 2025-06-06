# SlimmerMetAI.com Website

Deze repository bevat de code voor de SlimmerMetAI.com website, inclusief de frontend en PHP API voor hosting op Antagonist.

## Huidige architectuur (mei 2025)

```
slimmermetai-site/
├── public_html/            # Document-root
│   ├── index.php           # Front-controller (FastRoute + middleware)
│   ├── js/                 # Statische JS-bundles
│   ├── css/                # CSS / Tailwind builds
│   ├── images/ fonts/ ...  # Assets
│   └── uploads/            # Uploads, submappen per feature
│
├── src/                    # PSR-4 PHP-code (Domain / Application / Infrastructure)
│   ├── Http/Controller/    #   – Moderne controllers (PSR-15 style)
│   ├── Application/Service #   – Services (business-logic)
│   └── …
├── scripts/                # Dev/CI scripts (check-public-php.sh …)
└── vendor/                 # Composer dependencies
```

### Belangrijkste API-routes

| Route | Methode | Controller |
|-------|---------|------------|
| `/auth/login` | POST | `Auth\LoginController` |
| `/auth/register` | POST | `Auth\RegisterController` |
| `/stripe/checkout` | POST | `StripeController@createSession` |
| `/stripe/status/{id}` | GET  | `StripeController@status` |
| `/stripe/webhook` | POST | `StripeController@webhook` |
| `/api` | GET | `Api\IndexController` |
| `/api-proxy?endpoint=x` | GET | `Api\ProxyController` (beperkt) |
| `/api/stripe/payment-intent` | POST | `Api\StripePaymentIntentController` |
| `/api/presentation/convert` | POST | `Api\PresentationConvertController` |

Alle oude `.php`-bestanden (bijv. `stripe-checkout-session.php`) zijn verwijderd of leiden 301 naar bovenstaande routes.

### CI-beveiliging

Een script in `scripts/check-public-php.sh` laat de build falen zodra er nieuwe uitvoerbare `.php`-bestanden in `public_html` verschijnen (behalve de front-controller). Voeg in je CI-workflow:

```bash
bash scripts/check-public-php.sh
```

### Redirect-map voor Google Search Console

Bestand `docs/redirects.csv` bevat een lijst met permanente redirects van legacy URL's naar de nieuwe routes. Importeer dit CSV-bestand in het "URL-verhuis" rapport van Search Console.

## Mapstructuur

```
domains/slimmermetai.com/
├── database/             # Database scripts en migraties
│   └── setup.sql         # Database installatiebestand
├── public_html/          # Publiek toegankelijke bestanden (DocumentRoot)
│   ├── api/              # PHP API endpoints
│   │   ├── auth/         # Authenticatie endpoints
│   │   ├── users/        # Gebruiker gerelateerde endpoints
│   │   ├── config.php    # API configuratiebestand (of gebruik .env)
│   │   └── index.php     # API index
│   ├── assets/           # Statische bestanden (CSS, JS, afbeeldingen)
│   └── index.php         # Website hoofdpagina
└── uploads/              # Gebruikersuploads (buiten DocumentRoot voor veiligheid)
    └── profile_pictures/ # Profielfoto's
```

*(Opmerking: Controleer of de mapstructuur nog accuraat is)*

## Installatie

Voor gedetailleerde installatiestappen, zie `INSTALLATIE_CHECKLIST.md` (indien beschikbaar) of volg deze algemene stappen:

1.  **Voorbereiding:** Upload bestanden, zorg voor database credentials.
2.  **Configuratie:** Configureer de databaseverbinding en andere instellingen (bijv. in `.env` of `public_html/api/config.php`). Zorg voor correcte maprechten (bijv. `uploads/`).
3.  **Database:** Voer de database setup uit (bijv. via `database/setup.sql` of een installatiescript). **Verwijder eventuele installatiescripts na gebruik!**
4.  **Admin Account:** Na installatie is er mogelijk een standaard admin account. **Wijzig het wachtwoord hiervan onmiddellijk na de eerste login!**

## Belangrijke Configuratie (.env)

De applicatie gebruikt een `.env` bestand voor gevoelige configuratie zoals:

*   Database credentials
*   JWT Secret Key
*   Stripe API Keys (`STRIPE_SECRET_KEY`, `STRIPE_PUBLIC_KEY`)
*   Stripe Webhook Secret (`STRIPE_WEBHOOK_SECRET`)
*   Stripe Price IDs

Zorg ervoor dat dit bestand correct is ingevuld en **nooit** wordt meegecommit naar de repository (het staat in `.gitignore`). Gebruik `.env.example` als template.

## Beveiliging

*   Gebruik sterke, unieke wachtwoorden en keys.
*   **Wijzig standaard wachtwoorden onmiddellijk.**
*   Gebruik HTTPS.
*   Zorg dat `uploads/` niet direct web-toegankelijk is.
*   Houd afhankelijkheden (zoals via Composer) up-to-date.

## Stripe Integratie

Voor Stripe betalingen:

1.  Vul de Stripe keys en webhook secret in het `.env` bestand.
2.  Configureer de webhook endpoint in je Stripe Dashboard om te verwijzen naar de juiste handler (bijv. `stripe-webhook.php`). Selecteer de benodigde events.

## Licentie

Copyright © 2023-2024 SlimmerMetAI.com. Alle rechten voorbehouden.

Test deployment via Git push op $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")

Test deployment met veilig hook script op $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")

Test deployment met vereenvoudigde hook v3 op $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")

Test deployment met kale hook v4 op $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")

Test deployment met hook v5 (bash, clean) op $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")

Test deployment na line ending fix op $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")

Test deployment met hook v7 (geen commentaar) op $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")

## Frontend workflow

### Lokale ontwikkeling

1. `npm install` om dependencies te installeren.
2. `npm run dev` start de Vite-devserver met HMR op <http://localhost:5173>.
3. Draai je PHP-backend parallel (bijv. via Valet, XAMPP, Docker).

### Nieuwe component-CSS/JS toevoegen

1. Plaats component-CSS in `resources/css/components/<naam>.css`.
2. Plaats JS-modules per route in `resources/js/<route>/<bestand>.js`.
3. Voeg een dynamische import toe in `resources/js/core/main.js` zodat de bundle alleen laadt waar nodig.

### Linting & formatting

Commit hooks draaien automatisch:

```bash
npm run lint        # JavaScript (ESLint)
npm run lint:css    # CSS/SCSS (Stylelint)
npm run format      # Prettier auto-format
```

Deze hooks worden geactiveerd via Husky + lint-staged. Tijdens CI falen builds wanneer fouten blijven bestaan.

### Productie-build & deploy

`npm run build` genereert geoptimaliseerde assets in `public_html/assets`. De GitHub Actions workflow uploadt deze map als artefact (en kan optioneel als release-asset of naar een `gh-pages` branch/S3 worden gepusht).
