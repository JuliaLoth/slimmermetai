<?php /** @var string $title */ ?>
<slimmer-hero 
    title="Afrekenen" 
    subtitle="Rond je bestelling veilig af"
    background="gradient"
    centered>
</slimmer-hero>

<section class="section">
  <div class="container" id="checkout-container">
    <p>Je wordt doorgestuurd naar onze betaalprovider…</p>
    <div class="spinner" style="margin-top:1rem"></div>
  </div>
</section>

<?php use App\Infrastructure\Utils\Asset; ?>
<script type="module" src="<?= Asset::url('stripe') ?>"></script>
<script>
  // Debug informatie
  console.log('Betalen pagina geladen');
  
  // Direct checkout met test data als fallback
  document.addEventListener('DOMContentLoaded', async () => {
    console.log('DOM geladen, starten betaalproces...');
    
    try {
      // Wacht een moment voor module initialisatie
      await new Promise(resolve => setTimeout(resolve, 1000));
      
      if (window.Payment && typeof Payment.startPayment === 'function') {
        console.log('Payment object gevonden, starten betaling...');
        
        // Test data voor als er geen winkelwagen items zijn
        const testData = {
          line_items: [{
            price_data: {
              currency: 'eur',
              product_data: {
                name: 'Test AI Cursus',
                description: 'Test product voor lokale Stripe checkout'
              },
              unit_amount: 2999 // €29.99 in centen
            },
            quantity: 1
          }],
          mode: 'payment',
          success_url: window.location.origin + '/betaling-succes',
          cancel_url: window.location.origin + '/winkelwagen'
        };
        
        // Probeer eerst zonder data (vanuit winkelwagen), dan met test data
        try {
          await Payment.startPayment();
        } catch (firstError) {
          console.log('Eerste poging mislukt, proberen met test data:', firstError.message);
          
          // Gebruik test data als fallback
          const el = document.getElementById('checkout-container');
          el.innerHTML = '<p>Bezig met voorbereiden van test betaling...</p><div class="spinner"></div>';
          
          // Direct API call als Payment.startPayment faalt
          const response = await fetch('/stripe/checkout', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify(testData)
          });
          
          if (response.ok) {
            const data = await response.json();
            if (data.success && data.data && data.data.session && data.data.session.url) {
              console.log('Directe checkout sessie aangemaakt, redirecting...');
              window.location.href = data.data.session.url;
            } else {
              throw new Error('Geen geldige checkout URL ontvangen');
            }
          } else {
            throw new Error(`Checkout API fout: ${response.status}`);
          }
        }
        
      } else {
        console.error('Payment object niet gevonden');
        const el = document.getElementById('checkout-container');
        el.innerHTML = '<p style="color:red">Het betalingssysteem is niet beschikbaar. Controleer de browser console voor meer details.</p>';
        
        // Debug informatie
        console.log('window.Payment:', window.Payment);
        console.log('Beschikbare objecten:', Object.keys(window));
      }
      
    } catch (e) {
      console.error('Fout in checkout proces:', e);
      const el = document.getElementById('checkout-container');
      el.innerHTML = `<p style="color:red">Er ging iets mis: ${e.message}</p><p>Check de browser console voor meer details.</p>`;
    }
  });
</script> 