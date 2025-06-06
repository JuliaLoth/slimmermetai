<?php
define('SKIP_DB', true); // VOOR LOCAL DEVELOPMENT ZONDER DATABASE
// Unified front controller met FastRoute + PHP-DI

require_once dirname(__DIR__) . '/bootstrap.php';

use function FastRoute\simpleDispatcher;
use FastRoute\RouteCollector;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use App\Infrastructure\Http\ResponseEmitter;
use Psr\Http\Message\ServerRequestInterface;

// PSR-15
use App\Http\Middleware\MiddlewareDispatcher;
use App\Http\Routing\FastRouteRequestHandler;
use App\Http\Middleware\ErrorHandlingMiddleware;
use App\Http\Middleware\CorsMiddleware;
use App\Http\Middleware\BodyParsingMiddleware;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Middleware\AuthenticationMiddleware;
use App\Http\Middleware\RateLimitMiddleware;

$dispatcher = simpleDispatcher(function (RouteCollector $r) {
    // ----- Development Asset Handler (alleen in development) -----
    if (getenv('APP_ENV') === 'local' || getenv('APP_ENV') === 'development') {
        $r->addRoute('GET', '/dev-asset/{filename:.+}', function(string $filename) {
            $assetPath = __DIR__ . '/assets/js/' . $filename;
            
            if (!file_exists($assetPath)) {
                http_response_code(404);
                echo "Asset not found: " . htmlspecialchars($filename);
                return;
            }
            
            // Determine content type
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $contentType = match($extension) {
                'css' => 'text/css',
                'js' => 'application/javascript',
                'json' => 'application/json',
                'svg' => 'image/svg+xml',
                'png' => 'image/png',
                'jpg', 'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'woff' => 'font/woff',
                'woff2' => 'font/woff2',
                'ttf' => 'font/ttf',
                'eot' => 'application/vnd.ms-fontobject',
                default => 'application/octet-stream'
            };
            
            header('Content-Type: ' . $contentType);
            header('Cache-Control: public, max-age=3600'); // 1 hour cache for development
            readfile($assetPath);
            exit;
        });
    }

    // ----- Moderne controllers -----
    $r->addRoute('GET',  '/',              [App\Http\Controller\HomeController::class,        'index']);
    $r->addRoute('POST', '/auth/login',    [App\Http\Controller\Auth\LoginController::class, 'handle']);
    $r->addRoute('POST', '/auth/register', [App\Http\Controller\Auth\RegisterController::class,'handle']);
    $r->addRoute('POST', '/auth/forgot-password', [App\Http\Controller\Auth\ForgotPasswordController::class, 'handle']);
    $r->addRoute('POST', '/auth/refresh',  [App\Http\Controller\Auth\RefreshTokenController::class,'handle']);
    $r->addRoute('GET',  '/auth/me',       [App\Http\Controller\Auth\MeController::class,'handle']);
    $r->addRoute('POST', '/auth/logout',   [App\Http\Controller\Auth\LogoutController::class,'handle']);

    // ----- Stripe -----
    $r->addRoute('POST', '/api/stripe/checkout',   [App\Http\Controller\StripeController::class,'createSession']);
    $r->addRoute('GET',  '/api/stripe/status/{id}',[App\Http\Controller\StripeController::class,'status']);
    $r->addRoute('GET',  '/api/stripe/config',     [App\Http\Controller\StripeController::class,'config']);
    $r->addRoute('POST', '/api/stripe/webhook',    [App\Http\Controller\StripeController::class,'webhook']);

    // Legacy automatische registratie verwijderd – alle oude .php pagina's worden nu via .htaccess 301-redirects afgehandeld.

    // ----- Nieuwe routes -----
    $r->addRoute('GET',  '/tools',      [App\Http\Controller\ToolsController::class,       'index']);
    $r->addRoute('GET',  '/nieuws',     [App\Http\Controller\NieuwsController::class,      'index']);
    $r->addRoute('GET',  '/over-mij',   [App\Http\Controller\OverMijController::class,     'index']);
    $r->addRoute('GET',  '/e-learnings',[App\Http\Controller\ElearningsController::class,  'index']);
    $r->addRoute('GET',  '/ai-cursussen',[App\Http\Controller\ElearningsController::class,  'index']);
    $r->addRoute('GET',  '/login',      [App\Http\Controller\Auth\LoginPageController::class, 'index']);
    $r->addRoute('GET',  '/register',   [App\Http\Controller\Auth\RegisterPageController::class, 'index']);
    $r->addRoute('GET',  '/profiel',    [App\Http\Controller\ProfileController::class, 'index']);
    $r->addRoute('GET',  '/account',    [App\Http\Controller\AccountPageController::class, 'index']);
    $r->addRoute('GET',  '/dashboard',  [App\Http\Controller\DashboardController::class, 'index']);
    $r->addRoute('GET',  '/winkelwagen', [App\Http\Controller\CartController::class, 'index']);
    $r->addRoute('GET',  '/forgot-password', [App\Http\Controller\Auth\ForgotPasswordPageController::class, 'index']);
    $r->addRoute('GET',  '/mijn-tools', [App\Http\Controller\MyToolsController::class, 'index']);
    $r->addRoute('GET',  '/mijn-cursussen', [App\Http\Controller\MyCoursesController::class, 'index']);
    $r->addRoute('GET',  '/betaling-succes', [App\Http\Controller\PaymentSuccessController::class, 'index']);
    $r->addRoute('GET',  '/betaling-voltooid', [App\Http\Controller\PaymentCompletedController::class, 'index']);
    $r->addRoute('GET',  '/login-success', [App\Http\Controller\Auth\LoginSuccessPageController::class, 'index']);
    $r->addRoute('GET',  '/betalen',  [App\Http\Controller\BetalenController::class, 'index']);

    // Tool detail pages
    $r->addRoute('GET',  '/tools/email-assistant', [App\Http\Controller\ToolDetailController::class, 'emailAssistant']);
    $r->addRoute('GET',  '/tools/document-analyzer', [App\Http\Controller\ToolDetailController::class, 'documentAnalyzer']);
    $r->addRoute('GET',  '/tools/meeting-summarizer', [App\Http\Controller\ToolDetailController::class, 'meetingSummarizer']);
    $r->addRoute('GET',  '/tools/rapport-generator', [App\Http\Controller\ToolDetailController::class, 'rapportGenerator']);

    // Course detail pages
    $r->addRoute('GET',  '/e-learnings/ai-basics', [App\Http\Controller\CourseDetailController::class, 'aiBasics']);
    $r->addRoute('GET',  '/e-learnings/prompt-engineering', [App\Http\Controller\CourseDetailController::class, 'promptEngineering']);
    $r->addRoute('GET',  '/e-learnings/ai-automation', [App\Http\Controller\CourseDetailController::class, 'aiAutomation']);
    $r->addRoute('GET',  '/e-learnings/ai-strategy', [App\Http\Controller\CourseDetailController::class, 'aiStrategy']);
    $r->addRoute('GET',  '/e-learnings/ai-content', [App\Http\Controller\CourseDetailController::class, 'aiContent']);
    $r->addRoute('GET',  '/e-learnings/ai-data', [App\Http\Controller\CourseDetailController::class, 'aiData']);

    // Legacy alias route
    $r->addRoute('GET', '/tools.php', function() {
        header('Location: /tools', true, 301);
        exit;
    });

    $r->addRoute('GET', '/nieuws.php', function() {
        header('Location: /nieuws', true, 301);
        exit;
    });

    $r->addRoute('GET', '/over-mij.php', function() {
        header('Location: /over-mij', true, 301);
        exit;
    });
    $r->addRoute('GET', '/e-learnings.php', function() {
        header('Location: /e-learnings', true, 301);
        exit;
    });
    $r->addRoute('GET', '/ai-cursussen.php', function() {
        header('Location: /ai-cursussen', true, 301);
        exit;
    });
    $r->addRoute('GET', '/profiel.php', function() {
        header('Location: /profiel', true, 301);
        exit;
    });
    $r->addRoute('GET', '/account.php', function() {
        header('Location: /account', true, 301);
        exit;
    });
    $r->addRoute('GET', '/dashboard.php', function() {
        header('Location: /dashboard', true, 301);
        exit;
    });
    $r->addRoute('GET', '/winkelwagen.php', function() {
        header('Location: /winkelwagen', true, 301);
        exit;
    });
    $r->addRoute('GET', '/forgot-password.php', function() {
        header('Location: /forgot-password', true, 301);
        exit;
    });
    $r->addRoute('GET', '/login.php', function() {
        header('Location: /login', true, 301);
        exit;
    });
    $r->addRoute('GET', '/register.php', function() {
        header('Location: /register', true, 301);
        exit;
    });
    $r->addRoute('GET', '/mijn-tools.php', function() {
        header('Location: /mijn-tools', true, 301);
        exit;
    });
    $r->addRoute('GET', '/mijn-cursussen.php', function() {
        header('Location: /mijn-cursussen', true, 301);
        exit;
    });
    $r->addRoute('GET', '/betaling-succes.php', function() {
        header('Location: /betaling-succes', true, 301);
        exit;
    });
    $r->addRoute('GET', '/betaling-voltooid.php', function() {
        header('Location: /betaling-voltooid', true, 301);
        exit;
    });
    $r->addRoute('GET', '/login-success.php', function() {
        header('Location: /login-success', true, 301);
        exit;
    });

    $r->addRoute('GET', '/betalen.php', function() {
        header('Location: /betalen', true, 301);
        exit;
    });

    // ---- Via generator gegenereerde legacy-routes ----
    $legacyRoutesFile = dirname(__DIR__) . '/routes/legacy.php';
    if (is_file($legacyRoutesFile)) {
        (require $legacyRoutesFile)($r);
    }

    $r->addRoute('GET',  '/course-images-generator', [App\Http\Controller\CourseImagesGeneratorController::class, 'index']);

    // ----- API nieuwe controllers -----
    $r->addRoute('GET',  '/api', [App\Http\Controller\Api\IndexController::class, 'handle']);
    
    // ----- Health Check Endpoints -----
    $r->addRoute('GET', '/api/health', [App\Http\Controller\Api\HealthController::class, 'health']);
    $r->addRoute('GET', '/api/status', [App\Http\Controller\Api\HealthController::class, 'status']);
    $r->addRoute('GET', '/api/ready', [App\Http\Controller\Api\HealthController::class, 'ready']);
    $r->addRoute('GET', '/api/metrics', [App\Http\Controller\Api\HealthController::class, 'metrics']);
    
    $r->addRoute(['GET','OPTIONS'], '/api-proxy', [App\Http\Controller\Api\ProxyController::class, 'handle']);
    $r->addRoute(['GET','OPTIONS'], '/api-proxy.php', function() {
        header('Location: /api-proxy' . (isset($_SERVER['QUERY_STRING']) ? ('?'.$_SERVER['QUERY_STRING']) : ''), true, 301);
        exit;
    });

    $r->addRoute(['POST','OPTIONS'], '/api/stripe/payment-intent', [App\Http\Controller\Api\StripePaymentIntentController::class, 'create']);

    // ----- Moderne Auth API Routes -----
    $r->addRoute(['POST','OPTIONS'], '/api/auth/register', [App\Http\Controller\Api\AuthController::class, 'handle']);
    $r->addRoute(['POST','OPTIONS'], '/api/auth/login', [App\Http\Controller\Api\AuthController::class, 'handle']);
    $r->addRoute(['POST','OPTIONS'], '/api/auth/verify-email', [App\Http\Controller\Api\AuthController::class, 'handle']);
    $r->addRoute(['POST','OPTIONS'], '/api/auth/forgot-password', [App\Http\Controller\Api\AuthController::class, 'handle']);
    $r->addRoute(['POST','OPTIONS'], '/api/auth/reset-password', [App\Http\Controller\Api\AuthController::class, 'handle']);
    $r->addRoute(['POST','OPTIONS'], '/api/auth/refresh', [App\Http\Controller\Api\AuthController::class, 'handle']);
    $r->addRoute(['GET','OPTIONS'], '/api/auth/me', [App\Http\Controller\Api\AuthController::class, 'handle']);
    $r->addRoute(['POST','OPTIONS'], '/api/auth/logout', [App\Http\Controller\Api\AuthController::class, 'handle']);
    
    // ----- Moderne User API Routes -----
    $r->addRoute(['GET','PUT','OPTIONS'], '/api/users/profile', [App\Http\Controller\Api\UserController::class, 'handle']);
    $r->addRoute(['PUT','OPTIONS'], '/api/users/password', [App\Http\Controller\Api\UserController::class, 'handle']);
    $r->addRoute(['GET','PUT','OPTIONS'], '/api/users/preferences', [App\Http\Controller\Api\UserController::class, 'handle']);
    $r->addRoute(['GET','OPTIONS'], '/api/users/stats', [App\Http\Controller\Api\UserController::class, 'handle']);
    $r->addRoute(['GET','OPTIONS'], '/api/users/courses', [App\Http\Controller\Api\UserController::class, 'handle']);
    $r->addRoute(['GET','OPTIONS'], '/api/users/tools', [App\Http\Controller\Api\UserController::class, 'handle']);
    $r->addRoute(['POST','OPTIONS'], '/api/users/deactivate', [App\Http\Controller\Api\UserController::class, 'handle']);

    // ----- API legacy alias routes (tijdelijk) -----
    $r->addRoute('GET', '/api/index.php', function() { header('Location: /api', true, 301); exit; });

    $r->addRoute('POST', '/api/auth/login.php', [App\Http\Controller\Auth\LoginController::class, 'handle']);
    $r->addRoute('POST', '/api/auth/register.php', [App\Http\Controller\Auth\RegisterController::class, 'handle']);
    $r->addRoute('POST', '/api/users/register.php', [App\Http\Controller\UserController::class, 'register']);

    // Debug routes alleen in development
    if (getenv('APP_ENV') === 'local' || getenv('APP_ENV') === 'development') {
        $r->addRoute('GET',  '/api/stripe/simple-test.php', function() {
            header('Content-Type: text/plain; charset=utf-8');
            echo 'OK';
            exit;
        });
        
        $r->addRoute('GET', '/api/env-dump', function() { 
            header('Content-Type: text/plain'); 
            foreach ($_ENV as $k=>$v) { 
                echo "$k=$v\n"; 
            } 
            exit; 
        });
    }

    $r->addRoute(['POST','OPTIONS'], '/api/google_auth.php', [App\Http\Controller\Api\GoogleAuthController::class, 'handle']);

    // Google OAuth routes
    $r->addRoute('GET', '/api/auth/google.php', function() {
        require_once dirname(__DIR__) . '/api/auth/google.php';
        exit;
    });
    
    $r->addRoute('GET', '/api/auth/google-callback.php', function() {
        require_once dirname(__DIR__) . '/api/auth/google-callback.php';
        exit;
    });

    $r->addRoute(['POST','OPTIONS'], '/api/presentation/convert', [App\Http\Controller\Api\PresentationConvertController::class, 'convert']);
    $r->addRoute(['POST','OPTIONS'], '/api/slimmer-presenteren/convert.php', [App\Http\Controller\Api\PresentationConvertController::class, 'convert']);
});

// ---- Middleware dispatcher ----
$psrRequest = ServerRequest::fromGlobals();

$finalHandler = new FastRouteRequestHandler($dispatcher);

$middlewareStack = [
    container()->get(ErrorHandlingMiddleware::class),
    container()->get(CorsMiddleware::class),
    container()->get(BodyParsingMiddleware::class),
    container()->get(CsrfMiddleware::class),
    container()->get(AuthenticationMiddleware::class),
    container()->get(RateLimitMiddleware::class),
];

$runner = new MiddlewareDispatcher($middlewareStack, $finalHandler);

$response = $runner->handle($psrRequest);

(new ResponseEmitter())->emit($response);