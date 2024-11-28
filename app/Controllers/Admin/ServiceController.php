<?php
namespace App\Controllers\Admin;

use App\Models\service;
use App\Utils\Paginator;
use PDO;

class serviceController extends Controller
{
    public function getservice()
    {
        $serviceModel = new service(PDO());
        $recordsPerPage = 10;

        $totalRecords = $serviceModel->getservicesCount();
        $totalPages = ceil($totalRecords / $recordsPerPage);
        $currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
        } elseif ($currentPage < 1) {
            $currentPage = 1;
        }

        $paginator = new Paginator($recordsPerPage, $totalRecords, $currentPage);

        $services = $serviceModel->getPaginatedservices($recordsPerPage, $paginator->recordOffset);
        $user = AUTHGUARD()->user();
        $this->sendPage('/admin/service/service', [
            'services' => $services,
            'paginator' => $paginator,
            'status' => session_get_once("status"),
            'isOpenModal' => session_get_once("isOpenModal"),
            'formError' => session_get_once("formError"),
            'errors' => session_get_once("errors"),
            'user' => $user,
        ]);
    }

    public function getUpdateservice()
    {
        $serviceId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($serviceId > 0) {
            $serviceModel = new service(PDO());
            $service = $serviceModel->getserviceById($serviceId);
            $listservices = $serviceModel->getAllservices();
            if ($service) {
                $this->sendPage('/admin/service/update', [
                    'service' => $service,
                    'listservices' => $listservices,
                    'status' => session_get_once("status")
                ]);
            } else {
                echo "Error: Could not retrieve service.";
            }
        } else {
            redirect('/admin/service', ['status' => 'Invalid ID']);
        }
    }

    public function addservice()
    {
        $newservice = new service(PDO());
        $data = $this->filterserviceData($_POST); // Lấy dữ liệu từ form

        $errors = $newservice->validateservice($data); // Kiểm tra lỗi
        if (empty($errors)) {
            $newservice->addservice($data); // Gọi phương thức thêm dịch vụ vào DB
            redirect('/admin/service', [
                'status' => 'Service added successfully',
            ]);
        } else {
            redirect('/admin/service', ['isOpenModal' => true, 'formError' => $data, 'errors' => $errors, 'status' => 'Check your form please']);
        }
    }

    public function update()
    {
        $serviceId = (int) $_POST['id'];
        $serviceModel = new service(PDO());
        $name = $_POST['name'];
        
        // Kiểm tra tên dịch vụ có bị trùng hay không
        if ($serviceModel->isserviceNameTaken($name) && $serviceModel->getserviceNameById($serviceId) != $name) {
            redirect('/admin/service/update?id=' . $serviceId, ['status' => 'Tên dịch vụ đã có']);
        }

        // Cập nhật dịch vụ mà không cần xử lý ảnh nữa
        $serviceModel->updateservice($serviceId, $_POST['name'], $_POST['price'], $_POST['description']);
        redirect('/admin/service/update?id=' . $serviceId, ['status' => 'Update success']);
    }

    public function filterserviceData(array $data): array
    {
        return [
            'name' => trim($data['name']),
            'price' => trim($data['price']),
            'description' => trim($data['description']),
        ];
    }

    public function deleteservice()
    {
        $serviceId = (int) $_POST['service_id'];
        $serviceModel = new service(PDO());
        $serviceModel->deleteservice($serviceId); // Xóa dịch vụ
        redirect('/admin/service', ['status' => 'Service deleted successfully']);
    }
}
