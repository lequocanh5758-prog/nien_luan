<?php
declare(strict_types=1);

namespace App\Services;

class MigrationManager
{
    private static ?MigrationManager $instance = null;
    private $db;
    private string $migrationsPath;
    
    private function __construct()
    {
        $this->db = \Database::getInstance()->getConnection();
        $this->migrationsPath = __DIR__ . '/../../database/migrations';
        
        // Create migrations table if not exists
        $this->createMigrationsTable();
    }
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Create migrations table
     */
    private function createMigrationsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $this->db->exec($sql);
    }
    
    /**
     * Run pending migrations
     */
    public function migrate(): array
    {
        $pending = $this->getPendingMigrations();
        $executed = [];
        
        if (empty($pending)) {
            return ['message' => 'No pending migrations', 'executed' => []];
        }
        
        $batch = $this->getNextBatch();
        
        foreach ($pending as $migration) {
            try {
                $this->executeMigration($migration);
                $this->logMigration($migration, $batch);
                $executed[] = $migration;
            } catch (\Exception $e) {
                throw new \RuntimeException("Migration failed: {$migration} - " . $e->getMessage());
            }
        }
        
        return ['message' => 'Migrations completed', 'executed' => $executed];
    }
    
    /**
     * Rollback last batch
     */
    public function rollback(): array
    {
        $lastBatch = $this->getLastBatch();
        
        if (!$lastBatch) {
            return ['message' => 'Nothing to rollback', 'rolled_back' => []];
        }
        
        $migrations = $this->getMigrationsByBatch($lastBatch);
        $rolledBack = [];
        
        foreach ($migrations as $migration) {
            try {
                $this->rollbackMigration($migration);
                $this->removeMigrationLog($migration);
                $rolledBack[] = $migration;
            } catch (\Exception $e) {
                throw new \RuntimeException("Rollback failed: {$migration} - " . $e->getMessage());
            }
        }
        
        return ['message' => 'Rollback completed', 'rolled_back' => $rolledBack];
    }
    
    /**
     * Get pending migrations
     */
    public function getPendingMigrations(): array
    {
        $executed = $this->getExecutedMigrations();
        
        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0755, true);
            return [];
        }
        
        $files = glob($this->migrationsPath . '/*.php');
        $migrations = [];
        
        foreach ($files as $file) {
            $name = basename($file, '.php');
            if (!in_array($name, $executed)) {
                $migrations[] = $name;
            }
        }
        
        sort($migrations);
        return $migrations;
    }
    
    /**
     * Get executed migrations
     */
    private function getExecutedMigrations(): array
    {
        $stmt = $this->db->query("SELECT migration FROM migrations ORDER BY id");
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }
    
    /**
     * Execute migration
     */
    private function executeMigration(string $migration): void
    {
        $file = $this->migrationsPath . "/{$migration}.php";
        
        if (!file_exists($file)) {
            throw new \RuntimeException("Migration file not found: {$file}");
        }
        
        require_once $file;
        
        $className = $this->getClassName($migration);
        
        if (!class_exists($className)) {
            throw new \RuntimeException("Migration class not found: {$className}");
        }
        
        $instance = new $className();
        $instance->up();
    }
    
    /**
     * Rollback migration
     */
    private function rollbackMigration(string $migration): void
    {
        $file = $this->migrationsPath . "/{$migration}.php";
        
        if (!file_exists($file)) {
            throw new \RuntimeException("Migration file not found: {$file}");
        }
        
        require_once $file;
        
        $className = $this->getClassName($migration);
        $instance = new $className();
        $instance->down();
    }
    
    /**
     * Get class name from migration name
     */
    private function getClassName(string $migration): string
    {
        // Remove timestamp prefix
        $parts = explode('_', $migration, 2);
        return isset($parts[1]) ? str_replace(' ', '', ucwords(str_replace('_', ' ', $parts[1]))) : $migration;
    }
    
    /**
     * Log migration
     */
    private function logMigration(string $migration, int $batch): void
    {
        $stmt = $this->db->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)");
        $stmt->execute([$migration, $batch]);
    }
    
    /**
     * Remove migration log
     */
    private function removeMigrationLog(string $migration): void
    {
        $stmt = $this->db->prepare("DELETE FROM migrations WHERE migration = ?");
        $stmt->execute([$migration]);
    }
    
    /**
     * Get next batch number
     */
    private function getNextBatch(): int
    {
        $stmt = $this->db->query("SELECT MAX(batch) FROM migrations");
        return ($stmt->fetchColumn() ?: 0) + 1;
    }
    
    /**
     * Get last batch number
     */
    private function getLastBatch(): ?int
    {
        $stmt = $this->db->query("SELECT MAX(batch) FROM migrations");
        return $stmt->fetchColumn() ?: null;
    }
    
    /**
     * Get migrations by batch
     */
    private function getMigrationsByBatch(int $batch): array
    {
        $stmt = $this->db->prepare("SELECT migration FROM migrations WHERE batch = ? ORDER BY id DESC");
        $stmt->execute([$batch]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }
    
    /**
     * Create new migration file
     */
    public function create(string $name): string
    {
        $timestamp = date('Y_m_d_His');
        $filename = "{$timestamp}_{$name}.php";
        $filepath = $this->migrationsPath . "/{$filename}";
        
        $className = str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
        
        $template = "<?php\n\nuse App\\Services\\MigrationManager;\n\nclass {$className}\n{\n    public function up()\n    {\n        // Add your migration code here\n    }\n\n    public function down()\n    {\n        // Add your rollback code here\n    }\n}\n";
        
        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0755, true);
        }
        
        file_put_contents($filepath, $template);
        
        return $filename;
    }
}