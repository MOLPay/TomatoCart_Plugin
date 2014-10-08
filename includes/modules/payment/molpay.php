<?php

class osC_Payment_molpay extends osC_Payment {
    var $_title,
        $_code = 'molpay',
        $_status = true,
        $_sort_order,
        $_order_id;

    // class constructor
    function osC_Payment_molpay() {
        global $osC_Database, $osC_Language, $osC_ShoppingCart;

        $this->_title = $osC_Language->get('payment_molpay_title');
        $this->_method_title = $osC_Language->get('payment_molpay_method_title');
        $this->_sort_order = MODULE_PAYMENT_MOLPAY_SORT_ORDER;
        $this->_status = ((MODULE_PAYMENT_MOLPAY_STATUS == '1') ? true : false);

        if (MODULE_PAYMENT_MOLPAY_DIRECT == 1) {
            $this->form_action_url = 'https://www.onlinepayment.com.my/MOLPay/pay/'.MODULE_PAYMENT_MOLPAY_MERCHANT_ID.'/';
        } else {
            $this->form_action_url = 'https://www.onlinepayment.com.my/MOLPay/pay/'.MODULE_PAYMENT_MOLPAY_MERCHANT_ID.'/';
        }

        if ($this->_status === true) {
            $this->order_status = MODULE_PAYMENT_MOLPAY_ORDER_STATUS_ID > 0 ? (int)MODULE_PAYMENT_MOLPAY_ORDER_STATUS_ID : (int)ORDERS_STATUS_PAID;

            if ((int)MODULE_PAYMENT_MOLPAY_ZONE > 0) {
                $check_flag = false;

                $Qcheck = $osC_Database->query('select zone_id from :table_zones_to_geo_zones where geo_zone_id = :geo_zone_id and zone_country_id = :zone_country_id order by zone_id');
                $Qcheck->bindTable(':table_zones_to_geo_zones', TABLE_ZONES_TO_GEO_ZONES);
                $Qcheck->bindInt(':geo_zone_id', MODULE_PAYMENT_MOLPAY_ZONE);
                $Qcheck->bindInt(':zone_country_id', $osC_ShoppingCart->getBillingAddress('country_id'));
                $Qcheck->execute();

                while ($Qcheck->next()) {
                    if ($Qcheck->valueInt('zone_id') < 1) {
                        $check_flag = true;
                    break;
                    } elseif ($Qcheck->valueInt('zone_id') == $osC_ShoppingCart->getBillingAddress('zone_id')) {
                        $check_flag = true;
                        break;
                    }
                }

                if ($check_flag == false) {
                    $this->_status = false;
                }
            }
        }
    }

    function selection() {
        return array('id' => $this->_code,'module' => $this->_method_title);
    }

    function pre_confirmation_check() {
        global $osC_ShoppingCart;

        $cart_id = $osC_ShoppingCart->getCartID();
        if (empty($cart_id)) {
            $osC_ShoppingCart->generateCartID();
        }
    }

    function confirmation() {
        $this->_order_id = osC_Order::insert();
    }

    function process_button() {
        global $osC_Customer, $osC_Currencies, $osC_ShoppingCart, $osC_Tax, $osC_Language;

        $process_button_string = '';
        //vcode = md5( amount & merchantID & orderID & verify_key)
        $vcode = md5($osC_Currencies->formatRaw($osC_ShoppingCart->getTotal()).MODULE_PAYMENT_MOLPAY_MERCHANT_ID.$this->_order_id.MODULE_PAYMENT_MOLPAY_MERCHANT_KEY);
        $params = array(
            'merchant_id' => MODULE_PAYMENT_MOLPAY_MERCHANT_ID,
            'vcode' => $vcode,                                            
            //'returnurl' =>  HTTPS_SERVER . DIR_WS_HTTPS_CATALOG . FILENAME_CHECKOUT . '?callback&module=' . $this->_code
            //'returnurl' =>  HTTPS_SERVER . DIR_WS_HTTPS_CATALOG . FILENAME_CHECKOUT . '?process_return&module=' . $this->_code
            'returnurl' =>  HTTPS_SERVER . DIR_WS_HTTPS_CATALOG . FILENAME_CHECKOUT . '?process'
        );

        if ($osC_ShoppingCart->hasShippingAddress()) {
            $params['bill_name'] = $osC_ShoppingCart->getShippingAddress('firstname');
        } 
        else {
            $params['bill_name'] = $osC_ShoppingCart->getShippingAddress('firstname');
        }     
        $params['orderid'] = $this->_order_id;
        $params['amount'] = $osC_Currencies->formatRaw($osC_ShoppingCart->getTotal());
        $params['bill_email'] = $osC_Customer->getEmailAddress();
        $params['bill_mobile']= $osC_ShoppingCart->getBillingAddress('telephone_number');

        if ($osC_ShoppingCart->hasContents()) {
            $i = 1;
            foreach($osC_ShoppingCart->getProducts() as $product) {                      
                $arr_product[$i] = $product['name'] . ' (' . (int)$product['quantity'] . 'x )';
                $i++;
            }
        }

        $params['bill_desc'] = implode(",\n", $arr_product);            
        $process_button_string = '';
        $secureString = '';
        foreach($params as $k=>$v){
            $secureString .= $k.'='.urlencode(trim($v)).'&';
        }
        $secureString = substr( $secureString, 0, -1 );
        $params['signature'] = md5($secureString);
        foreach ($params as $key => $value) {
            $process_button_string .= osc_draw_hidden_field($key, $value);
        }
        return $process_button_string;
    }

