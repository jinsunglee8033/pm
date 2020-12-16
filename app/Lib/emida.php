<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 9/25/17
 * Time: 5:11 PM
 */

namespace App\Lib;


class emida
{                             
    private static $api_url = 'https://wswl.emida.net/soap/servlet/rpcrouter';
    private static $uid = 'demopmobile';
    private static $pwd = '9493407918';
    private static $tid = '8593003';
    private static $clerk_id = '1234';

    private static function init() {
        if (getenv('APP_ENV') == 'production') {
            self::$api_url = 'https://wswl.emida.net/soap/servlet/rpcrouter';
            self::$uid = 'TomPerfect';
            self::$pwd = '0SvqYgLFqG';
            self::$tid = '2780869';
            self::$clerk_id = '4119';
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
        try {

            self::init();

            $req = '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:debisys-soap-services">';
            $req .=     '<soapenv:Header/>';
            $req .=     '<soapenv:Body>';
            $req .=         '<urn:Login2 soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';
            $req .=             '<version xsi:type="xsd:string">01</version>';
            $req .=             '<userName xsi:type="xsd:string">' . self::$uid . '</userName>';
            $req .=             '<password xsi:type="xsd:string">' . self::$pwd . '</password>';
            $req .=             '<languageOption xsi:type="xsd:string">1</languageOption>';
            $req .=         '</urn:Login2>';
            $req .=     '</soapenv:Body>';
            $req .= '</soapenv:Envelope>';

            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"PinDistSale\""
                //"Content-length: ".strlen($req)
            ); //SOAPAction: your op URL

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);

            curl_setopt($ch, CURLINFO_HEADER_OUT, true);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            $info = curl_getinfo($ch);

            curl_close($ch);

            Helper::log('### login() ###', [
                'req' => $req,
                'res' => $output,
                'info' => $info
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);
            $ret->registerXPathNamespace('SOAP-ENV', 'http://schemas.xmlsoap.org/soap/envelope/');
            $body = $ret->xpath('//SOAP-ENV:Body');
            if (count($body) < 1) {
                return [
                    'error_code' => -2,
                    'error_msg' => 'Unable to get response body'
                ];
            }

            $body[0]->registerXPathNamespace('ns1', 'urn:debisys-soap-services');
            $res = $body[0]->xpath('ns1:Login2Response');
            if (count($res) < 1) {
                return [
                    'error_code' => -3,
                    'error_msg' => 'Unable to get XML in CDATA: ' . var_export($output, true)
                ];
            }

            $res_xml = $res[0]->return;
            $ret_new = simplexml_load_string($res_xml, null, LIBXML_NOCDATA);

            if (!empty($ret_new->ResponseCode) && $ret_new->ResponseCode != '00') {
                return [
                    'error_code' => $ret_new->ResponseCode,
                    'error_msg' => $ret_new->ResponseMessage
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'site_id' => isset($ret_new->SiteID) ? $ret_new->SiteID->__toString() : '',
                'clerk_id' => isset($ret_new->ClerkId) ? $ret_new->ClerkId->__toString() : ''
            ];

        } catch (\Exception $ex) {

            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] login() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function logout() {
        try {

            self::init();

            $req = '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:debisys-soap-services">';
            $req .=     '<soapenv:Header/>';
            $req .=     '<soapenv:Body>';
            $req .=         '<urn:Logout soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';
            $req .=             '<version xsi:type="xsd:string">01</version>';
            $req .=             '<userName xsi:type="xsd:string">' . self::$uid . '</userName>';
            $req .=             '<password xsi:type="xsd:string">' . self::$pwd . '</password>';
            $req .=             '<languageOption xsi:type="xsd:string">1</languageOption>';
            $req .=         '</urn:Logout>';
            $req .=     '</soapenv:Body>';
            $req .= '</soapenv:Envelope>';

            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"PinDistSale\""
                //"Content-length: ".strlen($req)
            ); //SOAPAction: your op URL

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);

            curl_setopt($ch, CURLINFO_HEADER_OUT, true);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            $info = curl_getinfo($ch);

            curl_close($ch);

            Helper::log('### logout() ###', [
                'req' => $req,
                'res' => $output,
                'info' => $info
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);
            $ret->registerXPathNamespace('SOAP-ENV', 'http://schemas.xmlsoap.org/soap/envelope/');
            $body = $ret->xpath('//SOAP-ENV:Body');
            if (count($body) < 1) {
                return [
                    'error_code' => -2,
                    'error_msg' => 'Unable to get response body'
                ];
            }

            $body[0]->registerXPathNamespace('ns1', 'urn:debisys-soap-services');
            $res = $body[0]->xpath('ns1:LogoutResponse');
            if (count($res) < 1) {
                return [
                    'error_code' => -3,
                    'error_msg' => 'Unable to get XML in CDATA: ' . var_export($output, true)
                ];
            }

            $res_xml = $res[0]->return;
            $ret_new = simplexml_load_string($res_xml, null, LIBXML_NOCDATA);

            if (!empty($ret_new->ResponseCode) && $ret_new->ResponseCode != '00') {
                return [
                    'error_code' => $ret_new->ResponseCode,
                    'error_msg' => $ret_new->ResponseMessage
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => ''
            ];

        } catch (\Exception $ex) {

            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] login() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    private static function pin_dist_sale($cid, $product_id, $mdn, $amt, $fee) {
        try {

            self::init();

            $req = '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:debisys-soap-services">';
            $req .=     '<soapenv:Header/>';
            $req .=     '<soapenv:Body>';
            $req .=         '<urn:PinDistSale soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';
            $req .=             '<version xsi:type="xsd:string">01</version>';
            $req .=             '<terminalId xsi:type="xsd:string">' . self::$tid . '</terminalId>';
            $req .=             '<clerkId xsi:type="xsd:string">' . self::$clerk_id . '</clerkId>';
            $req .=             '<productId xsi:type="xsd:string">' . $product_id . '</productId>';
            $req .=             '<accountId xsi:type="xsd:string">' . $mdn . '</accountId>';
            $req .=             '<amount xsi:type="xsd:string">' . number_format($amt + $fee, 2) . '</amount>';
            $req .=             '<invoiceNo xsi:type="xsd:string">' . $cid . '</invoiceNo>';
            $req .=             '<languageOption xsi:type="xsd:string">1</languageOption>';
            $req .=         '</urn:PinDistSale>';
            $req .=     '</soapenv:Body>';
            $req .= '</soapenv:Envelope>';

            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"PinDistSale\""
                //"Content-length: ".strlen($req)
            ); //SOAPAction: your op URL

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);

            curl_setopt($ch, CURLINFO_HEADER_OUT, true);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            $info = curl_getinfo($ch);

            curl_close($ch);

            Helper::log('### pin_dist_sale ###', [
                'req' => $req,
                'res' => $output,
                'info' => $info
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);
            $ret->registerXPathNamespace('SOAP-ENV', 'http://schemas.xmlsoap.org/soap/envelope/');
            $body = $ret->xpath('//SOAP-ENV:Body');
            if (count($body) < 1) {
                return [
                    'error_code' => -2,
                    'error_msg' => 'Unable to get response body'
                ];
            }

            $body[0]->registerXPathNamespace('ns1', 'urn:debisys-soap-services');
            $res = $body[0]->xpath('ns1:PinDistSaleResponse');
            if (count($res) < 1) {
                return [
                    'error_code' => -3,
                    'error_msg' => 'Unable to get XML in CDATA: ' . var_export($output, true)
                ];
            }

            $res_xml = $res[0]->return;
            $ret_new = simplexml_load_string($res_xml, null, LIBXML_NOCDATA);

            Helper::log('### EMIDA - PIN DIST SALES - RET NEW ###', [
                'ret_new' => $ret_new
            ]);

            $error_code = isset($ret_new->ResponseCode) ? $ret_new->ResponseCode->__toString() : '';
            $error_msg = isset($ret_new->ResponseMessage) ? $ret_new->ResponseMessage->__toString() : '';

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
                'pin' => isset($ret_new->Pin) ? $ret_new->Pin->__toString() : '',
                'vendor_tx_id' => isset($ret_new->ControlNo) ? $ret_new->ControlNo->__toString() : ''
            ];

        } catch (\Exception $ex) {

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
        try {

            self::init();

            $req = '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:debisys-soap-services">';
            $req .=     '<soapenv:Header/>';
            $req .=     '<soapenv:Body>';
            $req .=         '<urn:GetProductFee soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';
            $req .=             '<version xsi:type="xsd:string">01</version>';
            $req .=             '<productId xsi:type="xsd:string">' . $product_id . '</productId>';
            $req .=             '<amount xsi:type="xsd:string">' . $amt . '</amount>';
            $req .=         '</urn:GetProductFee>';
            $req .=     '</soapenv:Body>';
            $req .= '</soapenv:Envelope>';

            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"GetProductFee\""
                //"Content-length: ".strlen($req)
            ); //SOAPAction: your op URL

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);

            curl_setopt($ch, CURLINFO_HEADER_OUT, true);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            $info = curl_getinfo($ch);

            curl_close($ch);

            Helper::log('### get_trans_types ###', [
                'req' => $req,
                'res' => $output,
                'info' => $info
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);
            $ret->registerXPathNamespace('SOAP-ENV', 'http://schemas.xmlsoap.org/soap/envelope/');
            $body = $ret->xpath('//SOAP-ENV:Body');
            if (count($body) < 1) {
                return [
                    'error_code' => -2,
                    'error_msg' => 'Unable to get response body'
                ];
            }

            $body[0]->registerXPathNamespace('ns1', 'urn:debisys-soap-services');
            $res = $body[0]->xpath('ns1:GetProductFeeResponse');
            if (count($res) < 1) {
                return [
                    'error_code' => -3,
                    'error_msg' => 'Unable to get XML in CDATA: ' . var_export($output, true)
                ];
            }

            $res_xml = $res[0]->return;
            $ret_new = simplexml_load_string($res_xml, null, LIBXML_NOCDATA);

            if (!empty($ret_new->ResponseCode) && $ret_new->ResponseCode != '00') {
                return [
                    'error_code' => $ret_new->ResponseCode,
                    'error_msg' => $ret_new->ResponseMessage
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'fee' => $ret_new->ProductFee
            ];

        } catch (\Exception $ex) {

            $message = " - product_id : " . $product_id . "<br/>";
            $message .= " - amt : " . $amt . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] get_trans_types() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function get_products($trans_type_id = '', $carrier_id = '', $category_id = '', $product_id = '') {
        try {

            self::init();

            $req = '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:debisys-soap-services">';
            $req .=     '<soapenv:Header/>';
            $req .=     '<soapenv:Body>';
            $req .=         '<urn:GetProductList soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';
            $req .=             '<version xsi:type="xsd:string">01</version>';
            $req .=             '<terminalId xsi:type="xsd:string">' . self::$tid . '</terminalId>';
            $req .=             '<transTypeId xsi:type="xsd:string">' . $trans_type_id . '</transTypeId>';
            $req .=             '<carrierId xsi:type="xsd:string">' . $carrier_id . '</carrierId>';
            $req .=             '<categoryId xsi:type="xsd:string">' . $category_id . '</categoryId>';
            $req .=             '<productId xsi:type="xsd:string">' . $product_id . '</productId>';
            $req .=         '</urn:GetProductList>';
            $req .=     '</soapenv:Body>';
            $req .= '</soapenv:Envelope>';

            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"GetProductList\""
                //"Content-length: ".strlen($req)
            ); //SOAPAction: your op URL

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);

            curl_setopt($ch, CURLINFO_HEADER_OUT, true);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            $info = curl_getinfo($ch);

            curl_close($ch);

            Helper::log('### get_trans_types ###', [
                'req' => $req,
                'res' => $output,
                'info' => $info
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);
            $ret->registerXPathNamespace('SOAP-ENV', 'http://schemas.xmlsoap.org/soap/envelope/');
            $body = $ret->xpath('//SOAP-ENV:Body');
            if (count($body) < 1) {
                return [
                    'error_code' => -2,
                    'error_msg' => 'Unable to get response body'
                ];
            }

            $body[0]->registerXPathNamespace('ns1', 'urn:debisys-soap-services');
            $res = $body[0]->xpath('ns1:GetProductListResponse');
            if (count($res) < 1) {
                return [
                    'error_code' => -3,
                    'error_msg' => 'Unable to get XML in CDATA: ' . var_export($output, true)
                ];
            }

            $res_xml = $res[0]->return;
            $ret_new = simplexml_load_string($res_xml, null, LIBXML_NOCDATA);

            if (!empty($ret_new->ResponseCode)) {
                return [
                    'error_code' => $ret_new->ResponseCode,
                    'error_msg' => $ret_new->ResponseMessage
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'products' => $ret_new->Product
            ];

        } catch (\Exception $ex) {

            $message = " - trans_type_id : " . $trans_type_id . "<br/>";
            $message .= " - carrier_id : " . $carrier_id . "<br/>";
            $message .= " - category_id : " . $category_id . "<br/>";
            $message .= " - product_id : " . $product_id . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] get_trans_types() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function get_categories($trans_type_id = '', $carrier_id = '', $category_id = '', $product_id = '') {
        try {

            self::init();

            $req = '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:debisys-soap-services">';
            $req .=     '<soapenv:Header/>';
            $req .=     '<soapenv:Body>';
            $req .=         '<urn:GetCategoryList soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';
            $req .=             '<version xsi:type="xsd:string">01</version>';
            $req .=             '<terminalId xsi:type="xsd:string">' . self::$tid . '</terminalId>';
            $req .=             '<transTypeId xsi:type="xsd:string">' . $trans_type_id . '</transTypeId>';
            $req .=             '<carrierId xsi:type="xsd:string">' . $carrier_id . '</carrierId>';
            $req .=             '<categoryId xsi:type="xsd:string">' . $category_id . '</categoryId>';
            $req .=             '<productId xsi:type="xsd:string">' . $product_id . '</productId>';
            $req .=         '</urn:GetCategoryList>';
            $req .=     '</soapenv:Body>';
            $req .= '</soapenv:Envelope>';

            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"GetCategoryList\""
                //"Content-length: ".strlen($req)
            ); //SOAPAction: your op URL

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);

            curl_setopt($ch, CURLINFO_HEADER_OUT, true);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            $info = curl_getinfo($ch);

            curl_close($ch);

            Helper::log('### get_trans_types ###', [
                'req' => $req,
                'res' => $output,
                'info' => $info
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);
            $ret->registerXPathNamespace('SOAP-ENV', 'http://schemas.xmlsoap.org/soap/envelope/');
            $body = $ret->xpath('//SOAP-ENV:Body');
            if (count($body) < 1) {
                return [
                    'error_code' => -2,
                    'error_msg' => 'Unable to get response body'
                ];
            }

            $body[0]->registerXPathNamespace('ns1', 'urn:debisys-soap-services');
            $res = $body[0]->xpath('ns1:GetCategoryListResponse');
            if (count($res) < 1) {
                return [
                    'error_code' => -3,
                    'error_msg' => 'Unable to get XML in CDATA: ' . var_export($output, true)
                ];
            }

            $res_xml = $res[0]->return;
            $ret_new = simplexml_load_string($res_xml, null, LIBXML_NOCDATA);

            if (!empty($ret_new->ResponseCode)) {
                return [
                    'error_code' => $ret_new->ResponseCode,
                    'error_msg' => $ret_new->ResponseMessage
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'categories' => $ret_new->Category
            ];

        } catch (\Exception $ex) {

            $message = " - trans_type_id : " . $trans_type_id . "<br/>";
            $message .= " - carrier_id : " . $carrier_id . "<br/>";
            $message .= " - category_id : " . $category_id . "<br/>";
            $message .= " - product_id : " . $product_id . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] get_trans_types() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function get_carriers($trans_type_id = '', $carrier_id = '', $category_id = '', $product_id = '') {
        try {

            self::init();

            $req = '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:debisys-soap-services">';
            $req .=     '<soapenv:Header/>';
            $req .=     '<soapenv:Body>';
            $req .=         '<urn:GetCarrierList soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';
            $req .=             '<version xsi:type="xsd:string">01</version>';
            $req .=             '<terminalId xsi:type="xsd:string">' . self::$tid . '</terminalId>';
            $req .=             '<transTypeId xsi:type="xsd:string">' . $trans_type_id . '</transTypeId>';
            $req .=             '<carrierId xsi:type="xsd:string">' . $carrier_id . '</carrierId>';
            $req .=             '<categoryId xsi:type="xsd:string">' . $category_id . '</categoryId>';
            $req .=             '<productId xsi:type="xsd:string">' . $product_id . '</productId>';
            $req .=         '</urn:GetCarrierList>';
            $req .=     '</soapenv:Body>';
            $req .= '</soapenv:Envelope>';

            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"GetCarrierList\""
                //"Content-length: ".strlen($req)
            ); //SOAPAction: your op URL

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);

            curl_setopt($ch, CURLINFO_HEADER_OUT, true);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            $info = curl_getinfo($ch);

            curl_close($ch);

            Helper::log('### get_trans_types ###', [
                'req' => $req,
                'res' => $output,
                'info' => $info
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);
            $ret->registerXPathNamespace('SOAP-ENV', 'http://schemas.xmlsoap.org/soap/envelope/');
            $body = $ret->xpath('//SOAP-ENV:Body');
            if (count($body) < 1) {
                return [
                    'error_code' => -2,
                    'error_msg' => 'Unable to get response body'
                ];
            }

            $body[0]->registerXPathNamespace('ns1', 'urn:debisys-soap-services');
            $res = $body[0]->xpath('ns1:GetCarrierListResponse');
            if (count($res) < 1) {
                return [
                    'error_code' => -3,
                    'error_msg' => 'Unable to get XML in CDATA: ' . var_export($output, true)
                ];
            }

            $res_xml = $res[0]->return;
            $ret_new = simplexml_load_string($res_xml, null, LIBXML_NOCDATA);

            if (!empty($ret_new->ResponseCode)) {
                return [
                    'error_code' => $ret_new->ResponseCode,
                    'error_msg' => $ret_new->ResponseMessage
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'carriers' => $ret_new->Carrier
            ];

        } catch (\Exception $ex) {

            $message = " - trans_type_id : " . $trans_type_id . "<br/>";
            $message .= " - carrier_id : " . $carrier_id . "<br/>";
            $message .= " - category_id : " . $category_id . "<br/>";
            $message .= " - product_id : " . $product_id . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] get_trans_types() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function get_trans_types($trans_type_id = '', $carrier_id = '', $category_id = '', $product_id = '') {

        try {

            self::init();

            $req = '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:debisys-soap-services">';
            $req .=     '<soapenv:Header/>';
            $req .=     '<soapenv:Body>';
            $req .=         '<urn:GetTransTypeList soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';
            $req .=             '<version xsi:type="xsd:string">01</version>';
            $req .=             '<terminalId xsi:type="xsd:string">' . self::$tid . '</terminalId>';
            $req .=             '<transTypeId xsi:type="xsd:string">' . $trans_type_id . '</transTypeId>';
            $req .=             '<carrierId xsi:type="xsd:string">' . $carrier_id . '</carrierId>';
            $req .=             '<categoryId xsi:type="xsd:string">' . $category_id . '</categoryId>';
            $req .=             '<productId xsi:type="xsd:string">' . $product_id . '</productId>';
            $req .=         '</urn:GetTransTypeList>';
            $req .=     '</soapenv:Body>';
            $req .= '</soapenv:Envelope>';

            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"GetTransTypeList\""
                //"Content-length: ".strlen($req)
            ); //SOAPAction: your op URL

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);

            curl_setopt($ch, CURLINFO_HEADER_OUT, true);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            $info = curl_getinfo($ch);

            curl_close($ch);

            Helper::log('### get_trans_types ###', [
                'req' => $req,
                'res' => $output,
                'info' => $info
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);
            $body = $ret->xpath('//SOAP-ENV:Body');
            if (count($body) < 1) {
                return [
                    'error_code' => -2,
                    'error_msg' => 'Unable to get response body'
                ];
            }

            $body[0]->registerXPathNamespace('ns1', 'urn:debisys-soap-services');
            $res = $body[0]->xpath('ns1:GetTransTypeListResponse');
            if (count($res) < 1) {
                return [
                    'error_code' => -3,
                    'error_msg' => 'Unable to get XML in CDATA'
                ];
            }

            $res_xml = $res[0]->return;
            $ret_new = simplexml_load_string($res_xml, null, LIBXML_NOCDATA);

            if (!empty($ret_new->ResponseCode)) {
                return [
                    'error_code' => $ret_new->ResponseCode,
                    'error_msg' => $ret_new->ResponseMessage
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'trans_types' => $ret_new->TransType
            ];

        } catch (\Exception $ex) {

            $message = " - trans_type_id : " . $trans_type_id . "<br/>";
            $message .= " - carrier_id : " . $carrier_id . "<br/>";
            $message .= " - category_id : " . $category_id . "<br/>";
            $message .= " - product_id : " . $product_id . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] get_trans_types() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    // FreeUP Mobile
    public static function freeUp_util() {
        try {

            // if (getenv('APP_ENV') != 'production') {
            //     return [
            //         'error_code' => '',
            //         'error_msg' => 'DEV Test ... ',
            //         'min' => '1112223333'
            //     ];
            // }

            self::init();

            $req = '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:debisys-soap-services">';
            $req .=     '<soapenv:Header/>';
            $req .=     '<soapenv:Body>';
            $req .=         '<urn:FreeUpMobileUtil soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';
            $req .=             '<version xsi:type="xsd:string">01</version>';
            $req .=             '<terminalId xsi:type="xsd:string">' . self::$tid . '</terminalId>';
            $req .=             '<clerkId xsi:type="xsd:string">' . self::$clerk_id . '</clerkId>';
            $req .=             '<languageId xsi:type="xsd:string">1</languageId>';
            $req .=         '</urn:FreeUpMobileUtil>';
            $req .=     '</soapenv:Body>';
            $req .= '</soapenv:Envelope>';

            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"FreeUpMobileUtil\""
                //"Content-length: ".strlen($req)
            ); //SOAPAction: your op URL

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);

            curl_setopt($ch, CURLINFO_HEADER_OUT, true);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            $info = curl_getinfo($ch);

            curl_close($ch);

            Helper::log('### freeUp_activation ###', [
                'req' => $req,
                'res' => $output,
                'info' => $info
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);
            $ret->registerXPathNamespace('SOAP-ENV', 'http://schemas.xmlsoap.org/soap/envelope/');
            $body = $ret->xpath('//SOAP-ENV:Body');
            if (count($body) < 1) {
                return [
                    'error_code' => -2,
                    'error_msg' => 'Unable to get response body'
                ];
            }

            $body[0]->registerXPathNamespace('ns1', 'urn:debisys-soap-services');
            $res = $body[0]->xpath('ns1:FreeUpMobileUtilResponse');
            if (count($res) < 1) {
                return [
                    'error_code' => -3,
                    'error_msg' => 'Unable to get XML in CDATA: ' . var_export($output, true)
                ];
            }

            $res_xml = $res[0]->return;
            $ret_new = simplexml_load_string($res_xml, null, LIBXML_NOCDATA);

            Helper::log('### EMIDA - FreeUpMobileUtil - RET NEW ###', [
                'Carriers' => $ret_new->Carriers,
                'EquipmentTypes' => $ret_new->EquipmentTypes,
                'HandsetOs' => $ret_new->HandsetOs,
                'States' => $ret_new->States,
            ]);

        } catch (\Exception $ex) {

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
        try {

            if (getenv('APP_ENV') != 'production') {
                return [
                    'error_code' => '',
                    'error_msg' => 'DEV Test ... ',
                    'min' => '1112223333'
                ];
            }

            self::init();

            $req = '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:debisys-soap-services">';
            $req .=     '<soapenv:Header/>';
            $req .=     '<soapenv:Body>';
            $req .=         '<urn:FreeUpMobileActivations soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';
            $req .=             '<version xsi:type="xsd:string">01</version>';
            $req .=             '<terminalId xsi:type="xsd:string">' . self::$tid . '</terminalId>';
            $req .=             '<clerkId xsi:type="xsd:string">' . self::$clerk_id . '</clerkId>';
            $req .=             '<activationProductId xsi:type="xsd:string">' . $product_id . '</activationProductId>';
            $req .=             '<languageId xsi:type="xsd:string">1</languageId>';
            $req .=             '<invoiceNo xsi:type="xsd:string">' . $trans_id . '</invoiceNo>';
            $req .=             '<carrier xsi:type="xsd:string">' . $carrier . '</carrier>';
            $req .=             '<activationCode xsi:type="xsd:string">' . (empty($activation_code) ? '' : $activation_code) . '</activationCode>';
            $req .=             '<handsetOS xsi:type="xsd:string">' . $os_type . '</handsetOS>';
            $req .=             '<iccid xsi:type="xsd:string">' . (empty($iccid) ? '' : $iccid) . '</iccid>';
            $req .=             '<imei xsi:type="xsd:string">' . (empty($imei) ? '' : $imei) . '</imei>';
            $req .=             '<meid xsi:type="xsd:string">' . (empty($esn) ? '' : $esn) . '</meid>';
            $req .=             '<zip xsi:type="xsd:string">' . $zip . '</zip>';
            $req .=             '<npa xsi:type="xsd:string"></npa>';
            $req .=             '<pin xsi:type="xsd:string"></pin>';
            $req .=             '<pinProduct xsi:type="xsd:string">' . $product_pin . '</pinProduct>';
            $req .=             '<masterAccountEmail xsi:type="xsd:string"></masterAccountEmail>';

            if (!empty($portinfo)) {
                $req .=         '<equipmentType xsi:type="xsd:string">' . $portinfo->equipment_type . '</equipmentType>';
                $req .=         '<agent xsi:type="xsd:string">SOFTPLUS</agent>';
                $req .=         '<mdn xsi:type="xsd:string">' . $portinfo->number_to_port . '</mdn>';
                $req .=         '<firstName xsi:type="xsd:string">' . $portinfo->first_name . '</firstName>';
                $req .=         '<lastName xsi:type="xsd:string">' . $portinfo->last_name . '</lastName>';
                $req .=         '<streetNumber xsi:type="xsd:string">' . $portinfo->address1 . '</streetNumber>';
                $req .=         '<streetName xsi:type="xsd:string">' . $portinfo->address2 . '</streetName>';
                $req .=         '<city xsi:type="xsd:string">' . $portinfo->city . '</city>';
                $req .=         '<state xsi:type="xsd:string">' . $portinfo->state . '</state>';
                $req .=         '<contactEmail xsi:type="xsd:string">' . $portinfo->email . '</contactEmail>';
                $req .=         '<contactPhone xsi:type="xsd:string">' . $portinfo->call_back_phone . '</contactPhone>';
                $req .=         '<ospAccount xsi:type="xsd:string">' . $portinfo->account_no . '</ospAccount>';
                $req .=         '<ospPassword xsi:type="xsd:string">' . $portinfo->account_pin . '</ospPassword>';
            }

            $req .=         '</urn:FreeUpMobileActivations>';
            $req .=     '</soapenv:Body>';
            $req .= '</soapenv:Envelope>';

            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"FreeUpMobileActivations\""
                //"Content-length: ".strlen($req)
            ); //SOAPAction: your op URL

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);

            curl_setopt($ch, CURLINFO_HEADER_OUT, true);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            $info = curl_getinfo($ch);

            curl_close($ch);

            Helper::log('### freeUp_activation ###', [
                'req' => $req,
                'res' => $output,
                'info' => $info
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);
            $ret->registerXPathNamespace('SOAP-ENV', 'http://schemas.xmlsoap.org/soap/envelope/');
            $body = $ret->xpath('//SOAP-ENV:Body');
            if (count($body) < 1) {

//                // If 504, We assume that It's complete
//                $pos = strpos($output, '504');
//                if($pos !== false){
//                    return [
//                        'error_code' => '',
//                        'error_msg' => 'Please ',
//                        'min' => ''
//                    ];
//                }

                return [
                    'error_code' => -2,
                    'error_msg' => 'Unable to get response body'
                ];
            }

            $body[0]->registerXPathNamespace('ns1', 'urn:debisys-soap-services');
            $res = $body[0]->xpath('ns1:FreeUpMobileActivationsResponse');
            if (count($res) < 1) {
                return [
                    'error_code' => -3,
                    'error_msg' => 'Unable to get XML in CDATA: ' . var_export($output, true)
                ];
            }

            $res_xml = $res[0]->return;
            $ret_new = simplexml_load_string($res_xml, null, LIBXML_NOCDATA);

            Helper::log('### EMIDA - FreeUpMobileActivations - RET NEW ###', [
                'ret_new' => $ret_new
            ]);

            $error_code = isset($ret_new->ResponseCode) ? $ret_new->ResponseCode->__toString() : '';
            $error_msg = isset($ret_new->ResponseMessage) ? $ret_new->ResponseMessage->__toString() : '';

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
                'min' => isset($ret_new->Min) ? $ret_new->Min : ''
            ];

        } catch (\Exception $ex) {

            $message = " - product_id : " . $product_id . "<br/>";
            $message .= " - carrier : " . $carrier . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
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


    public static function freeUp_portin($carrier, $product_id, $product_pin, $activation_code, $iccid, $os_type, $imei, $esn, $zip, $trans_id, $portinfo) {
        try {

            // if (getenv('APP_ENV') != 'production') {
            //     return [
            //         'error_code' => '',
            //         'error_msg' => 'DEV Test ... ',
            //         'min' => '1112223333'
            //     ];
            // }

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

            $req = '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:debisys-soap-services">';
            $req .=     '<soapenv:Header/>';
            $req .=     '<soapenv:Body>';
            $req .=         '<urn:FreeUpMobilePortin soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';
            $req .=             '<version xsi:type="xsd:string">01</version>';
            $req .=             '<terminalId xsi:type="xsd:string">' . self::$tid . '</terminalId>';
            $req .=             '<clerkId xsi:type="xsd:string">' . self::$clerk_id . '</clerkId>';
            $req .=             '<activationProductId xsi:type="xsd:string">' . $product_id . '</activationProductId>';
            $req .=             '<languageId xsi:type="xsd:string">1</languageId>';
            $req .=             '<invoiceNo xsi:type="xsd:string">' . $trans_id . '</invoiceNo>';
            $req .=             '<carrier xsi:type="xsd:string">' . $carrier . '</carrier>';
            $req .=             '<activationCode xsi:type="xsd:string">' . (empty($activation_code) ? '' : $activation_code) . '</activationCode>';
            $req .=             '<handsetOS xsi:type="xsd:string">' . $os_type . '</handsetOS>';
            $req .=             '<iccid xsi:type="xsd:string">' . (empty($iccid) ? '' : $iccid) . '</iccid>';
            $req .=             '<imei xsi:type="xsd:string">' . (empty($imei) ? '' : $imei) . '</imei>';
            $req .=             '<meid xsi:type="xsd:string">' . (empty($esn) ? '' : $esn) . '</meid>';
            $req .=             '<zip xsi:type="xsd:string">' . $zip . '</zip>';
            $req .=             '<pin xsi:type="xsd:string"></pin>';
            $req .=             '<pinProduct xsi:type="xsd:string">' . $product_pin . '</pinProduct>';
            $req .=             '<masterAccountEmail xsi:type="xsd:string"></masterAccountEmail>';

            $req .=             '<equipmentType xsi:type="xsd:string">' . $portinfo->equipment_type . '</equipmentType>';
            $req .=             '<agent xsi:type="xsd:string">SOFTPLUS</agent>';
            $req .=             '<mdn xsi:type="xsd:string">' . $portinfo->number_to_port . '</mdn>';
            $req .=             '<firstName xsi:type="xsd:string">' . $portinfo->first_name . '</firstName>';
            $req .=             '<lastName xsi:type="xsd:string">' . $portinfo->last_name . '</lastName>';
            $req .=             '<streetNumber xsi:type="xsd:string">' . $portinfo->address1 . '</streetNumber>';
            $req .=             '<streetName xsi:type="xsd:string">' . $portinfo->address2 . '</streetName>';
            $req .=             '<city xsi:type="xsd:string">' . $portinfo->city . '</city>';
            $req .=             '<state xsi:type="xsd:string">' . $portinfo->state . '</state>';
            $req .=             '<contactEmail xsi:type="xsd:string">' . $portinfo->email . '</contactEmail>';
            $req .=             '<contactPhone xsi:type="xsd:string">' . $portinfo->call_back_phone . '</contactPhone>';
            $req .=             '<ospAccount xsi:type="xsd:string">' . $portinfo->account_no . '</ospAccount>';
            $req .=             '<ospPassword xsi:type="xsd:string">' . $portinfo->account_pin . '</ospPassword>';

            $req .=         '</urn:FreeUpMobilePortin>';
            $req .=     '</soapenv:Body>';
            $req .= '</soapenv:Envelope>';

            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"FreeUpMobilePortin\""
                //"Content-length: ".strlen($req)
            ); //SOAPAction: your op URL

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);

            curl_setopt($ch, CURLINFO_HEADER_OUT, true);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            $info = curl_getinfo($ch);

            curl_close($ch);

            Helper::log('### freeUp_activation ###', [
                'req' => $req,
                'res' => $output,
                'info' => $info
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);
            $ret->registerXPathNamespace('SOAP-ENV', 'http://schemas.xmlsoap.org/soap/envelope/');
            $body = $ret->xpath('//SOAP-ENV:Body');
            if (count($body) < 1) {
                return [
                    'error_code' => -2,
                    'error_msg' => 'Unable to get response body'
                ];
            }

            $body[0]->registerXPathNamespace('ns1', 'urn:debisys-soap-services');
            $res = $body[0]->xpath('ns1:FreeUpMobilePortinResponse');
            if (count($res) < 1) {
                return [
                    'error_code' => -3,
                    'error_msg' => 'Unable to get XML in CDATA: ' . var_export($output, true)
                ];
            }

            $res_xml = $res[0]->return;
            $ret_new = simplexml_load_string($res_xml, null, LIBXML_NOCDATA);

            Helper::log('### EMIDA - FreeUpMobilePortin - RET NEW ###', [
                'ret_new' => $ret_new
            ]);

            $error_code = isset($ret_new->ResponseCode) ? $ret_new->ResponseCode->__toString() : '';
            $error_msg = isset($ret_new->ResponseMessage) ? $ret_new->ResponseMessage->__toString() : '';

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
                'min' => isset($ret_new->Min) ? $ret_new->Min : ''
            ];

        } catch (\Exception $ex) {

            $message = " - product_id : " . $product_id . "<br/>";
            $message .= " - carrier : " . $carrier . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] freeUp_activation() Exception', $message);

            return [
                'error_code' => 'EXP-' . $ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }


    public static function freeUp_portin_status($mdn, $trans_id) {
        try {

            self::init();

            $req = '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:debisys-soap-services">';
            $req .=     '<soapenv:Header/>';
            $req .=     '<soapenv:Body>';
            $req .=         '<urn:FreeUpMobilePortinStatus soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';
            $req .=             '<version xsi:type="xsd:string">01</version>';
            $req .=             '<terminalId xsi:type="xsd:string">' . self::$tid . '</terminalId>';
            $req .=             '<clerkId xsi:type="xsd:string">' . self::$clerk_id . '</clerkId>';
            $req .=             '<mdn xsi:type="xsd:string">' . $mdn . '</mdn>';
            $req .=             '<prn xsi:type="xsd:string"></prn>';
            $req .=             '<languageId xsi:type="xsd:string">1</languageId>';
            $req .=             '<invoiceNo xsi:type="xsd:string">' . $trans_id . '</invoiceNo>';
            $req .=         '</urn:FreeUpMobilePortinStatus>';
            $req .=     '</soapenv:Body>';
            $req .= '</soapenv:Envelope>';

            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"FreeUpMobilePortinStatus\""
                //"Content-length: ".strlen($req)
            ); //SOAPAction: your op URL

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);

            curl_setopt($ch, CURLINFO_HEADER_OUT, true);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            $info = curl_getinfo($ch);

            curl_close($ch);

            Helper::log('### freeUp_portin_status ###', [
                'req' => $req,
                'res' => $output,
                'info' => $info
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);
            $ret->registerXPathNamespace('SOAP-ENV', 'http://schemas.xmlsoap.org/soap/envelope/');
            $body = $ret->xpath('//SOAP-ENV:Body');
            if (count($body) < 1) {
                return [
                    'error_code' => -2,
                    'error_msg' => 'Unable to get response body'
                ];
            }

            $body[0]->registerXPathNamespace('ns1', 'urn:debisys-soap-services');
            $res = $body[0]->xpath('ns1:FreeUpMobilePortinStatusResponse');
            if (count($res) < 1) {
                return [
                    'error_code' => -3,
                    'error_msg' => 'Unable to get XML in CDATA: ' . var_export($output, true)
                ];
            }

            $res_xml = $res[0]->return;
            $ret_new = simplexml_load_string($res_xml, null, LIBXML_NOCDATA);

            Helper::log('### EMIDA - FreeUpMobilePortinStatus - RET NEW ###', [
                'ret_new' => $ret_new
            ]);

            $error_code = isset($ret_new->ResponseCode) ? $ret_new->ResponseCode->__toString() : '';
            $error_msg = isset($ret_new->ResponseMessage) ? $ret_new->ResponseMessage->__toString() : '';

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
                'min' => isset($ret_new->min) ? $ret_new->min : ''
            ];

        } catch (\Exception $ex) {

            $message = " - mdn : " . $mdn . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] freeUp_portin_status() Exception', $message);

            return [
                'error_code' => 'EX-' . $ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    ### Locus Activation
    public static function LocusActivateGSMAfcode($trans_id, $act_prod_id, $pin_prod_id, $afcode, $npa, $city, $zip) {
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

            $req = '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:debisys-soap-services">';
            $req .=     '<soapenv:Header/>';
            $req .=     '<soapenv:Body>';
            $req .=         '<urn:LocusActivateGSMAfcode soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';
            $req .=             '<terminalId xsi:type="xsd:string">' . self::$tid . '</terminalId>';
            $req .=             '<pinProductId xsi:type="xsd:string">' . $pin_prod_id . '</pinProductId>';
            $req .=             '<clerkId xsi:type="xsd:string">' . self::$clerk_id . '</clerkId>';
            $req .=             '<activationProductId xsi:type="xsd:string">' . $act_prod_id . '</activationProductId>';
            $req .=             '<languageId xsi:type="xsd:string">1</languageId>';
            $req .=             '<invoiceNo xsi:type="xsd:string">' . $trans_id . '</invoiceNo>';
            $req .=             '<version xsi:type="xsd:string">01</version>';

            $req .=             '<afcode xsi:type="xsd:string">' . $afcode . '</afcode>';
            $req .=             '<npa xsi:type="xsd:string">' . $npa . '</npa>';
            $req .=             '<city xsi:type="xsd:string">' . $city . '</city>';
            $req .=             '<zip xsi:type="xsd:string">' . $zip . '</zip>';

            $req .=         '</urn:LocusActivateGSMAfcode>';
            $req .=     '</soapenv:Body>';
            $req .= '</soapenv:Envelope>';

            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"LocusActivateGSMAfcode\""
                //"Content-length: ".strlen($req)
            ); //SOAPAction: your op URL

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);

            curl_setopt($ch, CURLINFO_HEADER_OUT, true);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            $info = curl_getinfo($ch);

            curl_close($ch);

            Helper::log('### LocusActivateGSMAfcode ###', [
                'req' => $req,
                'res' => $output,
                'info' => $info
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);
            $ret->registerXPathNamespace('SOAP-ENV', 'http://schemas.xmlsoap.org/soap/envelope/');
            $body = $ret->xpath('//SOAP-ENV:Body');
            if (count($body) < 1) {
                return [
                    'error_code' => -2,
                    'error_msg' => 'Unable to get response body'
                ];
            }

            $body[0]->registerXPathNamespace('ns1', 'urn:debisys-soap-services');
            $res = $body[0]->xpath('ns1:LocusActivateGSMAfcodeResponse');
            if (count($res) < 1) {
                return [
                    'error_code' => -3,
                    'error_msg' => 'Unable to get XML in CDATA: ' . var_export($output, true)
                ];
            }

            $res_xml = $res[0]->return;
            $ret_new = simplexml_load_string($res_xml, null, LIBXML_NOCDATA);

            Helper::log('### EMIDA - LocusActivateGSMAfcode - RET NEW ###', [
                'ret_new' => $ret_new
            ]);

            $error_code = isset($ret_new->ResponseCode) ? $ret_new->ResponseCode->__toString() : '';
            $error_msg = isset($ret_new->ResponseMessage) ? $ret_new->ResponseMessage->__toString() : '';

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
                'serial' => isset($ret_new->serial) ? $ret_new->serial : '',
                'min' => isset($ret_new->min) ? $ret_new->min : ''
            ];

        } catch (\Exception $ex) {

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

//    public static function LookUpTransactionByInvoiceNo($trans_id) {
//        try {
//
//            self::login();
//
//            $req = '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:debisys-soap-services">';
//            $req .=     '<soapenv:Header/>';
//            $req .=     '<soapenv:Body>';
//            $req .=         '<urn:LookUpTransactionByInvoiceNo soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';
//            $req .=             '<Version xsi:type="xsd:string">01</Version>';
//            $req .=             '<TerminalId xsi:type="xsd:string">' . self::$tid . '</TerminalId>';
//            $req .=             '<ClerkId xsi:type="xsd:string">' . self::$clerk_id . '</ClerkId>';
//            $req .=             '<InvoiceNo xsi:type="xsd:string">' . $trans_id . '</InvoiceNo>';
//            $req .=         '</urn:LookUpTransactionByInvoiceNo>';
//            $req .=     '</soapenv:Body>';
//            $req .= '</soapenv:Envelope>';
//
//            $headers = array(
//                "Connection: Keep-Alive",
//                "User-Agent: PHPSoapClient",
//                "Content-Type: text/xml; charset=utf-8",
//                "SOAPAction: \"LookUpTransactionByInvoiceNo\""
//            );
//
//            $ch = curl_init();
//            curl_setopt($ch, CURLOPT_URL, self::$api_url);
//            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
//            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
//            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
//            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
//            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
//            curl_setopt($ch, CURLOPT_POST, 1);
//            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
//            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//            $output = curl_exec($ch);
//            $info = curl_getinfo($ch);
//            curl_close($ch);
//
//            Helper::log('### LookUpTransactionByInvoiceNo ###', [
//                'req' => $req,
//                'res' => $output,
//                'info' => $info
//            ]);
//
//            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);
//            $ret->registerXPathNamespace('SOAP-ENV', 'http://schemas.xmlsoap.org/soap/envelope/');
//            $body = $ret->xpath('//SOAP-ENV:Body');
//
//            if (count($body) < 1) {
//                return [
//                    'error_code' => -2,
//                    'error_msg' => 'Unable to get response body'
//                ];
//            }
//
//            $body[0]->registerXPathNamespace('ns1', 'urn:debisys-soap-services');
//            $res = $body[0]->xpath('ns1:LookUpTransactionByInvoiceNoResponse');
//            if (count($res) < 1) {
//                return [
//                    'error_code' => -3,
//                    'error_msg' => 'Unable to get XML in CDATA: ' . var_export($output, true)
//                ];
//            }
//
//            $res_xml = $res[0]->return;
//            $ret_new = simplexml_load_string($res_xml, null, LIBXML_NOCDATA);
//
//            Helper::log('### EMIDA - LookUpTransactionByInvoiceNo - RET NEW ###', [
//                'ret_new' => $ret_new
//            ]);
//
//            $error_code = isset($ret_new->ResponseCode) ? $ret_new->ResponseCode->__toString() : '';
//            $error_msg = isset($ret_new->ResponseMessage) ? $ret_new->ResponseMessage->__toString() : '';
//
//            if (empty($error_code)) {
//                return [
//                    'error_code' => -4,
//                    'error_msg' => 'Unable to get response code from vendor'
//                ];
//            }
//
//            if ($error_code != '00') {
//                return [
//                    'error_code' => $error_code,
//                    'error_msg' => $error_msg
//                ];
//            }
//
//            return [
//                'error_code' => '',
//                'error_msg' => $error_msg
//            ];
//
//        } catch (\Exception $ex) {
//
//            $message = " - code : " . $ex->getCode() . "<br/>";
//            $message .= " - message : " . $ex->getMessage() . "<br/>";
//            $message .= " - trace : " . $ex->getTraceAsString();
//
//            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] LookUpTransactionByInvoiceNo() Exception', $message);
//
//            return [
//                'error_code' => $ex->getCode(),
//                'error_msg' => $ex->getMessage(),
//                'error_trace' => $ex->getTraceAsString()
//            ];
//        }
//    }

    public static function LocusActivateGSMsim($trans_id, $act_prod_id, $pin_prod_id, $sim, $npa, $city, $zip) {

        try {

//             if (getenv('APP_ENV') != 'production') {
//                 return [
//                     'error_code' => '',
//                     'error_msg' => 'DEV Test ... ',
//                     'min' => '1112223333'
//                 ];
//             }

            self::login();

            $req = '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:debisys-soap-services">';
            $req .=     '<soapenv:Header/>';
            $req .=     '<soapenv:Body>';
            $req .=         '<urn:LocusActivateGSMsim soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';
            $req .=             '<terminalId xsi:type="xsd:string">' . self::$tid . '</terminalId>';
            $req .=             '<pinProductId xsi:type="xsd:string">' . $pin_prod_id . '</pinProductId>';
            $req .=             '<clerkId xsi:type="xsd:string">' . self::$clerk_id . '</clerkId>';
            $req .=             '<activationProductId xsi:type="xsd:string">' . $act_prod_id . '</activationProductId>';
            $req .=             '<languageId xsi:type="xsd:string">1</languageId>';
            $req .=             '<invoiceNo xsi:type="xsd:string">' . $trans_id . '</invoiceNo>';
            $req .=             '<version xsi:type="xsd:string">01</version>';

            $req .=             '<npa xsi:type="xsd:string">' . $npa . '</npa>';
            $req .=             '<sim xsi:type="xsd:string">' . $sim . '</sim>';
            $req .=             '<city xsi:type="xsd:string">' . $city . '</city>';
            $req .=             '<zip xsi:type="xsd:string">' . $zip . '</zip>';

            $req .=         '</urn:LocusActivateGSMsim>';
            $req .=     '</soapenv:Body>';
            $req .= '</soapenv:Envelope>';

            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"LocusActivateGSMsim\""
                //"Content-length: ".strlen($req)
            ); //SOAPAction: your op URL

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);

            curl_setopt($ch, CURLINFO_HEADER_OUT, true);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            $info = curl_getinfo($ch);

            curl_close($ch);

            Helper::log('### LocusActivateGSMsim ###', [
                'req' => $req,
                'res' => $output,
                'info' => $info
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);
            $ret->registerXPathNamespace('SOAP-ENV', 'http://schemas.xmlsoap.org/soap/envelope/');
            $body = $ret->xpath('//SOAP-ENV:Body');
            if (count($body) < 1) {
                return [
                    'error_code' => -2,
                    'error_msg' => 'Unable to get response body'
                ];
            }

            $body[0]->registerXPathNamespace('ns1', 'urn:debisys-soap-services');
            $res = $body[0]->xpath('ns1:LocusActivateGSMsimResponse');
            if (count($res) < 1) {
                return [
                    'error_code' => -3,
                    'error_msg' => 'Unable to get XML in CDATA: ' . var_export($output, true)
                ];
            }

            $res_xml = $res[0]->return;
            $ret_new = simplexml_load_string($res_xml, null, LIBXML_NOCDATA);

            Helper::log('### EMIDA - LocusActivateGSMsim - RET NEW ###', [
                'ret_new' => $ret_new
            ]);

            $error_code = isset($ret_new->ResponseCode) ? $ret_new->ResponseCode->__toString() : '';
            $error_msg = isset($ret_new->ResponseMessage) ? $ret_new->ResponseMessage->__toString() : '';

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
                'serial' => isset($ret_new->serial) ? $ret_new->serial : '',
                'min' => isset($ret_new->min) ? $ret_new->min : ''
            ];

        } catch (\Exception $ex) {

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
        try {

            self::login();

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

            $req = '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:debisys-soap-services">';
            $req .=     '<soapenv:Header/>';
            $req .=     '<soapenv:Body>';
            $req .=         '<urn:H2OWirelessPortin soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';
            $req .=             '<version xsi:type="xsd:string">01</version>';
            $req .=             '<terminalId xsi:type="xsd:string">' . self::$tid . '</terminalId>';
            $req .=             '<clerkId xsi:type="xsd:string">' . self::$clerk_id . '</clerkId>';
            $req .=             '<productId xsi:type="xsd:string">' . $act_prod_id . '</productId>';
            $req .=             '<languageOption xsi:type="xsd:string">1</languageOption>';
            $req .=             '<invoiceNo xsi:type="xsd:string">' . $trans_id . '</invoiceNo>';
            $req .=             '<emailAddress xsi:type="xsd:string">' . $email . '</emailAddress>';
            $req .=             '<callBackNumber xsi:type="xsd:string">'.$call_back_phone.'</callBackNumber>';
            $req .=             '<oldServiceProvider xsi:type="xsd:string">' . $old_service_provider . '</oldServiceProvider>';
            $req .=             '<cellNumberToTransfer xsi:type="xsd:string">'.$number_to_port.'</cellNumberToTransfer>';
            $req .=             '<cellNumberContract xsi:type="xsd:string">' . $cell_number_contract . '</cellNumberContract>';
            $req .=             '<accountHolderFirst xsi:type="xsd:string">' . $first_name . '</accountHolderFirst>';
            $req .=             '<accountHolderLast xsi:type="xsd:string">' . $last_name . '</accountHolderLast>';
            $req .=             '<accountNumber xsi:type="xsd:string">' . $account_no . '</accountNumber>';
            $req .=             '<accountPasswordPin xsi:type="xsd:string">' . $account_pin . '</accountPasswordPin>';
            $req .=             '<accountAddress xsi:type="xsd:string">' . $address . '</accountAddress>';
            $req .=             '<city xsi:type="xsd:string">' . $city . '</city>';
            $req .=             '<state xsi:type="xsd:string">' . $state . '</state>';
            $req .=             '<zipCode xsi:type="xsd:string">' . $zip . '</zipCode>';
            $req .=             '<serviceType xsi:type="xsd:string">a</serviceType>';
            $req .=             '<sim xsi:type="xsd:string">' . $sim . '</sim>';
            $req .=             '<imei xsi:type="xsd:string">' . $imei . '</imei>';
            $req .=             '<plan xsi:type="xsd:string">' . $pin_prod_id . '</plan>';
            $req .=         '</urn:H2OWirelessPortin>';
            $req .=     '</soapenv:Body>';
            $req .= '</soapenv:Envelope>';

            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"H2OWirelessPortin\""
                //"Content-length: ".strlen($req)
            ); //SOAPAction: your op URL

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);

            curl_setopt($ch, CURLINFO_HEADER_OUT, true);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            $info = curl_getinfo($ch);

            curl_close($ch);

            Helper::log('### H2OWirelessPortin ###', [
                'req' => $req,
                'res' => $output,
                'info' => $info
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);
            $ret->registerXPathNamespace('SOAP-ENV', 'http://schemas.xmlsoap.org/soap/envelope/');
            $body = $ret->xpath('//SOAP-ENV:Body');
            if (count($body) < 1) {
                return [
                    'error_code' => -2,
                    'error_msg' => 'Unable to get response body'
                ];
            }

            $body[0]->registerXPathNamespace('ns1', 'urn:debisys-soap-services');
            $res = $body[0]->xpath('ns1:H2OWirelessPortinResponse');
            if (count($res) < 1) {
                return [
                    'error_code' => -3,
                    'error_msg' => 'Unable to get XML in CDATA: ' . var_export($output, true)
                ];
            }

            $res_xml = $res[0]->return;
            $ret_new = simplexml_load_string($res_xml, null, LIBXML_NOCDATA);

            Helper::log('### EMIDA - H2OWirelessPortin - RET NEW ###', [
                'ret_new' => $ret_new
            ]);

            $error_code = isset($ret_new->ResponseCode) ? $ret_new->ResponseCode->__toString() : '';
            $error_msg = isset($ret_new->ResponseMessage) ? $ret_new->ResponseMessage->__toString() : '';

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
                'serial' => isset($ret_new->PORTINREFERENCENUMBER) ? $ret_new->PORTINREFERENCENUMBER : ''
            ];

        } catch (\Exception $ex) {

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

            $req = '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:debisys-soap-services">';
            $req .=     '<soapenv:Header/>';
            $req .=     '<soapenv:Body>';
            $req .=         '<urn:LocusCreateMultiLine2Lines soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';
            $req .=             '<terminalId xsi:type="xsd:string">' . self::$tid . '</terminalId>';
            $req .=             '<clerkId xsi:type="xsd:string">' . self::$clerk_id . '</clerkId>';
            $req .=             '<languageId xsi:type="xsd:string">1</languageId>';
            $req .=             '<invoiceNo xsi:type="xsd:string">' . $trans_id . '</invoiceNo>';
            $req .=             '<version xsi:type="xsd:string">01</version>';
            $req .=             '<productId xsi:type="xsd:string">' . $product_id . '</productId>';

            $req .=              '<amount xsi:type="xsd:string">50</amount>';
            $req .=             '<line1 xsi:type="xsd:string">' . $sim1 . '</line1>';
            $req .=             '<line2 xsi:type="xsd:string">' . $sim2 . '</line2>';

            $req .=         '</urn:LocusCreateMultiLine2Lines>';
            $req .=     '</soapenv:Body>';
            $req .= '</soapenv:Envelope>';

            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"LocusCreateMultiLine2Lines\""
                //"Content-length: ".strlen($req)
            ); //SOAPAction: your op URL

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);

            curl_setopt($ch, CURLINFO_HEADER_OUT, true);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            $info = curl_getinfo($ch);

            curl_close($ch);

            Helper::log('### LocusCreateMultiLine2Lines ###', [
                'req' => $req,
                'res' => $output,
                'info' => $info
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);
            $ret->registerXPathNamespace('SOAP-ENV', 'http://schemas.xmlsoap.org/soap/envelope/');
            $body = $ret->xpath('//SOAP-ENV:Body');
            if (count($body) < 1) {
                return [
                    'error_code' => -2,
                    'error_msg' => 'Unable to get response body'
                ];
            }

            $body[0]->registerXPathNamespace('ns1', 'urn:debisys-soap-services');
            $res = $body[0]->xpath('ns1:LocusCreateMultiLine2LinesResponse');
            if (count($res) < 1) {
                return [
                    'error_code' => -3,
                    'error_msg' => 'Unable to get XML in CDATA: ' . var_export($output, true)
                ];
            }

            $res_xml = $res[0]->return;
            $ret_new = simplexml_load_string($res_xml, null, LIBXML_NOCDATA);

            Helper::log('### EMIDA - LocusCreateMultiLine2Lines - RET NEW ###', [
                'ret_new' => $ret_new
            ]);

            $error_code = isset($ret_new->ResponseCode) ? $ret_new->ResponseCode->__toString() : '';
            $error_msg = isset($ret_new->ResponseMessage) ? $ret_new->ResponseMessage->__toString() : '';

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
                'transaction_id' => isset($ret_new->TransactionId) ? $ret_new->TransactionId : ''
            ];

        } catch (\Exception $ex) {

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

            $req = '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:debisys-soap-services">';
            $req .=     '<soapenv:Header/>';
            $req .=     '<soapenv:Body>';
            $req .=         '<urn:LocusCreateMultiLine4Lines soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';
            $req .=             '<terminalId xsi:type="xsd:string">' . self::$tid . '</terminalId>';
            $req .=             '<clerkId xsi:type="xsd:string">' . self::$clerk_id . '</clerkId>';
            $req .=             '<languageId xsi:type="xsd:string">1</languageId>';
            $req .=             '<invoiceNo xsi:type="xsd:string">' . $trans_id . '</invoiceNo>';
            $req .=             '<version xsi:type="xsd:string">01</version>';
            $req .=             '<productId xsi:type="xsd:string">' . $product_id . '</productId>';

            $req .=              '<amount xsi:type="xsd:string">100</amount>';
            $req .=             '<line1 xsi:type="xsd:string">' . $sim1 . '</line1>';
            $req .=             '<line2 xsi:type="xsd:string">' . $sim2 . '</line2>';
            $req .=             '<line3 xsi:type="xsd:string">' . $sim3 . '</line3>';
            $req .=             '<line4 xsi:type="xsd:string">' . $sim4 . '</line4>';

            $req .=         '</urn:LocusCreateMultiLine4Lines>';
            $req .=     '</soapenv:Body>';
            $req .= '</soapenv:Envelope>';

            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"LocusCreateMultiLine4Lines\""
                //"Content-length: ".strlen($req)
            ); //SOAPAction: your op URL

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);

