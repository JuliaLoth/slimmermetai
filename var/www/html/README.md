# SlimmerMetAI.com

Een platform voor het leren en toepassen van AI-tools en -technieken.

## Functionaliteiten

- Gebruikersbeheer (registratie, inloggen, wachtwoord reset)
- Persoonlijk dashboard
- E-learning modules over AI
- Verzameling van nuttige AI-tools
- Bibliotheek van effectieve prompts voor AI
- Favorieten systeem
- Responsief ontwerp voor alle apparaten

## Technische details

- Frontend: HTML, CSS, JavaScript (Vanilla)
- Backend: PHP
- Database: MySQL
- Server requirements: PHP 7.4+, MySQL 5.7+

## Mappenstructuur

```
/
├── assets/
│   ├── css/         # Stylesheet bestanden
│   ├── js/          # JavaScript bestanden
│   ├── images/      # Afbeeldingen
│   └── fonts/       # Lettertypen
├── database/        # Database scripts
├── includes/        # PHP includes (functies, configuratie)
├── partials/        # Herbruikbare HTML componenten
├── uploads/         # Gebruikersuploads (profielfoto's)
└── index.php        # Start pagina
```

## Installatie

1. Clone de repository naar je webserver
2. Maak een database aan en importeer `database/db_structure.sql`
3. Configureer database verbinding in `includes/config.php`
4. Zorg ervoor dat de webserver schrijfrechten heeft voor de map `uploads/`

## Gebruik

Na installatie kun je inloggen met de standaard admin account:
- Email: admin@slimmermetai.com
- Wachtwoord: Admin123!

## Licentie

Alle rechten voorbehouden. Dit project is eigendom van SlimmerMetAI. 