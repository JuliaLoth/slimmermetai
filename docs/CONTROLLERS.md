# 🎮 Controller Overzicht

*Automatisch gegenereerd op: 2025-06-06 15:11:05*

## Status Overzicht
- ✅ **Moderne Controllers**: 6
- ⚠️ **Legacy Controllers**: 80

## ✅ Moderne Controllers (met DI)

### AuthController
📁 `/src/Http/Controller\Api\AuthController.php`
**Dependencies:**
- AuthRepositoryInterface
- DatabaseInterface

### GoogleAuthController
📁 `/src/Http/Controller\Api\GoogleAuthController.php`
**Dependencies:**
- ErrorLoggerInterface

### HealthController
📁 `/src/Http/Controller\Api\HealthController.php`
**Dependencies:**
- DatabaseInterface

### PaymentController
📁 `/src/Http/Controller\Api\PaymentController.php`
**Dependencies:**
- PaymentRepositoryInterface

### UserController
📁 `/src/Http/Controller\Api\UserController.php`
**Dependencies:**
- UserRepositoryInterface
- DatabaseInterface

### CourseDetailController
📁 `/src/Http/Controller\CourseDetailController.php`
**Dependencies:**
- CourseRepositoryInterface

## ⚠️ Legacy Controllers (te moderniseren)

