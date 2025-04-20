<?php
class Migration {
    private $conn;
    private $migrationsPath;
    private $migrationsTable = 'migrations';

    public function __construct($conn) {
        $this->conn = $conn;
        $this->migrationsPath = dirname(__DIR__) . '/database/migrations';
        $this->createMigrationsTable();
    }

    /**
     * Create migrations table if it doesn't exist
     */
    private function createMigrationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        if (!$this->conn->query($sql)) {
            throw new Exception("Error creating migrations table: " . $this->conn->error);
        }
    }

    /**
     * Run pending migrations
     */
    public function migrate() {
        try {
            // Get list of migration files
            $files = scandir($this->migrationsPath);
            $migrations = [];
            
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                
                // Check if migration has already been run
                $sql = "SELECT id FROM {$this->migrationsTable} WHERE migration = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("s", $file);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    $migrations[] = $file;
                }
            }

            if (empty($migrations)) {
                echo "No pending migrations.\n";
                return;
            }

            // Get current batch number
            $sql = "SELECT MAX(batch) as batch FROM {$this->migrationsTable}";
            $result = $this->conn->query($sql);
            $row = $result->fetch_assoc();
            $batch = ($row['batch'] ?? 0) + 1;

            // Run pending migrations
            sort($migrations); // Ensure migrations run in order
            foreach ($migrations as $migration) {
                require_once $this->migrationsPath . '/' . $migration;
                
                $className = $this->getMigrationClassName($migration);
                $instance = new $className();
                
                $this->conn->begin_transaction();
                
                try {
                    echo "Running migration: {$migration}\n";
                    $instance->up($this->conn);
                    
                    // Record successful migration
                    $sql = "INSERT INTO {$this->migrationsTable} (migration, batch) VALUES (?, ?)";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->bind_param("si", $migration, $batch);
                    $stmt->execute();
                    
                    $this->conn->commit();
                    echo "Migration completed: {$migration}\n";
                } catch (Exception $e) {
                    $this->conn->rollback();
                    throw new Exception("Error running migration {$migration}: " . $e->getMessage());
                }
            }
        } catch (Exception $e) {
            error_log("Migration error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Rollback the last batch of migrations
     */
    public function rollback() {
        try {
            // Get last batch number
            $sql = "SELECT MAX(batch) as batch FROM {$this->migrationsTable}";
            $result = $this->conn->query($sql);
            $row = $result->fetch_assoc();
            $lastBatch = $row['batch'];

            if (!$lastBatch) {
                echo "Nothing to rollback.\n";
                return;
            }

            // Get migrations from last batch
            $sql = "SELECT migration FROM {$this->migrationsTable} WHERE batch = ? ORDER BY id DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $lastBatch);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $migration = $row['migration'];
                require_once $this->migrationsPath . '/' . $migration;
                
                $className = $this->getMigrationClassName($migration);
                $instance = new $className();
                
                $this->conn->begin_transaction();
                
                try {
                    echo "Rolling back migration: {$migration}\n";
                    $instance->down($this->conn);
                    
                    // Remove migration record
                    $sql = "DELETE FROM {$this->migrationsTable} WHERE migration = ?";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->bind_param("s", $migration);
                    $stmt->execute();
                    
                    $this->conn->commit();
                    echo "Rollback completed: {$migration}\n";
                } catch (Exception $e) {
                    $this->conn->rollback();
                    throw new Exception("Error rolling back migration {$migration}: " . $e->getMessage());
                }
            }
        } catch (Exception $e) {
            error_log("Rollback error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Reset the database by rolling back all migrations
     */
    public function reset() {
        try {
            // Get all migrations
            $sql = "SELECT migration FROM {$this->migrationsTable} ORDER BY id DESC";
            $result = $this->conn->query($sql);
            
            while ($row = $result->fetch_assoc()) {
                $migration = $row['migration'];
                require_once $this->migrationsPath . '/' . $migration;
                
                $className = $this->getMigrationClassName($migration);
                $instance = new $className();
                
                $this->conn->begin_transaction();
                
                try {
                    echo "Rolling back migration: {$migration}\n";
                    $instance->down($this->conn);
                    
                    // Remove migration record
                    $sql = "DELETE FROM {$this->migrationsTable} WHERE migration = ?";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->bind_param("s", $migration);
                    $stmt->execute();
                    
                    $this->conn->commit();
                    echo "Rollback completed: {$migration}\n";
                } catch (Exception $e) {
                    $this->conn->rollback();
                    throw new Exception("Error rolling back migration {$migration}: " . $e->getMessage());
                }
            }
        } catch (Exception $e) {
            error_log("Reset error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get migration class name from file name
     */
    private function getMigrationClassName($filename) {
        $name = str_replace('.php', '', $filename);
        $parts = explode('_', $name);
        array_shift($parts); // Remove version number
        return implode('', array_map('ucfirst', $parts));
    }
}
