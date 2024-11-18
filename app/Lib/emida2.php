<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 9/25/17
 * Time: 5:11 PM
 */

namespace App\Lib;


class emida2
{                             
    private static $api_url = 'https://wsprd.emida.net/services/rpcrouter?wsdl';
    private static $uid = '';
    private static $pwd = '';
    private static $tid = '';
    private static $clerk_id = '';

    private static function init() {
        if (getenv('APP_ENV') == 'production') {
            self::$api_url = 'https://wsprd.emida.net/services/rpcrouter?wsdl';
            self::$uid = '';
            self::$pwd = '';
            self::$tid = '';
            self::$clerk_id = '';
        }
    }

    public static function pin($cid, $product_id, $amt, $fee)
    {
        $ret = self::pin_dist_sale($cid, $product_id, '', $amt, $fee);
        if ($ret['error_code'] != '') {
            return $ret;
        }

        if (empty($ret['pin'])) {
            return [
                'error_code' => -98,
                'error_msg' => 'Unable to retrieve pin'
            ];
        }

        if (empty($ret['vendor_tx_id'])) {
            return [
                'error_code' => -99,
                'error_msg' => 'Unable to retrieve vendor transaction ID'
            ];
        }
        return $ret;
    }

    public static function rtr($cid, $product_id, $mdn, $amt, $fee) {
        $ret = self::pin_dist_sale($cid, $product_id, $mdn, $amt, $fee);
        if ($ret['error_code'] != '') {
            return $ret;
        }

        if (empty($ret['vendor_tx_id'])) {
            return [
                'error_code' => -99,
                'error_msg' => 'Unable to retrieve vendor transaction ID'
            ];
        }
        return $ret;
    }

