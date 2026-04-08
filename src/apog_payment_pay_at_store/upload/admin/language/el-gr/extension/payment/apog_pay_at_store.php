<?php
// Module Name
$_['payment_name'] = 'Πληρωμή στο κατάστημα';

// Heading
$_['heading_title'] = 'Apog: ' . $_['payment_name'];

// Text
$_['text_extension'] = 'Επεκτάσεις';
$_['text_success']   = 'Επιτυχία: Έχετε επεξεργαστεί τη μέθοδο πληρωμής ' . $_['payment_name'] . '!';
$_['text_edit']      = 'Επεξεργασία μεθόδου πληρωμής ' . $_['payment_name'];

// Entry
$_['entry_status']                    = 'Κατάσταση';
$_['entry_sort_order']                = 'Σειρά Ταξινόμησης';
$_['entry_order_status']              = 'Κατάσταση Παραγγελίας';
$_['entry_geo_zone']                  = 'Γεωγραφική Ζώνη';
$_['entry_min_total']                 = 'Ελάχιστο Ποσό';

$_['entry_excluded_stores']           = 'Απενεργοποίηση για Καταστήματα';
$_['entry_excluded_customer_groups']  = 'Απενεργοποίηση για Ομάδες Πελατών';
$_['entry_excluded_shipping_methods'] = 'Απενεργοποίηση για Τρόπους Αποστολής';
$_['entry_enable_logging']            = 'Ενεργοποίηση Καταγραφής Debug';

// Help
$_['help_excluded_stores']           = 'Απενεργοποιεί αυτή τη μέθοδο πληρωμής για τα επιλεγμένα καταστήματα.';
$_['help_excluded_customer_groups']  = 'Απενεργοποιεί αυτή τη μέθοδο πληρωμής για τις επιλεγμένες ομάδες πελατών.';
$_['help_excluded_shipping_methods'] = 'Απενεργοποιεί αυτή τη μέθοδο πληρωμής όταν χρησιμοποιούνται οι επιλεγμένοι τρόποι αποστολής.';
$_['help_min_total']                 = 'Το ελάχιστο ποσό παραγγελίας για να είναι διαθέσιμη αυτή η μέθοδος πληρωμής.';
$_['help_instructions']              = 'Προαιρετικές οδηγίες που εμφανίζονται στον πελάτη κατά το checkout.';
$_['help_enable_logging']            = 'Ενεργοποιεί το debug logging για αυτό τον τρόπο πληρωμής. Χρήσιμο για αποσφαλμάτωση.';

// Sections
$_['text_general']       = 'Γενικά';
$_['text_conditions']    = 'Προϋποθέσεις';
$_['text_restrictions']  = 'Περιορισμοί';
$_['entry_instructions'] = 'Οδηγίες';

// Error
$_['error_permission'] = 'Ειδοποίηση: Δεν έχετε άδεια να επεξεργαστείτε τη μέθοδο πληρωμής ' . $_['payment_name'] . '!';