<?php
namespace App\Models;

use PDO;

class service
{
    private $db;

    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
    }

    // Hàm kiểm tra dữ liệu đầu vào khi thêm hoặc cập nhật dịch vụ
    public function validateservice(array $data): array
    {
        $errors = [];

        // Kiểm tra tên dịch vụ
        if (empty($data['name']) || strlen($data['name']) < 2) {
            $errors['name'] = 'Tên dịch vụ phải ít nhất 2 ký tự.';
        } elseif ($this->isserviceNameTaken($data['name'])) {
            $errors['name'] = 'Tên dịch vụ đã được sử dụng.';
        }

        // Kiểm tra giá trị của price
        if (empty($data['price']) || !is_numeric($data['price']) || $data['price'] <= 0) {
            $errors['price'] = 'Giá dịch vụ phải là số và lớn hơn 0.';
        }

        // Kiểm tra mô tả
        if (empty($data['description']) || strlen($data['description']) < 5) {
            $errors['description'] = 'Mô tả phải có ít nhất 5 ký tự.';
        }

        return $errors;
    }

    // Kiểm tra tên dịch vụ đã tồn tại chưa
    public function isserviceNameTaken(string $name): bool
    {
        $statement = $this->db->prepare('SELECT COUNT(*) FROM services WHERE name = :name');
        $statement->execute(['name' => $name]);
        $count = (int) $statement->fetchColumn();
        return $count > 0;
    }

    // Lấy tên dịch vụ theo ID
    public function getserviceNameById(int $id): ?string
    {
        $statement = $this->db->prepare('SELECT name FROM services WHERE id = :id');
        $statement->execute(['id' => $id]);
        $serviceName = $statement->fetchColumn();
        return $serviceName ?: null;
    }

    // Lọc dữ liệu dịch vụ (chỉ lấy các trường cần thiết)
    public function filterserviceData(array $data): array
    {
        return [
            'name' => trim($data['name']),
            'price' => trim($data['price']),
            'description' => trim($data['description']),
        ];
    }

    // Lấy các dịch vụ có phân trang
    public function getPaginatedservices(int $limit, int $offset): array
    {
        $statement = $this->db->prepare('SELECT * FROM services LIMIT :limit OFFSET :offset');
        $statement->bindParam(':limit', $limit, PDO::PARAM_INT);
        $statement->bindParam(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy tổng số dịch vụ
    public function getservicesCount(): int
    {
        $statement = $this->db->query('SELECT COUNT(*) FROM services');
        return (int) $statement->fetchColumn();
    }

    // Lấy tất cả dịch vụ
    public function getAllservices(): array
    {
        $statement = $this->db->prepare('SELECT * FROM services');
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy dịch vụ theo ID
    public function getserviceById(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM services WHERE id = :id');
        $statement->execute(['id' => $id]);
        $service = $statement->fetch(PDO::FETCH_ASSOC);
        return $service ?: null;
    }

    // Thêm dịch vụ vào database
    public function addservice(array $data): void
    {
        $statement = $this->db->prepare('INSERT INTO services (name, price, description) VALUES (:name, :price, :description)');
        $statement->execute([
            'name' => $data['name'],
            'price' => $data['price'],
            'description' => $data['description']
        ]);
    }

    // Cập nhật dịch vụ trong database
    public function updateservice(int $id, string $name, float $price, string $description): void
    {
        $statement = $this->db->prepare('UPDATE services SET name = :name, price = :price, description = :description WHERE id = :id');
        $statement->execute([
            'name' => $name,
            'price' => $price,
            'description' => $description,
            'id' => $id
        ]);
    }

    // Xóa dịch vụ khỏi database
    public function deleteservice(int $id): void
    {
        $statement = $this->db->prepare('DELETE FROM services WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function getServicesByContracts($contracts)
    {
        $contractIds = implode(',', array_map(function ($contract) {
            return $contract['id'];
        }, $contracts));

        $sql = "SELECT * FROM services WHERE id_contract IN ($contractIds)";
        $statement = $this->db->prepare($sql);
        $statement->execute();
        return $statement->fetchAll();
    }
}
