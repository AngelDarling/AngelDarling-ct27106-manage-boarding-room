<?php

namespace App\Controllers\Admin;

use App\Models\type_room;
use App\Utils\Paginator;
use PDO;

class type_roomController extends Controller
{
    public function gettype_room()
    {
        $type_roomModel = new type_room(PDO());
        $recordsPerPage = 10;

        $totalRecords = $type_roomModel->gettype_roomsCount();
        $totalPages = ceil($totalRecords / $recordsPerPage);
        $currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
        } elseif ($currentPage < 1) {
            $currentPage = 1;
        }

        $paginator = new Paginator($recordsPerPage, $totalRecords, $currentPage);

        $type_rooms = $type_roomModel->getPaginatedtype_rooms($recordsPerPage, $paginator->recordOffset);
        $user = AUTHGUARD()->user();
        $this->sendPage('/admin/type_room/type_room', [
            'type_rooms' => $type_rooms,
            'paginator' => $paginator,
            'status' => session_get_once("status"),
            'isOpenModal' => session_get_once("isOpenModal"),
            'formError' => session_get_once("formError"),
            'errors' => session_get_once("errors"),
            'user' => $user,
        ]);
    }


    public function getUpdatetype_room()
    {
        $type_roomId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($type_roomId > 0) {
            $type_roomModel = new type_room(PDO());
            $type_room = $type_roomModel->gettype_roomById($type_roomId);
            $listtype_rooms = $type_roomModel->getAlltype_rooms();
            if ($type_room) {
                $this->sendPage('/admin/type_room/update', [
                    'type_room' => $type_room,
                    'listtype_rooms' => $listtype_rooms,
                    'status' => session_get_once("status")
                ]);
            } else {
                echo "Error: Could not retrieve type_room.";
            }
        } else {
            redirect('/admin/type_room', ['status' => 'Invalid ID']);
        }
    }

    public function addtype_room()
    {
        $newtype_room = new type_room(PDO());
        $data = $this->filtertype_roomData($_POST);

        $errors = $newtype_room->validatetype_room($data);
        if (empty($errors)) {
            $newtype_room->addtype_room($data, $_FILES['image']);
            redirect('/admin/type_room', [
                'status' => 'type_room added successfully',
            ]);
        } else {
            redirect('/admin/type_room', ['isOpenModal' => true, 'formError' => $data, 'errors' => $errors, 'status' => 'Check your form please']);
        }
    }

    public function update()
    {
        $type_roomId = (int) $_POST['id'];
        $type_roomModel = new type_room(PDO());
        $name = $_POST['name'];
        if ($type_roomModel->istype_roomNameTaken($name) && $type_roomModel->gettype_roomNameById($type_roomId) != $name) {
            redirect('/admin/type_room/update?id=' . $type_roomId, ['status' => 'Đã có hãng trong server']);
        }
        $image = $_FILES['image'];
        $type_roomModel->updatetype_room($type_roomId, $name, $image);
        redirect('/admin/type_room/update?id=' . $type_roomId, ['status' => 'Update success']);
    }
    public function filtertype_roomData(array $data): array
    {
        return [
            'name' => trim($data['name']),
        ];
    }
    public function deletetype_room()
    {
        $type_roomId = (int) $_POST['type_room_id'];
        $type_roomModel = new type_room(PDO());
        $type_roomModel->deletetype_room($type_roomId);
        redirect('/admin/type_room', ['status' => 'type_room deleted successfully']);
    }

}
