<?php

declare(strict_types=1);

namespace App\Models;

use Database;
use PDO;

class Banner extends BaseModel
{
    protected static $table = 'banners';
    protected static $primaryKey = 'id';
    protected static $timestamps = true;

    protected static $fillable = [
        'title',
        'description',
        'image_url',
        'image_data',
        'image_type',
        'link_url',
        'position',
        'is_active'
    ];

    public static function getActiveBanners(): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, title, description, image_url, image_data, image_type, link_url, position, display_order, is_active, created_at FROM banners WHERE is_active = 1 ORDER BY position ASC, created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
