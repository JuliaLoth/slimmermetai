<?php
namespace App\Infrastructure\Config;

class Config {
    private static ?Config $instance = null;
    private array $settings = [];
    private bool $envLoaded = false;

    private function __construct() {
        $this->loadEnv();
        $this->initSettings();
    }

    public static function getInstance(): Config {
        return self::$instance ??= new Config();
    }

    private function loadEnv(): void {
        if ($this->envLoaded) return;
        $siteRoot = dirname(dirname(dirname(__DIR__)));
        $baseEnv = $siteRoot . '/.env';
        $env = getenv('APP_ENV') ?: 'production';
        $envSpecific = $siteRoot . '/.env.' . $env;
        if (file_exists($baseEnv)) $this->parseEnvFile($baseEnv);
        if (file_exists($envSpecific)) $this->parseEnvFile($envSpecific);
        $this->envLoaded = true;
    }

    private function parseEnvFile(string $file): void {
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (str_starts_with(trim($line), '#') || strpos($line, '=') === false) continue;
            [$k,$v] = explode('=', $line, 2);
            $k=trim($k); $v=trim($v);
            if (preg_match('/^(["\"]).*\1$/', $v)) $v = substr($v,1,-1);
            putenv("$k=$v"); $_ENV[$k]=$v; $_SERVER[$k]=$v;
        }
    }

    private function initSettings(): void {
        $cast = static fn($v,$t)=>match($t){
            'int'=>(int)$v,'bool'=>filter_var($v,FILTER_VALIDATE_BOOLEAN),'float'=>(float)$v,'array'=>is_array($v)?$v:array_map('trim',explode(',',(string)$v)),default=>$v};
        $siteRoot = dirname(dirname(dirname(__DIR__)));

        $this->settings = [
            // basis
            'site_name' => getenv('SITE_NAME') ?: 'SlimmerMetAI',
            'site_url'  => getenv('SITE_URL') ?: 'https://slimmermetai.com',
            'admin_email'=> getenv('ADMIN_EMAIL') ?: 'admin@slimmermetai.com',
            // database
            'db_host'=> getenv('DB_HOST') ?: 'localhost',
            'db_name'=> getenv('DB_NAME') ?: 'slimmermetai',
            'db_user'=> getenv('DB_USER') ?: 'root',
            'db_pass'=> getenv('DB_PASS') ?: '',
            'db_charset'=> getenv('DB_CHARSET') ?: 'utf8mb4',
            // paden
            'site_root'=> $siteRoot,
            'public_root'=> $siteRoot.'/public_html',
            'public_includes'=> $siteRoot.'/public_html/includes',
            'secure_includes'=> $siteRoot.'/includes',
            'uploads_dir'=> $siteRoot.'/public_html/uploads',
            'profile_pic_dir'=> $siteRoot.'/public_html/uploads/profile_pictures',
            'max_upload_size'=> $cast(getenv('MAX_UPLOAD_SIZE')?:5*1024*1024,'int'),
            'allowed_file_types'=> $cast(getenv('ALLOWED_FILE_TYPES')?:'jpg,jpeg,png,pdf,doc,docx','array'),
            // sessie
            'session_lifetime'=> $cast(getenv('SESSION_LIFETIME')?:60*60*24*7,'int'),
            'session_name'=> getenv('SESSION_NAME') ?: 'SLIMMERMETAI_SESSION',
            'cookie_domain'=> getenv('COOKIE_DOMAIN') ?: '',
            'cookie_path'=> getenv('COOKIE_PATH') ?: '/',
            'cookie_secure'=> $cast(getenv('COOKIE_SECURE')??true,'bool'),
            'cookie_httponly'=> $cast(getenv('COOKIE_HTTPONLY')??true,'bool'),
            // security
            'password_min_length'=> $cast(getenv('PASSWORD_MIN_LENGTH')?:8,'int'),
            'bcrypt_cost'=> $cast(getenv('BCRYPT_COST')?:12,'int'),
            'login_max_attempts'=> $cast(getenv('LOGIN_MAX_ATTEMPTS')?:5,'int'),
            'login_lockout_time'=> $cast(getenv('LOGIN_LOCKOUT_TIME')?:15*60,'int'),
            // mail
            'mail_from'=> getenv('MAIL_FROM') ?: 'noreply@slimmermetai.com',
            'mail_from_name'=> getenv('MAIL_FROM_NAME') ?: 'SlimmerMetAI',
            'mail_reply_to'=> getenv('MAIL_REPLY_TO') ?: 'support@slimmermetai.com',
            // recaptcha
            'recaptcha_site_key'=> getenv('RECAPTCHA_SITE_KEY') ?: '',
            'recaptcha_secret_key'=> getenv('RECAPTCHA_SECRET_KEY') ?: '',
            // jwt
            'jwt_secret'=> getenv('JWT_SECRET') ?: '',
            'jwt_expiration'=> $cast(getenv('JWT_EXPIRATION')?:3600,'int'),
            // stripe
            'stripe_secret_key'=> getenv('STRIPE_SECRET_KEY') ?: '',
            'stripe_public_key'=> getenv('STRIPE_PUBLIC_KEY') ?: '',
            'stripe_webhook_secret'=> getenv('STRIPE_WEBHOOK_SECRET') ?: '',
            // google
            'google_client_id'=> getenv('GOOGLE_CLIENT_ID') ?: '',
            'google_client_secret'=> getenv('GOOGLE_CLIENT_SECRET') ?: '',
        ];

        $this->settings['app_env'] = getenv('APP_ENV') ?: 'production';
        $defaultDebug = $this->settings['app_env']==='local';
        $this->settings['debug_mode']= $cast(getenv('DEBUG_MODE')??$defaultDebug,'bool');
        $this->settings['display_errors']= $cast(getenv('DISPLAY_ERRORS')??$defaultDebug,'bool');
        date_default_timezone_set(getenv('TIMEZONE')?:'Europe/Amsterdam');
    }

    public function get(string $k,$d=null){return $this->settings[$k]??$d;}
    public function set(string $k,$v):void{$this->settings[$k]=$v;}
    public function all():array{return $this->settings;}
    public function has(string $k):bool{return isset($this->settings[$k]);}
    public function defineConstants():void{foreach($this->settings as $k=>$v){$c=strtoupper($k);if(!defined($c))define($c,$v);}}
    public function getTyped(string $k,string $t,$d=null){$v=$this->get($k,$d);return match($t){
        'int'=>(int)$v,'bool'=>filter_var($v,FILTER_VALIDATE_BOOLEAN),'float'=>(float)$v,'array'=>is_array($v)?$v:array_map('trim',explode(',',(string)$v)),default=>$v};}
} 