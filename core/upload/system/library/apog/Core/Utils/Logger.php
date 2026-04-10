<?php
/**
 * @package   Apog OpenCart 3 Extensions
 * @author    Apostolos Gouvalas
 * @copyright 2026 Apostolos Gouvalas
 * @license   Apache-2.0
 */

namespace Apog\Core\Utils;

/**
 * ApogLogger
 *
 * Shared logging utility for Apog modules.
 * Can log messages to daily module-specific log files.
 * Supports shipping, payment, or any other module type.
 */
class Logger {

    /** @var string Base directory for logs */
    private static $baseDir = DIR_LOGS . 'apog/';

     /**
     * Log a message to a daily file for a specific module type.
     *
     * Creates a folder for the module type if it doesn't exist, and appends the message
     * to a log file named {moduleType}_{moduleCode}_YYYY-MM-DD.log.
     *
     * @param string $message     The message text to log
     * @param string $moduleCode  Module code (e.g., 'apog_abc'); used in the filename
     * @param string $moduleType  Module type/folder (default: 'shipping'); allows different modules
     * @param string $type        Log level/type (default: 'info'); e.g., 'info', 'debug', 'error'
     *
     * @return void
     */
    public static function log(
        string $message, 
        string $moduleCode, 
        string $moduleType = 'shipping', 
        string $type = 'info'
    ): void {
        try {
            // Prepare folder path
            $dir = self::$baseDir . $moduleType . '/';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            // Log file: {moduleType}_{moduleCode}_YYYY-MM-DD.log
            $date = date('Y-m-d');
            $file = $dir . "{$moduleType}_{$moduleCode}_{$date}.log";

            // Format message with timestamp
            $time = date('Y-m-d H:i:s');
            $formatted = "[{$time}] [{$type}] {$message}" . PHP_EOL;

            // Append to file
            file_put_contents($file, $formatted, FILE_APPEND | LOCK_EX);
        } catch (\Exception $e) {
            // Silent fail; logging should not break the application
        }
    }

    /**
     * Cleanup old log files for a specific module type.
     *
     * Deletes all logs in the folder for the given module type older than $days.
     * Useful for housekeeping and limiting disk usage.
     *
     * @param string $moduleType  Module type/folder (default: 'shipping'); allows cleanup for other modules
     * @param int    $days        Number of days to retain logs (default: 15)
     *
     * @return void
     */
    public static function cleanup(string $moduleType = 'shipping', int $days = 15): void {
        $dir = self::$baseDir . $moduleType . '/';
        if (!is_dir($dir)) return;

        $files = glob($dir . '*.log');
        $now   = time();

        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file)) > ($days * 86400)) {
                @unlink($file);
            }
        }
    }
}
