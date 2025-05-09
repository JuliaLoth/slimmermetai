<?php
namespace App\Infrastructure\Database;

use PDO;use PDOException;use App\Infrastructure\Config\Config;use ErrorHandler; // ErrorHandler blijft legacy path

class Database {
    private static ?Database $instance = null; private ?PDO $pdo = null; private bool $isConnected = false; private int $transactionCounter = 0;
    private function __construct(){}
    public static function getInstance(): Database {return self::$instance??=new Database();}
    public function connect(): bool {
        if($this->isConnected) return true;
        $config = Config::getInstance();
        $dsn="mysql:host={$config->get('db_host')};dbname={$config->get('db_name')};charset={$config->get('db_charset')}";
        try{
            $this->pdo=new PDO($dsn,$config->get('db_user'),$config->get('db_pass'),[
                PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES=>false,
                PDO::MYSQL_ATTR_INIT_COMMAND=>"SET NAMES {$config->get('db_charset')}"
            ]);
            $this->isConnected=true;return true;
        }catch(PDOException $e){
            ErrorHandler::getInstance()->logError('Database connectie mislukt',['error'=>$e->getMessage()]);
            if($config->get('debug_mode')) throw $e; throw new PDOException('Database verbindingsfout. Probeer later opnieuw.');
        }
    }
    public function disconnect(): void {$this->pdo=null;$this->isConnected=false;}
    public function beginTransaction(): bool {if(!$this->isConnected) $this->connect(); if(!$this->transactionCounter) $this->pdo->beginTransaction(); $this->transactionCounter++; return true;}
    public function commit(): bool {if(!$this->isConnected) return false; $this->transactionCounter--; if(!$this->transactionCounter) $this->pdo->commit(); return true;}
    public function rollback() {if(!$this->isConnected) return false; $this->transactionCounter=0; return $this->pdo->rollBack();}
    private function execute(string $sql,array $params=[]){if(!$this->isConnected) $this->connect(); try{ $stmt=$this->pdo->prepare($sql);$stmt->execute($params);return $stmt; }catch(PDOException $e){ ErrorHandler::getInstance()->logError('DB query mislukt',['query'=>$sql,'params'=>json_encode($params),'error'=>$e->getMessage()]); throw $e;}}
    public function query($sql,$params=[]){return $this->execute($sql,$params);}public function fetch($sql,$params=[]){return $this->execute($sql,$params)->fetch();}public function fetchAll($sql,$params=[]){return $this->execute($sql,$params)->fetchAll();}
    public function insert($table,$data){$this->connect();$fields=array_keys($data);$placeholders=str_repeat('?,',count($fields)-1).'?';$sql="INSERT INTO $table (".implode(',',$fields).") VALUES ($placeholders)"; $this->execute($sql,array_values($data)); return $this->pdo->lastInsertId();}
    public function update($table,$data,$where,$whereParams=[]){$this->connect();$set=[];$vals=[];foreach($data as $k=>$v){$set[]="$k=?";$vals[]=$v;} $sql="UPDATE $table SET ".implode(',',$set)." WHERE $where"; $stmt=$this->execute($sql,array_merge($vals,$whereParams)); return $stmt->rowCount();}
    public function delete($table,$where,$params=[]){$this->connect();$sql="DELETE FROM $table WHERE $where";return $this->execute($sql,$params)->rowCount();}
    public function exists($table,$where,$params=[]){return (bool)$this->getValue("SELECT 1 FROM $table WHERE $where LIMIT 1",$params);}
    public function count($table,$where='',$params=[]){$sql="SELECT COUNT(*) cnt FROM $table".($where?" WHERE $where":"");return (int)$this->getValue($sql,$params);}
    public function getValue($sql,$params=[],$column=null){$row=$this->fetch($sql,$params); if(!$row) return null; return $column!==null?$row[$column]:reset($row);}public function getColumn($sql,$params=[],$column=null){$rows=$this->fetchAll($sql,$params);return array_column($rows,$column??0);}public function getPdo(): ?PDO {return $this->pdo;}
} 