<?php

interface PriceRepositoryInterface
{
    /**
     * Get all price records.
     * @return array
     */
    public function getAll(): array;

    /**
     * Get a price record by its ID.
     * @param int $id
     * @return object|null
     */
    public function getById(int $id): ?object;

    /**
     * Get all price records for a specific product.
     * @param int $productId
     * @return array
     */
    public function getByProduct(int $productId): array;

    /**
     * Get the active (applied) price for a specific product.
     * @param int $productId
     * @return object|null
     */
    public function getActiveByProduct(int $productId): ?object;

    /**
     * Add a new price record.
     * @param int $productId
     * @param float $price
     * @param string $startDate
     * @param string|null $endDate
     * @param string $condition
     * @param string $note
     * @param bool $autoApply
     * @return int Inserted record ID, or 0 on failure
     */
    public function add(int $productId, float $price, string $startDate, ?string $endDate, string $condition, string $note, bool $autoApply): int;

    /**
     * Update a price record.
     * @param int $id
     * @param array $data Associative array with keys: idHangHoa, giaBan, ngayApDung, ngayKetThuc, dieuKien, ghiChu
     * @return bool
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a price record.
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Set the active (applied) status of a price record.
     * @param int $id
     * @param bool $active
     * @return bool
     */
    public function setActive(int $id, bool $active): bool;

    /**
     * Update the reference price on the product (hanghoa) table.
     * @param int $productId
     * @param float $newPrice
     * @return bool
     */
    public function updatePriceForProduct(int $productId, float $newPrice): bool;

    /**
     * Get the price change history for a product.
     * @param int $productId
     * @return array
     */
    public function getPriceHistory(int $productId): array;
}
