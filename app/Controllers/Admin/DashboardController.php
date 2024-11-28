<?php

namespace App\Controllers\Admin;

use App\Models\Contract;
use App\Models\room;
use App\Models\User;

// use App\Models\Contact;
class DashboardController extends Controller
{
    public function __construct()
    {
        // if (!AUTHGUARD()->isUserLoggedIn()) {
        //   redirect('/login');
        // }

        parent::__construct();
    }
    public function getIndex()
    {

        $contractModel = new contract(PDO());
        $userModel = new User(PDO());
        $roomModel = new room(PDO());
        $totalUsers = $userModel->getTotalUsers();
        $pendingcontracts = $contractModel->getPendingcontracts();
        $totalRooms = $roomModel->getTotalRooms();
        $totalRooms_Controng = $roomModel->getTotalRooms_Controng();
        $totalRooms_Dadat = $roomModel->getTotalRooms_Dadat();
        

        $user = AUTHGUARD()->user();
        $this->sendPage("/admin/dashboard", [
            'total_users' => $totalUsers,
            'pending_contracts' => $pendingcontracts,
            'totalRooms' => $totalRooms,
            'user' => $user,
            'totalRooms_Controng' => $totalRooms_Controng,
            'totalRooms_Dadat' => $totalRooms_Dadat
        ]);
    }
}
