<?php

namespace App\Controllers;

use App\Models\room; 
use App\Models\service; 

class SanphamController extends Controller
{
    public function show($id)
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!AUTHGUARD()->isUserLoggedIn()) {
            redirect('/login');
        }

        $user = AUTHGUARD()->user();

        $id_now = $id['id'];  // Lấy id từ URL

        $roomModel = new room(PDO());
        $room = $roomModel->getroomByIdroom($id_now); 

        $serviceModel = new service(PDO());
        $services = $serviceModel->getAllservices();

        if (!$room) {
            echo "Không tìm thấy sản phẩm với ID: " . $id_now; 
            return;
        }
        $isCartEmpty = $this->isCartEmpty();
        $latestrooms = $roomModel->getLatestrooms(3);
        $data = [
            'user' => $user,
            'room' => $room,
            'latestrooms' => $latestrooms,
            'services' => $services,
            'isCartEmpty' => $isCartEmpty,
        ];

        $this->sendPage('user/sanpham.twig', $data);
    }

    // app/Controllers/SanphamController.php

public function isCartEmpty()
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Kiểm tra giỏ hàng trong session
    $cart = $_SESSION['cart'] ?? []; // Giả sử giỏ hàng được lưu trong session với key 'cart'
    return empty($cart); // Trả về true nếu giỏ hàng trống, false nếu có sản phẩm
}

}
