<?php

namespace App\Controllers\Admin;

use App\Models\maintenancerequest;
use App\Utils\Paginator;
use PDO;

class maintenance_requestController extends Controller
{
    public function getmaintenance_request()
    {
        $maintenance_requestModel = new MaintenanceRequest(PDO());
        $recordsPerPage = 10;

        $totalRecords = $maintenance_requestModel->getmaintenance_requestsCount();
        $totalPages = ceil($totalRecords / $recordsPerPage);
        $currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
        } elseif ($currentPage < 1) {
            $currentPage = 1;
        }

        $paginator = new Paginator($recordsPerPage, $totalRecords, $currentPage);

        $maintenance_requests = $maintenance_requestModel->getPaginatedmaintenance_requests($recordsPerPage, $paginator->recordOffset);
        $user = AUTHGUARD()->user();

        // Lấy tên người dùng và tên phòng cho mỗi yêu cầu bảo trì
        foreach ($maintenance_requests as &$request) {
            $request['user_name'] = $maintenance_requestModel->getUserNameById($request['id_user']);
            $request['room_name'] = $maintenance_requestModel->getRoomNameById($request['id_room']);
        }

        $this->sendPage('/admin/maintenance_request/maintenance_request', [
            'maintenance_requests' => $maintenance_requests,
            'paginator' => $paginator,
            'status' => session_get_once("status"),
            'isOpenModal' => session_get_once("isOpenModal"),
            'formError' => session_get_once("formError"),
            'errors' => session_get_once("errors"),
            'user' => $user,
        ]);
    }

    public function deletemaintenance_request()
    {
        $maintenanceRequestId = isset($_POST['maintenance_request_id']) ? (int) $_POST['maintenance_request_id'] : 0;

        // Kiểm tra dữ liệu hợp lệ
        if ($maintenanceRequestId > 0) {
            $maintenanceRequestModel = new MaintenanceRequest(PDO());
            $success = $maintenanceRequestModel->deleteMaintenanceRequest($maintenanceRequestId); // Xóa yêu cầu bảo trì

            if ($success) {
                redirect('/admin/maintenance_request', ['status' => 'Yêu cầu bảo trì đã bị xóa thành công']);
            } else {
                redirect('/admin/maintenance_request', ['status' => 'Không thể xóa yêu cầu bảo trì']);
            }
        } else {
            redirect('/admin/maintenance_request', ['status' => 'Dữ liệu không hợp lệ']);
        }
    }

    // Cập nhật trạng thái yêu cầu bảo trì thành "Đã duyệt"
    public function updatemaintenance_requestStatus()
{
    $maintenanceRequestId = isset($_POST['maintenance_request_id']) ? (int) $_POST['maintenance_request_id'] : 0;
    $status = 'Đã tiếp nhận'; // Cập nhật thành trạng thái "Đã duyệt"

    // Kiểm tra dữ liệu hợp lệ
    if ($maintenanceRequestId > 0) {
        $maintenanceRequestModel = new MaintenanceRequest(PDO());
        $success = $maintenanceRequestModel->updateRequestStatus($maintenanceRequestId, $status); // Cập nhật trạng thái yêu cầu bảo trì

        if ($success) {
            // Cập nhật trạng thái thành công
            redirect('/admin/maintenance_request', ['status' => 'Trạng thái yêu cầu bảo trì đã được cập nhật thành công']);
        } else {
            redirect('/admin/maintenance_request', ['status' => 'Cập nhật trạng thái yêu cầu bảo trì thất bại']);
        }
    } else {
        redirect('/admin/maintenance_request', ['status' => 'Dữ liệu không hợp lệ']);
    }
}
}
