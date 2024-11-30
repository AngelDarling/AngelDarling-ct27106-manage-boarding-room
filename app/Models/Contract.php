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
    public function createContract($roomId, $totalAmount, $startDate, $endDate, $deposit)
    {
        $stmt = $this->pdo->prepare("INSERT INTO contracts (id_room, start_date, end_date, total_amount, deposit, status) 
                                 VALUES (:room_id, :start_date, :end_date, :total_amount, :deposit, 'PENDING')");

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
        $sql = "SELECT c.*, 
               GROUP_CONCAT(t.name SEPARATOR ', ') AS tenant_names, 
               r.name AS room_name 
        FROM contracts c
        JOIN ky_ket k ON c.id = k.id_contract
        JOIN tenants t ON k.id_tenant = t.id
        JOIN rooms r ON c.id_room = r.id
        WHERE 1=1";

        // Nếu có trạng thái hợp đồng, thêm vào câu lệnh SQL
        if ($status) {
            $sql .= " AND c.status = :status";
        }

        // Nếu có tìm kiếm, thêm vào câu lệnh SQL
        if ($search) {
            $sql .= " AND (t.name LIKE :search OR t.email LIKE :search)";
        }

        // Nhóm kết quả theo hợp đồng
        $sql .= " GROUP BY c.id LIMIT :start, :limit";

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

        $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Thêm danh sách tenants vào mỗi hợp đồng
        foreach ($contracts as &$contract) {
            $contract['tenants'] = $this->getTenantsByContractId($contract['id']);
        }

        return $contracts;
    }


    public function getTotalRecords(string $status = '', string $search = '')
    {
        // Câu truy vấn để đếm tổng số hợp đồng
        $sql = "SELECT COUNT(*) FROM contracts c
            JOIN rooms r ON c.id_room = r.id
            WHERE 1=1";

        // Nếu có lọc theo trạng thái hợp đồng
        if ($status) {
            $sql .= " AND c.status = :status";
        }

        // Nếu có tìm kiếm
        if ($search) {
            $sql .= " AND c.id IN (
            SELECT k.id_contract
            FROM ky_ket k
            JOIN tenants t ON k.id_tenant = t.id
            WHERE t.email LIKE :search
        )";
        }

        // Chuẩn bị câu lệnh SQL
        $stmt = $this->db->prepare($sql);

        // Liên kết tham số nếu có
        if ($status) {
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        }

        if ($search) {
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
        $statement = $this->db->prepare($sql);
        $statement->bindParam(':contractId', $contractId, PDO::PARAM_INT);
        $statement->execute();
        $contract = $statement->fetch(PDO::FETCH_ASSOC);

    return $contract ? $contract['id_room'] : null;
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

        // Truy vấn hợp đồng thông qua bảng ky_ket
        $sql = "SELECT c.* 
            FROM contracts c
            JOIN ky_ket kk ON c.id = kk.id_contract
            WHERE kk.id_tenant IN ($tenantIds)";

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

    public function getAllServicesByContractIds($contractIds)
    {
        // Kiểm tra xem contractIds có phải là mảng không và chuyển nó thành chuỗi nếu là mảng
        if (is_array($contractIds)) {
            $contractIdsStr = implode(',', $contractIds); // Chuyển mảng thành chuỗi
        } else {
            // Nếu contractIds là chuỗi đã được nối với nhau
            $contractIdsStr = $contractIds;
        }
    
        // Câu truy vấn SQL để lấy tất cả dịch vụ liên quan đến nhiều hợp đồng
        $stmt = $this->pdo->prepare('
            SELECT s.*
            FROM services s
            JOIN lien_ket cs ON s.id = cs.id_service
            WHERE cs.id_contract IN (' . $contractIdsStr . ')
        ');
        
        $stmt->execute();
    
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Trả về tất cả dịch vụ liên quan đến các hợp đồng
    }
    


    
    // Lấy danh sách tenant liên quan đến hợp đồng
    public function getTenantsByContractId($contractId)
    {
        $sql = "
        SELECT t.* 
        FROM tenants t
        JOIN ky_ket k ON t.id = k.id_tenant
        WHERE k.id_contract = :contract_id
    ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':contract_id', $contractId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Trả về danh sách tenant liên quan
    }

    public function getTenantIdsByContract($contractId)
{
    $sql = "SELECT id_tenant FROM ky_ket WHERE id_contract = :contract_id";
    $statement = $this->db->prepare($sql);
    $statement->bindParam(':contract_id', $contractId, PDO::PARAM_INT);
    $statement->execute();
    return $statement->fetchAll(PDO::FETCH_COLUMN); // Trả về mảng chứa id_tenant
}

}
