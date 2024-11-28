<?php

namespace App\Controllers;

class Thanhtoan_successController extends Controller
{
    public function success()
    {
        if (!AUTHGUARD()->isUserLoggedIn()) {
            redirect('/login');
        }
        $user = AUTHGUARD()->user();
        $message = $_SESSION['message'] ?? 'Thanh toán thành công!';
        $this->sendPage('user/thanhtoan_success.twig', ['message' => $message, 'user' => $user]);
    }
}
