<?php
namespace App\Controllers\Admin;

use App\Models\tenant;
use App\Utils\Paginator;
use PDO;

class tenantController extends Controller
{
    public function gettenant()
    {
        $tenantModel = new tenant(PDO());
        $recordsPerPage = 10;

        $totalRecords = $tenantModel->gettenantsCount();
        $totalPages = ceil($totalRecords / $recordsPerPage);
        $currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
        } elseif ($currentPage < 1) {
            $currentPage = 1;
        }

        $paginator = new Paginator($recordsPerPage, $totalRecords, $currentPage);

        $tenants = $tenantModel->getPaginatedtenants($recordsPerPage, $paginator->recordOffset);
        $user = AUTHGUARD()->user();
        $this->sendPage('/admin/tenant/tenant', [
            'tenants' => $tenants,
            'paginator' => $paginator,
            'status' => session_get_once("status"),
            'isOpenModal' => session_get_once("isOpenModal"),
            'formError' => session_get_once("formError"),
            'errors' => session_get_once("errors"),
            'user' => $user,
        ]);
    }

    public function getUpdatetenant()
    {
        $tenantId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($tenantId > 0) {
            $tenantModel = new tenant(PDO());
            $tenant = $tenantModel->gettenantById($tenantId);
            if ($tenant) {
                $this->sendPage('/admin/tenant/update', [
                    'tenant' => $tenant,
                    'status' => session_get_once("status")
                ]);
            } else {
                echo "Error: Could not retrieve tenant.";
            }
        } else {
            redirect('/admin/tenant', ['status' => 'Invalid ID']);
        }
    }

    public function addtenant()
    {
        $newtenant = new tenant(PDO());
        $data = $this->filtertenantData($_POST); // Lấy dữ liệu từ form

        $errors = $newtenant->validatetenant($data); // Kiểm tra lỗi
        if (empty($errors)) {
            $newtenant->addtenant($data); // Gọi phương thức thêm người thuê vào DB
            redirect('/admin/tenant', [
                'status' => 'Tenant added successfully',
            ]);
        } else {
            redirect('/admin/tenant', ['isOpenModal' => true, 'formError' => $data, 'errors' => $errors, 'status' => 'Check your form please']);
        }
    }

    public function update()
    {
        $tenantId = (int) $_POST['id'];
        $tenantModel = new tenant(PDO());
        $name = $_POST['name'];
        $listTenant = $tenantModel->getAllTenants();
        // Kiểm tra tên người thuê có bị trùng hay không
        if ($tenantModel->istenantNameTaken($name) && $tenantModel->gettenantNameById($tenantId) != $name) {
            redirect('/admin/tenant/update?id=' . $tenantId, ['status' => 'Tên người thuê đã có']);
        }

        // Cập nhật thông tin người thuê
        $tenantModel->updatetenant($tenantId, $_POST['name'], $_POST['email'], $_POST['phone'], $_POST['address'], $_POST['cccd']);
        redirect('/admin/tenant/update?id=' . $tenantId, ['status' => 'Update success', 'listTenant' => $listTenant]);
    }

    public function filtertenantData(array $data): array
    {
        return [
            'name' => trim($data['name']),
            'email' => trim($data['email']),
            'phone' => trim($data['phone']),
            'address' => trim($data['address']),
            'cccd' => trim($data['cccd']),
        ];
    }

    public function deletetenant()
    {
        $tenantId = (int) $_POST['tenant_id'];
        $tenantModel = new tenant(PDO());
        $tenantModel->deletetenant($tenantId); // Xóa người thuê
        redirect('/admin/tenant', ['status' => 'Tenant deleted successfully']);
    }
}
