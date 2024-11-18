<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 2/1/17
 * Time: 2:06 PM
 */

namespace App\Lib;

use \SoapClient;
use \Log;
use App\Lib\sms;
use App\Lib\Helper;

class h2o_rtr
{
    //private static $api_url = 'http://trial.h2odirectnow.com/retailer/H2OServicesDemo.wsdl';
    private static $api_url = 'http://trial.h2odirectnow.com/retailer/H2OServices.php';
    private static $user = '';
    private static $pwd = '';
    private static $call_api_on_demo = false;

    private static function init() {
        //if (getenv('APP_ENV') == 'production' || self::$call_api_on_demo) {
        if (getenv('APP_ENV') == 'production') {
            //self::$api_url = 'https://www.h2odirectnow.com/retailer/H2OServices.wsdl';
            self::$api_url = 'https://www.h2odirectnow.com/retailer/H2OServices.php';
            self::$user = '';
            self::$pwd = '';
        }
    }

    public static function recharge($prod_id, $mdn, $amount, $ref_id) {
        try {

            self::init();

            if (getenv('APP_ENV') != 'production' && !self::$call_api_on_demo) {
                return [
                    'error_code' => '',
                    'error_msg' => '',
                    'client_sales_id' => '123123',
                    'sales_confirmation_id' => time(),
                    'comment1' => ''
                ];
            }

            $cid = $ref_id . 'T' . rand(1, 100);
            if ((int)$ref_id == -1 || (string)$ref_id == "-1" || (string)$ref_id === "-1" || str_contains($ref_id, "-")) {
                $cid = time();
            }

            $req = '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:H2O">';
            $req .= '<soapenv:Header/>';
            $req .= '<soapenv:Body>';
            $req .= '<urn:recharge_h2o soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';
            $req .= '<user_id xsi:type="xsd:string">' . self::$user . '</user_id>';
            $req .= '<passwd xsi:type="xsd:string">' . md5(self::$pwd) . '</passwd>';
            $req .= '<prod_id xsi:type="xsd:string">' . $prod_id . '</prod_id>';
            $req .= '<mdn xsi:type="xsd:string">' . $mdn . '</mdn>';
            $req .= '<amount xsi:type="xsd:float">' . $amount . '</amount>';
            $req .= '<client_sales_id xsi:type="xsd:string">' . $cid . '</client_sales_id>';
            $req .= '<comment1 xsi:type="xsd:string">' . $cid . '</comment1>';
            $req .= '</urn:recharge_h2o>';
            $req .= '</soapenv:Body>';
            $req .= '</soapenv:Envelope>';

            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"urn:H2O#recharge_h2o\""
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

            Helper::log('### recharge_curl ###', [
                'req' => $req,
                'res' => $output,
                'info' => $info
            ]);

            //return $output;
            if (empty($output)) {
                throw new \Exception('Vendor returned empty response');
            }

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);
            $res = $ret->xpath('//ns1:recharge_h2oResponse');
            if (count($res) < 1) {
                throw new \Exception('Failed to ge response from vendor');
            }

            $xml = $res[0];
            $error_code = '';
            if (isset($xml) && isset($xml->error_code)) {
                $error_code = $xml->error_code->__toString();
            }

            $error_msg = '';
            if (isset($xml) && isset($xml->error_msg)) {
                $error_msg = $xml->error_msg->__toString();
            }
            //var_dump($res->error_code);

            $sales_confirmation_id = '';
            if (isset($xml) && isset($xml->sales_confirmation_id)) {
                $sales_confirmation_id = $xml->sales_confirmation_id->__toString();
            }

            $client_sales_id = '';
            if (isset($xml) && isset($xml->client_sales_id)) {
                $client_sales_id = $xml->client_sales_id->__toString();
            }

            $comment1 = '';
            if (isset($xml) && isset($xml->comment1)) {
                $comment1 = $xml->comment1->__toString();
            }

            return [
                'error_code' => $error_code,
                'error_msg' => $error_msg,
                'client_sales_id' => $client_sales_id,
                'sales_confirmation_id' => $sales_confirmation_id,
                'comment1' => $comment1
            ];

        } catch (\Exception $ex) {

            $message = " - cid : " . $ref_id . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[H2O][' . getenv('APP_ENV') . '] recharge Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function payment($prod_id, $first_sim, $amount, $ref_id) {
        try {

            self::init();

            if (getenv('APP_ENV') != 'production' && !self::$call_api_on_demo) {
                return [
                    'error_code' => '',
                    'error_msg' => '',
                    'client_sales_id' => '123123',
                    'sales_confirmation_id' => time(),
                    'comment1' => ''
                ];
            }

            $cid = $ref_id . 'T' . rand(1, 100);
            if ((int)$ref_id == -1 || (string)$ref_id == "-1" || (string)$ref_id === "-1" || str_contains($ref_id, "-")) {
                $cid = time();
            }

            $req = '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:H2O">';
            $req .= '<soapenv:Header/>';
            $req .= '<soapenv:Body>';
            $req .= '<urn:payment soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">';
            $req .= '<user_id xsi:type="xsd:string">' . self::$user . '</user_id>';
            $req .= '<passwd xsi:type="xsd:string">' . md5(self::$pwd) . '</passwd>';
            $req .= '<prod_id xsi:type="xsd:string">' . $prod_id . '</prod_id>';
            $req .= '<mdn xsi:type="xsd:string">' . $first_sim . '</mdn>';
            $req .= '<amount xsi:type="xsd:float">' . $amount . '</amount>';
            $req .= '<client_sales_id xsi:type="xsd:string">' . $cid . '</client_sales_id>';
            $req .= '<comment1 xsi:type="xsd:string">' . $cid . '</comment1>';
            $req .= '</urn:payment>';
            $req .= '</soapenv:Body>';
            $req .= '</soapenv:Envelope>';

            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"urn:H2O#recharge_h2o\""
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

            Helper::log('### payment ###', [
                'req' => $req,
                'res' => $output,
                'info' => $info
            ]);

            //return $output;
            if (empty($output)) {
                throw new \Exception('Vendor returned empty response');
            }

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);
            $res = $ret->xpath('//ns1:paymentResponse');
            if (count($res) < 1) {
                throw new \Exception('Failed to ge response from vendor');
            }

            $xml = $res[0];
            $error_code = '';
            if (isset($xml) && isset($xml->error_code)) {
                $error_code = $xml->error_code->__toString();
            }

            $error_msg = '';
            if (isset($xml) && isset($xml->error_msg)) {
                $error_msg = $xml->error_msg->__toString();
            }
            //var_dump($res->error_code);

            $sales_confirmation_id = '';
            if (isset($xml) && isset($xml->sales_confirmation_id)) {
                $sales_confirmation_id = $xml->sales_confirmation_id->__toString();
            }

            $client_sales_id = '';
            if (isset($xml) && isset($xml->client_sales_id)) {
                $client_sales_id = $xml->client_sales_id->__toString();
            }

            $comment1 = '';
            if (isset($xml) && isset($xml->comment1)) {
                $comment1 = $xml->comment1->__toString();
            }

            return [
                'error_code' => $error_code,
                'error_msg' => $error_msg,
                'client_sales_id' => $client_sales_id,
                'sales_confirmation_id' => $sales_confirmation_id,
                'comment1' => $comment1
            ];

        } catch (\Exception $ex) {

            $message = " - cid : " . $ref_id . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[H2O][' . getenv('APP_ENV') . '] payment Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function recharge_soap($prod_id, $mdn, $amount, $ref_id) {
        try {

            ini_set('soap.wsdl_cache_enabled',0);
            ini_set('soap.wsdl_cache_ttl',0);

            self::init();

            if (getenv('APP_ENV') != 'production' && !self::$call_api_on_demo) {
                return [
                    'error_code' => '',
                    'error_msg' => '',
                    'serial' => time()
                ];
            }

            $opts = array(
                'http'=>array(
                    'user_agent' => 'PHPSoapClient'
                ),
                'ssl' => array(
                    'ciphers' => 'RC4-SHA',
                    'verify_peer' => false,
                    'verify_peer_name' => false
                )
            );

            $context = stream_context_create($opts);

            $client = new SoapClient(self::$api_url,
                array('stream_context' => $context,
                    'cache_wsdl' => WSDL_CACHE_NONE,
                    "trace" => 1,
                    "exceptions" => 1,
                    'verifypeer' => false,
                    'verifyhost' => false,
                )
            );

            //$client->__setTimeout(1800);

            $cid = $ref_id . 'T' . rand(1, 100);
            if ((int)$ref_id == -1 || (string)$ref_id == "-1" || (string)$ref_id === "-1" || str_contains($ref_id, "-")) {
                $cid = time();
            }

            $ret = $client->recharge_h2o(self::$user, md5(self::$pwd), $prod_id, $mdn, $amount, $cid, '');

            Helper::log('### h2o_rtr::recharge() called', [
                'request-header' =>  $client->__getLastRequestHeaders(),
                'request' => $client->__getLastRequest(),
                'response-header' => $client->__getLastResponseHeaders(),
                'response' => $client->__getLastResponse(),
                'ref_id' => $ref_id,
                'cid' => $cid,
                'ref_id == -1' => $ref_id == -1 ? 'Y' : 'N'
            ]);

            return $ret;

        } catch (\SoapFault $ex) {

            Helper::log('### h2o.recharge() failed: soap fault ###', [
                'code' => $ex->faultcode,
                'msg' => $ex->faultstring
            ]);

            Helper::send_mail('it@perfectmobileinc.com', '[H2O][' . getenv('APP_ENV') . ']' . 'h2o.recharge() failed: soap fault', $ex->faultstring . ' [' . $ex->faultcode . ' ]');

            if (isset($client)) {
                Helper::log('### h2o_rtr::recharge() called', [
                    'request-header' =>  $client->__getLastRequestHeaders(),
                    'request' => $client->__getLastRequest(),
                    'response-header' => $client->__getLastResponseHeaders(),
                    'response' => $client->__getLastResponse()
                ]);
            }

            return [
                'error_code' => $ex->faultcode,
                'error_msg' => $ex->faultstring
            ];

        } catch (\Exception $ex) {

            Helper::log('### h2o.recharge() failed: exception ###', [
                'code' => $ex->getCode(),
                'msg' => $ex->getMessage()
            ]);

            Helper::send_mail('it@perfectmobileinc.com', '[H2O][' . getenv('APP_ENV') . ']' . 'h2o.recharge() failed: soap fault', $ex->getMessage() . ' [' . $ex->getCode() . ' ]');

            if (isset($client)) {
                Helper::log('### h2o_rtr::recharge() called', [
                    'request-header' =>  $client->__getLastRequestHeaders(),
                    'request' => $client->__getLastRequest(),
                    'response-header' => $client->__getLastResponseHeaders(),
                    'response' => $client->__getLastResponse()
                ]);
            }

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage()
            ];

        }
    }

}