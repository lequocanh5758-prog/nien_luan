<?php

interface OrderRepositoryInterface
{
    /**
     * Get all orders with optional filters.
     * @param array $filters ['status' => string, 'user_id' => string, 'limit' => int]
     * @return array
     */
    public function getAll(array $filters = []): array;

    /**
     * Get order by ID.
     */
    public function getById(int $id): ?array;

    /**
     * Get orders by user.
     */
    public function getByUser(string $userId): array;

    /**
     * Get orders by status.
     */
    public function getByStatus(string $status): array;

    /**
     * Update order status.
     * @param int $id Order ID
     * @param string $status New status (pending, approved, delivered, completed, cancelled)
     * @return bool
     */
    public function updateStatus(int $id, string $status): bool;

    /**
     * Get order items.
     */
    public function getItems(int $orderId): array;

    /**
     * Get order statistics.
     * @return array ['pending' => int, 'approved' => int, 'delivered' => int, 'completed' => int, 'cancelled' => int]
     */
    public function getStatistics(): array;

    /**
     * Get orders with revenue data in date range.
     */
    public function getRevenueByDate(string $startDate, string $endDate): array;

    /**
     * Search orders by keyword.
     */
    public function search(string $keyword): array;
}
