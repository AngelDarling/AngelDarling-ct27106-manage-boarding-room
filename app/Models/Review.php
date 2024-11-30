<?php

namespace App\Models;

use PDO;

class Review
{
    protected $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Phương thức lưu đánh giá
    public function createReview(int $userId, int $roomId, int $rating, string $reviewText)
    {
        $stmt = $this->pdo->prepare("INSERT INTO reviews (id_user, id_room, rating, review_text) 
                                 VALUES (:user_id, :room_id, :rating, :review_text)");

        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':room_id', $roomId);
        $stmt->bindParam(':rating', $rating);
        $stmt->bindParam(':review_text', $reviewText);

        if ($stmt->execute()) {
            return $this->pdo->lastInsertId();  // Trả về ID của review vừa được thêm
        }

        return false;
    }

    // Lấy thông tin đánh giá theo ID
    public function getReviewById(int $reviewId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM reviews WHERE id = :id");
        $stmt->bindParam(':id', $reviewId, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return $stmt->fetch(PDO::FETCH_ASSOC);  // Trả về thông tin đánh giá dưới dạng mảng
        }

        return false;
    }

    // Lấy tất cả đánh giá cho một sản phẩm
    public function getReviewsByroomId(int $roomId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM reviews WHERE room_id = :room_id");
        $stmt->bindParam(':room_id', $roomId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);  // Trả về danh sách đánh giá
    }

    // Lấy tất cả đánh giá của người dùng
    public function getReviewsByUserId(int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM reviews WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);  // Trả về danh sách đánh giá của người dùng
    }

    // Cập nhật thông tin đánh giá
    public function updateReview(int $id, int $rating, string $reviewText): void
    {
        $stmt = $this->pdo->prepare("UPDATE reviews SET rating = :rating, review_text = :review_text WHERE id = :id");

        $stmt->execute([
            'id' => $id,
            'rating' => $rating,
            'review_text' => $reviewText
        ]);
    }

    // Xóa đánh giá
    public function deleteReview(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM reviews WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    // Lọc dữ liệu đánh giá (chỉ lấy các trường cần thiết)
    public function filterReviewData(array $data): array
    {
        return [
            'user_id' => (int)$data['user_id'],
            'room_id' => (int)$data['room_id'],
            'rating' => (int)$data['rating'],
            'review_text' => trim($data['review_text']),
        ];
    }

    // Kiểm tra tính hợp lệ của dữ liệu đánh giá
    public function validateReview(array $data): array
    {
        $errors = [];

        // Kiểm tra rating
        if (empty($data['rating']) || $data['rating'] < 1 || $data['rating'] > 5) {
            $errors['rating'] = 'Đánh giá phải trong khoảng từ 1 đến 5.';
        }

        // Kiểm tra review text
        if (empty($data['review_text']) || strlen($data['review_text']) < 5) {
            $errors['review_text'] = 'Nội dung đánh giá phải ít nhất 5 ký tự.';
        }

        return $errors;
    }

    // Kiểm tra người dùng đã đánh giá sản phẩm chưa
    public function hasUserReviewedroom(int $userId, int $roomId): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = :user_id AND room_id = :room_id");
        $stmt->execute(['user_id' => $userId, 'room_id' => $roomId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    // Lấy danh sách các sản phẩm đã được đánh giá
    public function getroomsReviewedByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT DISTINCT room_id FROM reviews WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);  // Trả về danh sách sản phẩm đã được đánh giá
    }
}
