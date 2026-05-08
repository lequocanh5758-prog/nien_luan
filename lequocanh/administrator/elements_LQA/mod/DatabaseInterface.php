<?php

interface DatabaseInterface
{
    /**
     * Get the underlying PDO connection.
     */
    public function getConnection(): PDO;

    /**
     * Start a database transaction.
     */
    public function beginTransaction(): bool;

    /**
     * Commit the current transaction.
     */
    public function commit(): bool;

    /**
     * Roll back the current transaction.
     */
    public function rollBack(): bool;
}
