# Database Connectie Unificatie - Migratie Gids

## Probleem
Het project heeft 3 verschillende database toegang methoden:
- **Legacy**: Directe PDO in `var/www/html/includes/Auth.php`
- **Gemengd**: `includes/StripeHelper.php` gebruikt `global $pdo`
- **Modern**: `src/Infrastructure/Database/Database.php` met DI container

## Oplossing: Geleidelijke Unificatie

### Stap 1: Legacy Bridge Implementatie ✅
- `includes/legacy/DatabaseBridge.php` gemaakt
- Global `$pdo` wordt nu automatisch gevuld via moderne Database klasse
- Backward compatibility behouden

### Stap 2: Legacy Code Identificatie

**A. Directe PDO Toegang:**
```php
// ❌ VOOR (Legacy)
$pdo = new PDO($dsn, $user, $pass);
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");

// ✅ NA (Bridge)
require_once 'includes/legacy/DatabaseBridge.php';
$pdo = getLegacyDatabase();
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");

// 🎯 EINDTOESTAND (Modern)
$database = container()->get(Database::class);
$users = $database->fetchAll("SELECT * FROM users WHERE id = ?", [$id]);
```

**B. Global $pdo Toegang:**
```php
// ❌ VOOR (Legacy)
function getUserById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// ✅ NA (Bridge)
function getUserById($id) {
    require_once 'includes/legacy/DatabaseBridge.php';
    $pdo = getLegacyDatabase();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// 🎯 EINDTOESTAND (Modern)
class UserRepository {
    public function __construct(private DatabaseInterface $database) {}
    
    public function findById(int $id): ?array {
        return $this->database->fetch("SELECT * FROM users WHERE id = ?", [$id]);
    }
}
```

### Stap 3: Prioriteiten voor Migratie

**HOGE PRIORITEIT:**
1. `includes/StripeHelper.php` - gebruikt veel `global $pdo`
2. `var/www/html/includes/Auth.php` - kritieke authenticatie code
3. API endpoints in `public_html/api/` - veel database calls

**MIDDELE PRIORITEIT:**
4. Controller classes die nog directe PDO gebruiken
5. Legacy helper functions

**LAGE PRIORITEIT:**
6. Standalone PHP bestanden
7. Utility scripts

### Stap 4: Migratie Per Bestand

#### A. StripeHelper.php Moderniseren
```php
// ❌ VOOR
class StripeHelper {
    public function getUserStripeId($userId) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT stripe_customer_id FROM users WHERE id = ?");
        // ...
    }
}

// ✅ NA
class StripeHelper {
    private DatabaseInterface $database;
    
    public function __construct(DatabaseInterface $database = null) {
        $this->database = $database ?? container()->get(Database::class);
    }
    
    public function getUserStripeId($userId) {
        return $this->database->getValue(
            "SELECT stripe_customer_id FROM users WHERE id = ?", 
            [$userId]
        );
    }
}
```

#### B. Auth.php Moderniseren
```php
// ❌ VOOR
class Auth {
    private $pdo;
    
    public function __construct() {
        $this->pdo = new PDO(/* ... */);
    }
}

// ✅ NA
class Auth {
    public function __construct(private DatabaseInterface $database) {}
    
    public function validateUser(string $email, string $password): ?array {
        return $this->database->fetch(
            "SELECT * FROM users WHERE email = ? AND active = 1", 
            [$email]
        );
    }
}
```

### Stap 5: Testing & Validatie

#### A. Migratie Tests
```php
// Test legacy bridge werkt
public function testLegacyBridgeWorks() {
    require_once 'includes/legacy/DatabaseBridge.php';
    $pdo = getLegacyDatabase();
    $this->assertInstanceOf(PDO::class, $pdo);
}

// Test moderne interface werkt
public function testModernDatabaseWorks() {
    $database = getModernDatabase();
    $this->assertInstanceOf(DatabaseInterface::class, $database);
}
```

#### B. Performance Monitoring
```php
// Log legacy usage voor tracking
function trackLegacyUsage() {
    logLegacyDatabaseUsage('StripeHelper::getUserStripeId');
}
```

### Stap 6: Cleanup (Eindtoestand)

Na volledige migratie:
1. Verwijder `includes/legacy/DatabaseBridge.php`
2. Verwijder alle `global $pdo` statements
3. Verwijder directe PDO instanties
4. Update alle classes naar dependency injection

### Voordelen na Migratie

✅ **Consistency**: Één database interface  
✅ **Testability**: Mockbare dependencies  
✅ **Maintainability**: Centrale database logica  
✅ **Performance**: Connection pooling  
✅ **Security**: Consistente query building  
✅ **Debugging**: Gecentraliseerde logging  

### Monitoring

Gebruik deze query om legacy usage te tracken:
```bash
grep -r "global \$pdo" . --exclude-dir=vendor
grep -r "new PDO" . --exclude-dir=vendor  
grep -r "getLegacyDatabase" . --exclude-dir=vendor
```

### Timeline
- **Week 1**: Legacy Bridge implementeren
- **Week 2**: StripeHelper.php migreren  
- **Week 3**: Auth.php migreren
- **Week 4**: API endpoints migreren
- **Week 5**: Testing & cleanup 