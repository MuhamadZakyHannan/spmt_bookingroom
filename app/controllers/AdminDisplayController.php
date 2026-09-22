<?php

require_once __DIR__ . '/../core/Controller.php';

/**
 * Mengelola perangkat monitor pintu dan token aksesnya.
 */
final class AdminDisplayController extends Controller
{
    private DisplayModel $displays;

    /** Menyiapkan dependensi yang dibutuhkan oleh AdminDisplayController. */
    public function __construct()
    {
        $this->displays = $this->model('DisplayModel');
    }

    /** Menampilkan halaman utama admin display. */
    public function index(): void
    {
        $this->requireAdmin();
        $error = '';
        if ($this->isPost()) {
            $this->validateCsrf('admin_displays.php');
            $error = $this->handleAction((string) ($_POST['action'] ?? ''));
        }

        $this->view('admin/displays', [
            'displays' => $this->displays->getAllDisplays(),
            'rooms' => $this->model('RoomModel')->getAllRooms(),
            'error' => $error,
        ]);
    }

    /** Menangani proses action. */
    private function handleAction(string $action): string
    {
        if ($action === 'create') {
            $roomId = (int) ($_POST['room_id'] ?? 0);
            $name = trim((string) ($_POST['display_name'] ?? ''));
            $token = trim((string) ($_POST['token'] ?? '')) ?: $this->newToken();
            if ($roomId <= 0 || $name === '') return 'Ruangan dan nama monitor wajib diisi!';
            if ($this->displays->getByToken($token)) return 'Token display sudah digunakan, silakan gunakan token lain!';
            if (!$this->displays->create($roomId, $name, $token)) return 'Gagal mendaftarkan display.';
            set_flash('success', 'Monitor display baru berhasil didaftarkan.');
            $this->redirect('admin_displays.php');
        }

        $displayId = (int) ($_POST['display_id'] ?? 0);
        if ($displayId > 0 && $action === 'regenerate_token') {
            $this->displays->updateToken($displayId, $this->newToken());
            set_flash('success', 'Token monitor display berhasil diperbarui.');
            $this->redirect('admin_displays.php');
        }
        if ($displayId > 0 && $action === 'delete') {
            $this->displays->delete($displayId);
            set_flash('success', 'Perangkat display berhasil dihapus.');
            $this->redirect('admin_displays.php');
        }
        return '';
    }

    /** Menjalankan proses new token pada admin display. */
    private function newToken(): string
    {
        return 'DISP-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }
}
