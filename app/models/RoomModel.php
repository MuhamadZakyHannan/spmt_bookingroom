<?php
require_once __DIR__ . '/../core/Database.php';

class RoomModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAllRooms($search = '', $status = '', $minCapacity = 0) {
        if (!$this->db) return [];

        $sql = "SELECT * FROM rooms WHERE 1=1";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (name LIKE ? OR code LIKE ? OR location LIKE ? OR facilities LIKE ?)";
            $term = "%$search%";
            $params[] = $term; $params[] = $term; $params[] = $term; $params[] = $term;
        }
        if ($status !== '') {
            $sql .= " AND status = ?";
            $params[] = $status;
        }
        if ($minCapacity > 0) {
            $sql .= " AND capacity >= ?";
            $params[] = $minCapacity;
        }

        $sql .= " ORDER BY code ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getActiveRooms() {
        if (!$this->db) return [];
        $stmt = $this->db->query("SELECT * FROM rooms WHERE status != 'maintenance' ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    public function getById($id) {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("SELECT * FROM rooms WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getByCode($code) {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("SELECT * FROM rooms WHERE code = ?");
        $stmt->execute([$code]);
        return $stmt->fetch();
    }

    public function create($data) {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("INSERT INTO rooms (code, name, capacity, location, floor, facilities, description, image, status) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['code'],
            $data['name'],
            $data['capacity'],
            $data['location'],
            $data['floor'],
            $data['facilities'],
            $data['description'],
            $data['image'],
            $data['status'] ?? 'available'
        ]);
    }

    public function update($id, $data) {
        if (!$this->db) return false;
        $sql = "UPDATE rooms SET 
                code = ?, 
                name = ?, 
                capacity = ?, 
                location = ?, 
                floor = ?, 
                facilities = ?, 
                description = ?, 
                image = ?, 
                status = ? 
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['code'],
            $data['name'],
            $data['capacity'],
            $data['location'],
            $data['floor'],
            $data['facilities'],
            $data['description'],
            $data['image'],
            $data['status'] ?? 'available',
            $id
        ]);
    }

    public function updateStatus($id, $status) {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("UPDATE rooms SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function delete($id) {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("DELETE FROM rooms WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getTotalRoomsCount() {
        if (!$this->db) return 0;
        return (int)$this->db->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
    }

    public function getAvailableRoomsCount() {
        if (!$this->db) return 0;
        return (int)$this->db->query("SELECT COUNT(*) FROM rooms WHERE status = 'available'")->fetchColumn();
    }
}
