<?php
/**
 * Include our library bootstrap to set up autoloading and any necessary initialization.
 */
require_once(DIR_SYSTEM . 'library/apog/bootstrap.php');

use Apog\Core\Payment\PaymentService;

/**
 * Class ModelExtensionPaymentApogPayAtStore
 *
 * Provides payment method data for OpenCart checkout.
 * Delegates availability logic to PaymentService.
 */
class ModelExtensionPaymentApogPayAtStore extends Model {

    /**
     * @var string Unique module code
     */
    protected $module_code = 'apog_pay_at_store';

    public function getMethod(array $address, float $total): array {
        $this->load->language('extension/payment/' . $this->module_code);

        $paymentService = new PaymentService($this->registry, $this->module_code);

        if (!$paymentService->isAvailable($address, $total)) {
            return [];
        }

        $storeName = $this->config->get('config_name');

        return [
            'code'       => $this->module_code,
            'title'      => sprintf($this->language->get('text_title'), $storeName),
            'terms'      => '',
            'sort_order' => $this->config->get('payment_' . $this->module_code . '_sort_order')
        ];
    }
}
