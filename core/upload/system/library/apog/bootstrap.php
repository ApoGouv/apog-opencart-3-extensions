<?php
if (defined('APOG_BOOTSTRAPPED')) {
    return;
}

// Safety guard
if (!defined('DIR_SYSTEM')) {
    return;
}

// Register autoloader
require_once __DIR__ . '/autoload.php';

define('APOG_BOOTSTRAPPED', true);