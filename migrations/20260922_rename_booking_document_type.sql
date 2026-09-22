-- Menyamakan istilah teknis dokumen dengan istilah antarmuka "Surat Pendukung".

UPDATE booking_documents
SET document_type = 'supporting_document'
WHERE document_type = 'request_letter';

ALTER TABLE booking_documents
    MODIFY document_type VARCHAR(50) NOT NULL DEFAULT 'supporting_document';
