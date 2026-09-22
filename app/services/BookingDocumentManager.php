<?php

/**
 * Mengorkestrasi validasi, penyimpanan file, dan metadata dokumen booking.
 */
class BookingDocumentManager
{
    private $documentModel;
    private $storageService;

    public function __construct($documentModel = null, $storageService = null)
    {
        $this->documentModel = $documentModel ?: new BookingDocumentModel();
        $this->storageService = $storageService ?: new BookingDocumentService();
    }

    public function validate(?array $file): array
    {
        return $this->storageService->validate($file);
    }

    public function storeValidated(int $bookingId, int $uploadedBy, array $validatedUpload): array
    {
        $stored = $this->storageService->store($validatedUpload);
        if (empty($stored['success'])) {
            return $stored;
        }

        $saved = $this->documentModel->replace($bookingId, $uploadedBy, [
            'original_name' => $validatedUpload['original_name'],
            'stored_name' => $stored['stored_name'],
            'mime_type' => $validatedUpload['mime_type'],
            'size_bytes' => $validatedUpload['size_bytes'],
            'sha256' => $validatedUpload['sha256'],
        ]);

        if (empty($saved['success'])) {
            $this->storageService->remove($stored['stored_name']);
            return ['success' => false, 'error' => 'Metadata dokumen gagal disimpan.'];
        }

        if (!empty($saved['old_stored_name']) && $saved['old_stored_name'] !== $stored['stored_name']) {
            $this->storageService->remove($saved['old_stored_name']);
        }

        return ['success' => true, 'document_id' => $saved['document_id']];
    }
}
