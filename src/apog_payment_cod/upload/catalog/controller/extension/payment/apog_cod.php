<?php
class ControllerExtensionPaymentApogCod extends Controller {

    /**
     * @var string Unique module code
     */
    protected $module_code = 'apog_cod';

    public function index() {
        $this->load->language('extension/payment/' . $this->module_code);

        $language_id = $this->config->get('config_language_id');

        $instructions = $this->config->get('payment_' . $this->module_code . '_instructions_' . $language_id);

        $data['instructions'] = !empty($instructions) ? nl2br($instructions) : '';

        return $this->load->view('extension/payment/' . $this->module_code, $data);
    }

    public function confirm() {
        $json = [];

        if (
            isset($this->session->data['payment_method']['code']) &&
            $this->session->data['payment_method']['code'] === $this->module_code
        ) {
            $this->load->language('extension/payment/' . $this->module_code);
            $this->load->model('checkout/order');

            $language_id = $this->config->get('config_language_id');

            $instructions = $this->config->get('payment_' . $this->module_code . '_instructions_' . $language_id);

            $comment = '';

            if (!empty($instructions)) {
                $comment .= $this->language->get('text_instruction') . "\n\n";
                $comment .= $instructions . "\n\n";
            }

            $text_payment = trim($this->language->get('text_payment'));
            if (!empty($text_payment)) {
                $comment .= $text_payment;
            }

            $this->model_checkout_order->addOrderHistory(
                $this->session->data['order_id'],
                $this->config->get('payment_' . $this->module_code . '_order_status_id'),
                $comment,
                true
            );

            $json['redirect'] = $this->url->link('checkout/success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
