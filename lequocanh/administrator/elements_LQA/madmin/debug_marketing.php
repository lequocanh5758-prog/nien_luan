<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../mod/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Kiem tra du lieu Marketing</h2>";

// Kiem tra bang news
echo "<h3>1. Bang News</h3>";
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM news");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Tong so tin tuc: " . $row['total'] . "</p>";
    
    $stmt = $db->query("SELECT COUNT(*) as published FROM news WHERE is_published = 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>So tin da xuat ban: " . $row['published'] . "</p>";
    
    $stmt = $db->query("SELECT id, title, is_published FROM news LIMIT 5");
    $news = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($news)) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Tieu de</th><th>is_published</th></tr>";
        foreach ($news as $n) {
            echo "<tr><td>{$n['id']}</td><td>{$n['title']}</td><td>{$n['is_published']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Khong co du lieu</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Loi: " . $e->getMessage() . "</p>";
}

// Kiem tra bang promotions
echo "<h3>2. Bang Promotions</h3>";
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM promotions");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Tong so uu dai: " . $row['total'] . "</p>";
    
    $stmt = $db->query("SELECT COUNT(*) as active FROM promotions WHERE is_active = 1 AND start_date <= CURDATE() AND end_date >= CURDATE()");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>So uu dai dang hoat dong: " . $row['active'] . "</p>";
    
    $stmt = $db->query("SELECT id, title, is_active, start_date, end_date FROM promotions LIMIT 5");
    $promos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($promos)) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Tieu de</th><th>is_active</th><th>start_date</th><th>end_date</th></tr>";
        foreach ($promos as $p) {
            echo "<tr><td>{$p['id']}</td><td>{$p['title']}</td><td>{$p['is_active']}</td><td>{$p['start_date']}</td><td>{$p['end_date']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Khong co du lieu</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Loi: " . $e->getMessage() . "</p>";
}

// Kiem tra bang pages (blog)
echo "<h3>3. Bang Pages (Blog)</h3>";
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM pages WHERE type = 'blog'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Tong so blog: " . $row['total'] . "</p>";
    
    $stmt = $db->query("SELECT COUNT(*) as published FROM pages WHERE type = 'blog' AND status = 'published'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>So blog da xuat ban: " . $row['published'] . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>Loi: " . $e->getMessage() . "</p>";
}
