<?php

namespace App\Controllers;

use App\Models\room; // Import model room
use App\Models\type_room;

class type_roomController extends Controller
{
    public function index()
    {
        if (!AUTHGUARD()->isUserLoggedIn()) {
            redirect('/login');
        }

        $user = AUTHGUARD()->user();

        $roomModel = new room(PDO());
        $latestrooms = $roomModel->getLatestrooms(3);

        $type_roomModel = new type_room(PDO());

        $type_rooms = $type_roomModel->getAlltype_rooms();
        $data = [
            'user' => $user,
            'latestrooms' => $latestrooms,
            'type_rooms' => $type_rooms,
        ];
        $this->sendPage('user/type_room.twig', $data);
    }
}
