<?php

namespace App\Controllers\Auth;

use App\Models\User;
use App\Controllers\Controller;

class RegisterController extends Controller
{
  public function __construct()
  {
    // Kiểm tra nếu người dùng đã đăng nhập, chuyển hướng đến trang chính
    if (AUTHGUARD()->isUserLoggedIn()) {
      redirect('/home');
    }

    parent::__construct();
  }

  public function create()
  {
    // Lấy giá trị đã lưu từ form và các lỗi (nếu có)
    $data = [
      'old' => $this->getSavedFormValues(),
      'errors' => $_SESSION['errors'] ?? null,
    ];

    // Xóa lỗi khỏi session sau khi đã lấy để tránh hiển thị lại lần sau
    unset($_SESSION['errors']);

    // Gửi trang đăng ký với template Twig
    echo $this->view->render('auth/register.twig', $data);
  }

  public function store()
  {
    $data = $this->filterUserData($_POST);
    // $this->validateCsrfToken($data['csrf_token']);

    // Lưu giá trị từ form, ngoại trừ mật khẩu
    $this->saveFormValues($_POST, ['password', 'password_confirmation']);

    // Lọc dữ liệu người dùng từ POST
    $newUser = new User(PDO());

    // Xác thực dữ liệu người dùng
    $model_errors = $newUser->validate($data);
    
    if (empty($model_errors)) {
      // Nếu không có lỗi, điền thông tin và lưu người dùng mới
      $avatar = $_FILES['avatar'];
      $avatarName = $newUser->uploadAvatar($avatar);
      $data['avatar'] = $avatarName;
      $newUser->fillUser($data)->save();

      // Gửi thông báo thành công và chuyển hướng đến trang đăng nhập
      $_SESSION['messages'] = ['success' => 'Tài khoản đã được tạo thành công.'];
      redirect('/login');
    }

    // Nếu có lỗi, lưu thông báo lỗi vào session và chuyển hướng về trang đăng ký
    $_SESSION['errors'] = $model_errors;
    redirect('/register');
  }

  protected function filterUserData(array $data)
  {
    return [
      'name' => $data['name'] ?? null,
      'email' => filter_var($data['email'], FILTER_VALIDATE_EMAIL),
      'password' => $data['password'] ?? null,
      'password_confirmation' => $data['password_confirmation'] ?? null,
      'avatar' => $data['avatar'] ?? null, // Avatar
      'gender' => $data['gender'] ?? null // Giới tính
    ];
  }
}
