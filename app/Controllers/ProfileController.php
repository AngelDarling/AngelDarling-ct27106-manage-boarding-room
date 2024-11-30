<?php

namespace App\Controllers;

use App\Models\Room;
use App\Models\Contract;
use App\Models\Tenant;
use App\Models\MaintenanceRequest;
use App\Models\Notification;

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
        $tenants = $tenants ?: []; // Nếu không có tenant, trả về mảng rỗng

        // Lấy các hợp đồng liên quan đến tenant
        $contractModel = new Contract(PDO());
        $contracts = $contractModel->getContractsByTenant($tenants); // Giả sử hàm này trả về hợp đồng của các tenants
        $contracts = $contracts ?: []; // Nếu không có hợp đồng, trả về mảng rỗng

        $roomModel = new room(PDO());

        // Loại bỏ các hợp đồng trùng lặp (nếu hợp đồng có thể trùng vì có nhiều tenant)
        $uniqueContracts = [];
        foreach ($contracts as $contract) {
            $uniqueContracts[$contract['id']] = $contract; // Lưu hợp đồng theo id, tự động loại bỏ trùng lặp
        }
        $contracts = array_values($uniqueContracts); // Chuyển lại thành mảng chỉ có các hợp đồng không trùng lặp

        foreach ($contracts as &$contract) {
            $contract['tenant_emails'] = [];
            $tenantIds = $contractModel->getTenantIdsByContract($contract['id']); // Giả sử bạn có phương thức này trong Contract model
            if (!empty($tenantIds)) {
                $contract['tenant_emails'] = $tenantModel->getEmailsByTenantIds($tenantIds); // Lấy email của tenants từ mảng tenantIds
            }
            $contract['rooms_name'] = $roomModel->getNameByRoomId($contract['id_room']);
        }
        // Lấy các phòng mà user đã đặt
        $roomModel = new Room(PDO());
        $roomIds = [];
        if (!empty($contracts)) {
            // Lấy room_id từ các hợp đồng
            $contractIds = implode(',', array_map(function ($contract) {
                return $contract['id'];
            }, $contracts));
            $roomIds = $contractModel->getRoomIdByContractId($contractIds); // Lấy danh sách room_id từ các hợp đồng
        }

        $rooms = [];
        if (!empty($roomIds)) {
            // Đảm bảo roomIds là mảng khi truyền vào getRoomsByIds
            if (!is_array($roomIds)) {
                $roomIds = [$roomIds]; // Chuyển roomId đơn lẻ thành mảng
            }
            $rooms = $roomModel->getRoomsByIds($roomIds); // Lấy các phòng dựa trên room_ids
        }

        // Lấy tất cả các dịch vụ liên quan đến các hợp đồng
        $services = [];
        if (!empty($contractIds)) {
            // Giả sử bạn có phương thức getAllServicesByContractIds() để lấy tất cả dịch vụ từ các hợp đồng
            $services = $contractModel->getAllServicesByContractIds($contractIds);
        }

        // Loại bỏ các hợp đồng trùng lặp (nếu hợp đồng có thể trùng vì có nhiều tenant)
        $uniqueservices = [];
        foreach ($services as $service) {
            $uniqueservices[$service['id']] = $service; // Lưu hợp đồng theo id, tự động loại bỏ trùng lặp
        }
        $services = array_values($uniqueservices);

        // Lấy thông báo từ session nếu có
        $success_message = $_SESSION['success_message'] ?? null;

        // Xóa thông báo để không hiện lại khi refresh
        unset($_SESSION['success_message']);

        // Cấu trúc dữ liệu trả về
        $data = [
            'user' => $user,
            'rooms' => $rooms,
            'tenants' => $tenants,
            'contracts' => $contracts,
            'services' => $services,
            'success_message' => $success_message,
        ];

        // Trả về view profile với dữ liệu cần thiết
        $this->sendPage('user/profile.twig', $data);
    }

    // Trong controller, ví dụ ProfileController.php

    public function submitMaintenanceRequest()
    {
        // Kiểm tra người dùng đã đăng nhập chưa
        if (!AUTHGUARD()->isUserLoggedIn()) {
            redirect('/login');
        }

        // Lấy thông tin từ form
        $room_id = $_POST['room_id'];
        $description = $_POST['description'];
        $user_id = $_POST['user_id'];

        // Gọi model để lưu yêu cầu bảo trì
        $maintenanceModel = new MaintenanceRequest(PDO());
        $maintenanceModel->createMaintenanceRequest($room_id, $description, $user_id);

        // Đặt thông báo thành công
        $_SESSION['success_message'] = 'Yêu cầu bảo trì đã được gửi thành công!';
        redirect('/profile');
    }


    // Controller (AdminController.php)
public function viewNotifications($tenantId) {
    // Lấy thông báo của người thuê từ CSDL
    $notificationModel = new Notification(PDO());
    $notifications = $notificationModel->getNotificationsByTenantId($tenantId);
    
    // Trả về dữ liệu dưới dạng JSON
    echo json_encode(['notifications' => $notifications]);
}
}