### AccountPageController
📁 `/src/Http/Controller\AccountPageController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### IndexController
📁 `/src/Http/Controller\Api\IndexController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### PresentationConvertController
📁 `/src/Http/Controller\Api\PresentationConvertController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### ProxyController
📁 `/src/Http/Controller\Api\ProxyController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### SessionController
📁 `/src/Http/Controller\Api\SessionController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### StripePaymentIntentController
📁 `/src/Http/Controller\Api\StripePaymentIntentController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### ForgotPasswordController
📁 `/src/Http/Controller\Auth\ForgotPasswordController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### ForgotPasswordPageController
📁 `/src/Http/Controller\Auth\ForgotPasswordPageController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### LoginController
📁 `/src/Http/Controller\Auth\LoginController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### LoginPageController
📁 `/src/Http/Controller\Auth\LoginPageController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### LoginSuccessPageController
📁 `/src/Http/Controller\Auth\LoginSuccessPageController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### LogoutController
📁 `/src/Http/Controller\Auth\LogoutController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### MeController
📁 `/src/Http/Controller\Auth\MeController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### RefreshTokenController
📁 `/src/Http/Controller\Auth\RefreshTokenController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### RegisterController
📁 `/src/Http/Controller\Auth\RegisterController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### RegisterPageController
📁 `/src/Http/Controller\Auth\RegisterPageController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### AuthController
📁 `/src/Http/Controller\AuthController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### BetalenController
📁 `/src/Http/Controller\BetalenController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### CartController
📁 `/src/Http/Controller\CartController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### CourseImagesGeneratorController
📁 `/src/Http/Controller\CourseImagesGeneratorController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### CourseViewerController
📁 `/src/Http/Controller\CourseViewerController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### DashboardController
📁 `/src/Http/Controller\DashboardController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### ElearningDashboardController
📁 `/src/Http/Controller\ElearningDashboardController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### ElearningsController
📁 `/src/Http/Controller\ElearningsController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### HomeController
📁 `/src/Http/Controller\HomeController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### AccountController
📁 `/src/Http/Controller\Legacy\AccountController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### ApiAuthLoginController
📁 `/src/Http/Controller\Legacy\ApiAuthLoginController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### ApiAuthRegisterController
📁 `/src/Http/Controller\Legacy\ApiAuthRegisterController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### ApiCreatePaymentIntentController
📁 `/src/Http/Controller\Legacy\ApiCreatePaymentIntentController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### ApiEnvDumpController
📁 `/src/Http/Controller\Legacy\ApiEnvDumpController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### ApiGoogleAuthController
📁 `/src/Http/Controller\Legacy\ApiGoogleAuthController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### ApiProxyController
📁 `/src/Http/Controller\Legacy\ApiProxyController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### ApiSlimmerPresenterenConvertController
📁 `/src/Http/Controller\Legacy\ApiSlimmerPresenterenConvertController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### ApiUsersRegisterController
📁 `/src/Http/Controller\Legacy\ApiUsersRegisterController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### BetalenController
📁 `/src/Http/Controller\Legacy\BetalenController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### BetalingSuccesController
📁 `/src/Http/Controller\Legacy\BetalingSuccesController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### BetalingVoltooidController
📁 `/src/Http/Controller\Legacy\BetalingVoltooidController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### ComponentsFooterController
📁 `/src/Http/Controller\Legacy\ComponentsFooterController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### ComponentsHeadController
📁 `/src/Http/Controller\Legacy\ComponentsHeadController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### ComponentsHeaderController
📁 `/src/Http/Controller\Legacy\ComponentsHeaderController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### ComponentsMetaTagsController
📁 `/src/Http/Controller\Legacy\ComponentsMetaTagsController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### CourseImagesGeneratorController
📁 `/src/Http/Controller\Legacy\CourseImagesGeneratorController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### DashboardController
📁 `/src/Http/Controller\Legacy\DashboardController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### ELearningsController
📁 `/src/Http/Controller\Legacy\ELearningsController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### Error403Controller
📁 `/src/Http/Controller\Legacy\Error403Controller.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### Error404Controller
📁 `/src/Http/Controller\Legacy\Error404Controller.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### Error500Controller
📁 `/src/Http/Controller\Legacy\Error500Controller.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### ForgotPasswordController
📁 `/src/Http/Controller\Legacy\ForgotPasswordController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### ImageFixController
📁 `/src/Http/Controller\Legacy\ImageFixController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### ImagesCoursesProfilePlaceholderController
📁 `/src/Http/Controller\Legacy\ImagesCoursesProfilePlaceholderController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### ImagesProfilePlaceholderController
📁 `/src/Http/Controller\Legacy\ImagesProfilePlaceholderController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### IncludesHeadController
📁 `/src/Http/Controller\Legacy\IncludesHeadController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### IncludesHeaderController
📁 `/src/Http/Controller\Legacy\IncludesHeaderController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### LoginController
📁 `/src/Http/Controller\Legacy\LoginController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### LoginSuccessController
📁 `/src/Http/Controller\Legacy\LoginSuccessController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### MijnCursussenController
📁 `/src/Http/Controller\Legacy\MijnCursussenController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### MijnToolsController
📁 `/src/Http/Controller\Legacy\MijnToolsController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### NieuwsController
📁 `/src/Http/Controller\Legacy\NieuwsController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### OverMijController
📁 `/src/Http/Controller\Legacy\OverMijController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### ProfielController
📁 `/src/Http/Controller\Legacy\ProfielController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### RegisterController
📁 `/src/Http/Controller\Legacy\RegisterController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### SlimmerPresenterenToolController
📁 `/src/Http/Controller\Legacy\SlimmerPresenterenToolController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### StripeApiConfigController
📁 `/src/Http/Controller\Legacy\StripeApiConfigController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### StripeCheckoutSessionController
📁 `/src/Http/Controller\Legacy\StripeCheckoutSessionController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### StripeConfigController
📁 `/src/Http/Controller\Legacy\StripeConfigController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### StripeWebhookController
📁 `/src/Http/Controller\Legacy\StripeWebhookController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### ToolsController
📁 `/src/Http/Controller\Legacy\ToolsController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### WinkelwagenController
📁 `/src/Http/Controller\Legacy\WinkelwagenController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### LegacyPageController
📁 `/src/Http/Controller\LegacyPageController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### MyCoursesController
📁 `/src/Http/Controller\MyCoursesController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### MyToolsController
📁 `/src/Http/Controller\MyToolsController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### NieuwsController
📁 `/src/Http/Controller\NieuwsController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### OverMijController
📁 `/src/Http/Controller\OverMijController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### PaymentCompletedController
📁 `/src/Http/Controller\PaymentCompletedController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### PaymentSuccessController
📁 `/src/Http/Controller\PaymentSuccessController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### ProfileController
📁 `/src/Http/Controller\ProfileController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### StripeController
📁 `/src/Http/Controller\StripeController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### ToolDetailController
📁 `/src/Http/Controller\ToolDetailController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### ToolsController
📁 `/src/Http/Controller\ToolsController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

### UserController
📁 `/src/Http/Controller\UserController.php`
🔧 **Actie nodig**: Moderniseer naar repository pattern

