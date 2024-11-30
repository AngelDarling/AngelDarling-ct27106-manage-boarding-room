<?php

namespace App\Models;
use PDO;
class MaintenanceRequest
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function createMaintenanceRequest($room_id, $description, $user_id)
    {
        $status = 'Chờ duyệt';
        $stmt = $this->pdo->prepare("INSERT INTO maintenance_requests (id_room, id_user, description, status) VALUES (:room_id, :user_id, :description, :status)");
        $stmt->bindParam(':room_id', $room_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':status', $status);
        $stmt->execute();
    }

    public function getPaginatedmaintenance_requests(int $limit, int $offset): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM maintenance_requests LIMIT :limit OFFSET :offset');
        $statement->bindParam(':limit', $limit, PDO::PARAM_INT);
        $statement->bindParam(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy tổng số dịch vụ
    public function getmaintenance_requestsCount(): int
    {
        $statement = $this->pdo->query('SELECT COUNT(*) FROM maintenance_requests');
        return (int) $statement->fetchColumn();
    }

    public function deleteMaintenanceRequest($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM maintenance_requests WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    // Cập nhật trạng thái yêu cầu bảo trì
    public function updateRequestStatus($id, $status)
    {
        $stmt = $this->pdo->prepare("UPDATE maintenance_requests SET status = :status WHERE id = :id");
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    public function getUserNameById($userId)
{
    $stmt = $this->pdo->prepare('SELECT name FROM users WHERE id = :user_id');
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $user ? $user['name'] : 'N/A'; // Trả về tên người dùng, hoặc 'N/A' nếu không tìm thấy
}

public function getRoomNameById($roomId)
{
    $stmt = $this->pdo->prepare('SELECT name FROM rooms WHERE id = :room_id');
    $stmt->bindParam(':room_id', $roomId, PDO::PARAM_INT);
    $stmt->execute();
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $room ? $room['name'] : 'N/A'; // Trả về tên phòng, hoặc 'N/A' nếu không tìm thấy
}

}
