<?php

namespace Apog\Traits\Back\Controller;

/**
 * Trait ControllerHelperTrait
 *
 */
trait ControllerHelperTrait
{
    /**
     * Prepares standard view data including language, breadcrumbs, and form actions.
     *
     * @return array Standard data array for the view.
     */
    protected function getStandardData()
    {
        $data['heading_title'] = $this->language->get('heading_title');
        $data['user_token'] = $this->session->data['user_token'];

        // Breadcrumbs setup
        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $data['user_token'], true)
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $data['user_token'] . '&type=' . $this->ext_type, true)
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/' . $this->ext_type . '/' . $this->module_code, 'user_token=' . $data['user_token'], true)
        ];

        // Form actions
        $data['action'] = $this->url->link('extension/' . $this->ext_type . '/' . $this->module_code, 'user_token=' . $data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $data['user_token'] . '&type=' . $this->ext_type, true);

        return $data;
    }

    /**
     * Helper to return the warning error if it exists.
     *
     * @return string
     */
    protected function getErrorWarning()
    {
        return isset($this->error['warning']) ? $this->error['warning'] : '';
    }

    /**
     * Validates if the current user has modify permissions for the extension.
     *
     * @return bool True if valid.
     */
    protected function validatePermission()
    {
        if (!$this->user->hasPermission('modify', 'extension/' . $this->ext_type . '/' . $this->module_code)) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return !$this->error;
    }
}
