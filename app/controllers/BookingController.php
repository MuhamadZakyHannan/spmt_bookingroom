<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/SawService.php';
require_once __DIR__ . '/../services/BookingLifecycleService.php';

class BookingController extends Controller {
    private $roomModel;
    private $bookingModel;
    private $notificationModel;
    private $documentManager;

    /** Menyiapkan dependensi yang dibutuhkan oleh BookingController. */
    public function __construct() {
        $this->roomModel = $this->model('RoomModel');
        $this->bookingModel = $this->model('BookingModel');
        $this->notificationModel = $this->model('NotificationModel');
        $this->documentManager = new BookingDocumentManager(
            $this->model('BookingDocumentModel'),
            new BookingDocumentService()
        );
    }

    /** Menampilkan dan memproses pembuatan booking. */
    public function create() {
        $this->requireAuth();

        $selectedRoomId = (int) ($_GET['room_id'] ?? 0);
        $requestedDate = $this->validRequestedDate((string) ($_GET['date'] ?? ''));
        $defaultStartTime = '09:00';
        $defaultEndTime = '10:00';
        if ($requestedDate === date('Y-m-d')) {
            $nextHour = (int) date('H') + 1;
            if ($nextHour >= 22) {
                $requestedDate = date('Y-m-d', strtotime('+1 day'));
                $defaultStartTime = '09:00';
                $defaultEndTime = '10:00';
            } else {
                $defaultStartTime = sprintf('%02d:00', max(8, $nextHour));
                $defaultEndTime = sprintf('%02d:00', max(9, $nextHour + 1));
            }
        }
        $values = [
            'room_id' => $selectedRoomId,
            'user_name' => $_SESSION['user_name'] ?? '',
            'user_dept' => $_SESSION['department'] ?? '',
            'title' => '',
            'date' => $requestedDate,
            'start_time' => $defaultStartTime,
            'end_time' => $defaultEndTime,
            'purpose' => '',
            'activity_type' => 'internal_divisi',
            'attendees_count' => 1,
        ];
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf('booking.php');
            $values = $this->readBookingInput($_POST);
            $selectedRoomId = $values['room_id'];
            $error = $this->validateBookingInput($values);
            $documentUpload = $this->documentManager->validate($_FILES['supporting_document'] ?? null);
            if ($error === '' && empty($documentUpload['success'])) {
                $error = $documentUpload['error'];
            }

            if ($error === '') {
                $isAdmin = is_admin();
                $result = $this->bookingModel->createWithSchedulePolicy(
                    array_merge($values, ['user_id' => (int) $_SESSION['user_id']]),
                    $isAdmin
                );

                if (!empty($result['success'])) {
                    $bookingId = (int) $result['booking_id'];
                    if (!empty($documentUpload['provided'])) {
                        $documentResult = $this->documentManager->storeValidated(
                            $bookingId,
                            (int) $_SESSION['user_id'],
                            $documentUpload
                        );
                        if (empty($documentResult['success'])) {
                            // Pengajuan baru dan dokumennya diperlakukan sebagai satu operasi.
                            $this->bookingModel->delete($bookingId);
                            $error = $documentResult['error'];
                        }
                    }

                    if ($error !== '') {
                        if ($this->isAjax()) {
                            $this->jsonResponse(['success' => false, 'error' => $error], 422);
                        }
                    } else {
                        $status = $result['status'];
                        if (!empty($result['pending_conflict'])) {
                            $message = 'Pengajuan berhasil dicatat sebagai Pending. Ada pengajuan lain pada jadwal yang sama; Administrator akan meninjau dan menentukan prioritasnya.';
                        } else {
                            $message = $status === 'confirmed'
                                ? 'Pemesanan ruangan oleh Admin berhasil dibuat dan langsung terkonfirmasi ke jadwal!'
                                : 'Pengajuan booking berhasil dikirim! Status saat ini menunggu persetujuan Administrator.';
                        }

                        if ($status === 'pending') {
                            $this->notificationModel->createForPendingBooking($bookingId);
                        }
                        set_flash('success', $message);

                        if ($this->isAjax()) {
                            $this->jsonResponse([
                                'success' => true,
                                'message' => $message,
                                'redirect' => 'my_bookings.php',
                            ]);
                        }
                        $this->redirect('my_bookings.php');
                    }
                } else {
                    $error = $this->bookingResultError($result);
                }
            }

            if ($this->isAjax() && $error !== '') {
                $this->jsonResponse(['success' => false, 'error' => $error], 422);
            }
        }

