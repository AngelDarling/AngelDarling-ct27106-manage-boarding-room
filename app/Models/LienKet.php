<?php
namespace App\Models;

use PDO;

class LienKet
{
    protected $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Thêm dịch vụ vào hợp đồng
    public function addServicesToContract($contractId, $services)
    {
        if (empty($services)) {
            return;
        }

        $stmt = $this->pdo->prepare("INSERT INTO lien_ket (id_service, id_contract) VALUES (:service_id, :contract_id)");

        foreach ($services as $serviceId) {
            $stmt->bindParam(':service_id', $serviceId);
            $stmt->bindParam(':contract_id', $contractId);
            $stmt->execute();
        }
    }
}
