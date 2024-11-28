<?php

namespace App\Models;

use PDO;

class Tenant
{
    protected $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Phương thức lưu thông tin người thuê
    public function createTenant($name, $email, $address, $phone, $cccd, $userId)
    {
        $stmt = $this->pdo->prepare("INSERT INTO Tenants (name, email, address, phone, cccd, id_user) 
                                 VALUES (:name, :email, :address, :phone, :cccd, :user_id)");

        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':cccd', $cccd);
        $stmt->bindParam(':user_id', $userId);  // Thêm user_id vào khi tạo tenant

        if ($stmt->execute()) {
            return $this->pdo->lastInsertId();  // Trả về ID của tenant vừa được thêm
        }

        return false;
    }

    // Lấy thông tin người thuê theo ID
    public function getTenantById($tenantId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM Tenants WHERE id = :id");
        $stmt->bindParam(':id', $tenantId, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return $stmt->fetch(PDO::FETCH_ASSOC);  // Trả về thông tin người thuê dưới dạng mảng
        }

        return false;
    }

    public function addtenant(array $data): void
    {
        // Chuẩn bị câu lệnh SQL để chèn thông tin người thuê vào bảng Tenants
        $statement = $this->pdo->prepare('INSERT INTO Tenants (name, email, phone, address, cccd) VALUES (:name, :email, :phone, :address, :cccd)');

        // Thực thi câu lệnh với các tham số từ mảng $data
        $statement->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'cccd' => $data['cccd']
        ]);
    }


    // Lấy danh sách người thuê có phân trang
    public function getPaginatedTenants(int $limit, int $offset): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM Tenants LIMIT :limit OFFSET :offset");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy tổng số người thuê
    public function getTenantsCount(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM Tenants");
        return (int) $stmt->fetchColumn();
    }

    // Kiểm tra tên người thuê đã tồn tại chưa
    public function isTenantNameTaken(string $name): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM Tenants WHERE name = :name');
        $stmt->execute(['name' => $name]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // Kiểm tra số điện thoại đã tồn tại chưa
    public function isPhoneTaken(string $phone): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM Tenants WHERE phone = :phone');
        $stmt->execute(['phone' => $phone]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // Kiểm tra số CCCD đã tồn tại chưa
    public function isCccdTaken(string $cccd): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM Tenants WHERE cccd = :cccd');
        $stmt->execute(['cccd' => $cccd]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function getTenantNameById(int $id): ?string
{
    // Chuẩn bị câu lệnh SQL để lấy tên người thuê từ bảng Tenants theo ID
    $statement = $this->pdo->prepare('SELECT name FROM Tenants WHERE id = :id');
    
    // Thực thi câu lệnh với tham số ID
    $statement->execute(['id' => $id]);
    
    // Lấy tên người thuê từ kết quả truy vấn
    $tenantName = $statement->fetchColumn();
    
    // Nếu không tìm thấy tên, trả về null
    return $tenantName ?: null;
}


    // Cập nhật thông tin người thuê
    public function updateTenant(int $id, string $name, string $email, string $address, string $phone, string $cccd): void
    {
        $stmt = $this->pdo->prepare("UPDATE Tenants SET name = :name, email = :email, address = :address, 
            phone = :phone, cccd = :cccd WHERE id = :id");

        $stmt->execute([
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'address' => $address,
            'phone' => $phone,
            'cccd' => $cccd
        ]);
    }

    // Xóa người thuê
    public function deleteTenant(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM Tenants WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    // Lọc dữ liệu người thuê (chỉ lấy các trường cần thiết)
    public function filterTenantData(array $data): array
    {
        return [
            'name' => trim($data['name']),
            'email' => trim($data['email']),
            'phone' => trim($data['phone']),
            'address' => trim($data['address']),
            'cccd' => trim($data['cccd']),
        ];
    }

    // Kiểm tra tính hợp lệ của dữ liệu người thuê (ví dụ: tên, email, phone, cccd)
    public function validateTenant(array $data): array
    {
        $errors = [];

        // Kiểm tra tên người thuê
        if (empty($data['name']) || strlen($data['name']) < 2) {
            $errors['name'] = 'Tên người thuê phải ít nhất 2 ký tự.';
        } elseif ($this->isTenantNameTaken($data['name'])) {
            $errors['name'] = 'Tên người thuê đã được sử dụng.';
        }

        // Kiểm tra email
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email không hợp lệ.';
        }

        // Kiểm tra số điện thoại
        if (empty($data['phone']) || !preg_match("/^[0-9]{10}$/", $data['phone'])) {
            $errors['phone'] = 'Số điện thoại không hợp lệ.';
        } elseif ($this->isPhoneTaken($data['phone'])) {
            $errors['phone'] = 'Số điện thoại đã được sử dụng.';
        }

        // Kiểm tra CCCD
        if (empty($data['cccd']) || strlen($data['cccd']) != 12) {
            $errors['cccd'] = 'Số CCCD phải có 12 ký tự.';
        } elseif ($this->isCccdTaken($data['cccd'])) {
            $errors['cccd'] = 'Số CCCD đã được sử dụng.';
        }

        return $errors;
    }

    public function getTenantsByUser($userId)
    {
        $sql = "SELECT * FROM tenants WHERE id_user = :user_id";
        $statement = $this->pdo->prepare($sql);
        $statement->bindParam(':user_id', $userId);
        $statement->execute();
        return $statement->fetchAll();
    }

    public function getAllTenants()
    {
        $statement = $this->pdo->prepare('SELECT * FROM tenants');
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
