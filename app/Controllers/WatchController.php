<?php

namespace App\Controllers;

use App\Models\room; // Import model room

class WatchController extends Controller
{
    public function index()
    {
        if (!AUTHGUARD()->isUserLoggedIn()) {
            redirect('/login');
        }

        $user = AUTHGUARD()->user();

        $roomModel = new room(PDO()); 
        $rooms = $roomModel->getAllroomsUser();
        $latestrooms = $roomModel->getLatestrooms(3);

        $data = [
            'rooms' => $rooms,
            'latestrooms' => $latestrooms,
            'user' => $user,
        ];
        $this->sendPage('user/watch.twig', $data);
    }

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
        
        $room = $roomModel->getroomByIdtype_room($id_now); 

        // Kiểm tra nếu không tìm thấy sản phẩm
        // if (!$room) {
        //     echo "Không tìm thấy sản phẩm với ID: " . $id_now; // Đảm bảo ID không phải mảng
        //     return;
        // }

        $latestrooms = $roomModel->getLatestrooms(3);
        $data = [
            'user' => $user,
            'rooms' => $room,
            'latestrooms' => $latestrooms,
        ];

        $this->sendPage('user/watch.twig', $data);
    }
}
