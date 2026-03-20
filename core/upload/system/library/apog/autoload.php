<?php
// system/library/apog/autoload.php

spl_autoload_register(function ($class) {

    $prefix = 'Apog\\';
    $base_dir = __DIR__ . '/';

    // Only handle Apog namespace
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    // Remove prefix (base namespace)
    $relative_class = substr($class, strlen($prefix));

    // Convert namespace to path
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require_once $file;
        return;
    }
});
