# SlimmerMetAI - Productie Deployment Checklist

## ✅ Code Aanpassingen Voltooid

### 1. Database Configuratie
- [x] `SKIP_DB = true` verwijderd uit `public_html/index.php`
- [x] Database connectie hersteld voor productie gebruik

### 2. Asset Handling
- [x] Development asset routes (`/dev-asset/`) alleen actief bij `APP_ENV=local/development`
- [x] Asset class aangepast voor productie routing via `/assets/js/`
- [x] Router.php aangepast voor productie asset serving

### 3. Stripe Configuratie
- [x] Mock mode alleen in development, productie gebruikt echte Stripe API
- [x] Verbeterde error handling zonder fallback naar mock in productie
- [x] Productie vereist geldige Stripe API keys

### 4. Google OAuth
- [x] Hardcoded localhost configuraties vervangen door environment variabelen
- [x] Dynamische redirect URI gebaseerd op `SITE_URL`
- [x] Development fallback behouden voor lokale ontwikkeling

### 5. Debug & Test Routes
- [x] Debug routes (`/api/env-dump`, `/api/stripe/simple-test.php`) alleen in development
- [x] Productie error handling zonder debug informatie

## 🔧 Handmatige Configuratie Vereist

### 1. Environment Bestand (.env)
Kopieer `ENV_PRODUCTION_EXAMPLE.txt` naar `.env` en configureer:

```bash
# Environment
APP_ENV=production
APP_DEBUG=false

# Database
DB_HOST=localhost
DB_NAME=slimmermetai_production
DB_USERNAME=slimmermetai_user
DB_PASSWORD=[INVULLEN]

# Stripe (LIVE KEYS!)
STRIPE_PUBLISHABLE_KEY=pk_live_[INVULLEN]
STRIPE_SECRET_KEY=sk_live_[INVULLEN]
STRIPE_WEBHOOK_SECRET=whsec_[INVULLEN]

# Google OAuth
GOOGLE_CLIENT_ID=[INVULLEN]
GOOGLE_CLIENT_SECRET=[INVULLEN]

# Site
SITE_URL=https://jouwdomein.nl
```

### 2. Database Setup
- [ ] MySQL/MariaDB database aanmaken
- [ ] Database gebruiker aanmaken met juiste rechten
- [ ] Database migraties uitvoeren
- [ ] Test database connectie

### 3. Stripe Configuratie
- [ ] Stripe account upgraden naar live mode
- [ ] Live API keys genereren en configureren
- [ ] Webhook endpoint configureren: `https://jouwdomein.nl/api/stripe/webhook`
- [ ] Webhook secret configureren
- [ ] Test checkout flow met echte Stripe

### 4. Google OAuth Setup
- [ ] Google Console project aanmaken/bijwerken
- [ ] Productie domein toevoegen aan authorized origins
- [ ] Callback URL configureren: `https://jouwdomein.nl/api/auth/google-callback.php`
- [ ] Client ID en secret genereren voor productie

### 5. Server Configuratie
- [ ] PHP 8.1+ geïnstalleerd
- [ ] Composer dependencies installeren: `composer install --no-dev --optimize-autoloader`
- [ ] Vite assets builden: `npm run build`
- [ ] Bestandspermissies controleren
- [ ] SSL certificaat configureren
- [ ] .htaccess configureren voor clean URLs

### 6. Mail Configuratie
- [ ] SMTP server configureren
- [ ] Mail templates testen
- [ ] SPF/DKIM records configureren

## 🚀 Deployment Stappen

1. **Code Upload**
   ```bash
   # Upload alle bestanden behalve:
   # - node_modules/
   # - .env (maak nieuw aan op server)
   # - composer.lock (regenereer op server)
   ```

2. **Server Dependencies**
   ```bash
   composer install --no-dev --optimize-autoloader
   npm install
   npm run build
   ```

3. **Environment Setup**
   ```bash
   cp ENV_PRODUCTION_EXAMPLE.txt .env
   # Bewerk .env met productie waarden
   ```

4. **Database Setup**
   ```bash
   # Voer database migraties uit
   # Test database connectie
   ```

5. **Permissions**
   ```bash
   chmod 755 public_html/
   chmod 644 public_html/index.php
   chmod 600 .env
   ```

6. **Tests**
   - [ ] Homepage laadt correct
   - [ ] Database connectie werkt
   - [ ] Stripe checkout werkt
   - [ ] Google OAuth werkt
   - [ ] Mail verzending werkt
   - [ ] Asset loading werkt (CSS/JS)

## 🔒 Security Checklist

- [ ] `.env` bestand niet publiek toegankelijk
- [ ] Database gebruiker heeft minimale rechten
- [ ] SSL certificaat geïnstalleerd en werkend
- [ ] Security headers geconfigureerd
- [ ] File upload permissions beperkt
- [ ] Error reporting uitgeschakeld in productie
- [ ] JWT secret sterk en uniek
- [ ] CSRF protection actief

## 📊 Monitoring Setup

- [ ] Error logging configureren
- [ ] Performance monitoring
- [ ] Uptime monitoring
- [ ] Backup strategie implementeren

## 🚨 Rollback Plan

- [ ] Backup van huidige live site
- [ ] Database backup
- [ ] Rollback procedure gedocumenteerd
- [ ] Emergency contact informatie 