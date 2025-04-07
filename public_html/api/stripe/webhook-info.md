# Stripe Webhook Instellen

Dit document geeft instructies voor het instellen van een Stripe webhook om betalingsgebeurtenissen automatisch te verwerken.

## Wat is een webhook?

Een webhook is een manier voor Stripe om je webapplicatie automatisch te informeren wanneer er een gebeurtenis plaatsvindt, zoals een succesvolle betaling. Stripe stuurt een HTTP POST-bericht naar de URL die je opgeeft, met details over de gebeurtenis.

## Stap 1: Vul het webhookscript in

In je webhook.php is er een variabele `$webhook_secret` die je moet invullen. Deze krijg je pas nadat je de webhook hebt aangemaakt in het Stripe Dashboard.

## Stap 2: Maak een webhook aan in het Stripe Dashboard

1. Log in op je [Stripe Dashboard](https://dashboard.stripe.com/)
2. Ga naar Developers > Webhooks
3. Klik op "Add endpoint"
4. Vul de volgende gegevens in:

### Webhook configuratie velden

| Veld | Waarde |
|------|--------|
| Events from | Your account |
| Payload style | Snapshot |
| API version | 2025-03-31.basil (of nieuwer) |
| Endpoint URL | https://slimmermetai.com/api/stripe/webhook.php |
| Endpoint name | slimmermetai-webhook |
| Description | Webhook voor het verwerken van Stripe betalingen voor Slimmer met AI |

### Te volgen events

Je moet kiezen welke events je wilt ontvangen. Voor een basisintegratie raden we aan om deze te selecteren:

- `checkout.session.completed` - Wanneer een checkout sessie is voltooid
- `payment_intent.succeeded` - Wanneer een betaling succesvol is verwerkt
- `payment_intent.payment_failed` - Wanneer een betaling mislukt

Je kunt meer events toevoegen afhankelijk van je behoeften.

## Stap 3: Kopieer de Webhook Secret

Na het aanmaken van de webhook toont Stripe een Signing Secret. Deze is alleen één keer zichtbaar en ziet eruit als `whsec_...`.

1. Kopieer deze Signing Secret
2. Open het bestand `webhook.php`
3. Vervang `$webhook_secret = 'whsec_';` met je echte secret: `$webhook_secret = 'whsec_jouw_secret_hier';`

## Stap 4: Test de webhook

Je kunt de webhook testen door een testbetaling uit te voeren of door de testfunctie in het Stripe Dashboard te gebruiken:

1. Ga naar de details van je webhook in het Dashboard
2. Klik op "Send test webhook"
3. Kies een event type (bijvoorbeeld `checkout.session.completed`)
4. Stuur het testevent

## Stap 5: Controleer de logs

Na het uitvoeren van een test moet je controleren of het event correct is ontvangen:

1. Controleer het bestand `webhook_log.txt` in dezelfde map als je webhook script
2. Je zou een log moeten zien met de details van het event

## Probleemoplossing

Als je webhook niet werkt, controleer dan:

1. Of de URL correct is en toegankelijk vanaf het internet
2. Of de Webhook Secret correct is ingevuld in webhook.php
3. Of de Stripe API-sleutel correct is
4. De webserverlogbestanden voor eventuele fouten

## Beveiligingsoverwegingen

- Webhooks moeten altijd via HTTPS worden verzonden
- Controleer altijd de handtekening om ervoor te zorgen dat het verzoek echt van Stripe komt
- Bewaar de webhook secret veilig en deel deze niet 