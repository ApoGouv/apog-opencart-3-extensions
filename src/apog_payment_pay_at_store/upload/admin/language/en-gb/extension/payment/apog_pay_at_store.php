<?php
// Module Name
$_['payment_name'] = 'Pay At Store';

// Heading
$_['heading_title'] = 'Apog: ' . $_['payment_name'];

// Text
$_['text_extension'] = 'Extensions';
$_['text_success']   = 'Success: You have modified the payment method ' . $_['payment_name'] . '!';
$_['text_edit']      = 'Edit payment method ' . $_['payment_name'];

// Entry
$_['entry_status']                    = 'Status';
$_['entry_sort_order']                = 'Sort Order';
$_['entry_order_status']              = 'Order Status';
$_['entry_geo_zone']                  = 'Geo Zone';
$_['entry_min_total']                 = 'Minimum Total';

$_['entry_excluded_stores']           = 'Disable for Stores';
$_['entry_excluded_customer_groups']  = 'Disable for Customer Groups';
$_['entry_excluded_shipping_methods'] = 'Disable for Shipping Methods';
$_['entry_enable_logging']            = 'Enable Debug Logging';

// Help
$_['help_excluded_stores']           = 'Disable this payment method for the selected stores.';
$_['help_excluded_customer_groups']  = 'Disable this payment method for the selected customer groups.';
$_['help_excluded_shipping_methods'] = 'Disable this payment method when the selected shipping methods are used.';
$_['help_min_total']                 = 'The minimum order total required before this payment method becomes available.';
$_['help_instructions']              = 'Optional instructions shown to the customer during checkout.';
$_['help_enable_logging']            = 'Enable debug logging for this payment module. Useful for troubleshooting.';

// Sections
$_['text_general']       = 'General';
$_['text_conditions']    = 'Conditions';
$_['text_restrictions']  = 'Restrictions';
$_['entry_instructions'] = 'Instructions';

// Error
$_['error_permission'] = 'Warning: You do not have permission to modify the payment method ' . $_['payment_name'] . '!';