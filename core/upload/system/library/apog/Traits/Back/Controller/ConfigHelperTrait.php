<?php
/**
 * @package   Apog OpenCart 3 Extensions
 * @author    Apostolos Gouvalas
 * @copyright 2026 Apostolos Gouvalas
 * @license   Apache-2.0
 */

namespace Apog\Traits\Back\Controller;

/**
 * Trait ConfigHelperTrait
 *
 */
trait ConfigHelperTrait
{
    protected function getConfigKey($key)
    {
        return $this->ext_type . '_' . $this->module_code . '_' . $key;
    }

    /**
     * Retrieves a configuration value prioritizing POST data, then DB config.
     *
     * @param string $key     The specific field key (without prefix).
     * @param mixed  $default Fallback value.
     *
     * @return mixed
     */
    protected function getConfigValue($key, $default = '')
    {
        $full_key = $this->getConfigKey($key);

        if (isset($this->request->post[$full_key])) {
            return $this->request->post[$full_key];
        }

        $value = $this->config->get($full_key);

        return ($value !== null) ? $value : $default;
    }
}
