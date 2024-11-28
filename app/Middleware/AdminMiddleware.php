<?php
namespace App\Middleware;

class AdminMiddleware
{
    public function handle()
    {
        // Kiểm tra nếu người dùng không đăng nhập hoặc không có vai trò ADMIN
        if (!AUTHGUARD()->isUserLoggedIn() || AUTHGUARD()->user()->role !== 'ADMIN') {
            redirect('/home');  // Chuyển hướng nếu không phải admin
        }
    }
}
