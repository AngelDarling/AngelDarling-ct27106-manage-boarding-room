<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../bootstrap.php';

define('APPNAME', 'Boarding Room');

session_start();

//CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Tạo một token ngẫu nhiên
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header('HTTP/1.0 403 Forbidden');
        echo "Invalid CSRF token!";
        exit();
    }
    unset($_SESSION['csrf_token']);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

}
//CSRF



// Đặt thời gian timeout là 5 phút (300 giây)
define('SESSION_TIMEOUT', 20);

// Kiểm tra nếu người dùng đã đăng nhập
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    // Lấy thời gian hiện tại
    $current_time = time();

    // Kiểm tra thời gian hoạt động cuối cùng
    if (isset($_SESSION['last_activity']) && ($current_time - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        // Nếu thời gian không hoạt động vượt quá 5 phút, hủy session
        session_unset();
        session_destroy();
        // Chuyển hướng về trang đăng nhập
        redirect('/login');
    }

    // Cập nhật lại thời gian hoạt động cuối cùng
    $_SESSION['last_activity'] = $current_time;
}


require_once __DIR__ . '/../vendor/autoload.php'; // Autoload các thư viện cần thiết

// Định nghĩa thư mục chứa các template
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../app/views');
$twig = new \Twig\Environment($loader, [
    'cache' => __DIR__ . '/../cache',  // Thêm chỗ lưu cache nếu cần thiết
]);

$dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
    // Auth routes
    $r->addRoute('POST', '/logout', ['\App\Controllers\auth\LoginController', 'destroy']);
    $r->addRoute('GET', '/register', ['\App\Controllers\auth\RegisterController', 'create']);
    $r->addRoute('POST', '/register', ['\App\Controllers\auth\RegisterController', 'store']);
    $r->addRoute('GET', '/login', ['\App\Controllers\auth\LoginController', 'create']);
    $r->addRoute('POST', '/login', ['\App\Controllers\auth\LoginController', 'store']);

    // Home routes (User)
    $r->addRoute('GET', '/', ['\App\Controllers\HomeController', 'index']);
    $r->addRoute('GET', '/home', ['\App\Controllers\HomeController', 'index']);

    // Các route khác...
    $r->addRoute('GET', '/type_room', ['\App\Controllers\type_roomController', 'index']);
    $r->addRoute('GET', '/new_room', ['\App\Controllers\new_roomController', 'index']);
    $r->addRoute('GET', '/room', ['\App\Controllers\roomController', 'index']);
    $r->addRoute('GET', '/room/{id:\d+}', ['\App\Controllers\roomController', 'show']); // Sử dụng số cho id
    $r->addRoute('GET', '/service', ['\App\Controllers\ServiceController', 'index']);
    $r->addRoute('GET', '/sanpham/{id:\d+}', ['\App\Controllers\SanphamController', 'show']); // Sử dụng số cho id
    $r->addRoute('GET', '/profile', ['\App\Controllers\profileController', 'index']);

    // Cart routes
    $r->addRoute('GET', '/giohang', ['\App\Controllers\GiohangController', 'index']);
    $r->addRoute('POST', '/giohang/add', ['\App\Controllers\GiohangController', 'addToCart']);
    // $r->addRoute('POST', '/giohang/update', ['\App\Controllers\GiohangController', 'updateCart']);
    $r->addRoute('POST', '/giohang/remove', ['\App\Controllers\GiohangController', 'removeFromCart']);

    // Thanh toán routes
    $r->addRoute('GET', '/thanhtoan', ['\App\Controllers\ThanhtoanController', 'index']);
    $r->addRoute('POST', '/thanhtoan', ['\App\Controllers\ThanhtoanController', 'confirm']);
    $r->addRoute('GET', '/thanhtoan_success', ['\App\Controllers\Thanhtoan_successController', 'success']);

    $r->addRoute('POST', '/maintenance-request', ['\App\Controllers\ProfileController', 'submitMaintenanceRequest']);
    
    // Đánh giá routes
    $r->addRoute('GET', '/reviews', ['\App\Controllers\ReviewController', 'index']); // Trang danh sách đánh giá của người dùng
    $r->addRoute('POST', '/review/store', ['\App\Controllers\ReviewController', 'submitReview']); // Xử lý tạo đánh giá mới
    $r->addRoute('POST', '/review/{id}/delete', ['\App\Controllers\ReviewController', 'deleteReview']); // Xử lý xóa đánh giá

    $r->addRoute('GET', '/profile/{id:\d+}', ['\App\Controllers\profileController', 'viewNotifications']);



    // Admin routes
    $r->addRoute('GET', '/admin/dashboard', ['\App\Controllers\Admin\DashboardController', 'getIndex']);

    $r->addRoute('GET', '/admin/user', ['\App\Controllers\Admin\UserController', 'getUsers']);
    $r->addRoute('GET', '/admin/user/detail', ['\App\Controllers\Admin\UserController', 'detailUser']);
    $r->addRoute('GET', '/admin/user/update', ['\App\Controllers\Admin\UserController', 'getUpdateUser']);

    $r->addRoute('GET', '/admin/room', ['\App\Controllers\Admin\roomController', 'getroom']);
    $r->addRoute('GET', '/admin/room/detail', ['\App\Controllers\Admin\roomController', 'detailroom']);
    $r->addRoute('GET', '/admin/room/update', ['\App\Controllers\Admin\roomController', 'getUpdateroom']);

    $r->addRoute('GET', '/admin/contract', ['\App\Controllers\Admin\ContractController', 'getContract']);
    $r->addRoute('GET', '/admin/contract/detail', ['\App\Controllers\Admin\ContractController', 'viewInvoice']);
    $r->addRoute('GET', '/admin/type_room', ['\App\Controllers\Admin\type_roomController', 'gettype_room']);
    $r->addRoute('GET', '/admin/type_room/update', ['\App\Controllers\Admin\type_roomController', 'getUpdatetype_room']);
    $r->addRoute('POST', '/admin/user/add', ['\App\Controllers\Admin\UserController', 'addUsers']);
    $r->addRoute('POST', '/admin/user/update', ['\App\Controllers\Admin\UserController', 'postUpdateUser']);
    $r->addRoute('POST', '/admin/user/delete', ['\App\Controllers\Admin\UserController', 'deleteUser']);


    $r->addRoute('POST', '/admin/room/add', ['\App\Controllers\Admin\roomController', 'addroom']);
    $r->addRoute('POST', '/admin/room/update-image', ['\App\Controllers\Admin\roomController', 'postUpdateImage']);
    $r->addRoute('POST', '/admin/room/update', ['\App\Controllers\Admin\roomController', 'postUpdateroom']);
    $r->addRoute('POST', '/admin/room/delete', ['\App\Controllers\Admin\roomController', 'deleteroom']);
    $r->addRoute('POST', '/admin/contract/update-status', ['\App\Controllers\Admin\ContractController', 'updateContractStatus']);
    $r->addRoute('POST', '/admin/contract/delete', ['\App\Controllers\Admin\ContractController', 'deleteContract']);
    $r->addRoute('POST', '/admin/type_room/add', ['\App\Controllers\Admin\type_roomController', 'addtype_room']);
    $r->addRoute('POST', '/admin/type_room/update', ['\App\Controllers\Admin\type_roomController', 'update']);
    $r->addRoute('POST', '/admin/type_room/delete', ['\App\Controllers\Admin\type_roomController', 'deletetype_room']);

    $r->addRoute('GET', '/admin/service', ['\App\Controllers\Admin\serviceController', 'getservice']);
    $r->addRoute('GET', '/admin/service/update', ['\App\Controllers\Admin\serviceController', 'getUpdateservice']);
    $r->addRoute('POST', '/admin/service/add', ['\App\Controllers\Admin\serviceController', 'addservice']);
    $r->addRoute('POST', '/admin/service/update', ['\App\Controllers\Admin\serviceController', 'update']);
    $r->addRoute('POST', '/admin/service/delete', ['\App\Controllers\Admin\serviceController', 'deleteservice']);


    $r->addRoute('GET', '/admin/notification', ['\App\Controllers\Admin\notificationController', 'getnotifications']);
    $r->addRoute('GET', '/admin/notification/update', ['\App\Controllers\Admin\notificationController', 'getUpdatenotification']);
    $r->addRoute('POST', '/admin/notification/add', ['\App\Controllers\Admin\notificationController', 'addnotification']);
    $r->addRoute('POST', '/admin/notification/update', ['\App\Controllers\Admin\notificationController', 'update']);
    $r->addRoute('POST', '/admin/notification/delete', ['\App\Controllers\Admin\notificationController', 'deletenotification']);

    
    $r->addRoute('GET', '/admin/tenant', ['\App\Controllers\Admin\TenantController', 'getTenant']);
    $r->addRoute('GET', '/admin/tenant/update', ['\App\Controllers\Admin\tenantController', 'getUpdatetenant']);
    $r->addRoute('POST', '/admin/tenant/add', ['\App\Controllers\Admin\tenantController', 'addtenant']);
    $r->addRoute('POST', '/admin/tenant/update', ['\App\Controllers\Admin\tenantController', 'update']);
    $r->addRoute('POST', '/admin/tenant/delete', ['\App\Controllers\Admin\tenantController', 'deletetenant']);

    $r->addRoute('POST', '/admin/user/update-avatar', ['\App\Controllers\Admin\UserController', 'updateAvatar']);
    $r->addRoute('POST', '/admin/user/update/{id:\d+}', ['\App\Controllers\Admin\UserController', 'updateUser']);
    $r->addRoute('POST', '/admin/user/delete/{id:\d+}', ['\App\Controllers\Admin\UserController', 'deleteUser']);

    $r->addRoute('GET', '/admin/maintenance_request', ['\App\Controllers\Admin\maintenance_requestController', 'getmaintenance_request']);
    $r->addRoute('POST', '/admin/maintenance_request/delete', ['\App\Controllers\Admin\maintenance_requestController', 'deletemaintenance_request']);
    $r->addRoute('POST', '/admin/maintenance_request/update-status', ['\App\Controllers\Admin\maintenance_requestController', 'updatemaintenance_requestStatus']);


});

