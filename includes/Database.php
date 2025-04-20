<?php
class Database {
    private $pdo;
    private static $instance = null;
    private $logger;
    private $inTransaction = false;

    private function __construct() {
        try {
            $this->pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_FOUND_ROWS => true,
                    PDO::ATTR_PERSISTENT => true
                ]
            );
            $this->logger = Logger::getInstance();
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Could not connect to the database.");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function beginTransaction() {
        if (!$this->inTransaction) {
            $this->inTransaction = $this->pdo->beginTransaction();
            return $this->inTransaction;
        }
        return false;
    }

    public function commit() {
        if ($this->inTransaction) {
            $this->inTransaction = false;
            return $this->pdo->commit();
        }
        return false;
    }

    public function rollback() {
        if ($this->inTransaction) {
            $this->inTransaction = false;
            return $this->pdo->rollBack();
        }
        return false;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->handleError($e, $sql, $params);
            throw $e;
        }
    }

    public function fetchAll($sql, $params = []) {
        try {
            $stmt = $this->query($sql, $params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->handleError($e, $sql, $params);
            return [];
        }
    }

    public function fetchOne($sql, $params = []) {
        try {
            $stmt = $this->query($sql, $params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->handleError($e, $sql, $params);
            return null;
        }
    }

    public function insert($table, $data) {
        try {
            $fields = array_keys($data);
            $placeholders = array_fill(0, count($fields), '?');
            
            $sql = sprintf(
                "INSERT INTO %s (%s) VALUES (%s)",
                $table,
                implode(', ', $fields),
                implode(', ', $placeholders)
            );
            
            $stmt = $this->query($sql, array_values($data));
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            $this->handleError($e, "INSERT INTO $table", $data);
            throw $e;
        }
    }

    public function update($table, $data, $where, $params = []) {
        try {
            $fields = array_map(function($field) {
                return "$field = ?";
            }, array_keys($data));
            
            $sql = sprintf(
                "UPDATE %s SET %s WHERE %s",
                $table,
                implode(', ', $fields),
                $where
            );
            
            $stmt = $this->query($sql, array_merge(array_values($data), $params));
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->handleError($e, "UPDATE $table", array_merge($data, $params));
            throw $e;
        }
    }

    public function delete($table, $where, $params = []) {
        try {
            $sql = sprintf("DELETE FROM %s WHERE %s", $table, $where);
            $stmt = $this->query($sql, $params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->handleError($e, "DELETE FROM $table", $params);
            throw $e;
        }
    }

    public function count($table, $where = '1', $params = []) {
        try {
            $sql = sprintf("SELECT COUNT(*) as count FROM %s WHERE %s", $table, $where);
            $result = $this->fetchOne($sql, $params);
            return $result['count'] ?? 0;
        } catch (PDOException $e) {
            $this->handleError($e, "COUNT FROM $table", $params);
            return 0;
        }
    }

    public function exists($table, $where, $params = []) {
        return $this->count($table, $where, $params) > 0;
    }

    public function insertBatch($table, $data) {
        if (empty($data)) return 0;

        try {
            $fields = array_keys($data[0]);
            $placeholders = array_fill(0, count($fields), '?');
            $rowPlaceholders = '(' . implode(', ', $placeholders) . ')';
            $allPlaceholders = array_fill(0, count($data), $rowPlaceholders);
            
            $sql = sprintf(
                "INSERT INTO %s (%s) VALUES %s",
                $table,
                implode(', ', $fields),
                implode(', ', $allPlaceholders)
            );
            
            $values = [];
            foreach ($data as $row) {
                $values = array_merge($values, array_values($row));
            }
            
            $stmt = $this->query($sql, $values);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->handleError($e, "BATCH INSERT INTO $table", $data);
            throw $e;
        }
    }

    public function updateBatch($table, $data, $indexField) {
        if (empty($data)) return 0;

        try {
            $cases = [];
            $ids = [];
            $params = [];
            
            // Build CASE statements for each field
            $fields = array_keys(reset($data));
            foreach ($fields as $field) {
                if ($field === $indexField) continue;
                
                $cases[$field] = "$field = CASE $indexField ";
                foreach ($data as $row) {
                    $cases[$field] .= "WHEN ? THEN ? ";
                    $params[] = $row[$indexField];
                    $params[] = $row[$field];
                    $ids[] = $row[$indexField];
                }
                $cases[$field] .= "ELSE $field END";
            }
            
            $sql = sprintf(
                "UPDATE %s SET %s WHERE %s IN (%s)",
                $table,
                implode(', ', $cases),
                $indexField,
                implode(',', array_fill(0, count(array_unique($ids)), '?'))
            );
            
            $stmt = $this->query($sql, array_merge($params, array_unique($ids)));
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->handleError($e, "BATCH UPDATE $table", $data);
            throw $e;
        }
    }

    public function truncate($table) {
        try {
            return $this->query("TRUNCATE TABLE $table");
        } catch (PDOException $e) {
            $this->handleError($e, "TRUNCATE TABLE $table");
            throw $e;
        }
    }

    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    public function quote($value) {
        return $this->pdo->quote($value);
    }

    private function handleError($exception, $sql, $params = []) {
        $context = [
            'sql' => $sql,
            'params' => $params,
            'error_code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ];
        
        $this->logger->logError(
            isset($_SESSION['user']) ? $_SESSION['user']['id'] : null,
            'database_error',
            $context
        );

        if ($this->inTransaction) {
            $this->rollback();
        }
    }

    public function getPDO() {
        return $this->pdo;
    }

    private function __clone() {}
    private function __wakeup() {}
}
?>
