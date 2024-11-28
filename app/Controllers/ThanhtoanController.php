<?php

namespace App\Controllers;

use App\Models\Contract;
use App\Models\User;
use App\Models\room;
use App\Controllers\Controller;
use App\Models\tenant;
use App\Models\Lienket;

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

        // Kiểm tra các trường nhập từ người dùng
        $name = $_POST['name'] ?? null;
        if (!$name) {
            $_SESSION['error'] = 'Vui lòng nhập họ và tên';
            redirect('/thanhtoan');
        }

        $email = $_POST['email'] ?? null;
        if (!$email) {
            $_SESSION['error'] = 'Vui lòng nhập email';
            redirect('/thanhtoan');
        }

        $address = $_POST['address'] ?? null;
        if (!$address) {
            $_SESSION['error'] = 'Vui lòng nhập địa chỉ giao hàng';
            redirect('/thanhtoan');
        }

        $phone = $_POST['phone'] ?? null;
        if (!$phone) {
            $_SESSION['error'] = 'Vui lòng nhập số điện thoại';
            redirect('/thanhtoan');
        }

        $cccd = $_POST['cccd'] ?? null;
        if (!$cccd) {
            $_SESSION['error'] = 'Vui lòng nhập căn cước công dân';
            redirect('/thanhtoan');
        }

        $startDate = $_POST['start_date'] ?? null;
        $endDate = $_POST['end_date'] ?? null;
        $deposit = $_POST['deposit'] ?? null;

        if (!$startDate || !$endDate || !$deposit) {
            $_SESSION['error'] = 'Vui lòng nhập đầy đủ thông tin ngày và tiền cọc';
            redirect('/thanhtoan');
        }

        // Tạo đối tượng Tenant và lưu vào cơ sở dữ liệu
        $tenantModel = new Tenant(PDO());
        $tenantId = $tenantModel->createTenant($name, $email, $address, $phone, $cccd, $userId);

        if (!$tenantId) {
            $_SESSION['error'] = 'Lỗi lưu thông tin người thuê. Vui lòng thử lại!';
            redirect('/thanhtoan');
        }

        // Kiểm tra giỏ hàng và tính tổng giá trị đơn hàng
        $rooms = $_SESSION['cart'] ?? null;
        if (!$rooms) {
            $_SESSION['error'] = 'Giỏ hàng trống. Vui lòng thử lại!';
            redirect('/thanhtoan');
        }

        $totalPrice = 0;
        $roomId = null;
        $services = []; // Mảng chứa các dịch vụ đã chọn cho phòng
        foreach ($rooms as $roomId => $room) {
            // Tính tổng giá trị phòng
            $totalPrice += $room['price'];

            // Tính tổng giá dịch vụ (nếu có)
            if (isset($room['services'])) {
                foreach ($room['services'] as $service) {
                    $totalPrice += $service['price'];
                    $services[] += $service['id']; // Giả sử mỗi dịch vụ có trường 'id'
                }
            }
        }

        // Lưu hợp đồng mới cho phòng trong giỏ hàng
        $contractModel = new Contract(PDO());
        $contractId = $contractModel->createContract($tenantId, $roomId, $totalPrice, $startDate, $endDate, $deposit);

        if ($contractId) {
            $lienKetModel = new LienKet(PDO());
            $lienKetModel->addServicesToContract($contractId, $services);

            $roomModel = new room(PDO());
            $roomModel->updateRoomStatus($roomId, 'Chờ duyệt');

            // Xóa giỏ hàng sau khi thanh toán
            unset($_SESSION['cart']);

            $_SESSION['message'] = 'Đặt phòng thành công! Hợp đồng đã được tạo.';
            redirect('/thanhtoan_success');
        }

        $_SESSION['error'] = 'Lỗi đặt phòng. Vui lòng thử lại!';
        redirect('/thanhtoan');
    }
}
