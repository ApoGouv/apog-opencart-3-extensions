<?php
// Module Name
$_['total_name'] = 'COD Fee';

// Heading
$_['heading_title'] = 'Apog: ' . $_['total_name'];

// Text
$_['text_extension']       = 'Extensions';
$_['text_success']         = 'Success: You have modified total ' . $_['total_name'] . '!';
$_['text_edit']            = 'Edit total ' . $_['total_name'];
$_['text_binding_payment'] = 'This total is bound to payment method:';

// Sections
$_['text_general']       = 'General';
$_['text_conditions']    = 'Conditions';
$_['text_restrictions']  = 'Restrictions';

// Entry
$_['entry_geo_zone']                  = 'Geo Zone';
$_['entry_tax_class']                 = 'Tax Class';
$_['entry_status']                    = 'Status';
$_['entry_sort_order']                = 'Sort Order';
$_['entry_fee']                       = 'Fee';
$_['entry_total_free']                = 'Free Threshold';
$_['entry_binding_payment_code']      = 'Bind to Payment Method';
$_['entry_excluded_stores']           = 'Exclude Stores';
$_['entry_excluded_customer_groups']  = 'Exclude Customer Groups';
$_['entry_excluded_shipping_methods'] = 'Exclude Shipping Methods';
$_['entry_excluded_payment_methods']  = 'Exclude Payment Methods';
$_['entry_enable_logging']            = 'Enable Debug Logging';

// Help
$_['help_binding_payment_code']      = 'This total will only apply when the specified payment method is used.';
$_['help_total_free']                = 'Minimum order total for zero fee in this geo zone.';
$_['help_excluded_stores']           = 'Disable this total for selected stores.';
$_['help_excluded_customer_groups']  = 'Disable this total for selected customer groups.';
$_['help_excluded_shipping_methods'] = 'Disable this total for selected shipping methods.';
$_['help_excluded_payment_methods']  = 'Disable this total for selected payment methods.';
$_['help_enable_logging']            = 'Enable debug logging for this module. Useful for troubleshooting.';

// Error
$_['error_permission'] = 'Warning: You do not have permission to modify total ' . $_['total_name'] . '!';
