<?php
require_once __DIR__ . '/../core/Controller.php';

class BookingDocumentController extends Controller
{
    private $documentModel;
    private $documentService;

    public function __construct()
    {
        $this->documentModel = $this->model('BookingDocumentModel');
        $this->documentService = new BookingDocumentService();
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

    private function canView(array $document): bool
    {
        return is_admin()
            || (int) $document['booking_user_id'] === (int) ($_SESSION['user_id'] ?? 0);
    }
}
