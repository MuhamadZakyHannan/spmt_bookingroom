<?php

require_once __DIR__ . '/../core/Controller.php';

class QrCheckinController extends Controller {
    private $qrCheckinModel;
    private $bookingModel;

    public function __construct() {
        $this->qrCheckinModel = $this->model('QrCheckinModel');
        $this->bookingModel = $this->model('BookingModel');
    }

    public function handle() {
        $token = trim($_REQUEST['token'] ?? '');
        if (!preg_match('/^[A-Za-z0-9_-]{40,60}$/', $token)) {
            $token = '';
        }

        if (!is_logged_in()) {
            if ($token !== '') {
                $_SESSION['login_redirect'] = 'qr_checkin.php?token=' . rawurlencode($token);
            }
            set_flash('info', 'Silakan login dengan akun pemilik booking untuk melanjutkan check-in QR.');
            $this->redirect('login.php');
        }

        $this->bookingModel->processAutomaticAttendanceTransitions();

        if ($this->isPost()) {
            $this->validateCsrf('qr_checkin.php?token=' . rawurlencode($token));
            $result = $this->qrCheckinModel->consume($token, (int)$_SESSION['user_id']);
            if ($result['success']) {
                set_flash('success', $result['message']);
                $this->redirect('my_bookings.php');
            }

            $context = ['valid' => false, 'message' => $result['message']];
        } else {
            $context = $this->qrCheckinModel->inspect($token, (int)$_SESSION['user_id']);
        }

        $this->view('booking/qr_checkin', [
            'token' => $token,
            'context' => $context,
        ]);
    }
}
