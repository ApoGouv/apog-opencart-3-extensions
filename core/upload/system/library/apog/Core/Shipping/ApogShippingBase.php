<?php
namespace Apog\Core\Shipping;

use Apog\Core\Utils\Logger;

/**
 * Class ApogShippingBase
 *
 * Base class for all Apog shipping module classes.
 * Provides:
 * - Registry access
 * - Module code handling
 * - Config helper method
 * - Logging helper
 *
 * @package Apog\Core\Shipping
 */
abstract class ApogShippingBase {

    /** @var \Registry OpenCart registry */
    protected $registry;

    /** @var string Module code (e.g., 'apog_acs') */
    protected $code;

    /** @var bool Whether logging is enabled for this module */
    protected $loggingEnabled;

    /**
     * Base constructor
     *
     * @param \Registry $registry OpenCart registry
     * @param string $code Module code
     */
    public function __construct($registry, string $code) {
        $this->registry = $registry;
        $this->code     = $code;

        // Cache logging flag
        $this->loggingEnabled = (bool)$this->cfg('enable_logging');
    }

    /**
     * Magic getter to access registry objects directly (e.g., config, customer, session)
     *
     * @param string $key
     * 
     * @return mixed
     */
    public function __get($key) {
        return $this->registry->get($key);
    }

    /**
     * Shortcut for accessing shipping-specific config keys
     *
     * @param string $key Config key suffix (without "shipping_{code}_")
     * 
     * @return mixed Config value
     */
    protected function cfg($key) {
        return $this->config->get("shipping_{$this->code}_{$key}");
    }

    /**
     * Logs a message if module logging is enabled
     *
     * @param string $message Text to log
     * @param string $type Log level/type (e.g., 'info', 'error', 'debug')
     * 
     * @return void
     */
    protected function log(string $message, string $type = 'info'): void {
        if ($this->loggingEnabled) {
            Logger::log($message, $this->code, 'shipping', $type);
        }
    }
}
