<?php
namespace App\Infrastructure\Security;

use function container;

class CsrfProtection {
    private static ?CsrfProtection $instance=null;
    private string $tokenName='csrf_token';
    private string $headerName='X-CSRF-Token';
    private string $cookieName='CSRF-Token';
    private int $tokenLength=32;
    private int $tokenLifetime=7200;

    /**
     * Legacy helper: haalt instantie uit DI-container.
     */
    public static function getInstance(): self { return container()->get(self::class); }

    public function __construct(){ if(session_status()===PHP_SESSION_NONE) session_start(); }
    public function generateToken(): string { $token=bin2hex(random_bytes($this->tokenLength/2)); $_SESSION[$this->tokenName]=['token'=>$token,'expires'=>time()+$this->tokenLifetime]; $this->setTokenCookie($token); return $token; }
    public function getToken(bool $refresh=false): string { if($refresh||!isset($_SESSION[$this->tokenName])||$_SESSION[$this->tokenName]['expires']<time()) return $this->generateToken(); return $_SESSION[$this->tokenName]['token']; }
    public function generateTokenField(bool $refresh=false): string {return '<input type="hidden" name="'.$this->tokenName.'" value="'.$this->getToken($refresh).'">';}
    public function validateToken($token=null): bool { if($token===null) $token=$this->getTokenFromRequest(); if($token===null) return false; if(!isset($_SESSION[$this->tokenName])) return false; if($_SESSION[$this->tokenName]['expires']<time()) return false; return hash_equals($_SESSION[$this->tokenName]['token'],$token);}    public function removeToken(): void { unset($_SESSION[$this->tokenName]); if(isset($_COOKIE[$this->cookieName])) setcookie($this->cookieName,'',time()-3600,'/','',true,true); }
    private function setTokenCookie($token){ setcookie($this->cookieName,$token,['expires'=>time()+$this->tokenLifetime,'path'=>'/','secure'=>true,'httponly'=>false,'samesite'=>'Strict']); }
    private function getTokenFromRequest(){ if(isset($_POST[$this->tokenName])) return $_POST[$this->tokenName]; $headers=function_exists('getallheaders')?getallheaders():[]; if(isset($headers[$this->headerName])) return $headers[$this->headerName]; $hk='HTTP_'.strtoupper(str_replace('-','_',$this->headerName)); if(isset($_SERVER[$hk])) return $_SERVER[$hk]; if(isset($_GET[$this->tokenName])) return $_GET[$this->tokenName]; if(isset($_COOKIE[$this->cookieName])) return $_COOKIE[$this->cookieName]; return null; }
    public function verifyRequestToken(bool $exitOnFailure=true): bool { if(!$this->validateToken()){ if($exitOnFailure){ http_response_code(403); echo 'Invalid CSRF token'; exit; } return false;} return true; }
    // setters
    public function setTokenName($n){$this->tokenName=$n;}
    public function setHeaderName($n){$this->headerName=$n;}
    public function setCookieName($n){$this->cookieName=$n;}
    public function setTokenLifetime($sec){$this->tokenLifetime=(int)$sec;}
    public function setTokenLength($len){$this->tokenLength=(int)$len;}
} 