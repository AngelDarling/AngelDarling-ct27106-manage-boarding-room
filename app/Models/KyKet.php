<?php
namespace App\Models;

use PDO;

class KyKet
{
    protected $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Thêm dịch vụ vào hợp đồng
    public function addTenantsToContract($contractId, $tenants)
    {
        if (empty($tenants)) {
            return;
        }

        $stmt = $this->pdo->prepare("INSERT INTO ky_ket (id_tenant, id_contract) VALUES (:tenant_id, :contract_id)");

        foreach ($tenants as $tenantId) {
            $stmt->bindParam(':tenant_id', $tenantId);
            $stmt->bindParam(':contract_id', $contractId);
            $stmt->execute();
        }
    }
    public function addTenantToContract($contractId, $tenant)
    {
        if (empty($tenant)) {
            return;
        }

        $stmt = $this->pdo->prepare("INSERT INTO ky_ket (id_tenant, id_contract) VALUES (:tenant_id, :contract_id)");
            $stmt->bindParam(':tenant_id', $tenant);
            $stmt->bindParam(':contract_id', $contractId);
            $stmt->execute();
    }
}