    function process() 
    {

        global $osC_Database, $osC_Currencies, $osC_ShoppingCart, $messageStack, $osC_Language;
            $vkey = MODULE_PAYMENT_MOLPAY_MERCHANT_KEY;

            $tranID = $_POST['tranID'];
            $orderid = $_POST['orderid'];
            $status = $_POST['status'];
            $domain = $_POST['domain'];
            $amount = $_POST['amount'];
            $currency = $_POST['currency'];
            $appcode = $_POST['appcode'];
            $paydate = $_POST['paydate'];
            $skey = $_POST['skey'];

            $key0 = md5( $tranID.$orderid.$status.$domain.$amount.$currency );
            $key1 = md5( $paydate.$domain.$key0.$appcode.$vkey );

            //if( $skey != $key1 )
            //   $status == 0;

            if( $status == '00' ){
                //if success
                if( $skey == $key1 )
                {
                    if (isset($orderid) && ($orderid > 0)) {
                            $Qcheck = $osC_Database->query('select orders_status, currency, currency_value from :table_orders where orders_id = :orders_id');
                            $Qcheck->bindTable(':table_orders', TABLE_ORDERS);
                            $Qcheck->bindInt(':orders_id', $orderid);
                            $Qcheck->execute();

                            if ($Qcheck->numberOfRows() > 0) {
                                $Qtotal = $osC_Database->query('select value from :table_orders_total where orders_id = :orders_id and class = "total" limit 1');
                                $Qtotal->bindTable(':table_orders_total', TABLE_ORDERS_TOTAL);
                                $Qtotal->bindInt(':orders_id', $_REQUEST['orderid']);
                                $Qtotal->execute();

                                //$comments = 'MOLPay Order Successful [' . $_REQUEST['cart_id'] . '; ' . $osC_Currencies->format($_REQUEST['total']) . ')]';
                                $comments = 'MOLPay Order Successful [' . $_REQUEST['orderid'] . '; ' . $osC_Currencies->format($_REQUEST['amount']) . ')]';
                                $osC_ShoppingCart->reset(true);
                                osC_Order::process($_REQUEST['orderid'], $this->order_status, $comments);
                                osc_redirect(osc_href_link(FILENAME_CHECKOUT, 'success', 'SSL', null, null, true));
                                exit;
                            }
                        }
                }
                else{
                        //$comments = "FAILED STATUS:, PLEASE CONTACT THE SELLER. key1:".$key1." skey:".$skey." vkey:".$vkey ;
                        $comments = "FAILED STATUS:, PLEASE CONTACT THE SELLER." ;
                        $messageStack->add_session('checkout', $comments);

                        osc_redirect(osc_href_link(FILENAME_CHECKOUT, 'checkout&view=paymentInformationForm', 'SSL'));

                }
            }
            else{
                //cash channel
                if($status == '22')
                {
                                $comments = 'MOLPay Order Successful [' . $_REQUEST['orderid'] . '; ' . $osC_Currencies->format($_REQUEST['amount']) . ')]. Awaiting your physical payment';
                                $osC_ShoppingCart->reset(true);
                                $this->order_status = "Pending";
                                osC_Order::process($_REQUEST['orderid'], $this->order_status, $comments);
                                osc_redirect(osc_href_link(FILENAME_CHECKOUT, 'success', 'SSL', null, null, true));
                }
                else
                {       //if failed
                        $comments = "FAILED STATUS, PLEASE CONTACT THE SELLER";

                        $messageStack->add_session('checkout', $comments);
                        osC_Order::insertOrderStatusHistory($_REQUEST['cart_order_id'], $this->order_status, $comments);
                        osc_redirect(osc_href_link(FILENAME_CHECKOUT, 'checkout&view=paymentInformationForm', 'SSL'));
                }


            }

    }


