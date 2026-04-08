<?php
/**
 * Include our library bootstrap to set up autoloading and any necessary initialization.
 */
require_once(DIR_SYSTEM . 'library/apog/bootstrap.php');

use Apog\Core\Payment\PaymentService;

/**
 * Class ModelExtensionPaymentApogCod
 *
 * Provides payment method data for OpenCart checkout.
 * Delegates availability logic to PaymentService.
 */
class ModelExtensionPaymentApogCod extends Model {

    /**
     * @var string Unique module code
     */
    protected $module_code = 'apog_cod';

    public function getMethod(array $address, float $total): array {
        $this->load->language('extension/payment/' . $this->module_code);

        $paymentService = new PaymentService($this->registry, $this->module_code);

        if (!$paymentService->isAvailable($address, $total)) {
            return [];
        }

        return [
            'code'       => $this->module_code,
            'title'      => $this->language->get('text_title'),
            'terms'      => '',
            'sort_order' => $this->config->get('payment_' . $this->module_code . '_sort_order')
        ];
    }
}
