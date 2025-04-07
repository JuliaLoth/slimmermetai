# Handleiding voor het testen van SlimmerMetAI.com functionaliteit

## 1. Testen van de winkelwagen en betaalfunctionaliteit

### Voorbereiding
1. Open een browser en ga naar https://www.slimmermetai.com
2. Open de browserconsole (F12 of Ctrl+Shift+I, dan tabblad "Console")

### Test 1: Producten toevoegen aan winkelwagen
1. Ga naar de Tools pagina (https://www.slimmermetai.com/tools.php)
2. Klik op "Toevoegen aan Winkelwagen" bij een of meerdere tools
3. Er zou een melding moeten verschijnen dat het product is toegevoegd
4. Controleer of het aantal items in de winkelwagen (rechtsboven) is verhoogd

### Test 2: Winkelwagen bekijken
1. Klik op het winkelwagenpictogram of ga naar https://www.slimmermetai.com/winkelwagen.php
2. Controleer of de toegevoegde producten zichtbaar zijn
3. Controleer of de totalen (subtotaal, BTW, totaal) correct worden berekend

### Test 3: Debugging van de betaalknop
1. Met de console open, kijk naar JavaScript fouten
2. Voeg het volgende script toe in de console om de betaalknop handmatig te activeren:

```javascript
var checkoutBtn = document.getElementById("checkout-btn");
console.log("Checkout button:", checkoutBtn);
if (checkoutBtn) {
    console.log("Activeren van checkout knop");
    checkoutBtn.disabled = false;
    checkoutBtn.addEventListener("click", function(e) {
        console.log("Checkout knop is geklikt");
        if (typeof StripePayment !== "undefined" && StripePayment.handleCheckout) {
            console.log("StripePayment.handleCheckout aanroepen");
            StripePayment.handleCheckout(e);
        } else {
            console.error("StripePayment is niet beschikbaar");
            console.log("Beschikbare globale objecten:", Object.keys(window));
        }
    });
    console.log("Event listener toegevoegd aan checkout knop");
}
```

3. Nadat je dit hebt uitgevoerd, klik op de "Afrekenen" knop
4. Kijk in de console naar berichten die worden weergegeven

### Test 4: Controleren van laden van StripePayment
Voer het volgende uit in de console om te controleren of StripePayment correct is geladen:

```javascript
console.log("StripePayment object:", typeof StripePayment, StripePayment);
console.log("StripePayment.handleCheckout:", typeof StripePayment.handleCheckout);
console.log("Proberen om StripePayment te initialiseren");
StripePayment.init();
```

### Test 5: Handmatig de betaalfunctie aanroepen
Als test 3 en 4 aantonen dat StripePayment beschikbaar is, probeer de handleCheckout functie direct aan te roepen:

```javascript
console.log("Handmatig StripePayment.handleCheckout aanroepen");
StripePayment.handleCheckout({preventDefault: function() { console.log("preventDefault aangeroepen"); }});
```

## 2. Testen van andere functionaliteit

### Test 6: E-learning pagina
1. Ga naar https://www.slimmermetai.com/e-learnings.php
2. Controleer of de cursuskaarten worden weergegeven
3. Klik op een cursus om te zien of de detailpagina correct wordt geladen

### Test 7: Login/Registratie
1. Ga naar https://www.slimmermetai.com/login.php
2. Test het inlogformulier met testgegevens
3. Test de "Registreren" optie (indien beschikbaar)

## 3. Rapportage

Houd alle fouten en problemen bij in een bestand met:
- De specifieke stap waar het probleem optrad
- Eventuele foutmeldingen uit de console
- Screenshots van het probleem
- Omschrijving van het verwachte gedrag versus het werkelijke gedrag 