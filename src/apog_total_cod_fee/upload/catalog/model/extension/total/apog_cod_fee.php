<?php
require_once(DIR_SYSTEM . 'library/apog/bootstrap.php');

use Apog\Core\Total\TotalService;

class ModelExtensionTotalApogCodFee extends Model {

    protected $module_code = 'apog_cod_fee';

    public function getTotal($total) {

        $this->load->language('extension/total/' . $this->module_code);

        $service = new TotalService($this->registry, $this->module_code);
        $result = $service->getTotal();

        if (empty($result)) {
            return;
        }

        $value = (float)$result['value'];
        $tax_class_id = (int)$result['tax_class_id'];

        // --- Apply total line ---
        $total['totals'][] = [
            'code'       => $this->module_code,
            'title'      => $this->language->get('text_title'),
            'value'      => $value,
            'sort_order' => $result['sort_order']
        ];

        // --- Apply taxes (OC standard) ---
        if ($tax_class_id) {
            $tax_rates = $this->tax->getRates($value, $tax_class_id);

            foreach ($tax_rates as $tax_rate) {
                if (!isset($total['taxes'][$tax_rate['tax_rate_id']])) {
                    $total['taxes'][$tax_rate['tax_rate_id']] = $tax_rate['amount'];
                } else {
                    $total['taxes'][$tax_rate['tax_rate_id']] += $tax_rate['amount'];
                }
            }
        }

        // --- Update grand total ---
        $total['total'] += $value;
    }
}
