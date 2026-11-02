<?php

# project/classes/DatabaseConnection.php

class DatabaseConnection {

    private PDO $pdo;
    private bool $inUse;
    public int $id;

    public function __construct(int $id) {
        $this->inUse = true;
        $this->id = $id;
        $config = require __DIR__ . '/../config/dbconfig.php';

        $dsn = "mysql:host={$config['host']};dbname={$config['db']};charset=utf8mb4";

        $this->pdo = new PDO(
            $dsn,
            $config['user'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
                PDO::ATTR_PERSISTENT         => false,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );

        $this->pdo->exec("SET SESSION time_zone = '+00:00'");

        $this->inUse = false;
    }

    public function query(string $sql, array $parameters = []): PDOStatement {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parameters);
        return $stmt;
    }

    public function fetch(string $sql, array $params = []): array|false {
        return $this->query($sql, $params)->fetch();
    }

    public function fetchAll(string $sql, array $params = []): array {
        return $this->query($sql, $params)->fetchAll();
    }

    public function beginTransaction(): bool {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool {
        return $this->pdo->commit();
    }

    public function rollBack(): bool {
        if ($this->pdo->inTransaction()) {
            return $this->pdo->rollBack();
        }

        return false;
    }

    public function inTransaction(): bool {
        return $this->pdo->inTransaction();
    }

    public function lastInsertId(): string|false {
        return $this->pdo->lastInsertId();
    }

    public function quote(string $value): string|false {
        return $this->pdo->quote($value);
    }

    public function getPdo(): PDO {
        return $this->pdo;
    }

    // PDO usage
    public function inUse(): bool {
        return $this->inUse;
    }

    public function returnToPool() {
        if ($this->inTransaction() === true) {
            $this->pdo->rollBack();
            error_log("invalid DBConnection returned to pool.");
        }
        $this->inUse = false;
    }

    public function acquire(): bool {
        if ($this->readyForUse()) {
            $this->inUse = true;
            return true;
        } else {
            return false;
        }
    }
    public function validate() {
        try {
            $stmt = $this->fetch("SELECT 1");
        } catch (Throwable $e) {
            $stmt = false;
        }

        if ($stmt === false) {
            $config = require __DIR__ . '/../config/dbconfig.php';
            $dsn = "mysql:host={$config['host']};dbname={$config['db']};charset=utf8mb4";

            $this->pdo = new PDO(
                $dsn,
                $config['user'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_STRINGIFY_FETCHES  => false,
                    PDO::ATTR_PERSISTENT         => false,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );

            $this->pdo->exec("SET SESSION time_zone = '+00:00'");

        }
    }
    public function readyForUse(): bool {
        return ($this->inUse === false) && ($this->inTransaction() === false);
    }
}