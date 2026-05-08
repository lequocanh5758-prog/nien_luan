<?php

require_once __DIR__ . '/PriceRepositoryInterface.php';
require_once __DIR__ . '/dongiaCls.php';

class PriceRepository implements PriceRepositoryInterface
{
    private $dongia;

    public function __construct(?PDO $db = null)
    {
        $this->dongia = new Dongia($db);
    }

    /**
     * {@inheritDoc}
     */
    public function getAll(): array
    {
        $result = $this->dongia->DongiaGetAll();
        return is_array($result) ? $result : [];
    }

    /**
     * {@inheritDoc}
     */
    public function getById(int $id): ?object
    {
        $result = $this->dongia->DongiaGetbyId($id);
        return $result ? $result : null;
    }

    /**
     * {@inheritDoc}
     */
    public function getByProduct(int $productId): array
    {
        $result = $this->dongia->DongiaGetbyIdHanghoa($productId);
        return is_array($result) ? $result : [];
    }

    /**
     * {@inheritDoc}
     */
    public function getActiveByProduct(int $productId): ?object
    {
        $result = $this->dongia->DongiaGetActiveByProduct($productId);
        return $result ? $result : null;
    }

    /**
     * {@inheritDoc}
     */
    public function add(int $productId, float $price, string $startDate, ?string $endDate, string $condition, string $note, bool $autoApply): int
    {
        $result = $this->dongia->DongiaAdd($productId, $price, $startDate, $endDate, $condition, $note, $autoApply);
        return is_numeric($result) ? (int) $result : 0;
    }

    /**
     * {@inheritDoc}
     */
    public function update(int $id, array $data): bool
    {
        $idHangHoa  = $data['idHangHoa']  ?? 0;
        $giaBan     = $data['giaBan']      ?? 0;
        $ngayApDung = $data['ngayApDung']  ?? '';
        $ngayKetThuc = $data['ngayKetThuc'] ?? null;
        $dieuKien   = $data['dieuKien']    ?? '';
        $ghiChu     = $data['ghiChu']      ?? '';

        return $this->dongia->DongiaUpdate($id, $idHangHoa, $giaBan, $ngayApDung, $ngayKetThuc, $dieuKien, $ghiChu);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(int $id): bool
    {
        return (bool) $this->dongia->DongiaDelete($id);
    }

    /**
     * {@inheritDoc}
     */
    public function setActive(int $id, bool $active): bool
    {
        return (bool) $this->dongia->DongiaUpdateStatus($id, $active);
    }

    /**
     * {@inheritDoc}
     */
    public function updatePriceForProduct(int $productId, float $newPrice): bool
    {
        return (bool) $this->dongia->HanghoaUpdatePrice($productId, $newPrice);
    }

    /**
     * {@inheritDoc}
     */
    public function getPriceHistory(int $productId): array
    {
        $result = $this->dongia->getPriceHistory($productId);
        return is_array($result) ? $result : [];
    }
}
