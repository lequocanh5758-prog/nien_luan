<?php
declare(strict_types=1);

namespace App\Models;

use Database;
use PDO;

class Blog
{
    private PDO $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getAll(int $limit = 10, int $offset = 0, string $status = 'published'): array
    {
        try {
            $sql = "SELECT * FROM blog_posts WHERE status = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$status, $limit, $offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Blog::getAll error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getBySlug(string $slug): ?array
    {
        try {
            $sql = "SELECT * FROM blog_posts WHERE slug = ? AND status = 'published'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$slug]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($post) {
                // Increment view count
                $this->db->prepare("UPDATE blog_posts SET view_count = view_count + 1 WHERE id = ?")->execute([$post['id']]);
            }
            
            return $post ?: null;
        } catch (\Exception $e) {
            error_log("Blog::getBySlug error: " . $e->getMessage());
            return null;
        }
    }
    
    public function create(string $title, string $content, ?string $excerpt = null, ?string $image = null, string $author = 'Admin'): bool
    {
        try {
            $slug = $this->createSlug($title);
            $excerpt = $excerpt ?: mb_substr(strip_tags($content), 0, 200) . '...';
            
            $sql = "INSERT INTO blog_posts (title, slug, content, excerpt, featured_image, author, status) VALUES (?, ?, ?, ?, ?, ?, 'published')";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$title, $slug, $content, $excerpt, $image, $author]);
        } catch (\Exception $e) {
            error_log("Blog::create error: " . $e->getMessage());
            return false;
        }
    }
    
    public function count(): int
    {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'published'");
            return (int)$stmt->fetchColumn();
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    private function createSlug(string $title): string
    {
        $slug = mb_strtolower($title);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
        $slug = $slug . '-' . time();
        return $slug;
    }
}