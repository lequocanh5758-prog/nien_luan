<?php

/**
 * ProductRepositoryInterface
 *
 * Focused interface for core product CRUD and query operations.
 * Extracted from the hanghoa class to provide a clean contract
 * for product data access.
 */
interface ProductRepositoryInterface
{
    /**
     * Get all products.
     *
     * @return array Array of product objects
     */
    public function getAll(): array;

    /**
     * Get a single product by its ID.
     *
     * @param int $id The product ID (idhanghoa)
     * @return object|null The product object, or null if not found
     */
    public function getById(int $id): ?object;

    /**
     * Add a new product.
     *
     * @param array $data Associative array with keys:
     *   - tenhanghoa (string, required)
     *   - mota (string)
     *   - giathamkhao (float, required)
     *   - hinhanh (int, image ID, default 0)
     *   - idloaihang (int, required, category ID)
     *   - idThuongHieu (int|null, brand ID)
     *   - idDonViTinh (int|null, unit ID)
     *   - idNhanVien (int|null, employee ID)
     *   - ghichu (string, notes)
     * @return int|false The new product's insert ID, or false on failure
     */
    public function add(array $data);

    /**
     * Update an existing product.
     *
     * @param int $id The product ID (idhanghoa)
     * @param array $data Associative array with keys:
     *   - tenhanghoa (string)
     *   - hinhanh (int, image ID)
     *   - mota (string)
     *   - giathamkhao (float)
     *   - idloaihang (int)
     *   - idThuongHieu (int|null)
     *   - idDonViTinh (int|null)
     *   - idNhanVien (int|null)
     *   - ghichu (string)
     * @return bool True if at least one row was updated
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a product by its ID.
     *
     * @param int $id The product ID (idhanghoa)
     * @return bool True if deletion succeeded, false if it failed
     *         (e.g. due to foreign key constraints)
     */
    public function delete(int $id): bool;

    /**
     * Search products by keyword (matches name, description, attributes).
     *
     * @param string $query The search keyword
     * @return array Array of matching product objects (max 50)
     */
    public function search(string $query): array;

    /**
     * Get all products in a given category.
     *
     * @param int $categoryId The category ID (idloaihang)
     * @return array Array of product objects
     */
    public function getByCategory(int $categoryId): array;

    /**
     * Get all products with a given status.
     *
     * @param int $status Status value: 1 = active, 2 = discontinued, 3 = out of stock
     * @return array Array of product objects
     */
    public function getByStatus(int $status): array;

    /**
     * Update the reference price (giathamkhao) of a product.
     *
     * @param int $id The product ID (idhanghoa)
     * @param float $price The new price
     * @return bool True if at least one row was updated
     */
    public function updatePrice(int $id, float $price): bool;

    /**
     * Get the current stock quantity of a product.
     *
     * @param int $id The product ID (idhanghoa)
     * @return int The stock quantity (0 if no stock record exists)
     */
    public function getStock(int $id): int;
}
