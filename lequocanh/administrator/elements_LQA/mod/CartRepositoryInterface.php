<?php

interface CartRepositoryInterface
{
    /**
     * Get the full cart contents for a user.
     *
     * @param string $userId
     * @return array
     */
    public function getCart(string $userId): array;

    /**
     * Add a product to the user's cart. If the product already exists,
     * the quantity is incremented.
     *
     * @param string $userId
     * @param int    $productId
     * @param int    $quantity
     * @return bool
     */
    public function addItem(string $userId, int $productId, int $quantity): bool;

    /**
     * Remove a product entirely from the user's cart.
     *
     * @param string $userId
     * @param int    $productId
     * @return bool
     */
    public function removeItem(string $userId, int $productId): bool;

    /**
     * Set the quantity for a specific product in the user's cart.
     * A quantity of 0 removes the item.
     *
     * @param string $userId
     * @param int    $productId
     * @param int    $quantity
     * @return bool
     */
    public function updateQuantity(string $userId, int $productId, int $quantity): bool;

    /**
     * Remove all items from the user's cart.
     *
     * @param string $userId
     * @return bool
     */
    public function clearCart(string $userId): bool;

    /**
     * Get the total number of items (sum of quantities) in the user's cart.
     *
     * @param string $userId
     * @return int
     */
    public function getItemCount(string $userId): int;

    /**
     * Get the list of items in the user's cart.
     * Alias of getCart() for readability.
     *
     * @param string $userId
     * @return array
     */
    public function getCartItems(string $userId): array;
}
