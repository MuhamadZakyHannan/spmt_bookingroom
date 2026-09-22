<?php

$applicationDirectory = dirname(__DIR__);

spl_autoload_register(
    /** Memuat class aplikasi dari lapisan core, service, model, dan controller. */
    static function (string $class) use ($applicationDirectory): void {
        $paths = [
            $applicationDirectory . '/core/' . $class . '.php',
            $applicationDirectory . '/services/' . $class . '.php',
            $applicationDirectory . '/models/' . $class . '.php',
            $applicationDirectory . '/controllers/' . $class . '.php',
        ];

        foreach ($paths as $file) {
            if (is_file($file)) {
                require_once $file;
                return;
            }
        }
    }
);