            curl_setopt($ch, CURLINFO_HEADER_OUT, true);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            $info = curl_getinfo($ch);

            curl_close($ch);

            Helper::log('### LocusCreateMultiLine4Lines ###', [
                'req' => $req,
                'res' => $output,
                'info' => $info
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);
            $ret->registerXPathNamespace('SOAP-ENV', 'http://schemas.xmlsoap.org/soap/envelope/');
            $body = $ret->xpath('//SOAP-ENV:Body');
            if (count($body) < 1) {
                return [
                    'error_code' => -2,
                    'error_msg' => 'Unable to get response body'
                ];
            }

            $body[0]->registerXPathNamespace('ns1', 'urn:debisys-soap-services');
            $res = $body[0]->xpath('ns1:LocusCreateMultiLine4LinesResponse');
            if (count($res) < 1) {
                return [
                    'error_code' => -3,
                    'error_msg' => 'Unable to get XML in CDATA: ' . var_export($output, true)
                ];
            }

            $res_xml = $res[0]->return;
            $ret_new = simplexml_load_string($res_xml, null, LIBXML_NOCDATA);

            Helper::log('### EMIDA - LocusCreateMultiLine4Lines - RET NEW ###', [
                'ret_new' => $ret_new
            ]);

            $error_code = isset($ret_new->ResponseCode) ? $ret_new->ResponseCode->__toString() : '';
            $error_msg = isset($ret_new->ResponseMessage) ? $ret_new->ResponseMessage->__toString() : '';

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
                'transaction_id' => isset($ret_new->TransactionId) ? $ret_new->TransactionId : ''
            ];

        } catch (\Exception $ex) {

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

            $req = '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:debisys-soap-services">';
            $req .=     '<soapenv:Header/>';
            $req .=     '<soapenv:Body>';
            $req .=         '<urn:LycaActivationRTR soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';
            $req .=             '<version xsi:type="xsd:string">01</version>';
            $req .=             '<terminalId xsi:type="xsd:string">' . self::$tid . '</terminalId>';
            $req .=             '<clerkId xsi:type="xsd:string">' . self::$clerk_id . '</clerkId>';
            $req .=             '<productId xsi:type="xsd:string">' . $product_id . '</productId>';

            $req .=             '<sim xsi:type="xsd:string">' . $sim . '</sim>';
            $req .=             '<zipCode xsi:type="xsd:string">' . $zip . '</zipCode>';

            $req .=             '<languageOption xsi:type="xsd:string">1</languageOption>';
            $req .=             '<contactEmail xsi:type="xsd:string"></contactEmail>';
            $req .=             '<amount xsi:type="xsd:string">' . $amount . '</amount>';
            $req .=             '<invoice xsi:type="xsd:string">' . $trans_id . '</invoice>';
            $req .=             '<languageId xsi:type="xsd:string">1</languageId>';

            $req .=         '</urn:LycaActivationRTR>';
            $req .=     '</soapenv:Body>';
            $req .= '</soapenv:Envelope>';

            $headers = array(
              "Connection: Keep-Alive",
              "User-Agent: PHPSoapClient",
              "Content-Type: text/xml; charset=utf-8",
              "SOAPAction: \"LycaActivationRTR\""
                //"Content-length: ".strlen($req)
            ); //SOAPAction: your op URL

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);

            curl_setopt($ch, CURLINFO_HEADER_OUT, true);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            $info = curl_getinfo($ch);

            curl_close($ch);

            Helper::log('### LycaActivationRTR ###', [
              'req' => $req,
              'res' => $output,
              'info' => $info
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);
            $ret->registerXPathNamespace('SOAP-ENV', 'http://schemas.xmlsoap.org/soap/envelope/');
            $body = $ret->xpath('//SOAP-ENV:Body');
            if (count($body) < 1) {
                return [
                  'error_code' => -2,
                  'error_msg' => 'Unable to get response body'
                ];
            }

            $body[0]->registerXPathNamespace('ns1', 'urn:debisys-soap-services');
            $res = $body[0]->xpath('ns1:LycaActivationRTRResponse');
            if (count($res) < 1) {
                return [
                  'error_code' => -3,
                  'error_msg' => 'Unable to get XML in CDATA: ' . var_export($output, true)
                ];
            }

            $res_xml = $res[0]->return;
            $ret_new = simplexml_load_string($res_xml, null, LIBXML_NOCDATA);

            Helper::log('### EMIDA - LycaActivationRTR - RET NEW ###', [
              'ret_new' => $ret_new
            ]);

            $error_code = isset($ret_new->ResponseCode) ? $ret_new->ResponseCode->__toString() : '';
            $error_msg = isset($ret_new->ResponseMessage) ? $ret_new->ResponseMessage->__toString() : '';

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
                'mdn' => isset($ret_new->MIN) ? $ret_new->MIN->__toString() : '' ,                                            //isset($ret_new->MIN) ? $ret_new->MIN : '',
                'ref_number' =>  isset($ret_new->PORTINREFERENCENUMBER) ? $ret_new->PORTINREFERENCENUMBER->__toString() : ''  //isset($ret_new->PORTINREFERENCENUMBER) ? $ret_new->PORTINREFERENCENUMBER : ''
            ];

        } catch (\Exception $ex) {

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

            $req = '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:debisys-soap-services">';
            $req .=     '<soapenv:Header/>';
            $req .=     '<soapenv:Body>';
            $req .=         '<urn:LycaPortinRTR soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';

            $req .=             '<version xsi:type="xsd:string">01</version>';
            $req .=             '<terminalId xsi:type="xsd:string">' . self::$tid . '</terminalId>';
            $req .=             '<clerkId xsi:type="xsd:string">' . self::$clerk_id . '</clerkId>';
            $req .=             '<productId xsi:type="xsd:string">' . $product_id . '</productId>';

            $req .=             '<sim xsi:type="xsd:string">' . $sim . '</sim>';
            $req .=             '<msisdn xsi:type="xsd:string">' . $mdn . '</msisdn>';
            $req .=             '<accountNumber xsi:type="xsd:string">' . $account_no . '</accountNumber>';
            $req .=             '<password xsi:type="xsd:string">' . $account_psw . '</password>';
            $req .=             '<zipCode xsi:type="xsd:string">' . $zip . '</zipCode>';

            $req .=             '<languageOption xsi:type="xsd:string">1</languageOption>';
            $req .=             '<contactEmail xsi:type="xsd:string"></contactEmail>';
            $req .=             '<amount xsi:type="xsd:string">' . $amount . '</amount>';
            $req .=             '<invoice xsi:type="xsd:string">' . $trans_id . '</invoice>';
            $req .=             '<languageId xsi:type="xsd:string">1</languageId>';

            $req .=         '</urn:LycaPortinRTR>';
            $req .=     '</soapenv:Body>';
            $req .= '</soapenv:Envelope>';

            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"LycaPortinRTR\""
                //"Content-length: ".strlen($req)
            ); //SOAPAction: your op URL

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);

            curl_setopt($ch, CURLINFO_HEADER_OUT, true);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            $info = curl_getinfo($ch);

            curl_close($ch);

            Helper::log('### LycaPortinRTR ###', [
                'req' => $req,
                'res' => $output,
                'info' => $info
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);
            $ret->registerXPathNamespace('SOAP-ENV', 'http://schemas.xmlsoap.org/soap/envelope/');
            $body = $ret->xpath('//SOAP-ENV:Body');
            if (count($body) < 1) {
                return [
                    'error_code' => -2,
                    'error_msg' => 'Unable to get response body'
                ];
            }

            $body[0]->registerXPathNamespace('ns1', 'urn:debisys-soap-services');
            $res = $body[0]->xpath('ns1:LycaPortinRTRResponse');
            if (count($res) < 1) {
                return [
                    'error_code' => -3,
                    'error_msg' => 'Unable to get XML in CDATA: ' . var_export($output, true)
                ];
            }

            $res_xml = $res[0]->return;
            $ret_new = simplexml_load_string($res_xml, null, LIBXML_NOCDATA);

            Helper::log('### EMIDA - LycaPortinRTR - RET NEW ###', [
                'ret_new' => $ret_new
            ]);

            $error_code = isset($ret_new->ResponseCode) ? $ret_new->ResponseCode->__toString() : '';
            $error_msg = isset($ret_new->ResponseMessage) ? $ret_new->ResponseMessage->__toString() : '';

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
                'mdn'           =>isset($ret_new->MIN) ? $ret_new->MIN : '',
                'ref_number'   => isset($ret_new->PORTINREFERENCENUMBER) ? $ret_new->PORTINREFERENCENUMBER : ''
            ];

        } catch (\Exception $ex) {

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

            $req = '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:debisys-soap-services">';
            $req .=     '<soapenv:Header/>';
            $req .=     '<soapenv:Body>';
            $req .=         '<urn:LycaPortInDetails soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';

            $req .=             '<version xsi:type="xsd:string">01</version>';
            $req .=             '<siteId xsi:type="xsd:string">' . self::$tid . '</siteId>';
            $req .=             '<clerkId xsi:type="xsd:string">' . self::$clerk_id . '</clerkId>';
            $req .=             '<productId xsi:type="xsd:string">' . $product_id . '</productId>';
            $req .=             '<languageOption xsi:type="xsd:string">1</languageOption>';

            $req .=             '<referenceType xsi:type="xsd:string">REF_CODE</referenceType>';
            $req .=             '<referenceNumber xsi:type="xsd:string">' . $ref_code . '</referenceNumber>';

            $req .=         '</urn:LycaPortInDetails>';
            $req .=     '</soapenv:Body>';
            $req .= '</soapenv:Envelope>';

            $headers = array(
              "Connection: Keep-Alive",
              "User-Agent: PHPSoapClient",
              "Content-Type: text/xml; charset=utf-8",
              "SOAPAction: \"ModifyPortIn\""
                //"Content-length: ".strlen($req)
            ); //SOAPAction: your op URL

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);

            curl_setopt($ch, CURLINFO_HEADER_OUT, true);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            $info = curl_getinfo($ch);

            curl_close($ch);

            Helper::log('### LycaPortInDetails ###', [
              'req' => $req,
              'res' => $output,
              'info' => $info
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);
            $ret->registerXPathNamespace('SOAP-ENV', 'http://schemas.xmlsoap.org/soap/envelope/');
            $body = $ret->xpath('//SOAP-ENV:Body');
            if (count($body) < 1) {
                return [
                  'error_code' => -2,
                  'error_msg' => 'Unable to get response body'
                ];
            }

            $body[0]->registerXPathNamespace('ns1', 'urn:debisys-soap-services');
            $res = $body[0]->xpath('ns1:LycaPortInDetailsResponse');
            if (count($res) < 1) {
                return [
                  'error_code' => -3,
                  'error_msg' => 'Unable to get XML in CDATA: ' . var_export($output, true)
                ];
            }

            $res_xml = $res[0]->return;
            $ret_new = simplexml_load_string($res_xml, null, LIBXML_NOCDATA);

            Helper::log('### EMIDA - LycaPortInDetails - RET NEW ###', [
              'ret_new' => $ret_new
            ]);

            $error_code = isset($ret_new->ResponseCode) ? $ret_new->ResponseCode->__toString() : '';
            $error_msg = (isset($ret_new->ModifyPortInMessage) ? $ret_new->ModifyPortInMessage->__toString() : '') . ', ' . (isset($ret_new->RejectReason) ? $ret_new->RejectReason->__toString() : '');

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
                  'lyca_ref_code' => isset($ret_new->ReferenceCode) ? $ret_new->ReferenceCode->__toString() : '',
                  'lyca_return_desc' => isset($ret_new->ReturnDescription) ? $ret_new->ReturnDescription->__toString() : '',
                  'transaction_id' => isset($ret_new->TransactionId) ? $ret_new->TransactionId : '',
                ];
            }

            return [
              'error_code' => '',
              'error_msg' => $error_msg,
              'lyca_ref_code' => isset($ret_new->ReferenceCode) ? $ret_new->ReferenceCode->__toString() : '',
              'lyca_return_desc' => isset($ret_new->ReturnDescription) ? $ret_new->ReturnDescription->__toString() : '',
              'transaction_id' => isset($ret_new->TransactionId) ? $ret_new->TransactionId : ''
            ];

        } catch (\Exception $ex) {

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

            $req = '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:debisys-soap-services">';
            $req .=     '<soapenv:Header/>';
            $req .=     '<soapenv:Body>';
            $req .=         '<urn:LycaModifyPortIn soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';

            $req .=             '<version xsi:type="xsd:string">01</version>';
            $req .=             '<siteId xsi:type="xsd:string">' . self::$tid . '</siteId>';
            $req .=             '<clerkId xsi:type="xsd:string">' . self::$clerk_id . '</clerkId>';
            $req .=             '<productId xsi:type="xsd:string">' . $product_id . '</productId>';
            $req .=             '<languageOption xsi:type="xsd:string">1</languageOption>';

            $req .=             '<referenceNumber xsi:type="xsd:string">' . $reference_no . '</referenceNumber>';
            $req .=             '<portInNumber xsi:type="xsd:string">' . $mdn . '</portInNumber>';
            $req .=             '<portInSIMNumber xsi:type="xsd:string">' . $sim . '</portInSIMNumber>';
            $req .=             '<portInAccountNumber xsi:type="xsd:string">' . $account_no . '</portInAccountNumber>';
            $req .=             '<portInPasswordPin xsi:type="xsd:string">' . $account_psw . '</portInPasswordPin>';
            $req .=             '<portInZipCode xsi:type="xsd:string">' . $zip . '</portInZipCode>';

            $req .=         '</urn:LycaModifyPortIn>';
            $req .=     '</soapenv:Body>';
            $req .= '</soapenv:Envelope>';

            $headers = array(
              "Connection: Keep-Alive",
              "User-Agent: PHPSoapClient",
              "Content-Type: text/xml; charset=utf-8",
              "SOAPAction: \"LycaModifyPortIn\""
                //"Content-length: ".strlen($req)
            ); //SOAPAction: your op URL

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);

            curl_setopt($ch, CURLINFO_HEADER_OUT, true);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            $info = curl_getinfo($ch);

            curl_close($ch);

            Helper::log('### ModifyPortIn ###', [
              'req' => $req,
              'res' => $output,
              'info' => $info
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);
            $ret->registerXPathNamespace('SOAP-ENV', 'http://schemas.xmlsoap.org/soap/envelope/');
            $body = $ret->xpath('//SOAP-ENV:Body');
            if (count($body) < 1) {
                return [
                  'error_code' => -2,
                  'error_msg' => 'Unable to get response body'
                ];
            }

            $body[0]->registerXPathNamespace('ns1', 'urn:debisys-soap-services');
            $res = $body[0]->xpath('ns1:LycaModifyPortInResponse');
            if (count($res) < 1) {
                return [
                  'error_code' => -3,
                  'error_msg' => 'Unable to get XML in CDATA: ' . var_export($output, true)
                ];
            }

            $res_xml = $res[0]->return;
            $ret_new = simplexml_load_string($res_xml, null, LIBXML_NOCDATA);

            Helper::log('### EMIDA - ModifyPortIn - RET NEW ###', [
              'ret_new' => $ret_new
            ]);

            $error_code = isset($ret_new->ResponseCode) ? $ret_new->ResponseCode->__toString() : '';
            $error_msg = (isset($ret_new->ModifyPortInMessage) ? $ret_new->ModifyPortInMessage->__toString() : '');

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
                  'lyca_error_code' => isset($ret_new->LycaErrorCode) ? $ret_new->LycaErrorCode->__toString() : '',
                  'lyca_error_desc' => isset($ret_new->LycaErrorDescription) ? $ret_new->LycaErrorDescription->__toString() : ''
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => $error_msg,

                'lyca_error_code' => isset($ret_new->LycaErrorCode) ? $ret_new->LycaErrorCode->__toString() : '',
                'lyca_error_desc' => isset($ret_new->LycaErrorDescription) ? $ret_new->LycaErrorDescription->__toString() : '',
                'transaction_id' => isset($ret_new->TransactionId) ? $ret_new->TransactionId : ''
            ];

        } catch (\Exception $ex) {

            $message = " - product_id : " . $product_id . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] ModifyPortIn() Exception', $message);

            return [
              'error_code' => 'E:' .$ex->getCode(),
              'error_msg' => $ex->getMessage(),
              'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function LycaCancelPortIn($reference_no, $product_id, $amount, $mdn, $emida_trans_id) {
        try {

            // if (getenv('APP_ENV') != 'production') {
            //     return [
            //         'error_code' => '',
            //         'error_msg' => 'DEV Test ... ',
            //         'min' => '1112223333'
            //     ];
            // }

            self::init();

            $req = '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:debisys-soap-services">';
            $req .=     '<soapenv:Header/>';
            $req .=     '<soapenv:Body>';
            $req .=         '<urn:CancelPortIn soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';

            $req .=             '<version xsi:type="xsd:string">01</version>';
            $req .=             '<terminalId xsi:type="xsd:string">' . self::$tid . '</terminalId>';
            $req .=             '<clerkId xsi:type="xsd:string">' . self::$clerk_id . '</clerkId>';
            $req .=             '<productId xsi:type="xsd:string">' . $product_id . '</productId>';
            $req .=             '<amount xsi:type="xsd:string">' . $amount . '</amount>';
            $req .=             '<languageOption xsi:type="xsd:string">1</languageOption>';

            $req .=             '<referenceNumber xsi:type="xsd:string">' . $reference_no . '</referenceNumber>';
            $req .=             '<portInNumber xsi:type="xsd:string">' . $mdn . '</portInNumber>';
            $req .=             '<emidaTransactionId xsi:type="xsd:string">' . $emida_trans_id . '</emidaTransactionId>>';

            $req .=         '</urn:CancelPortIn>';
            $req .=     '</soapenv:Body>';
            $req .= '</soapenv:Envelope>';

            $headers = array(
              "Connection: Keep-Alive",
              "User-Agent: PHPSoapClient",
              "Content-Type: text/xml; charset=utf-8",
              "SOAPAction: \"ModifyPortIn\""
                //"Content-length: ".strlen($req)
            ); //SOAPAction: your op URL

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);

            curl_setopt($ch, CURLINFO_HEADER_OUT, true);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            $info = curl_getinfo($ch);

            curl_close($ch);

            Helper::log('### CancelPortIn ###', [
              'req' => $req,
              'res' => $output,
              'info' => $info
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);
            $ret->registerXPathNamespace('SOAP-ENV', 'http://schemas.xmlsoap.org/soap/envelope/');
            $body = $ret->xpath('//SOAP-ENV:Body');
            if (count($body) < 1) {
                return [
                  'error_code' => -2,
                  'error_msg' => 'Unable to get response body'
                ];
            }

            $body[0]->registerXPathNamespace('ns1', 'urn:debisys-soap-services');
            $res = $body[0]->xpath('ns1:CancelPortInResponse');
            if (count($res) < 1) {
                return [
                  'error_code' => -3,
                  'error_msg' => 'Unable to get XML in CDATA: ' . var_export($output, true)
                ];
            }

            $res_xml = $res[0]->return;
            $ret_new = simplexml_load_string($res_xml, null, LIBXML_NOCDATA);

            Helper::log('### EMIDA - CancelPortIn - RET NEW ###', [
              'ret_new' => $ret_new
            ]);

            $error_code = isset($ret_new->ResponseCode) ? $ret_new->ResponseCode->__toString() : '';
            $error_msg = (isset($ret_new->CancelPortinMessage) ? $ret_new->CancelPortinMessage->__toString() : '');

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
              'transaction_id' => isset($ret_new->TransactionId) ? $ret_new->TransactionId : ''
            ];

        } catch (\Exception $ex) {

            $message = " - product_id : " . $product_id . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[EMIDA][' . getenv('APP_ENV') . '] LycaCancelPortIn() Exception', $message);

            return [
              'error_code' => 'E:' .$ex->getCode(),
              'error_msg' => $ex->getMessage(),
              'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function LycaCheckPortInEligibility($product_id, $mdn, $sim) {
        try {

            // if (getenv('APP_ENV') != 'production') {
            //     return [
            //         'error_code' => '',
            //         'error_msg' => 'DEV Test ... ',
            //         'min' => '1112223333'
            //     ];
            // }

            self::init();

            $req = '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:debisys-soap-services">';
            $req .=     '<soapenv:Header/>';
            $req .=     '<soapenv:Body>';
            $req .=         '<urn:LycaCheckPortInEligibility soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';

            $req .=             '<version xsi:type="xsd:string">01</version>';
            $req .=             '<siteId xsi:type="xsd:string">' . self::$tid . '</siteId>';
            $req .=             '<clerkId xsi:type="xsd:string">' . self::$clerk_id . '</clerkId>';
            $req .=             '<productId xsi:type="xsd:string">' . $product_id . '</productId>';
            $req .=             '<languageOption xsi:type="xsd:string">1</languageOption>';

            $req .=             '<portinPhoneNumber xsi:type="xsd:string">' . $mdn . '</portinPhoneNumber>>';
            $req .=             '<portinSIMNumber xsi:type="xsd:string">' . $sim . '</portinSIMNumber>>';

            $req .=         '</urn:LycaCheckPortInEligibility>';
            $req .=     '</soapenv:Body>';
            $req .= '</soapenv:Envelope>';

            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"LycaCheckPortInEligibility\""
                //"Content-length: ".strlen($req)
            ); //SOAPAction: your op URL

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);

            curl_setopt($ch, CURLINFO_HEADER_OUT, true);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            $info = curl_getinfo($ch);

            curl_close($ch);

            Helper::log('### LycaCheckPortInEligibility ###', [
                'req' => $req,
                'res' => $output,
                'info' => $info
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);
            $ret->registerXPathNamespace('SOAP-ENV', 'http://schemas.xmlsoap.org/soap/envelope/');
            $body = $ret->xpath('//SOAP-ENV:Body');
            if (count($body) < 1) {
                return [
                    'error_code' => -2,
                    'error_msg' => 'Unable to get response body'
                ];
            }

            $body[0]->registerXPathNamespace('ns1', 'urn:debisys-soap-services');
            $res = $body[0]->xpath('ns1:CancelPortInResponse');
            if (count($res) < 1) {
                return [
                    'error_code' => -3,
                    'error_msg' => 'Unable to get XML in CDATA: ' . var_export($output, true)
                ];
            }

            $res_xml = $res[0]->return;
            $ret_new = simplexml_load_string($res_xml, null, LIBXML_NOCDATA);

            Helper::log('### EMIDA - LycaCheckPortInEligibility - RET NEW ###', [
                'ret_new' => $ret_new
            ]);

            $res_code = isset($ret_new->ResponseCode) ? $ret_new->ResponseCode->__toString() : '';
            $res_msg = isset($ret_new->ResponseMessage) ? $ret_new->ResponseMessage->__toString() : '';

            $lyca_error_code = isset($ret_new->LycaErrorCode) ? $ret_new->LycaErrorCode->__toString() : '';
            $lyca_error_desc = isset($ret_new->LycaErrorDescription) ? $ret_new->LycaErrorDescription->__toString() : '';

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
                'transaction_id' => isset($ret_new->TransactionId) ? $ret_new->TransactionId : ''
            ];

        } catch (\Exception $ex) {

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

//    public static function ttest() {
//        try {
//
//
//            self::init();
//
//            $req = '';
//
//            $headers = array(
//                "Connection: Keep-Alive",
//                "User-Agent: PHPSoapClient",
//                "Content-Type: text/xml; charset=utf-8",
//                "SOAPAction: \"FreeUpMobileActivations\""
//                //"Content-length: ".strlen($req)
//            ); //SOAPAction: your op URL
//
//            $ch = curl_init();
//            curl_setopt($ch, CURLOPT_URL, 'http://demo.softpayplus.com/tresp');
//
//            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
//
//            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
////
////            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
////            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
////            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
//            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
////            curl_setopt($ch, CURLOPT_POST, 1);
////            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
//            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//            $output = curl_exec($ch);
//
//            $info = curl_getinfo($ch);
//
//            curl_close($ch);
//
//            //echo 'returns:' . $output . "<br>";
//            echo 'info:' .  print_r($info)  . "<br>";
//            echo 'done in emida::ttest()...<br>';
//
//        } catch (\Exception $ex) {
//
//            $message = " - code : " . $ex->getCode() . "<br/>";
//            $message .= " - message : " . $ex->getMessage() . "<br/>";
//            $message .= " - trace : " . $ex->getTraceAsString();
//
//            echo $message;
//        }
//    }
}