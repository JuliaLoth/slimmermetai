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
