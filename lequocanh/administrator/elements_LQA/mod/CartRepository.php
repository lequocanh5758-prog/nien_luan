<?php

require_once __DIR__ . '/giohangCls.php';

class CartRepository implements CartRepositoryInterface
{
    private $giohang;

    public function __construct(?PDO $db = null)
    {
        $this->giohang = new GioHang($db);
    }

    /**
     * {@inheritDoc}
     */
    public function getCart(string $userId): array
    {
        return $this->giohang->getCartByUserId($userId);
    }

    /**
     * {@inheritDoc}
     */
    public function addItem(string $userId, int $productId, int $quantity): bool
    {
        return $this->runWithUser($userId, function () use ($productId, $quantity) {
            return $this->giohang->addToCart($productId, $quantity);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function removeItem(string $userId, int $productId): bool
    {
        return $this->runWithUser($userId, function () use ($productId) {
            return $this->giohang->removeFromCart($productId);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function updateQuantity(string $userId, int $productId, int $quantity): bool
    {
        return $this->runWithUser($userId, function () use ($productId, $quantity) {
            return $this->giohang->updateQuantity($productId, $quantity);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function clearCart(string $userId): bool
    {
        return $this->runWithUser($userId, function () {
            return $this->giohang->clearCart();
        });
    }

    /**
     * {@inheritDoc}
     */
    public function getItemCount(string $userId): int
    {
        return $this->runWithUser($userId, function () {
            return $this->giohang->getCartItemCount();
        });
    }

    /**
     * {@inheritDoc}
     */
    public function getCartItems(string $userId): array
    {
        return $this->runWithUser($userId, function () {
            return $this->giohang->getCartItems();
        });
    }

    /**
     * Run a callback with a specific user set in the session.
     *
     * The GioHang class determines the current user from $_SESSION['USER'].
     * This helper temporarily overrides the session value so that the
     * repository can operate on behalf of an arbitrary userId.
     *
     * @param string   $userId
     * @param callable $callback
     * @return mixed
     */
    private function runWithUser(string $userId, callable $callback)
    {
        $previousUser = $_SESSION['USER'] ?? null;
        $_SESSION['USER'] = $userId;

        try {
            return $callback();
        } finally {
            if ($previousUser !== null) {
                $_SESSION['USER'] = $previousUser;
            } else {
                unset($_SESSION['USER']);
            }
        }
    }
}
