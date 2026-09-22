<?php
require_once __DIR__ . '/../core/Controller.php';

class BookingDocumentController extends Controller
{
    private $bookingModel;
    private $documentModel;
    private $documentService;
    private $documentManager;

    public function __construct()
    {
        $this->bookingModel = $this->model('BookingModel');
        $this->documentModel = $this->model('BookingDocumentModel');
        $this->documentService = new BookingDocumentService();
        $this->documentManager = new BookingDocumentManager(
            $this->documentModel,
            $this->documentService
        );
    }

    public function download(): void
    {
        $this->requireAuth();
        $documentId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        $document = $documentId ? $this->documentModel->getById((int) $documentId) : false;

        if (!$document || (!$this->canView($document))) {
            http_response_code(404);
            exit('Dokumen tidak ditemukan.');
        }

        $path = $this->documentService->resolvePath((string) $document['stored_name']);
        if (!$path) {
            http_response_code(404);
            exit('File dokumen tidak tersedia.');
        }

        $download = ($_GET['download'] ?? '') === '1';
        $disposition = $download ? 'attachment' : 'inline';
        $fallbackName = preg_replace('/[^A-Za-z0-9._-]/', '_', (string) $document['original_name']);
        if ($fallbackName === '') $fallbackName = 'dokumen';

        header('Content-Type: ' . $document['mime_type']);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: ' . $disposition . '; filename="' . $fallbackName
            . '"; filename*=UTF-8\'\'' . rawurlencode((string) $document['original_name']));
        header('Cache-Control: private, no-store, max-age=0');
        header('Content-Security-Policy: sandbox');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    public function manage(): void
    {
        $this->requireAuth();
        $bookingId = filter_var($_GET['booking_id'] ?? $_POST['booking_id'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        $returnTo = $this->resolveReturnUrl((string) ($_GET['return_to'] ?? $_POST['return_to'] ?? ''));

        if (!$bookingId) {
            set_flash('danger', 'Booking untuk surat pendukung tidak valid.');
            $this->redirect($returnTo);
        }

        $booking = $this->bookingModel->getById((int) $bookingId);
        if (!$booking || !$this->canManage($booking)) {
            set_flash('danger', 'Anda tidak memiliki izin untuk mengelola surat pendukung booking ini.');
            $this->redirect($returnTo);
        }

        if (!in_array($booking['status'], ['pending', 'confirmed'], true)) {
            set_flash('warning', 'Surat pendukung hanya dapat ditambahkan saat booking menunggu persetujuan atau sudah terkonfirmasi.');
            $this->redirect($returnTo);
        }

        $error = '';
        if ($this->isPost()) {
            $fallback = 'booking_document_upload.php?booking_id=' . (int) $bookingId;
            $this->validateCsrf($fallback);
            $validated = $this->documentManager->validate($_FILES['supporting_document'] ?? null);

            if (empty($validated['success'])) {
                $error = $validated['error'];
            } elseif (empty($validated['provided'])) {
                $error = 'Pilih file surat pendukung yang akan diunggah.';
            } else {
                $result = $this->documentManager->storeValidated(
                    (int) $bookingId,
                    (int) $_SESSION['user_id'],
                    $validated
                );
                if (!empty($result['success'])) {
                    set_flash('success', empty($booking['document_id'])
                        ? 'Surat pendukung berhasil ditambahkan.'
                        : 'Surat pendukung berhasil diganti.');
                    $this->redirect($returnTo);
                }
                $error = $result['error'] ?? 'Surat pendukung gagal disimpan.';
            }
        }

        $this->view('booking/document_upload', [
            'booking' => $booking,
            'return_to' => $returnTo,
            'error' => $error,
        ]);
    }

    private function canView(array $document): bool
    {
        return is_admin()
            || (int) $document['booking_user_id'] === (int) ($_SESSION['user_id'] ?? 0);
    }

    private function canManage(array $booking): bool
    {
        return is_admin()
            || (int) $booking['user_id'] === (int) ($_SESSION['user_id'] ?? 0);
    }

    private function resolveReturnUrl(string $requested): string
    {
        if (is_admin() && $requested === 'admin_bookings.php') {
            return 'admin_bookings.php';
        }
        return 'my_bookings.php';
    }
}
