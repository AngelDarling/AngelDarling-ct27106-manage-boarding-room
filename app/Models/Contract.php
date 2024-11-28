<?php

namespace App\Models;

use PDO;

class Contract
{
    protected $pdo;
    private PDO $db;
    public int $id;
    public int $id_tenants;
    public int $id_room;
    public float $total_amount;
    public string $status;
    public string $deposit;
    public string $start_date;
    public string $end_date;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->db = $pdo;
    }

    // Tạo hợp đồng mới
    public function createContract($tenantId, $roomId, $totalAmount, $startDate, $endDate, $deposit)
    {
        $stmt = $this->pdo->prepare("INSERT INTO contracts (id_tenants, id_room, start_date, end_date, total_amount, deposit, status) 
                                 VALUES (:tenant_id, :room_id, :start_date, :end_date, :total_amount, :deposit, 'PENDING')");

        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->bindParam(':room_id', $roomId);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->bindParam(':total_amount', $totalAmount);
        $stmt->bindParam(':deposit', $deposit);

        if ($stmt->execute()) {
            return $this->pdo->lastInsertId();  // Trả về ID của hợp đồng mới
        }

        return false;
    }

    // Cập nhật trạng thái của hợp đồng
    public function updatecontractStatusUser($contractId, $status)
    {
        $query = "UPDATE `contracts` SET status = :status WHERE id = :id";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $contractId);

        return $stmt->execute();
    }

    // Admin
    public function getcontractById(int $id): ?contract
    {
        $statement = $this->db->prepare('SELECT * FROM contracts WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->fillFromDbRow($row) : null;
    }

    private function fillFromDbRow(array $row): contract
    {
        $this->id = $row['id'];
        $this->id_tenants = $row['id_tenants'];  
        $this->id_room = $row['id_room'];     
        $this->total_amount = $row['total_amount'];  
        $this->status = $row['status'];       
        $this->deposit = $row['deposit'];      
        $this->start_date = $row['start_date'];  
        $this->end_date = $row['end_date'];   

        return $this;
    }

    public function updatecontractStatus(int $contractId, string $status): bool
    {
        $statement = $this->db->prepare('UPDATE contracts SET status = :status WHERE id = :id');
        return $statement->execute(['status' => $status, 'id' => $contractId]);
    }

    public function deletecontract(int $contractId): bool
    {
        $statement = $this->db->prepare('DELETE FROM contracts WHERE id = :id');
        return $statement->execute(['id' => $contractId]);
    }



    public function getPendingcontracts(): int
    {
        $statement = $this->db->prepare('SELECT COUNT(*) FROM contracts WHERE status = "PENDING"');
        $statement->execute();
        return (int) $statement->fetchColumn();
    }

    // Lấy danh sách hợp đồng
    public function getContracts($status = '', $search = '', $recordsPerPage = 10, $currentPage = 1)
    {
        // Câu truy vấn cơ bản
        $sql = "SELECT c.*, t.email AS tenant_email, r.name AS room_name FROM contracts c
            JOIN tenants t ON c.id_tenants = t.id
            JOIN rooms r ON c.id_room = r.id
            WHERE 1=1";

        // Nếu có trạng thái hợp đồng, thêm vào câu lệnh SQL
        if ($status) {
            $sql .= " AND c.status = :status";
        }

        // Nếu có tìm kiếm, thêm vào câu lệnh SQL
        if ($search) {
            $sql .= " AND (t.email LIKE :search)";
        }

        // Thêm câu lệnh LIMIT
        $sql .= " LIMIT :start, :limit";

        // Chuẩn bị câu lệnh SQL
        $stmt = $this->db->prepare($sql);

        // Liên kết tham số
        if ($status) {
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        }

        if ($search) {
            $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        }

        // Liên kết tham số LIMIT và OFFSET
        $stmt->bindValue(':start', ($currentPage - 1) * $recordsPerPage, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $recordsPerPage, PDO::PARAM_INT);

        // Thực thi câu truy vấn
        $stmt->execute();

        // Trả về danh sách hợp đồng
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalRecords(string $status = '', string $search = '')
    {
        // Câu truy vấn để đếm tổng số hợp đồng
        $sql = "SELECT COUNT(*) FROM contracts c
            JOIN tenants t ON c.id_tenants = t.id
            JOIN rooms r ON c.id_room = r.id
            WHERE 1=1";

        // Nếu có lọc theo trạng thái hợp đồng
        if ($status) {
            $sql .= " AND c.status = :status";
        }

        // Nếu có tìm kiếm
        if ($search) {
            $sql .= " AND (t.email LIKE :search)";
        }

        // Chuẩn bị câu lệnh SQL
        $stmt = $this->db->prepare($sql);

        // Liên kết tham số nếu có
        if ($status) {
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        }

        if ($search) {
            // Tìm kiếm theo từ khóa, sử dụng dấu "%" để tìm kiếm
            $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        }

        // Thực thi câu truy vấn
        $stmt->execute();

        // Trả về số lượng hợp đồng
        return $stmt->fetchColumn();
    }


    public function getRoomIdByContractId($contractId)
    {
        $sql = "SELECT id_room FROM contracts WHERE id = :contractId";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':contractId' => $contractId]);
        return $stmt->fetchColumn(); // Trả về `room_id` nếu có, null nếu không
    }

    public function getContractsByTenant($tenants)
    {
        $tenantIds = implode(',', array_map(function ($tenant) {
            return $tenant['id'];
        }, $tenants));
        // Nếu không có id nào trong mảng, trả về mảng rỗng
    if (empty($tenantIds)) {
        return [];
    }
        $sql = "SELECT * FROM contracts WHERE id_tenants IN ($tenantIds)";
        $statement = $this->db->prepare($sql);
        $statement->execute();
        return $statement->fetchAll();
    }

    // Phương thức lấy dịch vụ theo contract_id
    public function getServicesByContractId($contractId)
    {
        $stmt = $this->pdo->prepare('
            SELECT s.*
            FROM services s
            JOIN lien_ket cs ON s.id = cs.id_service
            WHERE cs.id_contract = :id_contract
        ');
        $stmt->bindParam(':id_contract', $contractId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);  // Trả về tất cả các dịch vụ liên quan đến hợp đồng
    }
}
