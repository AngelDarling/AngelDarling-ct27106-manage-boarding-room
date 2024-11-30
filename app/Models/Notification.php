<?php
namespace App\Models;

use PDO;

class Notification
{
    private $db;

    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
    }

    // Kiểm tra dữ liệu đầu vào khi thêm hoặc cập nhật thông báo
    public function validateNotification(array $data): array
    {
        $errors = [];

        // Kiểm tra nội dung thông báo
        if (empty($data['content']) || strlen($data['content']) < 5) {
            $errors['content'] = 'Nội dung thông báo phải có ít nhất 5 ký tự.';
        }

        // Kiểm tra loại thông báo
        if (empty($data['type'])) {
            $errors['type'] = 'Loại thông báo không được để trống.';
        }

        // Kiểm tra người thuê
        if (empty($data['id_tenants']) || !is_numeric($data['id_tenants'])) {
            $errors['id_tenants'] = 'Người thuê không hợp lệ.';
        }

        return $errors;
    }

    // Lấy thông báo có phân trang
    public function getPaginatedNotifications(int $limit, int $offset): array
    {
        $statement = $this->db->prepare('SELECT * FROM notification LIMIT :limit OFFSET :offset');
        $statement->bindParam(':limit', $limit, PDO::PARAM_INT);
        $statement->bindParam(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy tổng số thông báo
    public function getNotificationsCount(): int
    {
        $statement = $this->db->query('SELECT COUNT(*) FROM notification');
        return (int) $statement->fetchColumn();
    }

    // Lấy tất cả thông báo
    public function getAllNotifications(): array
    {
        $statement = $this->db->prepare('SELECT * FROM notification');
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy thông báo theo ID
    public function getNotificationById(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM notification WHERE id = :id');
        $statement->execute(['id' => $id]);
        $notification = $statement->fetch(PDO::FETCH_ASSOC);
        return $notification ?: null;
    }

    // Lấy thông báo theo ID của người thuê
    public function getNotificationsByTenant(int $tenantId): array
    {
        $statement = $this->db->prepare('SELECT * FROM notification WHERE id_tenants = :tenantId');
        $statement->execute(['tenantId' => $tenantId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    // Thêm thông báo vào database
    public function addNotification(array $data): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO notification (id_tenants, content, type) VALUES (:id_tenants, :content, :type)'
        );
        $statement->execute([
            'id_tenants' => $data['id_tenants'],
            'content' => $data['content'],
            'type' => $data['type']
        ]);
    }

    // Cập nhật thông báo trong database
    public function updateNotification(int $id, array $data): void
    {
        $statement = $this->db->prepare(
            'UPDATE notification SET id_tenants = :id_tenants, content = :content, type = :type WHERE id = :id'
        );
        $statement->execute([
            'id_tenants' => $data['id_tenants'],
            'content' => $data['content'],
            'type' => $data['type'],
            'id' => $id
        ]);
    }

    // Xóa thông báo khỏi database
    public function deleteNotification(int $id): void
    {
        $statement = $this->db->prepare('DELETE FROM notification WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    // Trong NotificationModel.php
public function getNotificationsByTenantId($tenantId) {
    // Truy vấn lấy các thông báo của người thuê từ CSDL
    $stmt = $this->db->prepare("SELECT * FROM notifications WHERE id_tenants = ?");
    $stmt->execute([$tenantId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

}