    function callback() 
    {

        global $osC_Database, $osC_Currencies, $osC_ShoppingCart, $messageStack, $osC_Language;
            $vkey = MODULE_PAYMENT_MOLPAY_MERCHANT_KEY;

            $nbcb   = $_POST['nbcb'];
            $tranID = $_POST['tranID'];
            $orderid = $_POST['orderid'];
            $status = $_POST['status'];
            $domain = $_POST['domain'];
            $amount = $_POST['amount'];
            $currency = $_POST['currency'];
            $appcode = $_POST['appcode'];
            $paydate = $_POST['paydate'];
            $skey = $_POST['skey'];

            $key0 = md5( $tranID.$orderid.$status.$domain.$amount.$currency );
            $key1 = md5( $paydate.$domain.$key0.$appcode.$vkey );

            //if( $skey != $key1 )
            //   $status == 0;

            if( $status == '00' ){
                //if success
                if( $skey == $key1 )
                {
                    if (isset($orderid) && ($orderid > 0)) {
                            $Qcheck = $osC_Database->query('select orders_status, currency, currency_value from :table_orders where orders_id = :orders_id');
                            $Qcheck->bindTable(':table_orders', TABLE_ORDERS);
                            $Qcheck->bindInt(':orders_id', $orderid);
                            $Qcheck->execute();

                            if ($Qcheck->numberOfRows() > 0) {
                                $Qtotal = $osC_Database->query('select value from :table_orders_total where orders_id = :orders_id and class = "total" limit 1');
                                $Qtotal->bindTable(':table_orders_total', TABLE_ORDERS_TOTAL);
                                $Qtotal->bindInt(':orders_id', $_REQUEST['orderid']);
                                $Qtotal->execute();

                                //$comments = 'MOLPay Order Successful [' . $_REQUEST['cart_id'] . '; ' . $osC_Currencies->format($_REQUEST['total']) . ')]';
                                $comments = 'MOLPay Order Successful [' . $_REQUEST['orderid'] . '; ' . $osC_Currencies->format($_REQUEST['amount']) . ')]';
                                $osC_ShoppingCart->reset(true);
                                osC_Order::process($_REQUEST['orderid'], $this->order_status, $comments);
                                osc_redirect(osc_href_link(FILENAME_CHECKOUT, 'success', 'SSL', null, null, true));
                            }
                        }
                }
                else{
                        //$comments = "FAILED STATUS:, PLEASE CONTACT THE SELLER. key1:".$key1." skey:".$skey." vkey:".$vkey ;
                        $comments = "FAILED STATUS:, PLEASE CONTACT THE SELLER." ;
                        $messageStack->add_session('checkout', $comments);

                        osc_redirect(osc_href_link(FILENAME_CHECKOUT, 'checkout&view=paymentInformationForm', 'SSL'));

                }
            }
            else{
                //cash channel
                if($status == '22')
                {
                                $comments = 'MOLPay Order Successful [' . $_REQUEST['orderid'] . '; ' . $osC_Currencies->format($_REQUEST['amount']) . ')]. Awaiting your physical payment';
                                $osC_ShoppingCart->reset(true);
                                $this->order_status = "Pending";
                                osC_Order::process($_REQUEST['orderid'], $this->order_status, $comments);
                                osc_redirect(osc_href_link(FILENAME_CHECKOUT, 'success', 'SSL', null, null, true));
                }
                else
                {       //if failed
                        $comments = "FAILED STATUS, PLEASE CONTACT THE SELLER";

                        $messageStack->add_session('checkout', $comments);
                        osC_Order::insertOrderStatusHistory($_REQUEST['cart_order_id'], $this->order_status, $comments);
                        osc_redirect(osc_href_link(FILENAME_CHECKOUT, 'checkout&view=paymentInformationForm', 'SSL'));
                }


            }

            if ( $nbcb==1 ) {
                  //callback IPN feedback to notified MOLPay
                  echo "CBTOKEN:MPSTATOK";
            }

    }

}
?>