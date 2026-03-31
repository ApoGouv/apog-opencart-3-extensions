<?php
require_once(DIR_SYSTEM . 'library/apog/bootstrap.php');

class ControllerExtensionPaymentApog{{ClassName}} extends Controller {
    use Apog\Traits\Back\Controller\ControllerHelperTrait;
    use Apog\Traits\Back\Controller\ConfigHelperTrait;
    use Apog\Traits\Back\Controller\FormOptionsTrait;

    protected $ext_type = 'payment';
    protected $module_code = 'apog_{{module_code}}';

    private $error = array();

    public function index() {
        if (!isset($this->ext_type, $this->module_code)) {
            throw new \Exception('Module context not properly defined.');
        }

        // --- Phase 1: Config Schema ---

        $global_fields = [
            'excluded_stores'           => [],
            'excluded_customer_groups'  => [],
            'excluded_shipping_methods' => [],
            'status'                    => 0,
            'sort_order'                => 0,
            'order_status_id'           => 0,
            'geo_zone_id'               => 0,
            'min_total'                 => 0.00,
            'enable_logging'            => 0,
        ];

        // --- Phase 2: Request Handling ---

        $this->load->language('extension/' . $this->ext_type . '/' . $this->module_code);
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validatePermission()) {
            $this->model_setting_setting->editSetting(
                $this->ext_type . '_' . $this->module_code,
                $this->request->post
            );

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect(
                $this->url->link(
                    'marketplace/extension',
                    'user_token=' . $this->session->data['user_token'] . '&type=' . $this->ext_type,
                    true
                )
            );
        }

        // --- Phase 3: Data Assembly ---

        $data = $this->getStandardData();
        $data['module_code'] = $this->module_code;
        $data['error_warning'] = $this->getErrorWarning();

        // Global config values
        foreach ($global_fields as $field => $default) {
            $field_key = $this->getConfigKey($field);
            $data[$field_key] = $this->getConfigValue($field, $default);
        }

        // --- Instructions (multilingual, optional) ---
        $this->load->model('localisation/language');
        $data['languages'] = $this->model_localisation_language->getLanguages();

        foreach ($data['languages'] as $language) {
            $lang_id = $language['language_id'];
            $instructions_field = 'instructions_' . $lang_id;
            $instructions_field_key = $this->getConfigKey($instructions_field);
            $data[$instructions_field_key] = $this->getConfigValue($instructions_field, '');
        }

        // --- Phase 4: UI Data ---

        $data['geo_zones']        = $this->loadGeoZones();
        $data['stores']           = $this->loadStores();
        $data['customer_groups']  = $this->loadCustomerGroups();
        $data['shipping_methods'] = $this->loadShippingMethods();
        $data['order_statuses']   = $this->loadOrderStatuses();

        // Standard layout
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput(
            $this->load->view('extension/' . $this->ext_type . '/' . $this->module_code, $data)
        );
    }
}