        $this->view('booking/create', [
            'rooms' => $this->roomModel->getAllRooms(),
            'selected_room_id' => $selectedRoomId,
            'user_name' => $values['user_name'],
            'user_dept' => $values['user_dept'],
            'title' => $values['title'],
            'date' => $values['date'],
            'start_time' => $values['start_time'],
            'end_time' => $values['end_time'],
            'purpose' => $values['purpose'],
            'activity_type' => $values['activity_type'],
            'activity_types' => SawService::ACTIVITY_TYPES,
            'attendees_count' => $values['attendees_count'],
            'error' => $error,
        ]);
    }

    /** Menampilkan dan memproses perubahan booking. */
    public function edit() {
        $this->requireAuth();

        $bookingId = filter_var($_GET['id'] ?? $_POST['booking_id'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        $returnTo = $this->resolveEditReturnUrl((string) ($_GET['return_to'] ?? $_POST['return_to'] ?? ''));
        if ($bookingId === false || $bookingId === null) {
            set_flash('danger', 'Booking yang akan diedit tidak valid.');
            $this->redirect($returnTo);
        }

        $booking = $this->bookingModel->getById((int) $bookingId);
        if (!$booking) {
            set_flash('danger', 'Booking tidak ditemukan.');
            $this->redirect($returnTo);
        }
        if (!$this->canEditBooking($booking)) {
            set_flash('danger', 'Booking ini tidak dapat diedit. Pemilik hanya dapat mengubah pengajuan Pending; booking terkonfirmasi hanya dapat diubah Administrator.');
            $this->redirect($returnTo);
        }

        $values = [
            'room_id' => (int) $booking['room_id'],
            'user_name' => (string) ($booking['user_name'] ?: $booking['requester_name']),
            'user_dept' => (string) ($booking['user_dept'] ?: $booking['requester_department']),
            'title' => (string) $booking['title'],
            'date' => (string) $booking['date'],
            'start_time' => substr((string) $booking['start_time'], 0, 5),
            'end_time' => substr((string) $booking['end_time'], 0, 5),
            'purpose' => (string) ($booking['purpose'] ?? ''),
            'activity_type' => (string) ($booking['activity_type'] ?? 'internal_divisi'),
            'attendees_count' => (int) $booking['attendees_count'],
        ];
        if (!array_key_exists($values['activity_type'], SawService::ACTIVITY_TYPES)) {
            $values['activity_type'] = 'internal_divisi';
        }

        $isConfirmed = ($booking['status'] ?? '') === 'confirmed';
        $oldRoomId = (int) $booking['room_id'];
        $relocateReason = '';

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrfFallback = 'edit_booking.php?id=' . (int) $bookingId;
            if ($returnTo === 'admin_bookings.php') {
                $csrfFallback .= '&return_to=admin_bookings.php';
            }
            $this->validateCsrf($csrfFallback);
            $values = $this->readBookingInput($_POST);
            $error = $this->validateBookingInput($values);
            $documentUpload = $this->documentManager->validate($_FILES['supporting_document'] ?? null);
            if ($error === '' && empty($documentUpload['success'])) {
                $error = $documentUpload['error'];
            }

            $newRoomId = (int) $values['room_id'];
            $isRoomChanged = ($oldRoomId !== $newRoomId);
            $relocateReason = trim((string) ($_POST['relocate_reason'] ?? ''));

            if ($error === '' && is_admin() && $isConfirmed && $isRoomChanged) {
                if ($relocateReason === '') {
                    $error = 'Harap sertakan alasan pemindahan ruangan agar pemohon mengetahui alasan jadwalnya dipindahkan.';
                }
            }

            if ($error === '') {
                $oldRoomName = '';
                $newRoomName = '';
                if ($isRoomChanged) {
                    $allRooms = $this->roomModel->getAllRooms();
                    foreach ($allRooms as $rm) {
                        if ((int) $rm['id'] === $oldRoomId) $oldRoomName = (string) $rm['name'];
                        if ((int) $rm['id'] === $newRoomId) $newRoomName = (string) $rm['name'];
                    }
                    if ($oldRoomName === '') $oldRoomName = 'Ruangan Asal';
                    if ($newRoomName === '') $newRoomName = 'Ruangan Baru';
                }

                if (is_admin() && $isConfirmed && $isRoomChanged) {
                    $cleanReason = trim($relocateReason);
                    $values['status_reason'] = BookingLifecycleService::REASON_RELOCATED_BY_ADMIN;
                    $values['admin_notes'] = 'Ruangan dialihkan dari ' . $oldRoomName . ' ke ' . $newRoomName . '.' . ($cleanReason !== '' ? ' Alasan: ' . $cleanReason : '');
                }

                $result = $this->bookingModel->updateWithSchedulePolicy(
                    (int) $bookingId,
                    $values,
                    (int) $_SESSION['user_id'],
                    is_admin()
                );

                if (!empty($result['success'])) {
                    $documentWarning = '';
                    if (!empty($documentUpload['provided'])) {
                        $documentResult = $this->documentManager->storeValidated(
                            (int) $bookingId,
                            (int) $_SESSION['user_id'],
                            $documentUpload
                        );
                        if (empty($documentResult['success'])) {
                            $documentWarning = ' Data booking tersimpan, tetapi dokumen gagal diperbarui: ' . $documentResult['error'];
                        }
                    }
                    if (($result['status'] ?? '') === 'pending') {
                        $this->notificationModel->refreshForPendingBooking((int) $bookingId);
                    }
                    if (!empty($result['is_relocated'])) {
                        $this->notificationModel->createForRelocatedBooking(
                            (int) $bookingId,
                            $oldRoomName,
                            $newRoomName,
                            $relocateReason
                        );
                        $message = 'Booking berhasil diperbarui dan ruangan dialihkan ke ' . $newRoomName . '. Catatan alasan telah dikirim ke pemohon.';
                    } elseif (!empty($result['pending_conflict']) && ($result['status'] ?? '') === 'confirmed') {
                        $message = 'Booking terkonfirmasi berhasil diperbarui. Ada pengajuan yang masih menunggu dan beririsan; Administrator perlu meninjau pengajuan tersebut.';
                    } elseif (!empty($result['pending_conflict'])) {
                        $message = 'Booking berhasil diperbarui. Ada pengajuan lain pada jadwal yang sama dan Administrator akan meninjau prioritasnya.';
                    } else {
                        $message = 'Booking berhasil diperbarui.';
                    }
                    set_flash($documentWarning === '' ? 'success' : 'warning', $message . $documentWarning);
                    $this->redirect($returnTo);
                }

                $reason = $result['reason'] ?? 'database_error';
                if (in_array($reason, ['forbidden', 'booking_not_found'], true)) {
                    set_flash('danger', 'Booking berubah atau Anda tidak lagi memiliki izin untuk mengeditnya.');
                    $this->redirect($returnTo);
                }
                $error = $this->bookingResultError($result);
            }
        }

        $this->view('booking/edit', [
            'booking' => $booking,
            'rooms' => $this->roomModel->getAllRooms(),
            'values' => $values,
            'activity_types' => SawService::ACTIVITY_TYPES,
            'current_document' => !empty($booking['document_id']) ? [
                'id' => (int) $booking['document_id'],
                'original_name' => $booking['document_name'],
                'mime_type' => $booking['document_mime_type'],
                'size_bytes' => (int) $booking['document_size_bytes'],
            ] : null,
            'return_to' => $returnTo,
            'error' => $error,
            'relocate_reason' => $relocateReason,
        ]);
    }

    /** Menampilkan dan memproses daftar booking milik pengguna. */
    public function myBookings() {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $this->validateCsrf('my_bookings.php');
            $action = $_POST['action'];
            $bookingId = (int) ($_POST['booking_id'] ?? 0);

            if ($bookingId > 0 && $action === 'cancel') {
                if ($this->bookingModel->cancel($bookingId, $_SESSION['user_id'], false)) {
                    set_flash('success', 'Pemesanan telah berhasil dibatalkan.');
                } else {
                    set_flash('danger', 'Pemesanan tidak dapat dibatalkan atau bukan milik akun Anda.');
                }
            }
            $this->redirect('my_bookings.php');
        }

        $this->view('booking/my_bookings', [
            'my_bookings' => $this->bookingModel->getByUserId($_SESSION['user_id']),
        ]);
    }

    /** Menjalankan proses valid requested date pada booking. */
    private function validRequestedDate(string $requestedDate): string {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestedDate)
            && strtotime($requestedDate) >= strtotime(date('Y-m-d'))
            ? $requestedDate
            : date('Y-m-d');
    }

    /** Mengambil data booking input. */
    private function readBookingInput(array $source): array {
        $activityType = trim((string) ($source['activity_type'] ?? 'internal_divisi'));
        if (!array_key_exists($activityType, SawService::ACTIVITY_TYPES)) {
            $activityType = 'internal_divisi';
        }

        return [
            'room_id' => (int) ($source['room_id'] ?? 0),
            'user_name' => trim((string) ($source['user_name'] ?? '')),
            'user_dept' => trim((string) ($source['user_dept'] ?? '')),
            'title' => trim((string) ($source['title'] ?? '')),
            'date' => trim((string) ($source['date'] ?? '')),
            'start_time' => trim((string) ($source['start_time'] ?? '')),
            'end_time' => trim((string) ($source['end_time'] ?? '')),
            'purpose' => trim((string) ($source['purpose'] ?? '')),
            'activity_type' => $activityType,
            'attendees_count' => (int) ($source['attendees_count'] ?? 1),
        ];
    }

    /** Memvalidasi booking input. */
    private function validateBookingInput(array $input): string {
        if (!$input['room_id'] || $input['title'] === '' || $input['date'] === ''
            || $input['start_time'] === '' || $input['end_time'] === ''
            || $input['user_name'] === '' || $input['user_dept'] === '') {
            return 'Harap isi semua kolom wajib (*)!';
        }

        $dateValue = DateTimeImmutable::createFromFormat('!Y-m-d', $input['date']);
        if (!$dateValue || $dateValue->format('Y-m-d') !== $input['date'] || $input['date'] < date('Y-m-d')) {
            return 'Tanggal pemesanan tidak valid atau sudah lewat!';
        }

        $timePattern = '/^(?:[01]\d|2[0-3]):[0-5]\d$/';
        if (!preg_match($timePattern, $input['start_time'])
            || !preg_match($timePattern, $input['end_time'])
            || $input['end_time'] <= $input['start_time']) {
            return 'Waktu selesai harus lebih lambat dari waktu mulai!';
        }

        if ($input['date'] === date('Y-m-d') && $input['start_time'] <= date('H:i')) {
            return 'Waktu mulai pemesanan tidak boleh mendahului waktu saat ini (sudah terlewat). Silakan sesuaikan jam pemesanan Anda.';
        }

        if ($input['attendees_count'] < 1 || $input['attendees_count'] > 100) {
            return 'Jumlah peserta harus antara 1 dan 100 orang.';
        }
        return '';
    }

    /** Menjalankan proses booking result error pada booking. */
    private function bookingResultError(array $result): string {
        $reason = $result['reason'] ?? 'database_error';
        if ($reason === 'past_time') {
            return $result['message'] ?? 'Waktu mulai pemesanan tidak boleh mendahului waktu saat ini (sudah terlewat). Silakan sesuaikan jam pemesanan Anda.';
        }
        if ($reason === 'confirmed_conflict') {
            $conflict = $result['conflict'];
            return 'Ruangan sudah terkonfirmasi untuk jadwal '
                . substr($conflict['start_time'], 0, 5) . '–' . substr($conflict['end_time'], 0, 5)
                . '. Pilih ruangan atau waktu lain.';
        }
        if ($reason === 'maintenance') {
            return 'Ruangan sedang dalam perawatan dan belum dapat dipesan.';
        }
        if ($reason === 'insufficient_capacity') {
            $room = $result['room'];
            return 'Jumlah peserta melebihi kapasitas maksimum ' . $room['name'] . ' (' . $room['capacity'] . ' orang).';
        }
        if ($reason === 'room_not_found') {
            return 'Ruangan yang dipilih tidak ditemukan.';
        }
        return 'Gagal menyimpan perubahan, terjadi kesalahan database.';
    }

    /** Memeriksa apakah edit booking terpenuhi. */
    private function canEditBooking(array $booking): bool {
        $status = (string) ($booking['status'] ?? '');
        if (is_admin()) {
            return in_array($status, ['pending', 'confirmed'], true);
        }

        return $status === 'pending'
            && (int) ($booking['user_id'] ?? 0) === (int) $_SESSION['user_id'];
    }

    /** Menentukan edit return url. */
    private function resolveEditReturnUrl(string $requested): string {
        return $requested === 'admin_bookings.php' && is_admin()
            ? 'admin_bookings.php'
            : 'my_bookings.php';
    }

}
