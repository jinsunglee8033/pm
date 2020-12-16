<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 11/27/17
 * Time: 4:33 PM
 */

namespace App\Lib;


class telestar
{
    private static $api_url = 'http://demo.geth2owireless.com/api';
    private static $api_user = 'pm_api';
    private static $api_pwd = 'aaaa1234';

    private static function init() {
        if (getenv('APP_ENV') == 'production') {
            self::$api_url = 'https://www.geth2owireless.com/api';
            self::$api_user = 'pm_api';
            self::$api_pwd = 'Jyk5183!!';
        }
    }

    public static function activate($cid, $sku, $amt, $sim, $afcode, $npa, $zip, $dc, $dp) {
        try {

            self::init();

            $req = '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:GetH2OService">';
            $req .=     '<soapenv:Header/>';
            $req .=         '<soapenv:Body>';
            $req .=             '<urn:activate soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';
            $req .=                 '<user_id xsi:type="xsd:string">' . self::$api_user . '</user_id>';
            $req .=                 '<password xsi:type="xsd:string">' . self::$api_pwd . '</password>';
            $req .=                 '<cid xsi:type="xsd:string">' . $cid . '</cid>';
            $req .=                 '<prod_id xsi:type="xsd:string">' . $sku . '</prod_id>';
            $req .=                 '<amt xsi:type="xsd:string">' . $amt . '</amt>';
            $req .=                 '<sim xsi:type="xsd:string">' . $sim . '</sim>';
            $req .=                 '<afcode xsi:type="xsd:string">' . $afcode. '</afcode>';
            $req .=                 '<npa xsi:type="xsd:string">' . $npa . '</npa>';
            $req .=                 '<zip xsi:type="xsd:string">' . $zip . '</zip>';
            $req .=                 '<comment1 xsi:type="xsd:string">' . $cid . '</comment1>';
            $req .=                 '<dc xsi:type="xsd:string">' . $dc . '</dc>';
            $req .=                 '<dp xsi:type="xsd:string">' . $dp . '</dp>';
            $req .=             '</urn:activate>';
            $req .=         '</soapenv:Body>';
            $req .=     '</soapenv:Envelope>';

            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"activate\""
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

            Helper::log('### Telestar H2O activate ###', [
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

            $body[0]->registerXPathNamespace('ns1', 'urn:GetH2OService');
            $res = $body[0]->xpath('ns1:activateResponse');
            if (count($res) < 1) {
                return [
                    'error_code' => -3,
                    'error_msg' => 'Unable to get XML in CDATA: ' . var_export($output, true)
                ];
            }

            $ret_new = $res[0];
            $error_code = strval($ret_new->error_code);
            $error_msg = strval($ret_new->error_msg);

            if ($error_code != '') {
                return [
                    'error_code' => $error_code,
                    'error_msg' => $error_msg
                ];
            }

            $tx_id = isset($ret_new->tx_id) ? $ret_new->tx_id->__toString() : "";
            $min = isset($ret_new->min) ? $ret_new->min->__toString() : "";

            if (empty($tx_id)) {
                return [
                    'error_code' => -5,
                    'error_msg' => 'Empty vendor TX.ID returned'
                ];
            }

            if (empty($min)) {
                return [
                    'error_code' => -5,
                    'error_msg' => 'Empty vendor TX.ID returned'
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'serial' => $tx_id,
                'min' => $min
            ];

        } catch (\Exception $ex) {

            $message = " - sku : " . $sku. "<br/>";
            $message .= " - amt : " . $amt . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[TELESTAR][' . getenv('APP_ENV') . '] activate() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function portin(
        $cid, $sku, $amt, $account_no, $account_pin,
        $street, $city, $state, $zip, $name, $email, $cb_phone,
        $sim, $number_to_port, $old_carrier, $old_carrier_contract,
        $dc, $dp
    ) {
        try {

            self::init();

            $req = '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:GetH2OService">';
            $req .= '<soapenv:Header/>';
            $req .= '<soapenv:Body>';
            $req .=     '<urn:portin soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';
            $req .=         '<user_id xsi:type="xsd:string">' . self::$api_user . '</user_id>';
            $req .=         '<password xsi:type="xsd:string">' . self::$api_pwd . '</password>';
            $req .=         '<cid xsi:type="xsd:string">' . $cid . '</cid>';
            $req .=         '<prod_id xsi:type="xsd:string">' . $sku . '</prod_id>';
            $req .=         '<amt xsi:type="xsd:string">' . $amt . '</amt>';
            $req .=         '<acctno xsi:type="xsd:string">' . $account_no . '</acctno>';
            $req .=         '<pass xsi:type="xsd:string">' . $account_pin . '</pass>';
            $req .=         '<street xsi:type="xsd:string">' . $street . '</street>';
            $req .=         '<city xsi:type="xsd:string">' . $city . '</city>';
            $req .=         '<state xsi:type="xsd:string">' . $state . '</state>';
            $req .=         '<zip xsi:type="xsd:string">' . $zip . '</zip>';
            $req .=         '<name xsi:type="xsd:string">' . $name . '</name>';
            $req .=         '<email xsi:type="xsd:string">' . $email . '</email>';
            $req .=         '<call_back_phone xsi:type="xsd:string">' . $cb_phone . '</call_back_phone>';
            $req .=         '<sim xsi:type="xsd:string">' . $sim . '</sim>';
            $req .=         '<number_to_port xsi:type="xsd:string">' . $number_to_port . '</number_to_port>';
            $req .=         '<old_service_provider xsi:type="xsd:string">' . htmlspecialchars($old_carrier, ENT_QUOTES). '</old_service_provider>';
            $req .=         '<old_service_provider_in_contract xsi:type="xsd:string">' . $old_carrier_contract . '</old_service_provider_in_contract>';
            $req .=         '<comment1 xsi:type="xsd:string">' . $cid . '</comment1>';
            $req .=         '<dc xsi:type="xsd:string">' . $dc . '</dc>';
            $req .=         '<dp xsi:type="xsd:string">' . $dp . '</dp>';
            $req .=     '</urn:portin>';
            $req .= '</soapenv:Body>';
            $req .= '</soapenv:Envelope>';

            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"portin\""
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

            Helper::log('### Telestar H2O port-in ###', [
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

            $body[0]->registerXPathNamespace('ns1', 'urn:GetH2OService');
            $res = $body[0]->xpath('ns1:portinResponse');
            if (count($res) < 1) {
                return [
                    'error_code' => -3,
                    'error_msg' => 'Unable to get XML in CDATA: ' . var_export($output, true)
                ];
            }

            $ret_new = $res[0];
            $error_code = strval($ret_new->error_code);
            $error_msg = strval($ret_new->error_msg);
            if ($error_code != '') {
                return [
                    'error_code' => $error_code,
                    'error_msg' => $error_msg
                ];
            }

            $tx_id = isset($ret_new->tx_id) ? $ret_new->tx_id->__toString() : "";
            $min = isset($ret_new->min) ? $ret_new->min->__toString() : "";

            if (empty($tx_id)) {
                return [
                    'error_code' => -5,
                    'error_msg' => 'Empty vendor TX.ID returned'
                ];
            }

            if (empty($min)) {
                return [
                    'error_code' => -5,
                    'error_msg' => 'Empty vendor TX.ID returned'
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'serial' => $tx_id,
                'min' => $min
            ];

        } catch (\Exception $ex) {

            $message = " - sku : " . $sku. "<br/>";
            $message .= " - amt : " . $amt . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[TELESTAR][' . getenv('APP_ENV') . '] port-in() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function rtr($cid, $sku, $mdn, $amt) {
        try {

            self::init();

            $cid = $cid . 'T' . rand(1, 100);

            $req = '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:GetH2OService">';
            $req .=     '<soapenv:Header/>';
            $req .=     '<soapenv:Body>';
            $req .=         '<urn:recharge soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';
            $req .=             '<user_id xsi:type="xsd:string">' . self::$api_user . '</user_id>';
            $req .=             '<password xsi:type="xsd:string">' . self::$api_pwd . '</password>';
            $req .=             '<cid xsi:type="xsd:string">' . $cid . '</cid>';
            $req .=             '<prod_id xsi:type="xsd:string">' . $sku . '</prod_id>';
            $req .=             '<mdn xsi:type="xsd:string">' . $mdn . '</mdn>';
            $req .=             '<amt xsi:type="xsd:string">' . $amt . '</amt>';
            $req .=             '<comment1 xsi:type="xsd:string">' . $cid . '</comment1>';
            $req .=         '</urn:recharge>';
            $req .=     '</soapenv:Body>';
            $req .= '</soapenv:Envelope>';

            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"recharge\""
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

            Helper::log('### Telestar H2O rtr ###', [
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

            $body[0]->registerXPathNamespace('ns1', 'urn:GetH2OService');
            $res = $body[0]->xpath('ns1:rechargeResponse');
            if (count($res) < 1) {
                return [
                    'error_code' => -3,
                    'error_msg' => 'Unable to get XML in CDATA: ' . var_export($output, true)
                ];
            }

            $ret_new = $res[0];
            $error_code = strval($ret_new->error_code);
            $error_msg = strval($ret_new->error_msg);

            if ($error_code != '') {
                return [
                    'error_code' => $error_code,
                    'error_msg' => $error_msg
                ];
            }

            $tx_id = isset($ret_new->tx_id) ? $ret_new->tx_id->__toString() : "";

            if (empty($tx_id)) {
                return [
                    'error_code' => -5,
                    'error_msg' => 'Empty vendor TX.ID returned'
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'tx_id' => $tx_id
            ];

        } catch (\Exception $ex) {

            $message = " - sku : " . $sku. "<br/>";
            $message .= " - amt : " . $amt . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[TELESTAR][' . getenv('APP_ENV') . '] rtr() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function get_portin_status($portin_tx_id) {
        try {

            self::init();

            $req = '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:GetH2OService">';
            $req .=     '<soapenv:Header/>';
            $req .=     '<soapenv:Body>';
            $req .=         '<urn:get_portin_status soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';
            $req .=             '<user_id xsi:type="xsd:string">' . self::$api_user . '</user_id>';
            $req .=             '<password xsi:type="xsd:string">' . self::$api_pwd . '</password>';
            $req .=             '<portin_tx_id xsi:type="xsd:string">' . $portin_tx_id . '</portin_tx_id>';
            $req .=             '<comment1 xsi:type="xsd:string">' . $portin_tx_id . '</comment1>';
            $req .=         '</urn:get_portin_status>';
            $req .=     '</soapenv:Body>';
            $req .= '</soapenv:Envelope>';

            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"get_portin_status\""
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

            Helper::log('### Telestar H2O get_portin_status ###', [
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

            $body[0]->registerXPathNamespace('ns1', 'urn:GetH2OService');
            $res = $body[0]->xpath('ns1:get_portin_statusResponse');
            if (count($res) < 1) {
                return [
                    'error_code' => -3,
                    'error_msg' => 'Unable to get XML in CDATA: ' . var_export($output, true)
                ];
            }

            $ret_new = $res[0];
            $error_code = strval($ret_new->error_code);
            $error_msg = strval($ret_new->error_msg);

            if ($error_code != '') {
                return [
                    'error_code' => $error_code,
                    'error_msg' => $error_msg
                ];
            }

            $status = isset($ret_new->status) ? $ret_new->status->__toString() : "";
            $status_msg = isset($ret_new->status_msg) ? $ret_new->status_msg->__toString() : "";
            $portin_failed_reason = isset($ret_new->portin_failed_reaso) ? $ret_new->portin_failed_reaso->__toString() : "";

            if (empty($status)) {
                return [
                    'error_code' => -5,
                    'error_msg' => 'Empty status returned'
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'status' => $status,
                'status_msg' => $status_msg,
                'portin_failed_reason' => $portin_failed_reason
            ];

        } catch (\Exception $ex) {

            $message = " - portin_tx_id : " . $portin_tx_id. "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[TELESTAR][' . getenv('APP_ENV') . '] get_portin_status() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function update_portin(
        $portin_tx_id, $account_no, $account_pin,
        $street, $city, $state, $zip, $name, $email, $cb_phone,
        $sim, $number_to_port, $old_carrier, $old_carrier_contract,
        $dc, $dp
    ) {
        try {

            self::init();

            $req = '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:GetH2OService">';
            $req .= '<soapenv:Header/>';
            $req .= '<soapenv:Body>';
            $req .=     '<urn:portin soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';
            $req .=         '<user_id xsi:type="xsd:string">' . self::$api_user . '</user_id>';
            $req .=         '<password xsi:type="xsd:string">' . self::$api_pwd . '</password>';
            $req .=         '<portin_tx_id xsi:type="xsd:string">' . $portin_tx_id . '</portin_tx_id>';
            $req .=         '<acctno xsi:type="xsd:string">' . $account_no . '</acctno>';
            $req .=         '<pass xsi:type="xsd:string">' . $account_pin . '</pass>';
            $req .=         '<street xsi:type="xsd:string">' . $street . '</street>';
            $req .=         '<city xsi:type="xsd:string">' . $city . '</city>';
            $req .=         '<state xsi:type="xsd:string">' . $state . '</state>';
            $req .=         '<zip xsi:type="xsd:string">' . $zip . '</zip>';
            $req .=         '<name xsi:type="xsd:string">' . $name . '</name>';
            $req .=         '<email xsi:type="xsd:string">' . $email . '</email>';
            $req .=         '<call_back_phone xsi:type="xsd:string">' . $cb_phone . '</call_back_phone>';
            $req .=         '<sim xsi:type="xsd:string">' . $sim . '</sim>';
            $req .=         '<number_to_port xsi:type="xsd:string">' . $number_to_port . '</number_to_port>';
            $req .=         '<old_service_provider xsi:type="xsd:string">' . $old_carrier . '</old_service_provider>';
            $req .=         '<old_service_provider_in_contract xsi:type="xsd:string">' . $old_carrier_contract . '</old_service_provider_in_contract>';
            $req .=         '<comment1 xsi:type="xsd:string">' . $portin_tx_id . '</comment1>';
            $req .=         '<dc xsi:type="xsd:string">' . $dc . '</dc>';
            $req .=         '<dp xsi:type="xsd:string">' . $dp . '</dp>';
            $req .=     '</urn:portin>';
            $req .= '</soapenv:Body>';
            $req .= '</soapenv:Envelope>';

            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"update_portin\""
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

            Helper::log('### Telestar H2O update_portin ###', [
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

            $body[0]->registerXPathNamespace('ns1', 'urn:GetH2OService');
            $res = $body[0]->xpath('ns1:update_portinResponse');
            if (count($res) < 1) {
                return [
                    'error_code' => -3,
                    'error_msg' => 'Unable to get XML in CDATA: ' . var_export($output, true)
                ];
            }

            $ret_new = $res[0];
            $error_code = strval($ret_new->error_code);
            $error_msg = strval($ret_new->error_msg);

            if ($error_code != '') {
                return [
                    'error_code' => $error_code,
                    'error_msg' => $error_msg
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => ''
            ];

        } catch (\Exception $ex) {

            $message = " - portin_tx_id : " . $portin_tx_id. "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[TELESTAR][' . getenv('APP_ENV') . '] update_portin() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }
}