    public static function login() {

        ini_set('default_socket_timeout', 600 );
        $client = new \SoapClient(self::$api_url, array("trace"=>1, "exceptions"=>0, "connection_timeout" => 1 ));

        try {

            self::init();

            $res_str = $client->Login2('01', self::$uid, self::$pwd, '1');

            Helper::log('### (Emida2) login2 ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $res_xml = simplexml_load_string($res_str, null, LIBXML_NOCDATA);

            if (!empty($res_xml->ResponseCode) && $res_xml->ResponseCode != '00') {
                return [
                    'error_code' => $res_xml->ResponseCode,
                    'error_msg' => $res_xml->ResponseMessage
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'clerk_id' => isset($res_xml->ClerkId) ? $res_xml->ClerkId->__toString() : '',
                'site_id' => isset($res_xml->SiteID) ? $res_xml->SiteID->__toString() : ''
            ];

        } catch (\Exception $ex) {

            Helper::log('### (Emida2) login2 (Exception) ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] login2() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function logout() {

        ini_set('default_socket_timeout', 600 );
        $client = new \SoapClient(self::$api_url, array("trace"=>1, "exceptions"=>0, "connection_timeout" => 1 ));


        try {

            self::init();

            $res_str = $client->Logout('01', self::$uid, self::$pwd, '1');

            Helper::log('### (Emida2) Logout ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $res_xml = simplexml_load_string($res_str, null, LIBXML_NOCDATA);

            if (!empty($res_xml->ResponseCode) && $res_xml->ResponseCode != '00') {
                return [
                    'error_code' => $res_xml->ResponseCode,
                    'error_msg' => $res_xml->ResponseMessage
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => ''
            ];

        } catch (\Exception $ex) {

            Helper::log('### (Emida2) Logout (Exception) ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] Logout() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    private static function pin_dist_sale($cid, $product_id, $mdn, $amt, $fee) {

        ini_set('default_socket_timeout', 600 );
        $client = new \SoapClient(self::$api_url, array("trace"=>1, "exceptions"=>0, "connection_timeout" => 1 ));


        try {

            self::init();

            $res_str = $client->PinDistSale('01', self::$tid, self::$clerk_id, $product_id, $mdn, number_format($amt + $fee, 2), $cid, '1');

            Helper::log('### (Emida2) pin_dist_sale ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $res_xml = simplexml_load_string($res_str, null, LIBXML_NOCDATA);

            $error_code = isset($res_xml->ResponseCode) ? $res_xml->ResponseCode->__toString() : '';
            $error_msg = isset($res_xml->ResponseMessage) ? $res_xml->ResponseMessage->__toString() : '';

            if (empty($error_code)) {
                return [
                    'error_code' => -4,
                    'error_msg' => 'Unable to get response code from vendor'
                ];
            }

            if ($error_code != '00') {
                return [
                    'error_code' => $error_code,
                    'error_msg' => $error_msg
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'pin' => isset($res_xml->Pin) ? $res_xml->Pin->__toString() : '',
                'vendor_tx_id' => isset($res_xml->ControlNo) ? $res_xml->ControlNo->__toString() : ''
            ];

        } catch (\Exception $ex) {

            Helper::log('### (Emida2) pin_dist_sale (Exception) ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $message = " - product_id : " . $product_id . "<br/>";
            $message .= " - amt : " . $amt . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] pin_dist_sale() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function get_product_fee($product_id, $amt) {

        ini_set('default_socket_timeout', 600 );
        $client = new \SoapClient(self::$api_url, array("trace"=>1, "exceptions"=>0, "connection_timeout" => 1 ));


        try {
            $res_str = $client->GetProductFee('01', $product_id, $amt);

            Helper::log('### (Emida2) get_products ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $res_xml = simplexml_load_string($res_str, null, LIBXML_NOCDATA);

            if (!empty($res_xml->ResponseCode) && $res_xml->ResponseCode != '00') {
                return [
                    'error_code' => $res_xml->ResponseCode,
                    'error_msg' => $res_xml->ResponseMessage
                ];
            }
            return [
                'error_code' => '',
                'error_msg' => '',
                'fee' => $res_xml->ProductFee
            ];
        } catch (\Exception $ex) {

            Helper::log('### (Emida2) get_products (Exception) ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $message = " - product_id : " . $product_id . "<br/>";
            $message .= " - amt : " . $amt . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] get_products() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function get_products($trans_type_id = '', $carrier_id = '', $category_id = '', $product_id = '') {

        ini_set('default_socket_timeout', 600 );
        $client = new \SoapClient(self::$api_url, array("trace"=>1, "exceptions"=>0, "connection_timeout" => 1 ));


        try {
            $res_str = $client->GetProductList('01', self::$tid, '', '', '', '');

            Helper::log('### (Emida2) get_products ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $res_xml = simplexml_load_string($res_str, null, LIBXML_NOCDATA);

            if (!empty($res_xml->ResponseCode) && $res_xml->ResponseCode != '00') {
                return [
                    'error_code' => $res_xml->ResponseCode,
                    'error_msg' => $res_xml->ResponseMessage
                ];
            }
            return [
                'error_code' => '',
                'error_msg' => '',
                'products' => $res_xml->Product
            ];
        } catch (\Exception $ex) {

            Helper::log('### (Emida2) get_products (Exception) ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $error_code = $ex->getCode();
            $error_msg = $ex->getMessage();

            $message = " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] get_products() Exception', $message);

            return [
                'error_code' => $error_code,
                'error_msg' => $error_msg
            ];
        }
    }

    public static function get_categories($trans_type_id = '', $carrier_id = '', $category_id = '', $product_id = '') {

        ini_set('default_socket_timeout', 600 );
        $client = new \SoapClient(self::$api_url, array("trace"=>1, "exceptions"=>0, "connection_timeout" => 1 ));


        try {

            $res_str = $client->GetCategoryList('01', self::$tid, '', '', '', '');

            Helper::log('### (Emida2) get_category_list ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $res_xml = simplexml_load_string($res_str, null, LIBXML_NOCDATA);

            if (!empty($res_xml->ResponseCode)) {
                return [
                    'error_code' => $res_xml->ResponseCode,
                    'error_msg' => $res_xml->ResponseMessage
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'categories' => $res_xml->Category
            ];

        } catch (\Exception $ex) {

            Helper::log('### (Emida2) get_category_list (Exception) ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $message = " - trans_type_id : " . $trans_type_id . "<br/>";
            $message .= " - carrier_id : " . $carrier_id . "<br/>";
            $message .= " - category_id : " . $category_id . "<br/>";
            $message .= " - product_id : " . $product_id . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] get_category_list() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }


    public static function get_carriers($trans_type_id = '', $carrier_id = '', $category_id = '', $product_id = '') {

        ini_set('default_socket_timeout', 600 );
        $client = new \SoapClient(self::$api_url, array("trace"=>1, "exceptions"=>0, "connection_timeout" => 1 ));

        try {

            $res_str = $client->GetCarrierList('01', self::$tid, '', '', '', '');

            Helper::log('### (Emida2) get_carriers ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $res_xml = simplexml_load_string($res_str, null, LIBXML_NOCDATA);

            if (!empty($res_xml->ResponseCode)) {
                return [
                    'error_code' => $res_xml->ResponseCode,
                    'error_msg' => $res_xml->ResponseMessage
                ];
            }
            return [
                'error_code' => '',
                'error_msg' => '',
                'carriers' => $res_xml->Carrier
            ];
        } catch (\Exception $ex) {

            Helper::log('### (Emida2) get_carriers (Exception) ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $message = " - trans_type_id : " . $trans_type_id . "<br/>";
            $message .= " - carrier_id : " . $carrier_id . "<br/>";
            $message .= " - category_id : " . $category_id . "<br/>";
            $message .= " - product_id : " . $product_id . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] get_carriers() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function get_trans_types($trans_type_id = '', $carrier_id = '', $category_id = '', $product_id = '') {

        ini_set('default_socket_timeout', 600 );
        $client = new \SoapClient(self::$api_url, array("trace"=>1, "exceptions"=>0, "connection_timeout" => 1 ));


        try {

            $res_str = $client->GetTransTypeList('01', self::$tid, '', '', '', '');

            Helper::log('### (Emida2) get_trans_type ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $res_xml = simplexml_load_string($res_str, null, LIBXML_NOCDATA);

            if (!empty($res_xml->ResponseCode)) {
                return [
                    'error_code' => $res_xml->ResponseCode,
                    'error_msg' => $res_xml->ResponseMessage
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'trans_types' => $res_xml->TransType
            ];

        } catch (\Exception $ex) {

            Helper::log('### (Emida2) get_trans_type (Exception) ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $message = " - trans_type_id : " . $trans_type_id . "<br/>";
            $message .= " - carrier_id : " . $carrier_id . "<br/>";
            $message .= " - category_id : " . $category_id . "<br/>";
            $message .= " - product_id : " . $product_id . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] get_trans_type() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    // FreeUP Mobile
    public static function freeUp_util() {

        ini_set('default_socket_timeout', 600 );
        $client = new \SoapClient(self::$api_url, array("trace"=>1, "exceptions"=>0, "connection_timeout" => 1 ));


        try {

            self::init();

            $res_str = $client->FreeUpMobileUtil('01', self::$tid, self::$clerk_id, '1');

            Helper::log('### (Emida2) FreeUpMobileUtil ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $res_xml = simplexml_load_string($res_str, null, LIBXML_NOCDATA);

            Helper::log('### (Emida2) EMIDA - FreeUpMobileUtil - RET NEW ###', [
                'Carriers' => $res_xml->Carriers,
                'EquipmentTypes' => $res_xml->EquipmentTypes,
                'HandsetOs' => $res_xml->HandsetOs,
                'States' => $res_xml->States,
            ]);

        } catch (\Exception $ex) {

            Helper::log('### (Emida2) FreeUpMobileUtil (Exception) ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] freeUp_activation() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }


    public static function freeUp_activation($carrier, $product_id, $product_pin, $activation_code, $iccid, $os_type, $imei, $esn, $zip, $trans_id, $portinfo = null) {

        ini_set('default_socket_timeout', 600 );
        $client = new \SoapClient(self::$api_url, array("trace"=>1, "exceptions"=>0, "connection_timeout" => 1 ));


        try {

//            if (getenv('APP_ENV') != 'production') {
//                return [
//                    'error_code' => '',
//                    'error_msg' => 'DEV Test ... ',
//                    'min' => '1112223333'
//                ];
//            }

            self::init();

            if (!empty($portinfo)) { // port in
                $res_str = $client->FreeUpMobileActivations('01', self::$tid, self::$clerk_id,$product_id, '1', $trans_id, $carrier,
                    (empty($activation_code) ? '' : $activation_code), $os_type, (empty($iccid) ? '' : $iccid), (empty($imei) ? '' : $imei),
                    (empty($esn) ? '' : $esn), $zip, '', '', $product_pin, '', $portinfo->equipment_type, 'SOFTPLUS',
                    $portinfo->number_to_port, $portinfo->first_name, $portinfo->last_name, $portinfo->address1,
                    $portinfo->address2, $portinfo->city, $portinfo->state, $portinfo->email, $portinfo->call_back_phone,
                    $portinfo->account_no, $portinfo->account_pin);
            }else{ // activate
                $res_str = $client->FreeUpMobileActivations('01', self::$tid, self::$clerk_id,$product_id, '1', $trans_id, $carrier,
                    (empty($activation_code) ? '' : $activation_code), $os_type, (empty($iccid) ? '' : $iccid), (empty($imei) ? '' : $imei),
                    (empty($esn) ? '' : $esn), $zip, '', '', $product_pin, '');
            }

            Helper::log('### (Emida2) FreeUpMobileActivations ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $res_xml = simplexml_load_string($res_str, null, LIBXML_NOCDATA);

            $error_code = isset($res_xml->ResponseCode) ? $res_xml->ResponseCode->__toString() : '';
            $error_msg = isset($res_xml->ResponseMessage) ? $res_xml->ResponseMessage->__toString() : '';

            if (empty($error_code)) {
                return [
                    'error_code' => -4,
                    'error_msg' => 'Unable to get response code from vendor'
                ];
            }

            if ($error_code != '00') {
                return [
                    'error_code' => $error_code,
                    'error_msg' => $error_msg
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => $error_msg,
                'min' => isset($res_xml->Min) ? $res_xml->Min : ''
            ];

        } catch (\Exception $ex) {

            Helper::log('### (Emida2) FreeUpMobileActivations (Exception) ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $message = " - product_id : " . $product_id . "<br/>";
            $message .= " - carrier : " . $carrier . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] FreeUpMobileActivations() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }


    public static function freeUp_portin($carrier, $product_id, $product_pin, $activation_code, $iccid, $os_type, $imei, $esn, $zip, $trans_id, $portinfo) {

        ini_set('default_socket_timeout', 600 );
        $client = new \SoapClient(self::$api_url, array("trace"=>1, "exceptions"=>0, "connection_timeout" => 1 ));


        try {

            switch ($product_id) {
                case '88880045':
                    $product_id = '88880152';
                    break;
                case '88880046':
                    $product_id = '88880153';
                    break;
                case '88880047':
                    $product_id = '88880154';
                    break;
                case '88880048':
                    $product_id = '88880155';
                    break;
                case '88880059':
                    $product_id = '88880157';
                    break;
                case '88880051':
                    $product_id = '88880158';
                    break;
                case '88880138':
                    $product_id = '88880139';
                    break;
//                case '88880040':
//                    $product_id = '88880131';
//                    break;
            }

            self::init();

            $res_str = $client->FreeUpMobilePortin('01', self::$tid, self::$clerk_id, $product_id, '1',
                $trans_id, $carrier, (empty($activation_code) ? '' : $activation_code),
                $os_type, (empty($iccid) ? '' : $iccid), (empty($imei) ? '' : $imei),
                (empty($esn) ? '' : $esn), $zip, '', $product_pin, '', $portinfo->equipment_type,
                'SOFTPLUS', $portinfo->number_to_port, $portinfo->first_name, $portinfo->last_name,
                $portinfo->address1, $portinfo->address2, $portinfo->city, $portinfo->state,
                $portinfo->email, $portinfo->call_back_phone, $portinfo->account_no, $portinfo->account_pin);

            Helper::log('### (Emida2) FreeUpMobileActivations ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $res_xml = simplexml_load_string($res_str, null, LIBXML_NOCDATA);

            $error_code = isset($res_xml->ResponseCode) ? $res_xml->ResponseCode->__toString() : '';
            $error_msg = isset($res_xml->ResponseMessage) ? $res_xml->ResponseMessage->__toString() : '';

            if (empty($error_code)) {
                return [
                    'error_code' => -4,
                    'error_msg' => 'Unable to get response code from vendor'
                ];
            }

            if ($error_code != '00') {
                return [
                    'error_code' => $error_code,
                    'error_msg' => $error_msg
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => $error_msg,
                'min' => isset($res_xml->Min) ? $res_xml->Min : ''
            ];

        } catch (\Exception $ex) {

            Helper::log('### (Emida2) FreeUpMobileActivations ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $message = " - product_id : " . $product_id . "<br/>";
            $message .= " - carrier : " . $carrier . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] FreeUpMobilePortin() Exception', $message);

            return [
                'error_code' => 'EXP-' . $ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }


    public static function freeUp_portin_status($mdn, $trans_id) {

        ini_set('default_socket_timeout', 600 );
        $client = new \SoapClient(self::$api_url, array("trace"=>1, "exceptions"=>0, "connection_timeout" => 1 ));


        try {

            self::init();

            $res_str = $client->FreeUpMobilePortinStatus('01', self::$tid, self::$clerk_id, $mdn, '', '1', $trans_id);

            Helper::log('### (Emida2) FreeUpMobilePortinStatus ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $res_xml = simplexml_load_string($res_str, null, LIBXML_NOCDATA);

            $error_code = isset($res_xml->ResponseCode) ? $res_xml->ResponseCode->__toString() : '';
            $error_msg = isset($res_xml->ResponseMessage) ? $res_xml->ResponseMessage->__toString() : '';

            if (empty($error_code)) {
                return [
                    'error_code' => -4,
                    'error_msg' => 'Unable to get response code from vendor'
                ];
            }

            if ($error_code != '00') {
                return [
                    'error_code' => $error_code,
                    'error_msg' => $error_msg
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => $error_msg,
                'min' => isset($res_xml->min) ? $res_xml->min : ''
            ];

        } catch (\Exception $ex) {

            Helper::log('### (Emida2) FreeUpMobilePortinStatus (Exception) ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $message = " - mdn : " . $mdn . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] FreeUpMobilePortinStatus() Exception', $message);

            return [
                'error_code' => 'EX-' . $ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    ### Locus Activation
    public static function LocusActivateGSMAfcode($trans_id, $act_prod_id, $pin_prod_id, $afcode, $npa, $city, $zip) {

        ini_set('default_socket_timeout', 600 );
        $client = new \SoapClient(self::$api_url, array("trace"=>1, "exceptions"=>0, "connection_timeout" => 1 ));


        try {

            #             Activation/Port In SKU  Description SPIFF SKU
            # 93000851    EasyGo $4.99 Pay Go Activation - INSTANT SPIFF  970000005
            # 93000881    EasyGo $10 Pay Go Activation - INSTANT SPIFF    970000005
            # 93000880    EasyGo $20 Monthly Unltd Activation -INSTANT SPIFF  970000010
            # 93000882    EasyGo $30 Monthly Unltd Activation -INSTANT SPIF   970000010
            # 93000810    H2O $10 Pay Go Activation - Instant SPIFF   970000005
            # 93000820    H2O $20 Pay Go Activation - Instant SPIFF   970000005
            # 93000825    H2O $25 Pay Go Activation - Instant SPIFF   970000005
            # 93000830    H2O $30 Pay Go Activation - Instant SPIFF   970000005
            # 93000800    H2O $100 Pay Go Activation - Instant SPIFF  970000005
            # 93000020    H2O $20 Monthly Unltd Activation - Instant SPIFF    970000010
            # 93000030    H2O $30 Monthly Unltd Activation - Instant SPIFF    970000020
            # 93000035    H2O $35 Monthly Unltd Activation - Instant SPIFF    970000020
            # 93000040    H2O $40 Monthly Unltd Activation - Instant SPIFF    970000020
            # 93000050    H2O $50 Monthly Unltd Activation - Instant SPIFF    970000020
            # 93000060    H2O $60 Monthly Unltd Activation - Instant SPIFF    970000020
            # 93002525    H2O BOLT $25 Activation - Instant SPIFF 970000005
            # 93002535    H2O BOLT $50 Activation - Instant SPIFF 970000010
            # 93003570    H2O BOLT $70 Activation - Instant SPIFF 970000010
            # 93003590    H2O BOLT $90 Activation - Instant SPIFF 970000010

            // if (getenv('APP_ENV') != 'production') {
            //     return [
            //         'error_code' => '',
            //         'error_msg' => 'DEV Test ... ',
            //         'min' => '1112223333'
            //     ];
            // }

            self::init();

            $res_str = $client->LocusActivateGSMAfcode(self::$tid, $pin_prod_id, self::$clerk_id, $act_prod_id, '1', $trans_id, '01',
                $afcode, $npa, $city, $zip);

            Helper::log('### (Emida2) LocusActivateGSMAfcode ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $res_xml = simplexml_load_string($res_str, null, LIBXML_NOCDATA);

            $error_code = isset($res_xml->ResponseCode) ? $res_xml->ResponseCode->__toString() : '';
            $error_msg = isset($res_xml->ResponseMessage) ? $res_xml->ResponseMessage->__toString() : '';

            if (empty($error_code)) {
                return [
                    'error_code' => -4,
                    'error_msg' => 'Unable to get response code from vendor'
                ];
            }

            if ($error_code != '00') {
                return [
                    'error_code' => $error_code,
                    'error_msg' => $error_msg
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => $error_msg,
                'serial' => isset($res_xml->serial) ? $res_xml->serial : '',
                'min' => isset($res_xml->min) ? $res_xml->min : ''
            ];

        } catch (\Exception $ex) {

            Helper::log('### (Emida2) LocusActivateGSMAfcode (Exception) ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $message = " - product_id : " . $act_prod_id . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] LocusActivateGSMAfcode() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function LocusActivateGSMsim($trans_id, $act_prod_id, $pin_prod_id, $sim, $npa, $city, $zip) {

        ini_set('default_socket_timeout', 600 );
        $client = new \SoapClient(self::$api_url, array("trace"=>1, "exceptions"=>0, "connection_timeout" => 1 ));


        try {

//             if (getenv('APP_ENV') != 'production') {
//                 return [
//                     'error_code' => '',
//                     'error_msg' => 'DEV Test ... ',
//                     'min' => '1112223333'
//                 ];
//             }

            self::login();

            $res_str = $client->LocusActivateGSMsim(self::$tid, $pin_prod_id, self::$clerk_id, $act_prod_id, '1',
                $trans_id, '01', $npa, $sim, $city, $zip);

            Helper::log('### (Emida2) LocusActivateGSMsim ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $res_xml = simplexml_load_string($res_str, null, LIBXML_NOCDATA);

            $error_code = isset($res_xml->ResponseCode) ? $res_xml->ResponseCode->__toString() : '';
            $error_msg = isset($res_xml->ResponseMessage) ? $res_xml->ResponseMessage->__toString() : '';

            if (empty($error_code)) {
                return [
                    'error_code' => -4,
                    'error_msg' => 'Unable to get response code from vendor'
                ];
            }

            if ($error_code != '00') {
                return [
                    'error_code' => $error_code,
                    'error_msg' => $error_msg
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => $error_msg,
                'serial' => isset($res_xml->serial) ? $res_xml->serial : '',
                'min' => isset($res_xml->min) ? $res_xml->min : ''
            ];

        } catch (\Exception $ex) {

            Helper::log('### (Emida2) LocusActivateGSMsim ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $message = " - product_id : " . $act_prod_id . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] LocusActivateGSMsim() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function H2OWirelessPortin(
        $trans_id, $act_prod_id, $email, $call_back_phone, $old_service_provider,
        $number_to_port, $cell_number_contract, $first_name, $last_name, $account_no,
        $account_pin, $address, $city, $state, $zip, $sim, $imei, $pin_prod_id) {

        ini_set('default_socket_timeout', 600 );
        $client = new \SoapClient(self::$api_url, array("trace"=>1, "exceptions"=>0, "connection_timeout" => 1 ));


        try {

            switch ($act_prod_id) {
                // H2O Monthly - Port In
                case '93000020':
                    $act_prod_id = '93110021';
                    break;
                case '93000030':
                    $act_prod_id = '93110030';
                    break;
                case '93000040':
                    $act_prod_id = '93110040';
                    break;
                case '93000050':
                    $act_prod_id = '93110050';
                    break;
                case '93000060':
                    $act_prod_id = '93110060';
                    break;

                // H2O Pay Go - Port In
                case '93000810':
                    $act_prod_id = '93110010';
                    break;
                case '93000820':
                    $act_prod_id = '93110020';
                    break;
                case '93000830':
                    $act_prod_id = '93110130';
                    break;
                case '93000800':
                    $act_prod_id = '93110100';
                    break;

                // Easy Go Monthly - Port In
                case '93000880':
                    $act_prod_id = '93111012';
                    break;
                case '93000882':
                    $act_prod_id = '93111013';
                    break;
            }

            self::login();

            $res_str = $client->H2OWirelessPortin('01', self::$tid, self::$clerk_id, $act_prod_id, '1', $trans_id,
                $email, $call_back_phone, $old_service_provider, $number_to_port, $cell_number_contract,
                $first_name, $last_name, $account_no, $account_pin, $address, $city, $state, $zip, 'a',
                $sim, $imei, $pin_prod_id);

            Helper::log('### (Emida2) H2OWirelessPortin ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $res_xml = simplexml_load_string($res_str, null, LIBXML_NOCDATA);

            $error_code = isset($res_xml->ResponseCode) ? $res_xml->ResponseCode->__toString() : '';
            $error_msg = isset($res_xml->ResponseMessage) ? $res_xml->ResponseMessage->__toString() : '';

            if (empty($error_code)) {
                return [
                    'error_code' => -4,
                    'error_msg' => 'Unable to get response code from vendor'
                ];
            }

            if ($error_code != '00') {
                return [
                    'error_code' => $error_code,
                    'error_msg' => $error_msg
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => $error_msg,
                'serial' => isset($res_xml->PORTINREFERENCENUMBER) ? $res_xml->PORTINREFERENCENUMBER : ''
            ];

        } catch (\Exception $ex) {

            Helper::log('### (Emida2) H2OWirelessPortin (Exception) ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);
            $message = " - product_id : " . $act_prod_id . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] H2OWirelessPortin() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function LocusCreateMultiLine2Lines($trans_id, $product_id, $sim1, $sim2) {

        ini_set('default_socket_timeout', 600 );
        $client = new \SoapClient(self::$api_url, array("trace"=>1, "exceptions"=>0, "connection_timeout" => 1 ));


        try {

            # Activation/Port In SKU  Description SPIFF SKU
            # 92000002    H2O Wireless Unlimited $50 - 2 Lines    99900030
            # 92000004    H2O Wireless Unlimited $100 - 4 Lines   99900065

            // if (getenv('APP_ENV') != 'production') {
            //     return [
            //         'error_code' => '',
            //         'error_msg' => 'DEV Test ... ',
            //         'min' => '1112223333'
            //     ];
            // }

            self::init();

            $res_str = $client->LocusCreateMultiLine2Lines(self::$tid, self::$clerk_id, '1', $trans_id, '01',
                $product_id, '50', $sim1, $sim2);

            Helper::log('### (Emida2) LocusCreateMultiLine2Lines ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $res_xml = simplexml_load_string($res_str, null, LIBXML_NOCDATA);

            $error_code = isset($res_xml->ResponseCode) ? $res_xml->ResponseCode->__toString() : '';
            $error_msg = isset($res_xml->ResponseMessage) ? $res_xml->ResponseMessage->__toString() : '';

            if (empty($error_code)) {
                return [
                    'error_code' => -4,
                    'error_msg' => 'Unable to get response code from vendor'
                ];
            }

            if ($error_code != '00') {
                return [
                    'error_code' => $error_code,
                    'error_msg' => $error_msg
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => $error_msg,
                'transaction_id' => isset($res_xml->TransactionId) ? $res_xml->TransactionId : ''
            ];

        } catch (\Exception $ex) {

            Helper::log('### (Emida2) LocusCreateMultiLine2Lines ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $message = " - product_id : " . $product_id . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] LocusCreateMultiLine2Lines() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function LocusCreateMultiLine4Lines($trans_id, $product_id, $sim1, $sim2, $sim3, $sim4) {

        ini_set('default_socket_timeout', 600 );
        $client = new \SoapClient(self::$api_url, array("trace"=>1, "exceptions"=>0, "connection_timeout" => 1 ));


        try {

            # Activation/Port In SKU  Description SPIFF SKU
            # 92000002    H2O Wireless Unlimited $50 - 2 Lines    99900030
            # 92000004    H2O Wireless Unlimited $100 - 4 Lines   99900065

            // if (getenv('APP_ENV') != 'production') {
            //     return [
            //         'error_code' => '',
            //         'error_msg' => 'DEV Test ... ',
            //         'min' => '1112223333'
            //     ];
            // }

            self::init();

            $res_str = $client->LocusCreateMultiLine4Lines(self::$tid, self::$clerk_id, '1', $trans_id, '01',
                $product_id, '50', $sim1, $sim2);

            Helper::log('### (Emida2) LocusCreateMultiLine4Lines ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $res_xml = simplexml_load_string($res_str, null, LIBXML_NOCDATA);

            $error_code = isset($res_xml->ResponseCode) ? $res_xml->ResponseCode->__toString() : '';
            $error_msg = isset($res_xml->ResponseMessage) ? $res_xml->ResponseMessage->__toString() : '';

            if (empty($error_code)) {
                return [
                    'error_code' => -4,
                    'error_msg' => 'Unable to get response code from vendor'
                ];
            }

            if ($error_code != '00') {
                return [
                    'error_code' => $error_code,
                    'error_msg' => $error_msg
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => $error_msg,
                'transaction_id' => isset($res_xml->TransactionId) ? $res_xml->TransactionId : ''
            ];

        } catch (\Exception $ex) {

            Helper::log('### (Emida2) LocusCreateMultiLine4Lines (Exception) ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $message = " - product_id : " . $product_id . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] LocusCreateMultiLine4Lines() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    ### Lyca APIs

    public static function LycaActivationRTR($trans_id, $product_id, $sim, $zip, $amount) {

        ini_set('default_socket_timeout', 600 );
        $client = new \SoapClient(self::$api_url, array("trace"=>1, "exceptions"=>0, "connection_timeout" => 1 ));


        try {

//            if (getenv('APP_ENV') != 'production') {
//                return [
//                  'error_code' => '',
//                  'error_msg' => 'DEV Test ... ',
//                  'mdn' => '1112223333'
//                ];
//            }
//
            self::init();

            $res_str = $client->LycaActivationRTR('01', self::$tid, self::$clerk_id, $product_id, $sim,
                $zip, '1', '', $amount, $trans_id, '1');

            Helper::log('### (Emida2) LycaActivationRTR ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $res_xml = simplexml_load_string($res_str, null, LIBXML_NOCDATA);

            $error_code = isset($res_xml->ResponseCode) ? $res_xml->ResponseCode->__toString() : '';
            $error_msg = isset($res_xml->ResponseMessage) ? $res_xml->ResponseMessage->__toString() : '';

            if (empty($error_code)) {
                return [
                  'error_code' => -4,
                  'error_msg' => 'Unable to get response code from vendor'
                ];
            }

            if ($error_code != '00') { // failed
                return [
                  'error_code' => $error_code,
                  'error_msg' => $error_msg
                ];
            }

            return [ // success
                'error_code' => '',
                'error_msg' => $error_msg,
                'mdn' => isset($res_xml->MIN) ? $res_xml->MIN->__toString() : '' ,                                            //isset($ret_new->MIN) ? $ret_new->MIN : '',
                'ref_number' =>  isset($res_xml->PORTINREFERENCENUMBER) ? $res_xml->PORTINREFERENCENUMBER->__toString() : ''  //isset($ret_new->PORTINREFERENCENUMBER) ? $ret_new->PORTINREFERENCENUMBER : ''
            ];

        } catch (\Exception $ex) {

            Helper::log('### (Emida2) LycaActivationRTR ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $message = " - product_id : " . $product_id . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] LycaActivationRTR() Exception', $message);

            return [
              'error_code' => 'E:' .$ex->getCode(),
              'error_msg' => $ex->getMessage(),
              'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function LycaPortinRTR($trans_id, $product_id, $sim, $mdn, $account_no, $account_psw, $zip, $amount) {

        ini_set('default_socket_timeout', 600 );
        $client = new \SoapClient(self::$api_url, array("trace"=>1, "exceptions"=>0, "connection_timeout" => 1 ));

        try {

            # Activation/Port In SKU    Description SPIFF SKU
            # 94000319    Lyca $19 Unlimited RTR Activation   94000017
            # 94000323    Lyca $23 Unlimited RTR Activation   94000013
            # 94000329    Lyca $29 Unlimited RTR Activation   94000015
            # 94000335    Lyca $35 Unlimited RTR Activation   94000018
            # 94000339    Lyca $39 Unlimited RTR Activation   94000015
            # 94000345    Lyca $45 Unlimited RTR Activation   94000015
            # 94000355    Lyca $50 Unlimited RTR Activation   94000015
        
            # 94000419    Lyca $19 Unlimited RTR Port In  94000017
            # 94000423    Lyca $23 Unlimited RTR Port In  94000013
            # 94000429    Lyca $29 Unlimited RTR Port In  94000015
            # 94000435    Lyca $35 Unlimited RTR Port In  94000018
            # 94000439    Lyca $39 Unlimited RTR Port In  94000015
            # 94000445    Lyca $45 Unlimited RTR Port In  94000015
            # 94000455    Lyca $55 Unlimited RTR Port In  94000015
        
            # 94000110    Check PortIn Elegibility    
            # 94000460    PortIn Details  
            # 94000465    Modify PortIn   
            # 94000470    Cancel PortIn   

            // if (getenv('APP_ENV') != 'production') {
            //     return [
            //         'error_code' => '',
            //         'error_msg' => 'DEV Test ... ',
            //         'min' => '1112223333'
            //     ];
            // }

            switch ($product_id) {
                case '7044':
                    $product_id = '7046';
                    break;
            }

            self::init();

            $res_str = $client->LycaPortinRTR('01', self::$tid, self::$clerk_id, $product_id, $sim,
                $mdn, $account_no, $account_psw, $zip, '1', '', $amount, $trans_id, '1');

            Helper::log('### (Emida2) LycaPortinRTR ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $res_xml = simplexml_load_string($res_str, null, LIBXML_NOCDATA);

            $error_code = isset($res_xml->ResponseCode) ? $res_xml->ResponseCode->__toString() : '';
            $error_msg = isset($res_xml->ResponseMessage) ? $res_xml->ResponseMessage->__toString() : '';

            if (empty($error_code)) {
                return [
                    'error_code' => -4,
                    'error_msg' => 'Unable to get response code from vendor'
                ];
            }

            if ($error_code != '00') {
                return [
                    'error_code' => $error_code,
                    'error_msg' => $error_msg
                ];
            }

            return [
                'error_code'    => '',
                'error_msg'     => $error_msg,
                'mdn'           =>isset($res_xml->MIN) ? $res_xml->MIN : '',
                'ref_number'   => isset($res_xml->PORTINREFERENCENUMBER) ? $res_xml->PORTINREFERENCENUMBER : ''
            ];

        } catch (\Exception $ex) {

            Helper::log('### (Emida2) LycaPortinRTR (Exception) ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $message = " - product_id : " . $product_id . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] LycaPortinRTR() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function LycaPortInDetails($product_id, $ref_code) {

        ini_set('default_socket_timeout', 600 );
        $client = new \SoapClient(self::$api_url, array("trace"=>1, "exceptions"=>0, "connection_timeout" => 1 ));


        try {

            // if (getenv('APP_ENV') != 'production') {
            //     return [
            //         'error_code' => '',
            //         'error_msg' => 'DEV Test ... ',
            //         'min' => '1112223333'
            //     ];
            // }

            switch ($product_id) {
                case '7044':
                    $product_id = '7046';
                    break;
            }

            self::init();

            $res_str = $client->LycaPortInDetails('01', self::$tid, self::$clerk_id, $product_id, '1',
                'REF_CODE', $ref_code);

            Helper::log('### (Emida2) LycaPortInDetails ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $res_xml = simplexml_load_string($res_str, null, LIBXML_NOCDATA);

            $error_code = isset($res_xml->ResponseCode) ? $res_xml->ResponseCode->__toString() : '';
            $error_msg = (isset($res_xml->ModifyPortInMessage) ? $res_xml->ModifyPortInMessage->__toString() : '') . ', ' . (isset($ret_new->RejectReason) ? $ret_new->RejectReason->__toString() : '');

            if (empty($error_code)) {
                return [
                  'error_code' => -4,
                  'error_msg' => 'Unable to get response code from vendor'
                ];
            }

            if ($error_code != '00') {
                return [
                  'error_code' => $error_code,
                  'error_msg' => $error_msg,
                  'lyca_ref_code' => isset($res_xml->ReferenceCode) ? $res_xml->ReferenceCode->__toString() : '',
                  'lyca_return_desc' => isset($res_xml->ReturnDescription) ? $res_xml->ReturnDescription->__toString() : '',
                  'transaction_id' => isset($res_xml->TransactionId) ? $res_xml->TransactionId : '',
                ];
            }

            return [
              'error_code' => '',
              'error_msg' => $error_msg,
              'lyca_ref_code' => isset($res_xml->ReferenceCode) ? $res_xml->ReferenceCode->__toString() : '',
              'lyca_return_desc' => isset($res_xml->ReturnDescription) ? $res_xml->ReturnDescription->__toString() : '',
              'transaction_id' => isset($res_xml->TransactionId) ? $res_xml->TransactionId : ''
            ];

        } catch (\Exception $ex) {

            Helper::log('### (Emida2) LycaPortInDetails (Exception) ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $message = " - product_id : " . $product_id . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] LycaPortInDetails() Exception', $message);

            return [
              'error_code' => 'E:' .$ex->getCode(),
              'error_msg' => $ex->getMessage(),
              'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function LycaModifyPortIn($reference_no, $product_id, $sim, $mdn, $account_no, $account_psw, $zip) {

        ini_set('default_socket_timeout', 600 );
        $client = new \SoapClient(self::$api_url, array("trace"=>1, "exceptions"=>0, "connection_timeout" => 1 ));

        try {

            // if (getenv('APP_ENV') != 'production') {
            //     return [
            //         'error_code' => '',
            //         'error_msg' => 'DEV Test ... ',
            //         'min' => '1112223333'
            //     ];
            // }

            switch ($product_id) {
                case '7044':
                    $product_id = '7046';
                    break;
            }

            self::init();

            $res_str = $client->LycaModifyPortIn('01', self::$tid, self::$clerk_id, $product_id, '1',
                $reference_no, $mdn, $sim, $account_no, $account_psw, $zip);

            Helper::log('### (Emida2) LycaModifyPortIn ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $res_xml = simplexml_load_string($res_str, null, LIBXML_NOCDATA);

            $error_code = isset($res_xml->ResponseCode) ? $res_xml->ResponseCode->__toString() : '';
            $error_msg = (isset($res_xml->ModifyPortInMessage) ? $res_xml->ModifyPortInMessage->__toString() : '');

            if (empty($error_code)) {
                return [
                  'error_code' => -4,
                  'error_msg' => 'Unable to get response code from vendor'
                ];
            }

            if ($error_code != '00') {
                return [
                  'error_code' => $error_code,
                  'error_msg' => $error_msg,
                  'lyca_error_code' => isset($res_xml->LycaErrorCode) ? $res_xml->LycaErrorCode->__toString() : '',
                  'lyca_error_desc' => isset($res_xml->LycaErrorDescription) ? $res_xml->LycaErrorDescription->__toString() : ''
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => $error_msg,

                'lyca_error_code' => isset($res_xml->LycaErrorCode) ? $res_xml->LycaErrorCode->__toString() : '',
                'lyca_error_desc' => isset($res_xml->LycaErrorDescription) ? $res_xml->LycaErrorDescription->__toString() : '',
                'transaction_id' => isset($res_xml->TransactionId) ? $res_xml->TransactionId : ''
            ];

        } catch (\Exception $ex) {

            Helper::log('### (Emida2) LycaModifyPortIn (Exception) ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $message = " - product_id : " . $product_id . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] LycaModifyPortIn() Exception', $message);

            return [
              'error_code' => 'E:' .$ex->getCode(),
              'error_msg' => $ex->getMessage(),
              'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function LycaCancelPortIn($reference_no, $product_id, $amount, $mdn, $emida_trans_id) {

        ini_set('default_socket_timeout', 600 );
        $client = new \SoapClient(self::$api_url, array("trace"=>1, "exceptions"=>0, "connection_timeout" => 1 ));


        try {

            // if (getenv('APP_ENV') != 'production') {
            //     return [
            //         'error_code' => '',
            //         'error_msg' => 'DEV Test ... ',
            //         'min' => '1112223333'
            //     ];
            // }

            self::init();

            $res_str = $client->CancelPortIn('01', self::$tid, self::$clerk_id, $product_id, $amount, '1',
                $reference_no, $mdn, $emida_trans_id);

            Helper::log('### (Emida2) CancelPortIn ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $res_xml = simplexml_load_string($res_str, null, LIBXML_NOCDATA);

            $error_code = isset($res_xml->ResponseCode) ? $res_xml->ResponseCode->__toString() : '';
            $error_msg = (isset($res_xml->CancelPortinMessage) ? $res_xml->CancelPortinMessage->__toString() : '');

            if (empty($error_code)) {
                return [
                  'error_code' => -4,
                  'error_msg' => 'Unable to get response code from vendor'
                ];
            }

            if ($error_code != '00') {
                return [
                  'error_code' => $error_code,
                  'error_msg' => $error_msg
                ];
            }

            return [
              'error_code' => '',
              'error_msg' => $error_msg,
              'transaction_id' => isset($res_xml->TransactionId) ? $res_xml->TransactionId : ''
            ];

        } catch (\Exception $ex) {

            Helper::log('### (Emida2) CancelPortIn (Exception) ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $message = " - product_id : " . $product_id . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] CancelPortIn() Exception', $message);

            return [
              'error_code' => 'E:' .$ex->getCode(),
              'error_msg' => $ex->getMessage(),
              'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function LycaCheckPortInEligibility($product_id, $mdn, $sim) {

        ini_set('default_socket_timeout', 600 );
        $client = new \SoapClient(self::$api_url, array("trace"=>1, "exceptions"=>0, "connection_timeout" => 1 ));


        try {

            // if (getenv('APP_ENV') != 'production') {
            //     return [
            //         'error_code' => '',
            //         'error_msg' => 'DEV Test ... ',
            //         'min' => '1112223333'
            //     ];
            // }

            self::init();

            $res_str = $client->LycaCheckPortInEligibility('01', self::$tid, self::$clerk_id, $product_id, '1',
                $mdn, $sim);

            Helper::log('### (Emida2) LycaCheckPortInEligibility ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $res_xml = simplexml_load_string($res_str, null, LIBXML_NOCDATA);

            $res_code = isset($res_xml->ResponseCode) ? $res_xml->ResponseCode->__toString() : '';
            $res_msg = isset($res_xml->ResponseMessage) ? $res_xml->ResponseMessage->__toString() : '';

            $lyca_error_code = isset($res_xml->LycaErrorCode) ? $res_xml->LycaErrorCode->__toString() : '';
            $lyca_error_desc = isset($res_xml->LycaErrorDescription) ? $res_xml->LycaErrorDescription->__toString() : '';

            if (empty($error_code)) {
                return [
                    'error_code' => -4,
                    'error_msg' => 'Unable to get response code from vendor'
                ];
            }

            if ($res_code != '00') { // response failed -> still pending
                return [
                    'res_code' => $res_code,
                    'res_msg' => $res_msg
                ];
            }

            return [ // response success -> check if success or cancel
                'res_code' => '',
                'lyca_error_code' => $lyca_error_code,
                'lyca_error_desc' => $lyca_error_desc,
                'transaction_id' => isset($res_xml->TransactionId) ? $res_xml->TransactionId : ''
            ];

        } catch (\Exception $ex) {

            Helper::log('### (Emida2) LycaCheckPortInEligibility ###', [
                'req' => $client->__getLastRequest(),
                'res' => $client->__getLastResponse()
            ]);

            $message = " - product_id : " . $product_id . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] LycaCheckPortInEligibility() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }


}