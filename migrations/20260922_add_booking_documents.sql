-- Dokumen pendukung pengajuan booking disimpan di luar web root.
-- Tabel ini hanya menyimpan metadata dan referensi nama file acak.

CREATE TABLE IF NOT EXISTS booking_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    document_type VARCHAR(50) NOT NULL DEFAULT 'supporting_document',
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(100) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    size_bytes INT UNSIGNED NOT NULL,
    sha256 CHAR(64) NOT NULL,
    uploaded_by INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_booking_documents_booking
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    CONSTRAINT fk_booking_documents_uploader
        FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uq_booking_document_type (booking_id, document_type),
    UNIQUE KEY uq_booking_document_stored_name (stored_name),
    INDEX idx_booking_documents_uploader (uploaded_by),
    INDEX idx_booking_documents_sha256 (sha256)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
