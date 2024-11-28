<?php
namespace App\Models;

use PDO;

class type_room
{
    private $db;

    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
    }
    public function validatetype_room(array $data): array
    {
        $errors = [];

        if (empty($data['name']) || strlen($data['name']) < 2) {
            $errors['name'] = 'Tên thiết bị phải ít nhất 2 ký tự.';
        } elseif ($this->istype_roomNameTaken($data['name'])) {
            $errors['name'] = 'Tên thiết bị đã được sử dụng.';
        }

        return $errors;
    }
    public function istype_roomNameTaken(string $name): bool
    {
        $statement = $this->db->prepare('SELECT COUNT(*) FROM type_rooms WHERE name = :name');
        $statement->execute(['name' => $name]);
        $count = (int) $statement->fetchColumn();
        return $count > 0;
    }
    public function gettype_roomNameById(int $id): ?string
    {
        $statement = $this->db->prepare('SELECT name FROM type_rooms WHERE id = :id');
        $statement->execute(['id' => $id]);
        $type_roomName = $statement->fetchColumn();
        return $type_roomName ?: null;
    }

    public function filtertype_roomData(array $data): array
    {
        return [
            'name' => trim($data['name']),
        ];
    }
    public function getPaginatedtype_rooms(int $limit, int $offset): array
    {
        $statement = $this->db->prepare('SELECT * FROM type_rooms LIMIT :limit OFFSET :offset');
        $statement->bindParam(':limit', $limit, PDO::PARAM_INT);
        $statement->bindParam(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
    public function gettype_roomsCount(): int
    {
        $statement = $this->db->query('SELECT COUNT(*) FROM type_rooms');
        return (int) $statement->fetchColumn() + 1;
    }

    public function getAlltype_rooms(): array
    {
        $statement = $this->db->prepare('SELECT * FROM type_rooms');
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function gettype_roomById(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM type_rooms WHERE id = :id');
        $statement->execute(['id' => $id]);
        $type_room = $statement->fetch(PDO::FETCH_ASSOC);
        return $type_room ?: null;
    }

    public function addtype_room(array $data, array $image): void
    {
        $imagePath = $this->uploadImage($image);
        $statement = $this->db->prepare('INSERT INTO type_rooms (name, image) VALUES (:name, :image)');
        $statement->execute(['name' => $data['name'], 'image' => $imagePath]);
    }


    public function updatetype_room(int $id, string $name, array $image): void
    {
        $type_room = $this->gettype_roomById($id);
        $imagePath = $type_room['image'];
        if ($image['error'] === UPLOAD_ERR_OK) {
            $imagePath = $this->uploadImage($image);
            if ($type_room['image'] && file_exists(__DIR__ . '/../../public/assets/uploads/type_rooms/' . $type_room['image'])) {
                unlink(__DIR__ . '/../../public/uploads/type_rooms/' . $type_room['image']); // Delete old image
            }
        }

        $statement = $this->db->prepare('UPDATE type_rooms SET name = :name, image = :image WHERE id = :id');
        $statement->execute(['name' => $name, 'image' => $imagePath, 'id' => $id]);
    }


    private function uploadImage(array $file): ?string
    {
        if ($file['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../public/assets/uploads/type_rooms/';
            $filename = uniqid() . '-' . basename($file['name']);
            $uploadFile = $uploadDir . $filename;

            if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
                return $filename;
            }
        }
        return null;
    }
    public function updatetype_roomImage(int $type_roomId, string $newImage): bool
    {
        // Get current image
        $type_room = $this->gettype_roomById($type_roomId);
        if ($type_room && $type_room['image']) {
            $oldImagePath = __DIR__ . '/../../public/assets/uploads/type_rooms/' . $type_room['image'];
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath); // Delete old image
            }
        }

        $statement = $this->db->prepare('UPDATE type_rooms SET image = :image WHERE id = :id');
        return $statement->execute(['image' => $newImage, 'id' => $type_roomId]);
    }
    public function deletetype_room(int $id): void
    {
        // Get current type_room details to delete the image
        $type_room = $this->gettype_roomById($id);
        if ($type_room && $type_room['image']) {
            $imagePath = __DIR__ . '/../../public/assets/uploads/type_rooms/' . $type_room['image'];
            if (file_exists($imagePath)) {
                unlink($imagePath); // Delete the old image
            }
        }

        $statement = $this->db->prepare('DELETE FROM type_rooms WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

}
