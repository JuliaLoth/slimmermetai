# SlimmerMetAI Development Setup

## Database Configuratie

Voor lokale development heb je een `.env` bestand nodig in de root directory met de volgende configuratie:

```env
# Application Environment
APP_ENV=development
DEBUG_MODE=true
DISPLAY_ERRORS=true

# Site Configuration
SITE_NAME="SlimmerMetAI"
SITE_URL="http://localhost:8000"
ADMIN_EMAIL="admin@slimmermetai.com"

# Database Configuration (MySQL/MariaDB)
DB_HOST=localhost
DB_NAME=slimmermetai_dev
DB_USER=root
DB_PASS=your_mysql_password_here
DB_CHARSET=utf8mb4

# Session & Security
SESSION_LIFETIME=604800
SESSION_NAME=SLIMMERMETAI_SESSION
COOKIE_DOMAIN=
COOKIE_PATH=/
COOKIE_SECURE=false
COOKIE_HTTPONLY=true

# Password Security
PASSWORD_MIN_LENGTH=8
BCRYPT_COST=12

# JWT Configuration
JWT_SECRET=your_super_secret_jwt_key_here_min_32_chars
JWT_EXPIRATION=3600

# Google OAuth (Development)
GOOGLE_CLIENT_ID_DEVELOPMENT=your_google_client_id_development
GOOGLE_CLIENT_SECRET_DEVELOPMENT=your_google_client_secret_development

# Stripe (Development)
STRIPE_SECRET_KEY=sk_test_your_stripe_secret_key_here
STRIPE_PUBLIC_KEY=pk_test_your_stripe_public_key_here
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret_here

# Email Configuration
MAIL_FROM=noreply@localhost
MAIL_FROM_NAME="SlimmerMetAI Dev"
MAIL_REPLY_TO=support@localhost

# reCAPTCHA (Development)
RECAPTCHA_SITE_KEY=your_recaptcha_site_key
RECAPTCHA_SECRET_KEY=your_recaptcha_secret_key

# File Upload
MAX_UPLOAD_SIZE=5242880
ALLOWED_FILE_TYPES=jpg,jpeg,png,pdf,doc,docx

# Timezone
TIMEZONE=Europe/Amsterdam
```

## Vereisten

1. **MySQL/MariaDB Server** draaiend op localhost
2. **Database aanmaken**: `CREATE DATABASE slimmermetai_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
3. **PHP 8.0+** met PDO MySQL extensie
4. **Composer** voor dependencies

## Database Setup

```sql
-- Maak database aan
CREATE DATABASE slimmermetai_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Maak development user aan (optioneel)
CREATE USER 'slimmermetai_dev'@'localhost' IDENTIFIED BY 'development_password';
GRANT ALL PRIVILEGES ON slimmermetai_dev.* TO 'slimmermetai_dev'@'localhost';
FLUSH PRIVILEGES;
```

## Development Server Starten

```bash
# Install dependencies
composer install

# Start development server
php -S localhost:8000 router.php
```

## API Testing

Met SKIP_DB=true werkt de API zonder database voor testing:
- GET http://localhost:8000/api - API info
- Andere endpoints vereisen database connectie

## Troubleshooting

### Database Connection Issues
1. Controleer of MySQL/MariaDB draait
2. Verifieer .env database credentials
3. Test connectie: `mysql -u root -p`
4. Voor API testing: SKIP_DB=true in bootstrap.php

### Permission Issues
1. Controleer file permissions op logs/ directory
2. Web server moet kunnen schrijven naar uploads/

## Production Configuratie

Voor productie gebruik:
- APP_ENV=production
- DEBUG_MODE=false
- DISPLAY_ERRORS=false
- Sterke JWT_SECRET (min 32 karakters)
- Echte database credentials
- HTTPS certificaten
- COOKIE_SECURE=true 