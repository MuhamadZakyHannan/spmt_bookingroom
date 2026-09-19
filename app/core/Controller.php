<?php
/**
 * Base Controller Class
 */
require_once __DIR__ . '/SawService.php';

class Controller {
    /**
     * Load Model
     */
    public function model($modelName) {
        $modelFile = __DIR__ . '/../models/' . $modelName . '.php';
        if (file_exists($modelFile)) {
            require_once $modelFile;
            return new $modelName();
        }
        throw new Exception("Model {$modelName} not found.");
    }

    /**
     * Render View
     */
    public function view($viewPath, $data = []) {
        $viewFile = __DIR__ . '/../views/' . $viewPath . '.php';
        if (file_exists($viewFile)) {
            extract($data);
            require_once $viewFile;
        } else {
            throw new Exception("View {$viewPath} not found.");
        }
    }

    /**
     * Redirect helper
     */
    public function redirect($url) {
        header("Location: " . $url);
        exit;
    }

    /**
     * Auth Guard
     */
    public function requireAuth() {
        if (!is_logged_in()) {
            set_flash('danger', 'Silakan login terlebih dahulu untuk mengakses halaman ini.');
            $this->redirect('login.php');
        }
    }

    /**
     * Admin Guard
     */
    public function requireAdmin() {
        $this->requireAuth();
        if (!is_admin()) {
            set_flash('danger', 'Akses ditolak. Anda memerlukan hak akses Administrator.');
            $this->redirect('dashboard.php');
        }
    }

    /**
     * Check if request is POST
     */
    public function isPost() {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    }

    /**
     * Check if request is AJAX
     */
    public function isAjax() {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_POST['is_ajax']) && $_POST['is_ajax'] === '1')
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    }

    /**
     * CSRF Guard
     */
    public function validateCsrf($fallbackRedirect = null) {
        if ($this->isPost()) {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if (!verify_csrf_token($token)) {
                if ($this->isAjax()) {
                    $this->jsonResponse([
                        'success' => false,
                        'error' => 'Sesi keamanan kedaluwarsa atau token CSRF tidak valid. Silakan muat ulang halaman.'
                    ], 403);
                } else {
                    set_flash('danger', 'Permintaan ditolak: Token keamanan (CSRF) tidak valid atau sesi telah berakhir. Silakan ulangi.');
                    if ($fallbackRedirect) {
                        $this->redirect($fallbackRedirect);
                    } else {
                        $referer = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
                        $this->redirect($referer);
                    }
                }
                return false;
            }
        }
        return true;
    }

    /**
     * JSON Response Helper
     */
    public function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        exit;
    }
}
