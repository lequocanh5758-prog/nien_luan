<?php
declare(strict_types=1);

namespace App\Services;

class QueryLogger
{
    private static ?QueryLogger $instance = null;
    private array $queries = [];
    private string $logFile;
    private bool $enabled;
    
    private function __construct()
    {
        $this->logFile = __DIR__ . '/../../logs/query_' . date('Y-m-d') . '.log';
        $this->enabled = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
        
        // Create logs directory if not exists
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Log a query
     */
    public function log(string $query, array $params = [], float $executionTime = 0): void
    {
        if (!$this->enabled) {
            return;
        }
        
        $entry = [
            'time' => date('Y-m-d H:i:s'),
            'query' => $query,
            'params' => $params,
            'execution_time' => round($executionTime * 1000, 2) . 'ms',
            'memory' => round(memory_get_usage(true) / 1024 / 1024, 2) . 'MB'
        ];
        
        $this->queries[] = $entry;
        
        // Log to file
        $logLine = sprintf(
            "[%s] %s | Params: %s | Time: %s | Memory: %s\n",
            $entry['time'],
            $entry['query'],
            json_encode($entry['params']),
            $entry['execution_time'],
            $entry['memory']
        );
        
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Get all logged queries
     */
    public function getQueries(): array
    {
        return $this->queries;
    }
    
    /**
     * Get query count
     */
    public function getQueryCount(): int
    {
        return count($this->queries);
    }
    
    /**
     * Get total execution time
     */
    public function getTotalExecutionTime(): float
    {
        $total = 0;
        foreach ($this->queries as $query) {
            $total += (float)str_replace('ms', '', $query['execution_time']);
        }
        return $total;
    }
    
    /**
     * Get slow queries (threshold in ms)
     */
    public function getSlowQueries(float $threshold = 100): array
    {
        return array_filter($this->queries, function($query) use ($threshold) {
            $time = (float)str_replace('ms', '', $query['execution_time']);
            return $time > $threshold;
        });
    }
    
    /**
     * Clear logged queries
     */
    public function clear(): void
    {
        $this->queries = [];
    }
}