$adminRoutes = [
    '/admin/dashboard',

    // Routes liên quan đến user management
    '/admin/user',
    '/admin/user/detail',
    '/admin/user/update',
    '/admin/user/add',
    '/admin/user/delete',
    '/admin/user/update-avatar',
    '/admin/user/update/{id:\d+}',
    '/admin/user/delete/{id:\d+}',

    // Routes liên quan đến room management
    '/admin/room',
    '/admin/room/detail',
    '/admin/room/update',
    '/admin/room/add',
    '/admin/room/update-image',
    '/admin/room/delete',

    // Routes liên quan đến Contract management
    '/admin/contract',
    '/admin/contract/detail',
    '/admin/contract/update-status',
    '/admin/contract/delete',

    // Routes liên quan đến type_room management
    '/admin/type_room',
    '/admin/type_room/update',
    '/admin/type_room/add',
    '/admin/type_room/delete'
];

// Lấy URL và phương thức HTTP hiện tại
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Xử lý URI (loại bỏ query string)
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

// Lấy query string
$queryParams = [];
if (isset($_SERVER['QUERY_STRING'])) {
    parse_str($_SERVER['QUERY_STRING'], $queryParams); // Phân tích query string thành mảng
}

$userRole = $_SESSION['role'] ?? 'USER';
// Định tuyến (routing) URL tới hàm hoặc lớp xử lý
$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        // Xử lý khi không tìm thấy trang
        echo $twig->render('errors/404.twig', ['role' => $userRole, 'APPNAME' => APPNAME]);
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        // Phương thức không hợp lệ (ví dụ: POST cho một route chỉ hỗ trợ GET)
        $allowedMethods = $routeInfo[1];
        echo '405 Method Not Allowed';
        break;
    case FastRoute\Dispatcher::FOUND:
        // Gọi hàm hoặc phương thức xử lý
        $handler = $routeInfo[1]; // Lấy controller và phương thức
        $vars = $routeInfo[2]; // Các tham số từ URL


        // Kiểm tra middleware cho route admin
        if (in_array($uri, $adminRoutes)) {
            $adminMiddleware = new \App\Middleware\AdminMiddleware();
            $adminMiddleware->handle(); // Nếu không phải admin, sẽ chuyển hướng ra khỏi admin
        }


        // Kiểm tra và gọi hàm hoặc phương thức xử lý
        list($class, $method) = $handler;
        (new $class)->$method($vars, $queryParams);
        break;
}