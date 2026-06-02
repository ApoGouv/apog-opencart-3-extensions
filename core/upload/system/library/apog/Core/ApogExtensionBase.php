<?php
/**
 * @package   Apog OpenCart 3 Extensions
 * @author    Apostolos Gouvalas
 * @copyright 2026 Apostolos Gouvalas
 * @license   Apache-2.0
 */

namespace Apog\Core;

use Apog\Core\Utils\Logger;

/**
 * Class ApogExtensionBase
 *
 * Base class for all Apog extension classes (payment, shipping, etc.).
 * Provides:
 * - Registry access
 * - Module code handling
 * - Config helper method
 * - Logging helper
 *
 * @package Apog\Core
 */
abstract class ApogExtensionBase {

    /** @var \Registry OpenCart registry */
    protected $registry;

    /** @var string Module code (e.g., 'apog_acs', 'apog_cod') */
    protected $moduleCode;

    /** @param string $type Extension type (e.g., 'payment', 'shipping') */
    protected $extensionType;

    /** @var bool Whether logging is enabled for this module */
    protected $loggingEnabled;

    /**
     * Base constructor
     *
     * @param \Registry $registry OpenCart registry
     * @param string $moduleCode Module code
     * @param string $extensionType Extension type (e.g., 'payment', 'shipping')
     */
    public function __construct($registry, string $moduleCode, string $extensionType) {
        $this->registry      = $registry;
        $this->moduleCode    = $moduleCode;
        $this->extensionType = $extensionType;

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
     * Retrieves extension configuration value
     *
     * Format:
     *   {extensionType}_{moduleCode}_{key}
     *
     * Example:
     *   payment_apog_cod_status
     *
     * Also provides a fallback default value if the config key is not set.
     *
     * @param string $key     Config key suffix (without prefix)
     * @param mixed  $default Default value returned if config is null
     *
     * @return mixed
     */
    protected function cfg($key, $default = null) {
        $value = $this->config->get("{$this->extensionType}_{$this->moduleCode}_{$key}");
        return $value !== null ? $value : $default;
    }

    /**
     * Extracts the base method code from a full OpenCart method code.
     *
     * Examples:
     *   flat.flat        → flat
     *   apog_cod.cod     → apog_cod
     *   cod              → cod
     *
     * @param string $code Full method code
     *
     * @return string Base code (before first dot)
     */
    protected function getBaseMethodCode(string $code): string {
        return explode('.', $code, 2)[0];
    }

    /**
     * Resolves the effective customer group ID for the current checkout context.
     *
     * This method determines the active customer group by inspecting multiple
     * possible sources in order of priority, taking into account both
     * OpenCart core behavior and Journal checkout AJAX overrides.
     *
     * Priority order:
     *
     * 1. POST data (direct user selection during checkout)
     *    - `customer_group_id`
     *    - `order_data[customer_group_id]` (Journal AJAX structure)
     *
     * 2. Session data (persisted checkout state)
     *    - `session->data['customer_group_id']`
     *    - `session->data['order_data']['customer_group_id']`
     *
     * 3. Logged-in customer account group
     *    - `$this->customer->getGroupId()`
     *
     * 4. Runtime config fallback (OpenCart/Journal mutated state)
     *    - `config_customer_group_id`
     *
     * Note:
     * - Journal Checkout may mutate `config_customer_group_id` during AJAX sync,
     *   making it reflect the latest checkout state rather than store configuration.
     * - This method ensures compatibility with both default OpenCart checkout
     *   and Journal-based checkout flows.
     * - Returned value is always cast to integer for strict comparison safety.
     *
     * @return int The resolved customer group ID for the current request context.
     */
    protected function resolveCustomerGroupId(): int {
        // 1. POST (user action in current request)
        if (isset($this->request->post['customer_group_id'])) {
            return (int)$this->request->post['customer_group_id'];
        }

        // Journal uses nested structure in some AJAX calls
        if (isset($this->request->post['order_data']['customer_group_id'])) {
            return (int)$this->request->post['order_data']['customer_group_id'];
        }

        // 2. Session (OpenCart / Journal persistence layer)
        if (isset($this->session->data['customer_group_id'])) {
            return (int)$this->session->data['customer_group_id'];
        }

        if (isset($this->session->data['order_data']['customer_group_id'])) {
            return (int)$this->session->data['order_data']['customer_group_id'];
        }

        // 3. Logged-in customer
        if ($this->customer->isLogged()) {
            return (int)$this->customer->getGroupId();
        }

        // 4. Journal / OpenCart runtime mutated config (IMPORTANT FALLBACK)
        return (int)$this->config->get('config_customer_group_id');
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
            Logger::log($message, $this->moduleCode, $this->extensionType, $type);
        }
    }
}
