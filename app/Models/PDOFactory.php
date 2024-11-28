<?php

namespace App\Models;

use PDO;
use PDOException;

class PDOFactory
{
    public function create(array $config): PDO
    {
        // Giải nén cấu hình kết nối
        [
            'dbhost' => $dbhost,
            'dbname' => $dbname,
            'dbuser' => $dbuser,
            'dbpass' => $dbpass
        ] = $config;

        // Tạo DSN (Data Source Name) cho MySQL
        $dsn = "mysql:host={$dbhost};dbname={$dbname};charset=utf8mb4";

        try {
            // Kết nối PDO với các thiết lập bảo mật và lỗi
            $pdo = new PDO($dsn, $dbuser, $dbpass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,  // Thiết lập chế độ ném ngoại lệ cho lỗi
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  // Lấy kết quả dạng mảng liên kết
                PDO::ATTR_EMULATE_PREPARES => false,  // Tắt giả lập Prepared Statements
            ]);

            return $pdo;

        } catch (PDOException $e) {
            // Xử lý lỗi nếu kết nối không thành công
            throw new PDOException('Lỗi kết nối cơ sở dữ liệu: ' . $e->getMessage());
        }
    }
}
