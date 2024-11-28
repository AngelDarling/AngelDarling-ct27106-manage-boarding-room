<?php

namespace App\Controllers;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;
;

class Controller
{
    protected $view;
    // Hủy session khi off quá lâu
    protected $timeout_duration = 600; // 10 phút

    public function __construct()
    {
        $loader = new FilesystemLoader(ROOTER . 'app/views');
        $this->view = new Environment($loader);
        $this->view->addGlobal('csrf_token', $_SESSION['csrf_token']);
        // Đăng ký hàm tùy chỉnh cho Twig
        $this->view->addFunction(new TwigFunction('url_with_params', 'url_with_params'));



        // session_start(); // Bắt đầu session

        // // Kiểm tra nếu user đã đăng nhập
        // if (isset($_SESSION['user_id'])) {
        //     $this->checkSessionTimeout();
        // } else {
        //     // Nếu chưa đăng nhập, chuyển hướng tới trang đăng nhập
        //     redirect('/login');
        // }
    }

    public function sendPage($page, array $data = [])
    {
        $cart = $_SESSION['cart'] ?? [];
        // Chuyển giỏ hàng thành JSON để truyền vào Twig
        $cartJson = json_encode($cart);

        // Thêm thông tin giỏ hàng vào dữ liệu truyền vào view
        $data['cartJson'] = $cartJson;

        // Gửi dữ liệu vào Twig template
        echo $this->view->render($page, $data);
    }

    // Lưu các giá trị của form được cho trong $data vào $_SESSION 
    protected function saveFormValues(array $data, array $except = [])
    {
        $form = [];
        foreach ($data as $key => $value) {
            if (!in_array($key, $except, true)) {
                $form[$key] = $value;
            }
        }
        $_SESSION['form'] = $form;
    }

    protected function getSavedFormValues()
    {
        return session_get_once('form', []);
    }

    public function sendNotFound()
    {
        http_response_code(404);
        exit($this->view->render('errors/404.twig'));
    }

    
    // private function checkSessionTimeout()
    // {
    //     if (isset($_SESSION['LAST_ACTIVITY'])) {
    //         $elapsed_time = time() - $_SESSION['LAST_ACTIVITY'];
    //         if ($elapsed_time > $this->timeout_duration) {
    //             // Hủy session và chuyển hướng đến trang đăng nhập
    //             session_unset();
    //             session_destroy();
    //             redirect('/login?timeout=1');
    //         }
    //     }

    //     // Cập nhật lại thời gian hoạt động
    //     $_SESSION['LAST_ACTIVITY'] = time();
    // }

}
