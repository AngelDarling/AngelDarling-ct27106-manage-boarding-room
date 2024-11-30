<?php

namespace App\Controllers;

use App\Models\Contract;
use App\Models\User;
use App\Models\room;
use App\Controllers\Controller;
use App\Models\tenant;
use App\Models\Lienket;
use App\Models\Kyket;

class ThanhtoanController extends Controller
{
    public function index()
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            redirect('/login');
        }
        if (!AUTHGUARD()->isUserLoggedIn()) {
            redirect('/login');
        }
        $userModel = new User(PDO());
        $user = $userModel->getUserByIdUser($userId);
        $roomModel = new room(PDO());
        $latestrooms = $roomModel->getLatestrooms(3);
        $rooms = $_SESSION['cart'] ?? [];

        // Tính tổng giá trị đơn hàng
        $totalPrice = 0;
        foreach ($rooms as $roomId => $room) {
            // Tổng giá phòng
            $totalPrice += $room['price'];

            // Tổng giá dịch vụ (nếu có)
            if (isset($room['services'])) {
                foreach ($room['services'] as $service) {
                    $totalPrice += $service['price'];
                }
            }
        }
        $data = [
            'user' => $user,
            'rooms' => $rooms,
            'latestrooms' => $latestrooms,
            'totalPrice' => $totalPrice,
        ];
        $this->sendPage('user/thanhtoan.twig', $data);
    }

    public function confirm()
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            redirect('/login');
        }

        // Kiểm tra và lấy thông tin từ form
        $tenants = $_POST['tenants'] ?? null; // Mảng chứa danh sách người thuê
        $startDate = $_POST['start_date'] ?? null;
        $endDate = $_POST['end_date'] ?? null;
        $deposit = $_POST['deposit'] ?? null;

        // Kiểm tra các trường thông tin chính
        if (!$tenants || !$startDate || !$endDate || !$deposit) {
            $_SESSION['error'] = 'Vui lòng nhập đầy đủ thông tin người thuê, ngày thuê và tiền cọc';
            redirect('/thanhtoan');
        }

        // Kiểm tra giỏ hàng
        $rooms = $_SESSION['cart'] ?? null;
        if (!$rooms) {
            $_SESSION['error'] = 'Giỏ hàng trống. Vui lòng thêm phòng trước khi thanh toán!';
            redirect('/thanhtoan');
        }

        $totalPrice = 0;
        $roomId = null;
        $services = []; // Mảng chứa các dịch vụ đã chọn cho phòng

        // Tính toán tổng giá trị đơn hàng
        foreach ($rooms as $roomId => $room) {
            $totalPrice += $room['price'];

            if (isset($room['services'])) {
                foreach ($room['services'] as $service) {
                    $totalPrice += $service['price'];
                    $services[] = $service['id']; // Lưu ID dịch vụ
                }
            }
        }

        // Lưu thông tin hợp đồng
        $contractModel = new Contract(PDO());
        $contractId = $contractModel->createContract($roomId, $totalPrice, $startDate, $endDate, $deposit);

        if (!$contractId) {
            $_SESSION['error'] = 'Lỗi tạo hợp đồng. Vui lòng thử lại!';
            redirect('/thanhtoan');
        }

        // Lưu thông tin từng người thuê và liên kết với hợp đồng
        $tenantModel = new Tenant(PDO());
        $kyKetModel = new KyKet(PDO());

        // Liên kết dịch vụ với hợp đồng
        $lienKetModel = new LienKet(PDO());
        $lienKetModel->addServicesToContract($contractId, $services);

        // Cập nhật trạng thái phòng
        $roomModel = new Room(PDO());
        $roomModel->updateRoomStatus($roomId, 'Chờ duyệt');
        foreach ($tenants as $tenant) {
            $name = $tenant['name'] ?? null;
            $email = $tenant['email'] ?? null;
            $address = $tenant['address'] ?? null;
            $phone = $tenant['phone'] ?? null;
            $cccd = $tenant['cccd'] ?? null;

            // Kiểm tra thông tin người thuê
            if (!$name || !$email || !$address || !$phone || !$cccd) {
                $_SESSION['error'] = 'Vui lòng nhập đầy đủ thông tin cho tất cả người thuê!';
                redirect('/thanhtoan');
            }

            // Lưu thông tin người thuê vào cơ sở dữ liệu
            $tenantId = $tenantModel->createTenant($name, $email, $address, $phone, $cccd, $userId);

            if (!$tenantId) {
                $_SESSION['error'] = 'Lỗi lưu thông tin người thuê. Vui lòng thử lại!';
                redirect('/thanhtoan');
            }

            // Liên kết người thuê với hợp đồng trong bảng trung gian
            $kyKetModel->addTenantToContract($contractId, $tenantId);
        }

        

        // Xóa giỏ hàng sau khi thanh toán
        unset($_SESSION['cart']);

        $_SESSION['message'] = 'Đặt phòng thành công! Hợp đồng đã được tạo.';
        redirect('/thanhtoan_success');
    }
}
