<?php

namespace App\Controllers\Admin;

use App\Models\Contract;
use App\Models\Room;
use App\Models\Tenant;
use App\Utils\Paginator;

class ContractController extends Controller
{
    public function __construct()
    {
        // Kiểm tra nếu người dùng chưa đăng nhập thì chuyển hướng về trang login
        parent::__construct();
    }

    // Hàm hiển thị danh sách hợp đồng
    public function getContract()
    {
        $recordsPerPage = 10;
        // Lọc theo trạng thái hợp đồng (PENDING, CANCELLED, PROCESSING, DONE)
        $status = isset($_GET['filterBy']) ? $_GET['filterBy'] : '';
        $status = in_array($status, ['PENDING', 'CANCELLED', 'PROCESSING', 'DONE']) ? $status : '';

        // Tìm kiếm hợp đồng theo từ khóa
        $search = isset($_GET['search']) ? $_GET['search'] : '';

        // Tạo model hợp đồng
        $contractModel = new Contract(PDO());
        $totalRecords = $contractModel->getTotalRecords($status, $search);

        // Tính tổng số trang
        $totalPages = ceil($totalRecords / $recordsPerPage);
        $currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
        } elseif ($currentPage < 1) {
            $currentPage = 1;
        }

        // Tạo phân trang
        $paginator = new Paginator($recordsPerPage, $totalRecords, $currentPage);

        // Lấy danh sách hợp đồng với các dữ liệu liên quan
        $contracts = $contractModel->getContracts($status, $search, $recordsPerPage, $currentPage);

        
        // Trả về trang hợp đồng
        $this->sendPage('/admin/contract/contract', [
            'contracts' => $contracts,
            'paginator' => $paginator,
            'filter' => $status,
            'search' => $search,
            'status' => session_get_once("status"),
            'isOpenModal' => session_get_once("isOpenModal"),
            'formError' => session_get_once("formError"),
            'errors' => session_get_once("errors"),
            'user' => AUTHGUARD()->user(),
        ]);
    }

    // Hàm xóa hợp đồng
    public function deleteContract()
    {
        $contractId = isset($_POST['contract_id']) ? (int) $_POST['contract_id'] : 0;
        if ($contractId > 0) {
            $contractModel = new Contract(PDO());
            $success = $contractModel->deleteContract($contractId);
            if ($success) {
                redirect('/admin/contract', ['status' => 'Hợp đồng đã được xóa thành công']);
            } else {
                redirect('/admin/contract', ['status' => 'Xóa hợp đồng thất bại']);
            }
        } else {
            redirect('/admin/contract', ['status' => 'ID hợp đồng không hợp lệ']);
        }
    }

    // Hàm cập nhật trạng thái hợp đồng
    public function updateContractStatus()
    {
        $contractId = isset($_POST['contract_id']) ? (int) $_POST['contract_id'] : 0;
        $status = isset($_POST['status']) ? $_POST['status'] : '';

        // Kiểm tra dữ liệu hợp lệ
        if ($contractId > 0 && in_array($status, ['PENDING', 'PROCESSING', 'DONE', 'CANCELLED'])) {
            $contractModel = new Contract(PDO());
            $success = $contractModel->updateContractStatus($contractId, $status);

            if ($success && $status === 'DONE') {
                // Nếu trạng thái hợp đồng là DONE, cập nhật trạng thái phòng
                $roomId = $contractModel->getRoomIdByContractId($contractId); // Giả sử bạn có phương thức này
                if ($roomId) {
                    $roomModel = new Room(PDO());
                    $roomUpdated = $roomModel->updateRoomStatus($roomId, 'Đã đặt'); // Trạng thái mới của phòng
                    if (!$roomUpdated) {
                        redirect('/admin/contract', ['status' => 'Cập nhật hợp đồng thành công nhưng không thể cập nhật trạng thái phòng']);
                    }
                }
            }

            if ($success) {
                redirect('/admin/contract', ['status' => 'Trạng thái hợp đồng đã được cập nhật']);
            } else {
                redirect('/admin/contract', ['status' => 'Cập nhật trạng thái hợp đồng thất bại']);
            }
        } else {
            redirect('/admin/contract', ['status' => 'Dữ liệu không hợp lệ']);
        }
    }

    public function viewInvoice()
    {
        $contractId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if ($contractId > 0) {
            $contractModel = new Contract(PDO());
            $tenantModel = new Tenant(PDO());
            $roomModel = new Room(PDO());
            $contract = $contractModel->getContractById($contractId);

            $services = [];
            $services = $contractModel->getServicesByContractId($contractId) ?: [];
        
            if ($contract) {
                $tenant = $tenantModel->getTenantById($contract->id_tenants);
                $room = $roomModel->getRoomByIdRoom($contract->id_room);

                $statusColors = [
                    'PENDING' => 'warning',
                    'PROCESSING' => 'info',
                    'DONE' => 'success',
                    'CANCELLED' => 'danger'
                ];

                $contractData = [
                    'id' => $contract->id,
                    'tenant_name' => $tenant['name'],
                    'room_name' => $room['name'],
                    'start_date' => $contract->start_date,
                    'end_date' => $contract->end_date,
                    'total_amount' => $contract->total_amount,
                    'deposit' => $contract->deposit,
                    'balance' => $contract->total_amount - $contract->deposit,
                    'status' => $contract->status,
                    'status_color' => $statusColors[$contract->status] ?? 'secondary',
                    'services' => $services
                ];

                // Return contract data to the view
                $this->sendPage('admin/contract/detail', ['contract' => $contractData]);
            } else {
                redirect('/admin/order', ['status' => 'Invalid order ID']);
            }
        } else {
            redirect('/admin/order', ['status' => 'Invalid order ID']);
        }
    }
}
