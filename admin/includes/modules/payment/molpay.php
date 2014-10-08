<?php
/**
 * MOLPay TomatoCart Plugin
 * 
 * @package Payment Gateway
 * @author MOLPay Technical Team <technical@molpay.com>
 * @version 1.0.0
 */
class osC_Payment_molpay extends osC_Payment_Admin {

    
    var $_title;
    var $_code = 'molpay';
    var $_author_name = 'molpay';
    var $_author_www = 'http://www.tomatocart.com';
    var $_status = false;
    
    /**
     * Constructor
     */
    function osC_Payment_molpay() {
        global $osC_Language;

        $this->_title = $osC_Language->get('payment_molpay_title');
        $this->_description = $osC_Language->get('payment_molpay_description');
        $this->_method_title = $osC_Language->get('payment_molpay_method_title');
        $this->_status = (defined('MODULE_PAYMENT_MOLPAY_STATUS') && (MODULE_PAYMENT_MOLPAY_STATUS == '1') ? true : false);
        $this->_sort_order = (defined('MODULE_PAYMENT_MOLPAY_SORT_ORDER') ? MODULE_PAYMENT_MOLPAY_SORT_ORDER : null);
    }
    function isInstalled() {
        return (bool)defined('MODULE_PAYMENT_MOLPAY_STATUS');
    }
    function install() {
        global $osC_Database, $osC_Language;

        parent::install();

        $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Molpay', 'MODULE_PAYMENT_MOLPAY_STATUS', '-1', 'Do you want to accept Molpay payments?', '6', '0', 'osc_cfg_set_boolean_value(array(1, -1))', now())");
        $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('MOLPay Merchant ID:', 'MODULE_PAYMENT_MOLPAY_MERCHANT_ID', '','MOLPay Merchant ID.', '6', '0', now())");
        $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('MOLPay Verify Key:', 'MODULE_PAYMENT_MOLPAY_MERCHANT_KEY', '', 'Please refer to your MOLPay Merchant Profile for this key.', '6', '0', now())");
        $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_MOLPAY_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
        $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment Zone', 'MODULE_PAYMENT_MOLPAY_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'osc_cfg_use_get_zone_class_title', 'osc_cfg_set_zone_classes_pull_down_menu', now())");
        $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_MOLPAY_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '0', 'osc_cfg_set_order_statuses_pull_down_menu', 'osc_cfg_use_get_order_status_title', now())");
    }
    function getKeys() {
        if (!isset($this->_keys)) {
            $this->_keys = array(
                'MODULE_PAYMENT_MOLPAY_STATUS',
                'MODULE_PAYMENT_MOLPAY_MERCHANT_ID',
                'MODULE_PAYMENT_MOLPAY_MERCHANT_KEY',
                'MODULE_PAYMENT_MOLPAY_ZONE',
                'MODULE_PAYMENT_MOLPAY_ORDER_STATUS_ID',
                'MODULE_PAYMENT_MOLPAY_SORT_ORDER');
        }

        return $this->_keys;
    }  
}
?>

