<?php

/**
 * mPDO - Wrapper class cho Database class
 * Tạo để tương thích với code cũ sử dụng mPDO
 */

// Tìm và load file database.php
$possible_paths = [
    __DIR__ . '/mod/database.php',
    __DIR__ . '/../elements_LQA/mod/database.php',
    dirname(__DIR__) . '/elements_LQA/mod/database.php',
    dirname(dirname(__DIR__)) . '/administrator/elements_LQA/mod/database.php'
];

$database_loaded = false;
foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $database_loaded = true;
        break;
    }
}

if (!$database_loaded) {
    throw new Exception('Không thể tìm thấy file database.php');
}

class mPDO 
{
    private $conn;
    
    public function __construct() 
    {
        try {
            $db = Database::getInstance();
            $this->conn = $db->getConnection();
        } catch (Exception $e) {
            throw new Exception('Không thể kết nối database: ' . $e->getMessage());
        }
    }
    
    /**
     * Thực thi câu lệnh SQL (INSERT, UPDATE, DELETE)
     * 
     * @param string $sql Câu lệnh SQL
     * @param array $params Tham số bind
     * @return bool Kết quả thực thi
     */
    public function execute($sql, $params = []) 
    {
        try {
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('mPDO Execute Error: ' . $e->getMessage() . ' | SQL: ' . $sql);
            throw $e;
        }
    }
    
    /**
     * Thực thi câu lệnh SELECT và trả về kết quả
     * 
     * @param string $sql Câu lệnh SQL
     * @param array $params Tham số bind
     * @param bool $fetchAll True để lấy tất cả, False để lấy 1 dòng
     * @return array|false Kết quả query
     */
    public function executeS($sql, $params = [], $fetchAll = false) 
    {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            if ($fetchAll) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            error_log('mPDO ExecuteS Error: ' . $e->getMessage() . ' | SQL: ' . $sql);
            throw $e;
        }
    }
    
    /**
     * Lấy ID của bản ghi vừa insert
     * 
     * @return string Last insert ID
     */
    public function lastInsertId() 
    {
        return $this->conn->lastInsertId();
    }
    
    /**
     * Bắt đầu transaction
     */
    public function beginTransaction() 
    {
        return $this->conn->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() 
    {
        return $this->conn->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() 
    {
        return $this->conn->rollback();
    }
    
    /**
     * Lấy connection PDO gốc
     * 
     * @return PDO
     */
    public function getConnection() 
    {
        return $this->conn;
    }
    
    /**
     * Kiểm tra bảng có tồn tại không
     * 
     * @param string $tableName Tên bảng
     * @return bool
     */
    public function tableExists($tableName) 
    {
        try {
            $sql = "SHOW TABLES LIKE ?";
            $result = $this->executeS($sql, [$tableName]);
            return $result !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Lấy thông tin cấu trúc bảng
     * 
     * @param string $tableName Tên bảng
     * @return array
     */
    public function describeTable($tableName) 
    {
        try {
            $sql = "DESCRIBE `$tableName`";
            return $this->executeS($sql, [], true);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Đếm số dòng trong bảng
     * 
     * @param string $tableName Tên bảng
     * @param string $condition Điều kiện WHERE (optional)
     * @param array $params Tham số cho điều kiện
     * @return int
     */
    public function countRows($tableName, $condition = '', $params = []) 
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM `$tableName`";
            if ($condition) {
                $sql .= " WHERE $condition";
            }
            
            $result = $this->executeS($sql, $params);
            return $result ? (int)$result['total'] : 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Thực thi nhiều câu lệnh SQL
     * 
     * @param array $sqlStatements Mảng các câu lệnh SQL
     * @return bool
     */
    public function executeMultiple($sqlStatements) 
    {
        try {
            $this->beginTransaction();
            
            foreach ($sqlStatements as $sql) {
                if (is_array($sql)) {
                    $this->execute($sql['sql'], $sql['params'] ?? []);
                } else {
                    $this->execute($sql);
                }
            }
            
            $this->commit();
            return true;
        } catch (Exception $e) {
            $this->rollback();
            error_log('mPDO ExecuteMultiple Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Escape string để tránh SQL injection
     * 
     * @param string $string Chuỗi cần escape
     * @return string
     */
    public function quote($string) 
    {
        return $this->conn->quote($string);
    }
    
    /**
     * Lấy thông tin lỗi cuối cùng
     * 
     * @return array
     */
    public function errorInfo() 
    {
        return $this->conn->errorInfo();
    }
}

?>
