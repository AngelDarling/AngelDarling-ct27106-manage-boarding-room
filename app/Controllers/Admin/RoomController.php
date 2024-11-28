<?php

namespace App\Controllers\Admin;

use App\Models\room;
use App\Utils\Paginator;

// use App\Models\Contact;
class roomController extends Controller
{
    public function __construct()
    {
        // if (!AUTHGUARD()->isUserLoggedIn()) {
        //   redirect('/login');
        // }

        parent::__construct();
    }
    public function getroom()
    {
        $roomModel = new room(PDO());
        $type_rooms = $roomModel->gettype_rooms();
        $recordsPerPage = 10;

        $sort = isset($_GET['sort']) ? $_GET['sort'] : 'asc';
        $search = isset($_GET['search']) ? $_GET['search'] : '';

        $roomModel = new room(PDO());
        $totalRecords = $roomModel->getTotalRecords($search);

        $totalPages = ceil($totalRecords / $recordsPerPage);
        $currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
        } elseif ($currentPage < 1) {
            $currentPage = 1;
        }

        $paginator = new Paginator($recordsPerPage, $totalRecords, $currentPage);

        $rooms = $roomModel->getrooms($paginator->recordOffset, $recordsPerPage, $sort, $search);
        foreach ($rooms as &$room) {
            $room['type_room'] = $roomModel->gettype_roomNameById($room['type_room_id']);
        }
        unset($room);
        $user = AUTHGUARD()->user();
        $this->sendPage('/admin/room/room', [
            'rooms' => $rooms,
            'paginator' => $paginator,
            'sort' => $sort,
            'search' => $search,
            'type_rooms' => $type_rooms,
            'status1' => session_get_once("status1"),
            'isOpenModal' => session_get_once("isOpenModal"),
            'formError' => session_get_once("formError"),
            'errors' => session_get_once("errors"),
            'user' => $user,
        ]);
    }
    protected function filterroomData(array $data): array
    {
        return [
            'id' => $data['id'] ?? '',
            'name' => $data['name'] ?? '',
            'type_room' => $data['type_room'] ?? '',
            'price' => $data['price'] ?? '',
            'status' => $data['status'] ?? '',
            'short_description' => $data['short_description'] ?? '',
            'long_description' => $data['long_description'] ?? '',
            'image' => $data['image'] ?? null,
        ];
    }

    public function addroom()
    {
        $newroom = new room(PDO());
        $data = $this->filterroomData($_POST);

        $errors = $newroom->validateroom($data);
        if (empty($errors)) {
            $newroom->addroom($data, $_FILES['image']);
            redirect('/admin/room', ['status1' => 'room added successfully']);
        } else {
            redirect('/admin/room', ['isOpenModal' => true, 'formError' => $data, 'errors' => $errors, 'status1' => 'Check your form please']);
        }
    }
    public function detailroom()
    {
        $roomId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($roomId > 0) {
            $roomModel = new room(PDO());
            $listrooms = $roomModel->getAllrooms();

            $room = $roomModel->getroomById($roomId);

            $type_room = $roomModel->gettype_roomNameById($room->type_room);


            if ($room) {
                $this->sendPage('/admin/room/detail', [
                    'room' => $room,
                    'listrooms' => $listrooms,
                    'type_room' => $type_room,
                    'status1' => session_get_once("status1"),
                ]);
            } else {
                echo "Error: Could not retrieve room.";
            }
        } else {
            redirect('/admin/room', ['status1' => 'Invalid ID']);
        }
    }
    public function getUpdateroom()
    {
        $roomId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($roomId > 0) {
            $roomModel = new room(PDO());
            $room = $roomModel->getroomById($roomId);
            $type_rooms = $roomModel->gettype_rooms();
            $listrooms = $roomModel->getAllrooms();
            if ($room) {
                $this->sendPage('/admin/room/update', [
                    'room' => $room,
                    'type_rooms' => $type_rooms,
                    'listrooms' => $listrooms,
                    'status1' => session_get_once("status1")
                ]);
            } else {
                echo "Error: Could not retrieve room.";
            }
        } else {
            redirect('/admin/room', ['status1' => 'Invalid ID']);
        }
    }
    public function postUpdateroom()
    {
        $data = $this->filterroomData($_POST);
        $roomId = $data['id'] ?? 0;

        if ($roomId > 0) {
            $roomModel = new room(PDO());
            $errors = $roomModel->validateroom($data);
            if (empty($errors)) {
                $roomModel->updateroom($roomId, $data);
                redirect('/admin/room/update?id=' . $roomId, ['status1' => 'room updated successfully']);
            } else {
                redirect('/admin/room/update?id=' . $roomId, ['formError' => $data, 'errors' => $errors, 'status1' => 'Check your form please']);
            }
        } else {
            redirect('/admin/room', ['status1' => 'Invalid ID']);
        }
    }
    public function postUpdateImage()
    {
        $roomId = $_POST['room_id'];
        $roomModel = new room(PDO());

        // Check if a new image is uploaded
        if (!empty($_FILES['new_image']['name'])) {
            $newImage = $roomModel->uploadImage($_FILES['new_image']);
            $roomModel->updateImage($roomId, $newImage);
            redirect('/admin/room/update?id=' . $roomId, ['status1' => 'room image updated successfully']);
        } else {
            redirect('/admin/room/update?id=' . $roomId, ['status1' => 'No image uploaded']);
        }
    }
    public function deleteroom()
    {
        $roomId = isset($_POST['room_id']) ? (int) $_POST['room_id'] : 0;
        if ($roomId > 0) {
            $roomModel = new room(PDO());
            $success = $roomModel->deleteroom($roomId);
            if ($success) {
                redirect('/admin/room', ['status1' => 'room deleted successfully']);
            } else {
                redirect('/admin/room', ['status1' => 'Failed to delete room']);
            }
        } else {
            redirect('/admin/room', ['status1' => 'Invalid room ID']);
        }
    }

}
