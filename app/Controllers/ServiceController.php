<?php

namespace App\Controllers;

use App\Models\room; 

class ServiceController extends Controller
{
    public function index()
    {
    if (!AUTHGUARD()->isUserLoggedIn()) {
        redirect('/login');
      }
  
      $user = AUTHGUARD()->user();

      $roomModel = new room(PDO()); 
      $latestrooms = $roomModel->getLatestrooms(3);
      $data = [
        'latestrooms' => $latestrooms,
        'user' => $user,
    ];
        $this->sendPage('user/service.twig', $data);
    }
}
