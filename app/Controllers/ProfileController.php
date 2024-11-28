<?php
namespace App\Controllers;

use App\Models\Room; 
use App\Models\Contract;
use App\Models\Tenant;

class ProfileController extends Controller
{
    public function index()
    {
        // Kiểm tra người dùng đã đăng nhập chưa
        if (!AUTHGUARD()->isUserLoggedIn()) {
            redirect('/login');
        }

        // Lấy thông tin người dùng
        $user = AUTHGUARD()->user();

        // Lấy các tenant liên quan đến user này
        $tenantModel = new Tenant(PDO());
        $tenants = $tenantModel->getTenantsByUser($user->id);
        $tenants = $tenants ?: [];

        // Lấy các hợp đồng liên quan đến tenant
        $contractModel = new Contract(PDO());
        $contracts = $contractModel->getContractsByTenant($tenants);
        $contracts = $contracts ?: [];
        $contractIds = implode(',', array_map(function ($contract) {
            return $contract['id'];
        }, $contracts));
        
         // Lấy các phòng mà user đã đặt
        $roomModel = new Room(PDO()); 
        $roomId = $contractModel->getRoomIdByContractId($contractIds);
        $rooms = $roomModel->getroomById($roomId) ?: [];
        
        // Lấy các dịch vụ liên quan đến các hợp đồng
        $services = [];
        if (!empty($contractIds)) {
            $services = $contractModel->getServicesByContractId($contractIds) ?: [];
        }

        // Cấu trúc dữ liệu trả về
        $data = [
            'user' => $user,
            'rooms' => $rooms,
            'tenants' => $tenants,
            'contracts' => $contracts,
            'services' => $services,
        ];

        // Trả về view profile với dữ liệu cần thiết
        $this->sendPage('user/profile.twig', $data);
    }
}

