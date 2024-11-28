<?php

namespace App\Controllers\Auth;

use App\Models\User;
use App\Controllers\Controller;

class LoginController extends Controller
{
    public function create()
    {
        // Kiểm tra nếu người dùng đã đăng nhập, chuyển hướng đến trang chính
        if (AUTHGUARD()->isUserLoggedIn()) {
            redirect('/home');
        }
        $message = "";
        if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
            $message = "Phiên đăng nhập của bạn đã hết hạn. Vui lòng đăng nhập lại.";
        }
        // Lấy thông báo và giá trị cũ từ phiên
        $data = [
            'messages' => session_get_once('messages'),  // Các thông báo (nếu có)
            'old' => $this->getSavedFormValues(),  // Lưu lại giá trị form cũ (như email)
            'errors' => session_get_once('errors'),  // Lỗi (nếu có)
            'message' => session_get_once($message)
        ];

        // Gửi trang đăng nhập với dữ liệu
        $this->sendPage('auth/login.twig', $data);  // Cập nhật tên file view
    }

    public function store()
    {

        // Lọc thông tin đăng nhập từ POST
        $user_credentials = $this->filterUserCredentials($_POST);

        // Khởi tạo biến lưu lỗi
        $errors = [];
        // Tìm người dùng dựa trên email
        $user = (new User(PDO()))->where('email', $user_credentials['email']);

        // Kiểm tra người dùng tồn tại hay không
        if ($user->id === -1) {
            // Người dùng không tồn tại
            $errors['email'] = 'Invalid email or password.';
        } else if (AUTHGUARD()->login($user, $user_credentials)) {
            // Đăng nhập thành công
            $_SESSION['role'] = $user->role;  // Lưu role vào session

            if ($user->role === 'ADMIN') {
                // Nếu là admin, chuyển đến trang admin
                redirect('/admin/dashboard');
            } else {
                // Nếu là user, chuyển đến trang home
                redirect('/home');
            }
        } else {
            // Sai mật khẩu
            $errors['password'] = 'Invalid email or password.';
        }

        // Đăng nhập không thành công: lưu giá trị trong form, trừ password
        $this->saveFormValues($_POST, ['password']);
        
        // Trả lại thông báo lỗi và chuyển hướng về trang login
        redirect('/login', ['errors' => $errors]);
    }

    public function destroy()
    {
        // Đăng xuất người dùng
        AUTHGUARD()->logout();
        redirect('/login');
    }

    protected function filterUserCredentials(array $data)
    {
        return [
            'email' => filter_var($data['email'], FILTER_VALIDATE_EMAIL),
            'password' => $data['password'] ?? null
        ];
    }
}
