# Controlelijst voor FTP-uploaden

Gebruik deze lijst om te controleren of alle bestanden correct zijn geüpload naar je webserver.

## Hoofdmappen

- [ ] `/assets/`
- [ ] `/database/`
- [ ] `/includes/`
- [ ] `/partials/`
- [ ] `/uploads/` (met schrijfrechten)

## Belangrijke PHP-bestanden

- [ ] `index.php` (homepage)
- [ ] `login.php` (inlogpagina)
- [ ] `register.php` (registratiepagina)
- [ ] `dashboard.php` (gebruikersdashboard)
- [ ] `profile.php` (profielpagina)
- [ ] `e-learnings.php` (overzicht van e-learnings)
- [ ] `tools.php` (overzicht van tools)
- [ ] `blog.php` (blog overzicht)
- [ ] `over-mij.php` (over mij pagina)
- [ ] `contact.php` (contactformulier)
- [ ] `winkelwagen.php` (winkelwagen)
- [ ] `afrekenen.php` (afrekenpagina)
- [ ] `privacy.php` (privacybeleid)
- [ ] `terms.php` (algemene voorwaarden)
- [ ] `cookies.php` (cookiebeleid)

## CSS-bestanden (in `/assets/css/`)

- [ ] `styles.css` (algemene stijlen)
- [ ] `auth.css` (stijlen voor inloggen/registreren)
- [ ] `dashboard.css` (stijlen voor dashboard)

## JavaScript-bestanden (in `/assets/js/`)

- [ ] `main.js` (algemene scripts)
- [ ] `auth.js` (scripts voor inloggen/registreren)
- [ ] `dashboard.js` (scripts voor dashboard)

## Database-bestanden

- [ ] `/database/db_structure.sql` (database structuur)

## Includes-bestanden

- [ ] `/includes/config.php` (configuratie)
- [ ] `/includes/functions.php` (algemene functies)
- [ ] `/includes/Database.php` (database-klasse)
- [ ] `/includes/Auth.php` (authenticatieklasse)
- [ ] `/includes/Security.php` (beveiligingsklasse)
- [ ] `/includes/GDPR.php` (privacyklasse)
- [ ] `/includes/header.php` (header-component)
- [ ] `/includes/footer.php` (footer-component)
- [ ] `/includes/ajax/toggle_favorite.php` (AJAX handler voor favorieten)
- [ ] `/includes/delete_account.php` (script voor accountverwijdering)

## Mappen met benodigde schrijfrechten (chmod 755 of 775)

- [ ] `/uploads/`
- [ ] `/uploads/profile_pictures/`
- [ ] `/logs/` (indien aanwezig)

## Configuratie controleren

- [ ] Database-instellingen in `includes/config.php`
- [ ] DEBUG_MODE uitgeschakeld in productie
- [ ] DISPLAY_ERRORS uitgeschakeld in productie
- [ ] COOKIE_SECURE ingeschakeld als er HTTPS wordt gebruikt

## Na het uploaden

- [ ] Test de verbinding met de database
- [ ] Test het inloggen met het standaard beheeraccount
- [ ] Wijzig het wachtwoord van het standaard beheeraccount
- [ ] Controleer of uploaden van profielfoto's werkt
- [ ] Controleer of de favorieten-functionaliteit werkt
- [ ] Test het contactformulier als deze aanwezig is 