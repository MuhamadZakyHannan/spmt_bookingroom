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
}
