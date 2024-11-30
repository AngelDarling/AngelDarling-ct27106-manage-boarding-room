<?php

namespace App\Controllers;

use App\Models\Review;
use App\Models\room;
use App\Models\User;

class ReviewController extends Controller
{
    public function index()
    {
        // Kiểm tra người dùng đã đăng nhập chưa
        if (!AUTHGUARD()->isUserLoggedIn()) {
            redirect('/login');
        }

        // Lấy thông tin người dùng
        $user = AUTHGUARD()->user();

        // Lấy các đánh giá của người dùng này
        $reviewModel = new Review(PDO());
        $reviews = $reviewModel->getReviewsByUserId($user->id);
        $reviews = $reviews ?: []; // Nếu không có review, trả về mảng rỗng

        // Lấy thông tin sản phẩm liên quan đến các đánh giá
        $roomModel = new room(PDO());
        foreach ($reviews as &$review) {
            $room = $roomModel->getroomById($review['room_id']);
            $review['room_name'] = $room['name'] ?? 'Sản phẩm không tồn tại';
        }

        // Lấy thông báo từ session nếu có
        $success_message = $_SESSION['success_message'] ?? null;

        // Xóa thông báo để không hiện lại khi refresh
        unset($_SESSION['success_message']);

        // Cấu trúc dữ liệu trả về
        $data = [
            'user' => $user,
            'reviews' => $reviews,
            'success_message' => $success_message,
        ];

        // Trả về view review với dữ liệu cần thiết
        $this->sendPage('user/sanpham.twig', $data);
    }

    // Phương thức để tạo đánh giá mới
    public function submitReview()
    {
        // Kiểm tra người dùng đã đăng nhập chưa
        if (!AUTHGUARD()->isUserLoggedIn()) {
            redirect('/login');
        }

        // Lấy thông tin từ form
        $room_id = $_POST['room_id'];
        $rating = (int)$_POST['rating'];
        $review_text = $_POST['review_text'];
        $user_id = $_POST['user_id'];

        // Kiểm tra tính hợp lệ của dữ liệu (ví dụ: rating phải từ 1 đến 5)
        if ($rating < 1 || $rating > 5) {
            // $_SESSION['error_message'] = 'Đánh giá phải từ 1 đến 5 sao!';
            redirect('/sanpham/' . $room_id);
        }

        // Gọi model để lưu đánh giá
        $reviewModel = new Review(PDO());
        $reviewModel->createReview($user_id, $room_id, $rating, $review_text);

        // Đặt thông báo thành công
        $_SESSION['success_message'] = 'Đánh giá của bạn đã được gửi thành công!';
        redirect('/sanpham/' . $room_id);
    }

    // Phương thức để xóa đánh giá
    public function deleteReview($id)
    {
        // Kiểm tra người dùng đã đăng nhập chưa
        if (!AUTHGUARD()->isUserLoggedIn()) {
            redirect('/login');
        }

        // Kiểm tra quyền của người dùng (chỉ xóa review của chính mình)
        $user = AUTHGUARD()->user();
        $reviewModel = new Review(PDO());
        $review = $reviewModel->getReviewById($id);

        if ($review['user_id'] != $user->id) {
            // $_SESSION['error_message'] = 'Bạn không có quyền xóa đánh giá này!';
            redirect('/sanpham/' . $review['room_id']);
        }

        // Gọi model để xóa đánh giá
        $reviewModel->deleteReview($id);

        // Đặt thông báo thành công
        // $_SESSION['success_message'] = 'Đánh giá đã được xóa thành công!';
        redirect('/sanpham/' . $review['room_id']);
    }
}
