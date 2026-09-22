<?php

require_once __DIR__ . '/../core/Controller.php';

/**
 * Mengelola data, status, dan gambar ruang rapat.
 */
final class AdminRoomController extends Controller
{
    private RoomModel $rooms;
    private RoomImageService $images;

    public function __construct()
    {
        $this->rooms = $this->model('RoomModel');
        $this->images = new RoomImageService();
    }

    public function index(): void
    {
        $this->requireAdmin();
        $error = '';
        if ($this->isPost()) {
            $this->validateCsrf('admin_rooms.php');
            $error = $this->handleAction((string) ($_POST['action'] ?? ''));
        }

        $this->view('admin/rooms', [
            'rooms' => $this->rooms->getAllRooms(),
            'error' => $error,
        ]);
    }

    private function handleAction(string $action): string
    {
        if ($action === 'add') return $this->add();
        if ($action === 'edit') return $this->edit();

        $roomId = (int) ($_POST['room_id'] ?? 0);
        if ($action === 'update_status' && $roomId > 0) {
            $this->rooms->updateStatus($roomId, (string) ($_POST['status'] ?? 'available'));
            set_flash('success', 'Status ruangan berhasil diperbarui.');
            $this->redirect('admin_rooms.php');
        }
        if ($action === 'delete' && $roomId > 0) {
            $this->rooms->delete($roomId);
            set_flash('success', 'Ruangan berhasil dihapus.');
            $this->redirect('admin_rooms.php');
        }
        return '';
    }

    private function add(): string
    {
        $data = $this->roomInput();
        if ($error = $this->validateRoomInput($data)) return $error;
        if ($this->rooms->getByCode($data['code'])) {
            return 'Kode ruangan ' . $data['code'] . ' sudah ada dalam database!';
        }

        $data['image'] = $this->images->store(
            $_FILES['room_image'] ?? null,
            'public/rooms/KalTim.jpeg'
        );
        if (!$this->rooms->create($data)) return 'Gagal menyimpan ruangan baru.';

        set_flash('success', 'Ruangan baru "' . htmlspecialchars($data['name']) . '" berhasil ditambahkan.');
        $this->redirect('admin_rooms.php');
    }

    private function edit(): string
    {
        $roomId = (int) ($_POST['room_id'] ?? 0);
        $current = $roomId > 0 ? $this->rooms->getById($roomId) : false;
        if (!$current) return 'Data ruangan tidak ditemukan.';

        $data = $this->roomInput();
        if ($error = $this->validateRoomInput($data)) return $error;
        $sameCode = $this->rooms->getByCode($data['code']);
        if ($sameCode && (int) $sameCode['id'] !== $roomId) {
            return 'Kode ruangan ' . $data['code'] . ' sudah digunakan oleh ruangan lain!';
        }

        $data['image'] = $this->images->store(
            $_FILES['room_image'] ?? null,
            $current['image'] ?: 'public/rooms/KalTim.jpeg'
        );
        if (!$this->rooms->update($roomId, $data)) return 'Gagal memperbarui data ruangan.';

        set_flash('success', 'Informasi ruangan "' . htmlspecialchars($data['name']) . '" berhasil diperbarui.');
        $this->redirect('admin_rooms.php');
    }

    private function roomInput(): array
    {
        $status = (string) ($_POST['status'] ?? 'available');
        return [
            'code' => strtoupper(trim((string) ($_POST['code'] ?? ''))),
            'name' => trim((string) ($_POST['name'] ?? '')),
            'capacity' => (int) ($_POST['capacity'] ?? 0),
            'location' => trim((string) ($_POST['location'] ?? '')),
            'floor' => trim((string) ($_POST['floor'] ?? '')),
            'facilities' => trim((string) ($_POST['facilities'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'status' => in_array($status, ['available', 'occupied', 'maintenance'], true)
                ? $status
                : 'available',
        ];
    }

    private function validateRoomInput(array $data): string
    {
        return $data['code'] === '' || $data['name'] === ''
            || $data['capacity'] <= 0 || $data['location'] === ''
            ? 'Harap lengkapi field wajib (Kode, Nama, Kapasitas, Lokasi)!'
            : '';
    }
}
