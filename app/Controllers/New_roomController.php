<?php

namespace App\Controllers;

use App\Models\room; 
class new_roomController extends Controller
{
    public function index()
    {
        if (!AUTHGUARD()->isUserLoggedIn()) {
            redirect('/login');
        }

        $user = AUTHGUARD()->user();

        $roomModel = new room(PDO()); 
        $rooms = $roomModel->getLatestrooms(3);
        $latestrooms = $roomModel->getLatestrooms(3);
        $data = [
            'rooms' => $rooms, 
            'latestrooms' => $latestrooms,
            'user' => $user,
        ];

        $this->sendPage('user/new_room.twig', $data);
    }
}
