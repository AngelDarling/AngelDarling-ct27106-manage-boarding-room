<?php

namespace App\Models;

use PDO;

class room
{
    protected $pdo;

    private PDO $db;
    public int $id = -1;
    public string $name;
    public string $image;
    public int $type_room;
    public float $price;
    public string $status;
    public string $short_description;
    public string $long_description;
    public string $created_at;
    public string $updated_at;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Lấy tất cả sản phẩm
    public function getAllroomsUser()
    {
        $stmt = $this->pdo->prepare("
            SELECT rooms.*, type_rooms.name AS type_room 
            FROM rooms 
            LEFT JOIN type_rooms ON rooms.type_room_id = type_rooms.id
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy những sản phẩm có thời gian tạo gần nhất
    public function getLatestrooms($limit) // Thay $limit bằng một số để lấy số sản phẩm mong muốn
    {
        $stmt = $this->pdo->prepare("
            SELECT rooms.*, type_rooms.name AS type_room 
            FROM rooms 
            LEFT JOIN type_rooms ON rooms.type_room_id = type_rooms.id 
            ORDER BY rooms.created_at DESC 
            LIMIT :limit
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy sản phẩm theo ID
    public function getroomByIdroom($id)
    {
        $stmt = $this->pdo->prepare("
            SELECT rooms.*, type_rooms.name AS type_room 
            FROM rooms 
            LEFT JOIN type_rooms ON rooms.type_room_id = type_rooms.id 
            WHERE rooms.id = :id
        ");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getroomByIdtype_room($id)
    {
        $stmt = $this->pdo->prepare("
            SELECT rooms.*, type_rooms.name AS type_room 
            FROM rooms 
            LEFT JOIN type_rooms ON rooms.type_room_id = type_rooms.id 
            WHERE rooms.type_room_id = :id
        ");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Lấy nhiều sản phẩm theo các ID
    public function getroomsByIds(array $ids)
    {
        if (empty($ids)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("SELECT * FROM rooms WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    // Admin
    public function fill(array $data): room
    {
        $this->id = $data['id'] ?? '';
        $this->name = $data['name'] ?? '';
        $this->image = $data['image'] ?? '';
        $this->price = $data['price'] ?? 0.0;
        $this->status = $data['status'] ?? 0;
        $this->type_room = $data['type_room_id'] ?? 0;
        $this->short_description = $data['short_description'] ?? '';
        $this->long_description = $data['long_description'] ?? '';
        return $this;
    }

    private function fillFromDbRow(array $row): room
    {
        [
            'id' => $this->id,
            'name' => $this->name,
            'image' => $this->image,
            'price' => $this->price,
            'status' => $this->status,
            'type_room' => $this->type_room,
            'short_description' => $this->short_description,
            'long_description' => $this->long_description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ] = $row;
        return $this;
    }

    public function getrooms(int $offset, int $limit, $sort = 'asc', $search = ''): array
    {
        $query = "SELECT * FROM rooms WHERE 1=1";

        if ($search) {
            $query .= " AND name LIKE :search";
        }

        $query .= " ORDER BY price " . ($sort === 'desc' ? 'DESC' : 'ASC');
        $query .= " LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($query);

        if ($search) {
            $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy tổng số bản ghi
    public function getTotalRecords($search = ''): int
    {
        $query = "SELECT COUNT(*) FROM rooms WHERE 1=1";

        if ($search) {
            $query .= " AND name LIKE :search";
        }

        $stmt = $this->pdo->prepare($query);

        if ($search) {
            $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        }

        $stmt->execute();
        return (int) $stmt->fetchColumn() + 1;
    }
    public function gettype_rooms(): array
    {
        $statement = $this->pdo->prepare('SELECT id, name FROM type_rooms');
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
    public function validateroom(array $data): array
    {
        $errors = [];
        if (empty($data['name']) || strlen($data['name']) < 3) {
            $errors['name'] = 'Tên sản phẩm phải ít nhất 3 ký tự.';
        }
        if (empty($data['price']) || !is_numeric($data['price']) || $data['price'] <= 0) {
            $errors['price'] = 'Giá sản phẩm không hợp lệ.';
        }
        if (empty($data['status'])) {
            $errors['status'] = 'Trạng thái không được để trống.';
        }
        if (empty($data['short_description'])) {
            $errors['short_description'] = 'Phần mô tả ngắn không được để trống.';
        }
        if (empty($data['long_description'])) {
            $errors['long_description'] = 'Phần mô tả dài không được để trống.';
        }
        return $errors;
    }

    public function addroom(array $data, array $file): bool
    {
        $errors = $this->validateroom($data);
        if (!empty($errors)) {
            return false;
        }
        $image = $this->uploadImage($file);
        $statement = $this->pdo->prepare(
            'INSERT INTO rooms (name, type_room_id, image, price, status, short_description, long_description, created_at, updated_at)
            VALUES (:name, :type_room, :image, :price, :status, :short_description, :long_description, NOW(), NOW())'
        );
        return $statement->execute([
            'name' => $data['name'],
            'type_room' => $data['type_room'],
            'image' => $image,
            'price' => $data['price'],
            'status' => $data['status'],
            'short_description' => $data['short_description'],
            'long_description' => $data['long_description']
        ]);
    }

    public function uploadImage(array $file): ?string
    {
        if ($file['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../public/assets/uploads/rooms/';
            $filename = uniqid() . '-' . basename($file['name']);
            $uploadFile = $uploadDir . $filename;

            if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
                return $filename;
            }
        }
        return null;
    }

    public function getAllrooms(): array
    {
        $statement = $this->pdo->prepare('SELECT id, name FROM rooms');
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getroomById(int $id): ?room
    {
        $statement = $this->pdo->prepare('SELECT * FROM rooms WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return $this->fill($row);
        }
        return null;
    }

    public function gettype_roomNameById(int $type_roomId): ?string
    {
        $statement = $this->pdo->prepare('SELECT name FROM type_rooms WHERE id = :id');
        $statement->execute(['id' => $type_roomId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row ? $row['name'] : null;
    }

    public function updateroom(int $roomId, array $data): bool
    {
        $statement = $this->pdo->prepare('UPDATE rooms SET name = :name, type_room_id = :type_room, price = :price, status = :status, short_description = :short_description, long_description = :long_description WHERE id = :id');
        return $statement->execute([
            'name' => $data['name'],
            'type_room' => $data['type_room'],
            'price' => $data['price'],
            'status' => $data['status'],
            'short_description' => $data['short_description'],
            'long_description' => $data['long_description'],
            'id' => $roomId
        ]);
    }

    public function updateImage(int $roomId, string $newImage): bool
    {
        // Get current image
        $room = $this->getroomById($roomId);
        if ($room && $room->image) {
            $oldImagePath = __DIR__ . '/../../public/assets/uploads/rooms/' . $room->image;
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath); // Delete old image
            }
        }

        $statement = $this->pdo->prepare('UPDATE rooms SET image = :image WHERE id = :id');
        return $statement->execute(['image' => $newImage, 'id' => $roomId]);
    }

    public function deleteroom(int $roomId): bool
    {
        // Get current room details to delete the image
        $room = $this->getroomById($roomId);
        if ($room && $room->image) {
            $imagePath = __DIR__ . '/../../public/assets/uploads/rooms/' . $room->image;
            if (file_exists($imagePath)) {
                unlink($imagePath); // Delete old image
            }
        }

        $statement = $this->pdo->prepare('DELETE FROM rooms WHERE id = :id');
        return $statement->execute(['id' => $roomId]);
    }

    public function getroomNameById(int $id): ?string
    {
        $statement = $this->pdo->prepare('SELECT name FROM rooms WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row ? $row['name'] : null;
    }
    
    public function getTotalRooms(): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM rooms');
        $statement->execute();
        return (int) $statement->fetchColumn();
    }

    public function getTotalRooms_Controng(): int
{
    // Sửa lỗi cú pháp trong SQL: phải dùng dấu nháy đơn bên ngoài chuỗi
    $statement = $this->pdo->prepare("SELECT COUNT(*) FROM rooms WHERE status = 'Còn trống'");
    $statement->execute();
    
    // Trả về kết quả dưới dạng số nguyên
    return (int) $statement->fetchColumn();
}


    public function getTotalRooms_Dadat(): int
    {
        $statement = $this->pdo->prepare("SELECT COUNT(*) FROM rooms WHERE status = 'Đã đặt'");
        $statement->execute();
        return (int) $statement->fetchColumn();
    }

    public function updateRoomStatus($roomId, $status)
    {
        $sql = "UPDATE rooms SET status = :status WHERE id = :roomId";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':status' => $status,
            ':roomId' => $roomId,
        ]);
        return $stmt->rowCount() > 0;
    }
}