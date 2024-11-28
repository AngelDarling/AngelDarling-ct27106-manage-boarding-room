<?php

namespace App\Controllers;

use App\Models\room;
use App\Models\service; // Import model service

class GiohangController extends Controller
{
    public function index()
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if (!AUTHGUARD()->isUserLoggedIn()) {
        redirect('/login');
    }

    $user = AUTHGUARD()->user();
    $cart = $_SESSION['cart'] ?? [];

    $roomModel = new room(PDO());
    $latestrooms = $roomModel->getLatestrooms(3);

    $serviceModel = new service(PDO());
    $services = $serviceModel->getAllServices();

    // Tính tổng giá giỏ hàng (bao gồm phòng và dịch vụ)
    $totalPrice = 0;
    foreach ($cart as $roomId => $room) {
        // Tổng giá phòng
        $totalPrice += $room['price'];

        // Tổng giá dịch vụ
        if (isset($room['services'])) {
            foreach ($room['services'] as $service) {
                $totalPrice += $service['price'];
            }
        }
    }

    $data = [
        'user' => $user,
        'latestrooms' => $latestrooms,
        'rooms' => $cart,
        'totalPrice' => $totalPrice, // Truyền tổng giá vào view
        'cartJson' => json_encode($cart),
        'services' => $services,
    ];
    $this->sendPage('user/giohang.twig', $data);
}


    public function addToCart()
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['themgiohang'])) {
        $roomModel = new room(PDO());
        $serviceModel = new service(PDO());

        // Nhận giá trị từ form
        $roomId = $_POST['id_room'];
        $room = $roomModel->getroomByIdroom($roomId);

        if ($room) {
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }

            // Thêm phòng vào giỏ hàng
            $_SESSION['cart'][$roomId] = [
                'name' => $room['name'],
                'price' => $room['price'],
                'services' => [], // Khởi tạo mảng dịch vụ cho phòng
            ];
        }

            // Kiểm tra dịch vụ nếu có
            if (isset($_POST['services'])) {
                foreach ($_POST['services'] as $serviceId) {
                    $service = $serviceModel->getServiceById($serviceId);
                    if ($service) {
                       
                        // Thêm dịch vụ vào phòng trong giỏ hàng
                        $_SESSION['cart'][$roomId]['services'][] = [
                            'id' => $service['id'],
                            'name' => $service['name'],
                            'price' => $service['price'],
                        ];
                    }
                }
            }


        // Chuyển hướng về trang giỏ hàng
        redirect('/giohang');
    }
}


    public function removeFromCart()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_room'])) {
            if (!AUTHGUARD()->isUserLoggedIn()) {
                redirect('/login');
            }
            $roomId = $_POST['id_room'];

            if (isset($_SESSION['cart'][$roomId])) {
                unset($_SESSION['cart'][$roomId]);
            }

            // Chuyển hướng về trang giỏ hàng sau khi xóa
            redirect('/giohang');
        }
    }


//     public function addService()
// {
//     if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_service'])) {
//         $serviceId = $_POST['id_service'];
//         $roomId = $_POST['id_room'];

//         // Tìm dịch vụ theo ID
//         $serviceModel = new service(PDO());
//         $service = $serviceModel->getServiceById($serviceId);

//         if ($service) {
//             // Kiểm tra xem phòng này đã có dịch vụ này chưa
//             if (!isset($_SESSION['cart'][$roomId]['services'])) {
//                 $_SESSION['cart'][$roomId]['services'] = [];
//             }

//             $_SESSION['cart'][$roomId]['services'][$serviceId] = [
//                 'name' => $service['name'],
//                 'price' => $service['price']
//             ];
//         }

//         redirect('/giohang');
//     }
// }

}

