<?php

namespace App\Controllers;

use App\Models\room;
class HomeController extends Controller
{
    
    public function index()
    {
        $this->checkLogin();

        $user = AUTHGUARD()->user();

        $roomModel = new room(PDO());
        $rooms = $roomModel->getLatestrooms(6);
        $latestrooms = $roomModel->getLatestrooms(3);

        $data = [
            'user' => $user,
            'messages' => session_get_once('messages'),
            'rooms' => $rooms,
            'latestrooms' => $latestrooms,
        ];

        $this->sendPage('user/index.twig', $data);
    }

    protected function checkLogin()
    {
        if (!AUTHGUARD()->isUserLoggedIn()) {
            redirect('/login');
        }
    }
}
