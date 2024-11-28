<?php

namespace App\Models;

use PDO;

class User
{
    private PDO $db;

    public int $id = -1;
    public string $email;
    public string $name;
    public string $password;
    public ?string $avatar = null; // Thêm avatar
    public string $gender; // Thêm gender
    public string $role; // Thêm role

    public string $created_at;
    public string $updated_at;
    public int $id_user = -1;

    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
    }

    // Tìm người dùng theo cột và giá trị được cung cấp
    public function where(string $column, string $value): User
    {
        $statement = $this->db->prepare("SELECT * FROM USERS WHERE $column = :value");
        $statement->execute(['value' => $value]);
        $row = $statement->fetch();
        if ($row) {
            $this->fillFromDbRowUser($row);
        }
        return $this;
    }

    // Lưu thông tin người dùng vào cơ sở dữ liệu
    public function save(): bool
    {
        $result = false;

        if ($this->id >= 0) {
            $statement = $this->db->prepare(
                'UPDATE USERS SET email = :email, name = :name, avatar = :avatar, gender = :gender, 
                 password = :password, updated_at = NOW() WHERE id = :id'
            );
            $result = $statement->execute([
                'id' => $this->id,
                'email' => $this->email,
                'name' => $this->name,
                'avatar' => $this->avatar,
                'gender' => $this->gender,
                'password' => $this->password
            ]);
        } else {
            $statement = $this->db->prepare(
                'INSERT INTO USERS (email, name, avatar, gender, password, created_at, updated_at)
                 VALUES (:email, :name, :avatar, :gender, :password, NOW(), NOW())'
            );
            $result = $statement->execute([
                'email' => $this->email,
                'name' => $this->name,
                'avatar' => $this->avatar,
                'gender' => $this->gender,
                'password' => $this->password
            ]);
            if ($result) {
                $this->id = $this->db->lastInsertId();
            }
        }

        return $result;
    }

    // Điền dữ liệu vào model từ mảng dữ liệu
    public function fillUser(array $data): User
    {
        $this->email = $data['email'];
        $this->name = $data['name'];
        $this->avatar = $data['avatar'] ?? null; // Nếu không có avatar, để null
        $this->gender = $data['gender'];
        $this->password = password_hash($data['password'], PASSWORD_DEFAULT);
        return $this;
    }

    // Điền dữ liệu từ hàng kết quả cơ sở dữ liệu
    private function fillFromDbRowUser(array $row)
    {
        $this->id = $row['id'];
        $this->email = $row['email'];
        $this->name = $row['name'];
        $this->avatar = $row['avatar'];
        $this->gender = $row['gender'];
        $this->password = $row['password'];
        $this->role = $row['role']; // Lấy vai trò người dùng
    }

    // Kiểm tra email đã được sử dụng chưa
    private function isEmailInUse(string $email): bool
    {
        $statement = $this->db->prepare('SELECT count(*) FROM USERS WHERE email = :email');
        $statement->execute(['email' => $email]);
        return $statement->fetchColumn() > 0;
    }

    // Validate thông tin người dùng
    public function validate(array $data): array
    {
        $errors = [];

        if (empty($data['email'])) {
            $errors['email'] = 'Email không hợp lệ.';
        } elseif ($this->isEmailInUse($data['email'])) {
            $errors['email'] = 'Email đã được sử dụng.';
        }

        if (strlen($data['password']) < 6) {
            $errors['password'] = 'Mật khẩu phải có ít nhất 6 ký tự.';
        } elseif ($data['password'] != $data['password_confirmation']) {
            $errors['password'] = 'Xác nhận mật khẩu không khớp.';
        }

        if (empty($data['gender'])) {
            $errors['gender'] = 'Giới tính không được để trống.';
        }

        return $errors;
    }

    public function getUserByIdUser(int $id): ?User
{
    // Truy vấn cơ sở dữ liệu để lấy thông tin người dùng theo id
    $statement = $this->db->prepare("SELECT * FROM USERS WHERE id = :id");
    $statement->execute(['id' => $id]);
    
    // Lấy kết quả và trả về đối tượng User nếu có dữ liệu
    $row = $statement->fetch();
    if ($row) {
        $user = new User($this->db);
        $user->fillFromDbRowUser($row);
        return $user;  // Trả về đối tượng User
    }
    
    // Nếu không tìm thấy người dùng, trả về null
    return null;
}


    // Admin
    public function setUser(User $user): User
    {
        $this->id_user = $user->id_user;
        return $this;
    }
    public function fill(array $data): User
    {
        $this->name = $data['name'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->avatar = $data['avatar'] ?? '';
        $this->gender = $data['gender'] ?? '';
        $this->role = $data['role'] ?? '';
        $this->password = isset($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : '';
        return $this;
    }

    private function fillFromDbRow(array $row): User
    {
        [
            'id' => $this->id_user,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'gender' => $this->gender,
            'role' => $this->role,
            'password' => $this->password,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ] = $row;
        return $this;
    }
    public function validateUser(array $data): array
    {
        $errors = [];
        if (empty($data['name']) || strlen($data['name']) < 3) {
            $errors['name'] = 'Tên phải ít nhất 3 ký tự.';
        }
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email không hợp lệ.';
        } elseif ($this->isEmailRegistered($data['email'])) {
            $errors['email'] = 'Email đã đăng kí';
        }
        if (empty($data['password']) || strlen($data['password']) < 6) {
            $errors['password'] = 'Mật khẩu phải có ít nhất 6 ký tự.';
        }
        if ($data['password'] != $data['confirm_password']) {
            $errors['confirm_password'] = 'Mật khẩu không giống nhau';
        }
        return $errors;
    }
    public function validateUpdateUser(array $data): array
    {
        $errors = [];
        if (empty($data['name']) || strlen($data['name']) < 3) {
            $errors['name'] = 'Tên phải ít nhất 3 ký tự.';
        }
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email không hợp lệ.';
        }
        return $errors;
    }
    // Thêm user mới
    public function addUser(array $data, array $file): bool
    {
        $errors = $this->validateUser($data);
        if (!empty($errors)) {
            return false; // Có lỗi, không thể thêm user
        }
        $avatar = $this->uploadAvatar($file);
        $statement = $this->db->prepare(
            'INSERT INTO users (name, email, avatar, gender, role, password, created_at, updated_at)
            VALUES (:name, :email, :avatar, :gender, :role, :password, NOW(), NOW())'
        );
        return $statement->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'avatar' => $avatar,
            'gender' => $data['gender'],
            'role' => $data['role'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT)
        ]);
    }

    // Cập nhật user hiện có
    public function updateUser(int $userId, array $data): bool
    {
        $statement = $this->db->prepare('UPDATE users SET name = :name, email = :email, role = :role, gender = :gender WHERE id = :id');
        return $statement->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'gender' => $data['gender'],
            'id' => $userId
        ]);
    }

    // Xóa user
    public function deleteUser(int $id): bool
    {
        // Get current user details to delete the avatar
        $user = $this->getUserById($id);
        if ($user && $user['avatar']) {
            $avatarPath = __DIR__ . '/../../public/assets/uploads/avatars/' . $user['avatar'];
            if (file_exists($avatarPath)) {
                unlink($avatarPath); // Delete old avatar
            }
        }

        $statement = $this->db->prepare('DELETE FROM users WHERE id = :id_user');

        return $statement->execute(['id_user' => $id]);
    }


    // Lấy tổng số bản ghi (có thể áp dụng bộ lọc tìm kiếm và vai trò nếu cần)

    public function getTotalRecords($filter = '', $search = ''): int
    {
        $query = "SELECT COUNT(*) FROM users WHERE 1=1";

        if ($filter) {
            $query .= " AND role = :filter";
        }

        if ($search) {
            $query .= " AND name LIKE :search";
        }

        $stmt = $this->db->prepare($query);

        if ($filter) {
            $stmt->bindValue(':filter', $filter, PDO::PARAM_STR);
        }

        if ($search) {
            $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        }

        $stmt->execute();
        return (int) $stmt->fetchColumn() + 1;
    }

    // Lấy dữ liệu người dùng theo phân trang, bộ lọc tìm kiếm, vai trò và sắp xếp theo tên
    public function getUsers(int $offset, int $limit, $filter = '', $search = '', $sortBy = 'name'): array
    {
        $query = "SELECT * FROM users WHERE 1=1";

        if ($filter) {
            $query .= " AND role = :filter";
        }

        if ($search) {
            $query .= " AND name LIKE :search";
        }

        $query .= " ORDER BY $sortBy LIMIT :offset, :limit";

        $stmt = $this->db->prepare($query);

        if ($filter) {
            $stmt->bindValue(':filter', $filter, PDO::PARAM_STR);
        }

        if ($search) {
            $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        }

        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function uploadAvatar(array $file): ?string
    {
        if ($file['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . './../../public/assets/uploads/avatars/';


            $filename = uniqid() . '-' . basename($file['name']);
            $uploadFile = $uploadDir . $filename;

            // Chuyển file đã upload vào thư mục lưu trữ
            if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
                return $filename;
            }
        }
        return null;
    }

    public function getUserById($id)
    {
        $statement = $this->db->prepare('SELECT * FROM users WHERE id = :id');
        $statement->execute(['id' => $id]);
        return $statement->fetch(PDO::FETCH_ASSOC);
    }
    public function getAllUsers()
    {
        $statement = $this->db->prepare('SELECT * FROM users');
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateAvatar(int $userId, string $newAvatar)
    {
        // Xóa avatar cũ
        $user = $this->getUserById($userId);
        if (!empty($user['avatar'])) {
            $oldAvatarPath = realpath(__DIR__ . '/../../public/assets/uploads/avatars/' . $user['avatar']);
            if ($oldAvatarPath && file_exists($oldAvatarPath)) {
                unlink($oldAvatarPath);
            }

        }

        // Cập nhật avatar mới
        $statement = $this->db->prepare('UPDATE users SET avatar = :avatar WHERE id = :id');
        $statement->execute(['avatar' => $newAvatar, 'id' => $userId]);
    }
    public function getTotalUsers(): int
    {
        $statement = $this->db->prepare('SELECT COUNT(*) FROM users');
        $statement->execute();
        return (int) $statement->fetchColumn();
    }
    public function isEmailRegistered(string $email): bool
    {
        $statement = $this->db->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
        $statement->execute(['email' => $email]);
        $count = (int) $statement->fetchColumn();
        return $count > 0;
    }
    public function getEmailById(int $id): ?string
    {
        $statement = $this->db->prepare('SELECT email FROM users WHERE id = :id');
        $statement->execute(['id' => $id]);
        $email = $statement->fetchColumn();
        return $email ?: null;
    }
}
