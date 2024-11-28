<?php

namespace App\Controllers\Admin;
use App\Models\contract;
use App\Models\User;
use App\Utils\Paginator;
// use App\Models\Contact;
class UserController extends Controller
{
    public function __construct()
    {
        // if (!AUTHGUARD()->isUserLoggedIn()) {
        //   redirect('/login');
        // }

        parent::__construct();
    }
    public function getUsers()
    {
        $recordsPerPage = 10;


        $filter = isset($_GET['filterBy']) ? $_GET['filterBy'] : '';
        $filter = $filter == "anything" ? '' : $filter;
        $search = isset($_GET['search']) ? $_GET['search'] : '';

        $userModel = new User(PDO());
        $totalRecords = $userModel->getTotalRecords($filter, $search);

        $totalPages = ceil($totalRecords / $recordsPerPage);
        $currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
        } elseif ($currentPage < 1) {
            $currentPage = 1;
        }

        $paginator = new Paginator($recordsPerPage, $totalRecords, $currentPage);

        // Lấy dữ liệu người dùng theo phân trang và bộ lọc tìm kiếm, vai trò
        $users = $userModel->getUsers($paginator->recordOffset, $recordsPerPage, $filter, $search);
        $user = AUTHGUARD()->user();
        $this->sendPage('/admin/user/user', [
            'users' => $users,
            'paginator' => $paginator,
            'filter' => $filter,
            'search' => $search,
            'status' => session_get_once("status"),
            'isOpenModal' => session_get_once("isOpenModal"),
            'formError' => session_get_once("formError"),
            'errors' => session_get_once("errors"),
            'user' => $user,
        ]);
    }

    protected function filterUserData(array $data)
    {
        return [
            'email' => $data['email'] ?? '',
            'password' => $data['password'] ?? '',
            'name' => $data['name'] ?? '',
            'role' => $data['role'] ?? '',
            'gender' => $data['gender'] ?? '',
            'avatar' => $data['avatar'] ?? null,
            'confirm_password' => $data['confirm_password'] ?? ''
        ];
    }
    //add user(POST)
    public function addUsers()
    {
        $data = $this->filterUserData($_POST);
        $newUser = new User(PDO());
        $errors = $newUser->validateUser($data);
        if (empty($errors)) {
            $newUser->addUser($data, $_FILES['avatar']);
            redirect('/admin/user', ['status' => 'Update user success']);
        } else {
            $data['password'] = '';
            redirect('/admin/user', ['isOpenModal' => true, 'formError' => $data, 'errors' => $errors, 'status' => 'Check your form please']);
        }
    }
    public function detailUser()
    {
        $userId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($userId > 0) {
            $userModel = new User(PDO());
            $listUser = $userModel->getAllUsers();
            $user = $userModel->getUserById($userId);
            //
            $contractModel = new contract(PDO());
            // $contracts = $contractModel->getcontractsByUserId($userId);
            // $totalAmount = $contractModel->getTotalAmountByUserId($userId);
            if ($user) {
                $this->sendPage('/admin/user/detail', [
                    'user' => $user,
                    'listUser' => $listUser,
                    // 'contracts' => $contracts,
                    // 'total_amount' => $totalAmount
                ]);
            } else {
                redirect('/admin/user', ['status' => 'Id invalid']);
            }
        } else {
            redirect('/admin/user', ['status' => 'Id invalid']);
        }
    }
    public function getUpdateUser()
    {
        $userId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($userId > 0) {
            $userModel = new User(PDO());
            $listUser = $userModel->getAllUsers();
            $user = $userModel->getUserById($userId);
            if ($user) {
                $this->sendPage(
                    '/admin/user/update',
                    [
                        'user' => $user,
                        'listUser' => $listUser,
                        'status' => session_get_once("status")
                    ]
                );
            } else {
                redirect('/admin/user', ['status' => 'Id invalid']);
            }
        } else {
            redirect('/admin/user', ['status' => 'Id invalid']);
        }
    }
    public function updateAvatar()
    {
        $userId = $_POST['user_id'];
        $userModel = new User(PDO());

        // Upload avatar mới
        $newAvatar = $userModel->uploadAvatar($_FILES['new_avatar']);

        if ($newAvatar) {
            $userModel->updateAvatar($userId, $newAvatar);
            redirect('/admin/user/update?id=' . $userId, ['status' => 'Avatar updated successfully']);
        } else {
            redirect('/admin/user', ['status' => 'Failed to upload avatar']);
        }
    }
    public function postUpdateUser()
    {
        $userId = $_POST['user_id'];
        $userModel = new User(PDO());
        $data = [
            'name' => $_POST['name'],
            'email' => $_POST['email'],
            'role' => $_POST['role'],
            'gender' => $_POST['gender']
        ];
        $errors = $userModel->validateUpdateUser($data);
        if ($userModel->isEmailRegistered($data['email']) && $userModel->getEmailById($userId) != $data['email']) {
            redirect('/admin/user/update?id=' . $userId, ['status' => 'Email đã được đăng kí']);
        }
        if (!empty($errors)) {
            redirect('/admin/user/update?id=' . $userId, ['status' => 'Check your update']);
            exit();
        }
        $updateStatus = $userModel->updateUser($userId, $data);
        if ($updateStatus) {
            redirect('/admin/user/update?id=' . $userId, ['status' => 'User updated successfully']);
        } else {
            redirect('/admin/user/update?id=' . $userId, ['status' => 'Failed to update user']);
        }
    }
    public function deleteUser()
    {
        $userId = $_POST['user_id'];
        $userModel = new User(PDO());
        if ($userId == $_SESSION['user_id']) {
            redirect('/admin/user', ['status' => 'Không thể xóa User đang đăng nhập']);
        }

        if ($userModel->deleteUser($userId)) {
            redirect('/admin/user', ['status' => 'User deleted successfully']);
        } else {
            redirect('/admin/user', ['status' => 'Failed to delete user']);
        }
    }
}