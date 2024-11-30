<?php
namespace App\Controllers\Admin;

use App\Models\Notification;
use App\Utils\Paginator;
use PDO;
use App\Models\Tenant;

class notificationController extends Controller
{
    // Lấy danh sách thông báo kèm phân trang
    public function getNotifications()
    {
        $notificationModel = new Notification(PDO());
        $recordsPerPage = 10;

        $totalRecords = $notificationModel->getNotificationsCount();
        $totalPages = ceil($totalRecords / $recordsPerPage);
        $currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
        } elseif ($currentPage < 1) {
            $currentPage = 1;
        }
        $tenantModel = new Tenant(PDO());
        $tenants = $tenantModel->getAllTenants();
        $paginator = new Paginator($recordsPerPage, $totalRecords, $currentPage);

        $notifications = $notificationModel->getPaginatedNotifications($recordsPerPage, $paginator->recordOffset);
        $user = AUTHGUARD()->user();
        $this->sendPage('/admin/notification/notification', [
            'notifications' => $notifications,
            'paginator' => $paginator,
            'status' => session_get_once("status"),
            'isOpenModal' => session_get_once("isOpenModal"),
            'formError' => session_get_once("formError"),
            'errors' => session_get_once("errors"),
            'user' => $user,
            'tenant' => $tenants
        ]);
    }

    // Hiển thị thông tin chi tiết của một thông báo để cập nhật
    public function getUpdateNotification()
    {
        $notificationId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($notificationId > 0) {
            $notificationModel = new Notification(PDO());
            $notification = $notificationModel->getNotificationById($notificationId);
            if ($notification) {
                $tenantModel = new Tenant(PDO());
                $tenants = $tenantModel->getAllTenants();
                $this->sendPage('/admin/notification/update', [
                    'notification' => $notification,
                    'tenants' => $tenants,
                    'status' => session_get_once("status")
                ]);
            } else {
                echo "Error: Could not retrieve notification.";
            }
        } else {
            redirect('/admin/notification', ['status' => 'Invalid ID']);
        }
    }

    // Thêm thông báo mới
    public function addNotification()
    {
        $notificationModel = new Notification(PDO());
        $data = $this->filterNotificationData($_POST); // Lấy dữ liệu từ form

        // Kiểm tra lỗi
        $errors = $notificationModel->validateNotification($data);
        if (empty($errors)) {
            $notificationModel->addNotification($data); // Thêm thông báo vào DB
            redirect('/admin/notification', [
                'status' => 'Notification added successfully',
            ]);
        } else {
            redirect('/admin/notification', [
                'isOpenModal' => true, 
                'formError' => $data, 
                'errors' => $errors, 
                'status' => 'Check your form please'
            ]);
        }
    }

    // Cập nhật thông báo
    public function update()
    {
        $notificationId = (int) $_POST['id'];
        $notificationModel = new Notification(PDO());
        $data = $this->filterNotificationData($_POST);

        // Kiểm tra tính hợp lệ của dữ liệu
        $errors = $notificationModel->validateNotification($data);
        if (!empty($errors)) {
            redirect('/admin/notification/update?id=' . $notificationId, [
                'status' => 'Check your form please', 
                'errors' => $errors
            ]);
        }

        // Cập nhật thông báo
        $notificationModel->updateNotification($notificationId, $data);
        redirect('/admin/notification/update?id=' . $notificationId, ['status' => 'Update success']);
    }

    // Lọc dữ liệu thông báo
    public function filterNotificationData(array $data): array
    {
        return [
            'id_tenants' => (int) $data['id_tenants'], // Thêm trường id_tenants
            'content' => trim($data['content']),
            'type' => trim($data['type']), // Thêm trường type
        ];
    }

    // Xóa thông báo
    public function deleteNotification()
    {
        $notificationId = (int) $_POST['notification_id'];
        $notificationModel = new Notification(PDO());
        $notificationModel->deleteNotification($notificationId); // Xóa thông báo
        redirect('/admin/notification', ['status' => 'Notification deleted successfully']);
    }
}
