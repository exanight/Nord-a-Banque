<?php
declare(strict_types=1);
final class Database {
    public readonly PDO $pdo;
    public function __construct(array $config) {
        $this->pdo = new PDO($config['db_dsn'], $config['db_user'], $config['db_password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    public function rows(string $sql, array $params = []): array { $q=$this->pdo->prepare($sql);$q->execute($params);return $q->fetchAll(); }
    public function one(string $sql, array $params = []): ?array { return $this->rows($sql,$params)[0]??null; }
    public function execute(string $sql, array $params = []): void { $q=$this->pdo->prepare($sql);$q->execute($params); }
    public function transaction(callable $fn): mixed {
        $this->pdo->beginTransaction();
        try { $result=$fn();$this->pdo->commit();return $result; }
        catch(Throwable $error) { if($this->pdo->inTransaction())$this->pdo->rollBack();throw $error; }
    }
}
