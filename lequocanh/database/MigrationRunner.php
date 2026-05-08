<?php
declare(strict_types=1);

/**
 * Simple Migration Runner
 * Version tracking with up/down migrations
 */
class MigrationRunner
{
    private $pdo;
    private $migrationsPath;

    public function __construct(?PDO $pdo = null, ?string $migrationsPath = null)
    {
        if ($pdo === null) {
            require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';
            $this->pdo = Database::getInstance()->getConnection();
        } else {
            $this->pdo = $pdo;
        }

        $this->migrationsPath = $migrationsPath ?? __DIR__ . '/migrations';
        $this->ensureMigrationsTable();
    }

    private function ensureMigrationsTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                version VARCHAR(255) NOT NULL UNIQUE,
                name VARCHAR(255) NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Run all pending migrations
     */
    public function migrate(): array
    {
        $applied = [];
        $pending = $this->getPendingMigrations();

        if (empty($pending)) {
            echo "No pending migrations.\n";
            return $applied;
        }

        foreach ($pending as $file) {
            $version = $this->extractVersion($file);
            $name = $this->extractName($file);

            echo "Running migration: {$version}_{$name}... ";

            try {
                $this->pdo->beginTransaction();

                $sql = file_get_contents($this->migrationsPath . '/' . $file);
                $this->executeMigration($sql);

                $stmt = $this->pdo->prepare("INSERT INTO migrations (version, name) VALUES (?, ?)");
                $stmt->execute([$version, $name]);

                $this->pdo->commit();

                echo "DONE\n";
                $applied[] = ['version' => $version, 'name' => $name, 'status' => 'applied'];
            } catch (\Exception $e) {
                $this->pdo->rollBack();
                echo "FAILED: " . $e->getMessage() . "\n";
                $applied[] = ['version' => $version, 'name' => $name, 'status' => 'failed', 'error' => $e->getMessage()];
                break;
            }
        }

        return $applied;
    }

    /**
     * Rollback to a specific version (inclusive - rolls back THAT version)
     */
    public function rollback(string $targetVersion): array
    {
        $rolledBack = [];
        $applied = $this->getAppliedMigrations();

        $toRollback = array_filter($applied, function ($m) use ($targetVersion) {
            return $m['version'] >= $targetVersion;
        });

        if (empty($toRollback)) {
            echo "No migrations to rollback at version {$targetVersion}.\n";
            return $rolledBack;
        }

        // Rollback in reverse order
        $toRollback = array_reverse($toRollback);

        foreach ($toRollback as $migration) {
            $file = $migration['version'] . '_' . $migration['name'] . '.sql';
            $downFile = $this->migrationsPath . '/' . $migration['version'] . '_' . $migration['name'] . '.down.sql';

            echo "Rolling back: {$migration['version']}_{$migration['name']}... ";

            if (!file_exists($downFile)) {
                echo "SKIPPED (no .down.sql file)\n";
                $rolledBack[] = ['version' => $migration['version'], 'name' => $migration['name'], 'status' => 'skipped'];
                continue;
            }

            try {
                $this->pdo->beginTransaction();

                $sql = file_get_contents($downFile);
                $this->executeMigration($sql);

                $stmt = $this->pdo->prepare("DELETE FROM migrations WHERE version = ?");
                $stmt->execute([$migration['version']]);

                $this->pdo->commit();

                echo "DONE\n";
                $rolledBack[] = ['version' => $migration['version'], 'name' => $migration['name'], 'status' => 'rolled_back'];
            } catch (\Exception $e) {
                $this->pdo->rollBack();
                echo "FAILED: " . $e->getMessage() . "\n";
                $rolledBack[] = ['version' => $migration['version'], 'name' => $migration['name'], 'status' => 'failed', 'error' => $e->getMessage()];
                break;
            }
        }

        return $rolledBack;
    }

    /**
     * Show migration status
     */
    public function status(): array
    {
        $allFiles = $this->getMigrationFiles();
        $applied = $this->getAppliedVersions();

        $status = [];
        foreach ($allFiles as $file) {
            $version = $this->extractVersion($file);
            $name = $this->extractName($file);
            $isApplied = in_array($version, $applied);

            $status[] = [
                'version' => $version,
                'name' => $name,
                'status' => $isApplied ? 'applied' : 'pending',
                'file' => $file,
            ];
        }

        return $status;
    }

    /**
     * Print status table
     */
    public function printStatus(): void
    {
        $status = $this->status();

        echo "\n";
        echo str_pad('Version', 12) . str_pad('Name', 40) . "Status\n";
        echo str_repeat('-', 62) . "\n";

        foreach ($status as $s) {
            $statusStr = $s['status'] === 'applied' ? "\033[32m✓ applied\033[0m" : "\033[33m○ pending\033[0m";
            echo str_pad($s['version'], 12) . str_pad($s['name'], 40) . $statusStr . "\n";
        }

        echo "\n";
        $pendingCount = count(array_filter($status, fn($s) => $s['status'] === 'pending'));
        echo "Total: " . count($status) . " migrations ({$pendingCount} pending)\n\n";
    }

    private function getMigrationFiles(): array
    {
        $files = glob($this->migrationsPath . '/*.sql');
        $files = array_filter($files, function ($f) {
            return !str_contains(basename($f), '.down.sql');
        });
        $files = array_map('basename', $files);
        sort($files);
        return $files;
    }

    private function getPendingMigrations(): array
    {
        $allFiles = $this->getMigrationFiles();
        $applied = $this->getAppliedVersions();

        return array_filter($allFiles, function ($file) use ($applied) {
            $version = $this->extractVersion($file);
            return !in_array($version, $applied);
        });
    }

    private function getAppliedMigrations(): array
    {
        $stmt = $this->pdo->query("SELECT version, name FROM migrations ORDER BY version ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getAppliedVersions(): array
    {
        $stmt = $this->pdo->query("SELECT version FROM migrations ORDER BY version ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function extractVersion(string $filename): string
    {
        return explode('_', $filename, 2)[0];
    }

    private function extractName(string $filename): string
    {
        $name = explode('_', $filename, 2)[1] ?? $filename;
        return preg_replace('/\.sql$/', '', $name);
    }

    private function executeMigration(string $sql): void
    {
        // Split by semicolons, ignoring those inside strings/comments
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($s) => !empty($s) && !preg_match('/^--/', $s)
        );

        foreach ($statements as $statement) {
            if (!empty(trim($statement))) {
                $this->pdo->exec($statement);
            }
        }
    }
}

// CLI usage
if (php_sapi_name() === 'cli' && basename($_SERVER['PHP_SELF'] ?? '') === 'MigrationRunner.php') {
    require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';

    $runner = new MigrationRunner();
    $command = $argv[1] ?? 'status';

    switch ($command) {
        case 'migrate':
            $runner->migrate();
            break;
        case 'rollback':
            $version = $argv[2] ?? null;
            if (!$version) {
                echo "Usage: php MigrationRunner.php rollback <version>\n";
                exit(1);
            }
            $runner->rollback($version);
            break;
        case 'status':
        default:
            $runner->printStatus();
            break;
    }
}
