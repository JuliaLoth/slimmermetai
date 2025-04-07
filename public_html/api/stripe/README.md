# Stripe Integratie Setup

## Probleem met API-sleutel

Er is momenteel een probleem met de Stripe integratie waarbij de API-sleutel niet wordt geaccepteerd door de Stripe API. Het systeem geeft de foutmelding:

```
Invalid API Key provided: sk_test_***********************************************************************************************IBxE
```

Dit betekent dat de huidige API-sleutel ongeldig is of niet langer wordt geaccepteerd door Stripe.

## Hoe je dit kunt oplossen

### 1. Verkrijg een nieuwe API-sleutel van Stripe

1. Log in op je [Stripe Dashboard](https://dashboard.stripe.com/)
2. Ga naar "Developers" en klik op "API keys"
3. Je hebt twee sleutels nodig:
   - **Publishable key** - begint met `pk_test_` voor de testomgeving of `pk_live_` voor de productieomgeving
   - **Secret key** - begint met `sk_test_` voor de testomgeving of `sk_live_` voor de productieomgeving

### 2. Update de API-sleutels in de code

#### Secret Key (Backend)

Update de secret key in de volgende bestanden:

1. `domains/slimmermetai.com/public_html/api/stripe/create-checkout-session.php`
2. `domains/slimmermetai.com/public_html/api/stripe/test-api.php`

Zoek de volgende regel in beide bestanden:

```php
$stripe_secret_key = 'sk_test_51Qf2ltG2yqBai5FsJPkIjbvL3CfTcvdMxWUyKpZ1zVnPrJO0xwwVMMEp4JjYVrDpMOQqQGMjbPvfPvENGDgMUXfV00hM4nIBxE';
```

Vervang deze door je nieuwe secret key:

```php
$stripe_secret_key = 'sk_test_JOUWNIEUWESTRIPEKEY'; // Vervang met je eigen sleutel
```

#### Publishable Key (Frontend)

Update de publishable key in het volgende bestand:

1. `domains/slimmermetai.com/public_html/api/stripe/config.php`

Zoek de volgende regel:

```php
'publishableKey' => 'pk_test_51Qf2ltG2yqBai5FsCQsuBl84wnMb9omItJI9mTEl6sE0IeKJbwC9in96zPcFxdHwSxpwlruaKtQK0dwmOykEE9i900lcaOgyyB'
```

Vervang deze door je nieuwe publishable key:

```php
'publishableKey' => 'pk_test_JOUWNIEUWESTRIPEKEY' // Vervang met je eigen sleutel
```

### 3. Test de integratie

Na het bijwerken van de sleutels, test de integratie door:

1. Ga naar `/api/stripe/debug.php` om te controleren of de API-sleutel correct wordt geladen
2. Ga naar `/api/stripe/test-api.php` om een test-checkout sessie aan te maken
3. Test het echte checkout proces in de winkelwagenpagina

## Veiligheidsoverwegingen

- Bewaar API-sleutels nooit in publiek toegankelijke bestanden
- Gebruik altijd HTTPS voor alle pagina's die met Stripe communiceren
- Voor een productieomgeving, overweeg het gebruik van omgevingsvariabelen voor API-sleutels

## Ondersteuning

Als je nog steeds problemen ondervindt na het bijwerken van de API-sleutels, controleer:

1. Of je account bij Stripe actief is
2. Of je de juiste rechten hebt voor de API-sleutels
3. Of de webhook-instellingen correct zijn geconfigureerd 