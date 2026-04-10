<?php
/**
 * @package   Apog OpenCart 3 Extensions
 * @author    Apostolos Gouvalas
 * @copyright 2026 Apostolos Gouvalas
 * @license   Apache-2.0
 */

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