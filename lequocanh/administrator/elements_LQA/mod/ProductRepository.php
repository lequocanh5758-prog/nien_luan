<?php

require_once __DIR__ . '/ProductRepositoryInterface.php';
require_once __DIR__ . '/hanghoaCls.php';

/**
 * ProductRepository
 *
 * Implementation of ProductRepositoryInterface that delegates
 * to the existing hanghoa class. Provides a clean, focused API
 * over the legacy data access layer.
 */
class ProductRepository implements ProductRepositoryInterface
{
    /**
     * @var hanghoa
     */
    private $hanghoa;

    /**
     * @param PDO|null $db Optional database connection. If null,
     *        the hanghoa class will create its own via Database::getInstance().
     */
    public function __construct(?PDO $db = null)
    {
        $this->hanghoa = new hanghoa($db);
    }

    /**
     * {@inheritDoc}
     */
    public function getAll(): array
    {
        return $this->hanghoa->HanghoaGetAll();
    }

    /**
     * {@inheritDoc}
     */
    public function getById(int $id): ?object
    {
        $result = $this->hanghoa->HanghoaGetbyId($id);
        return $result ?: null;
    }

    /**
     * {@inheritDoc}
     *
     * Maps the flat data array to the positional parameters
     * expected by hanghoa::HanghoaAdd().
     */
    public function add(array $data)
    {
        return $this->hanghoa->HanghoaAdd(
            $data['tenhanghoa']  ?? '',
            $data['mota']        ?? '',
            $data['giathamkhao'] ?? 0,
            $data['hinhanh']     ?? 0,
            $data['idloaihang']  ?? 0,
            $data['idThuongHieu'] ?? null,
            $data['idDonViTinh'] ?? null,
            $data['idNhanVien']  ?? null,
            $data['ghichu']      ?? ''
        );
    }

    /**
     * {@inheritDoc}
     *
     * Maps the flat data array to the positional parameters
     * expected by hanghoa::HanghoaUpdate().
     */
    public function update(int $id, array $data): bool
    {
        $result = $this->hanghoa->HanghoaUpdate(
            $data['tenhanghoa']  ?? '',
            $data['hinhanh']     ?? 0,
            $data['mota']        ?? '',
            $data['giathamkhao'] ?? 0,
            $data['idloaihang']  ?? 0,
            $data['idThuongHieu'] ?? null,
            $data['idDonViTinh'] ?? null,
            $data['idNhanVien']  ?? null,
            $id,
            $data['ghichu']      ?? ''
        );
        return (bool) $result;
    }

    /**
     * {@inheritDoc}
     *
     * Note: hanghoa::HanghoaDelete() returns an array with a 'success' key
     * rather than a plain boolean. This method normalizes that to a bool.
     */
    public function delete(int $id): bool
    {
        $result = $this->hanghoa->HanghoaDelete($id);

        if (is_array($result)) {
            return !empty($result['success']);
        }

        return (bool) $result;
    }

    /**
     * {@inheritDoc}
     */
    public function search(string $query): array
    {
        return $this->hanghoa->searchHanghoa($query);
    }

    /**
     * {@inheritDoc}
     */
    public function getByCategory(int $categoryId): array
    {
        return $this->hanghoa->HanghoaGetbyIdloaihang($categoryId);
    }

    /**
     * {@inheritDoc}
     */
    public function getByStatus(int $status): array
    {
        return $this->hanghoa->getProductsByStatus($status);
    }

    /**
     * {@inheritDoc}
     */
    public function updatePrice(int $id, float $price): bool
    {
        $result = $this->hanghoa->HanghoaUpdatePrice($id, $price);
        return (bool) $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getStock(int $id): int
    {
        return $this->hanghoa->getTonKho($id);
    }
}
