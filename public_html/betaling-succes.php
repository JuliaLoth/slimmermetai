<?php
/**
 * Betaling Succes Pagina
 * 
 * Deze pagina wordt getoond na een succesvolle betaling via Stripe.
 * De pagina haalt informatie op over de checkout sessie om details te tonen.
 */

// Controleer of er een sessie ID is doorgegeven
$sessionId = $_GET['session_id'] ?? null;

// Laad de Stripe configuratie
require_once __DIR__ . '/api/stripe/stripe-api-config.php';

// Titel en bericht instellen
$pageTitle = "Betaling Succesvol";
$message = "Bedankt voor je bestelling!";
$orderDetails = null;

// Als er een sessie ID is, haal dan de details op
if ($sessionId) {
    try {
        // Laad Stripe SDK
        loadStripeSDK();
        
        // Haal de sessie informatie op
        $session = \Stripe\Checkout\Session::retrieve($sessionId);
        
        // Log de sessie details
        error_log('Sessie details voor betaling succes pagina: ' . json_encode($session));
        
        // Sla bestelling details op
        $orderDetails = [
            'id' => $session->id,
            'payment_status' => $session->payment_status,
            'amount_total' => $session->amount_total ? ($session->amount_total / 100) : 0,
            'currency' => $session->currency ?? 'eur',
            'customer_email' => $session->customer_details->email ?? '',
            'customer_name' => $session->customer_details->name ?? '',
            'created' => date('d-m-Y H:i', $session->created),
            'metadata' => $session->metadata ?? []
        ];
        
        // Pas het bericht aan met de naam van de klant
        if (!empty($orderDetails['customer_name'])) {
            $message = "Bedankt voor je bestelling, " . htmlspecialchars($orderDetails['customer_name']) . "!";
        }
        
    } catch (Exception $e) {
        error_log('Fout bij ophalen sessie informatie: ' . $e->getMessage());
        // Als er een fout is, laat dan een algemeen bericht zien
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        .success-icon {
            font-size: 5rem;
            color: #28a745;
        }
        .order-card {
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .page-header {
            background-color: #f8f9fa;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="page-header">
        <div class="container text-center">
            <i class="bi bi-check-circle-fill success-icon mb-3"></i>
            <h1 class="display-4"><?php echo $pageTitle; ?></h1>
            <p class="lead"><?php echo $message; ?></p>
        </div>
    </header>
    
    <div class="container pb-5">
        <?php if ($orderDetails): ?>
            <!-- Besteldetails -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card order-card mb-4">
                        <div class="card-header bg-success text-white">
                            <h3 class="card-title mb-0">Bestelgegevens</h3>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Bestelnummer:</strong></p>
                                    <p><?php echo substr($orderDetails['id'], 0, 10) . '...'; ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Datum:</strong></p>
                                    <p><?php echo $orderDetails['created']; ?></p>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Betaalstatus:</strong></p>
                                    <p>
                                        <?php if ($orderDetails['payment_status'] === 'paid'): ?>
                                            <span class="badge bg-success">Betaald</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark"><?php echo ucfirst($orderDetails['payment_status']); ?></span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Totaalbedrag:</strong></p>
                                    <p class="fw-bold fs-5">€<?php echo number_format($orderDetails['amount_total'], 2, ',', '.'); ?></p>
                                </div>
                            </div>
                            
                            <?php if (!empty($orderDetails['customer_email'])): ?>
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <p class="mb-1"><strong>E-mail:</strong></p>
                                        <p><?php echo htmlspecialchars($orderDetails['customer_email']); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($orderDetails['metadata'])): ?>
                                <div class="row">
                                    <div class="col-12">
                                        <p class="mb-1"><strong>Aanvullende informatie:</strong></p>
                                        <ul class="list-unstyled">
                                            <?php foreach ($orderDetails['metadata'] as $key => $value): ?>
                                                <?php if ($key !== 'created_at' && $key !== 'client_ip' && $key !== 'origin'): ?>
                                                    <li><strong><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $key))); ?>:</strong> <?php echo htmlspecialchars($value); ?></li>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Wat nu sectie -->
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card order-card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Wat nu?</h3>
                    </div>
                    <div class="card-body">
                        <p>Je bestelling is succesvol verwerkt. Hier zijn de volgende stappen:</p>
                        
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3">
                                <i class="bi bi-envelope-check fs-2 text-primary"></i>
                            </div>
                            <div>
                                <h5>Bevestiging per e-mail</h5>
                                <p>Je ontvangt binnenkort een bevestigingsmail met alle details van je bestelling.</p>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3">
                                <i class="bi bi-clock-history fs-2 text-primary"></i>
                            </div>
                            <div>
                                <h5>Verwerkingstijd</h5>
                                <p>We verwerken je bestelling zo snel mogelijk. Voor digitale producten is dit meestal direct beschikbaar.</p>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-start">
                            <div class="me-3">
                                <i class="bi bi-question-circle fs-2 text-primary"></i>
                            </div>
                            <div>
                                <h5>Vragen?</h5>
                                <p>Heb je vragen over je bestelling? Neem dan contact met ons op via <a href="mailto:info@slimmermetai.com">info@slimmermetai.com</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Knoppen -->
        <div class="row mt-4 text-center">
            <div class="col-12">
                <a href="/" class="btn btn-primary btn-lg">Terug naar de homepagina</a>
            </div>
        </div>
    </div>
    
<?php require_once __DIR__ . '/components/footer.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
