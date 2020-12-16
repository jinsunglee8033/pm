<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 7/10/17
 * Time: 10:12 AM
 */

namespace App\Lib;

class lyca
{
    //private static $api_url = 'ITGAPI_demo.wsdl';
    public static $api_url = 'http://185.13.105.246:4006';
    private static $call_live_on_demo = false;
    private static $use_fail_over = false;
    private static $ns = 'http://www.plintron.com/hdr/';

    ### constant ###
    private static $entity = 'PERFECT';
    private static $channel_ref = 'POS-T';

    public static function init() {

        Helper::log('APP_ENV: ' .getenv('APP_ENV'));

        if (getenv('APP_ENV') == 'production' || self::$call_live_on_demo) {
            //self::$api_url = 'ITGAPI_live.wsdl';
            self::$api_url = 'http://192.30.216.110:2244';
            if (self::$use_fail_over) {
                //self::$api_url = 'ITGAPI_fail_over.wsdl';
                self::$api_url = 'http://192.30.220.110:2244';
            }
        }
    }

    public static function getPortinDetail($cid, $min) {
        try {

            self::init();

            if (getenv('APP_ENV') != 'production' && !self::$call_live_on_demo) {
                return [
                    'error_code' => '',
                    'error_msg' => '',
                    'min' => '4432516180',
                    'portin_ref_no' => 'MNPPI0000665288'
                ];
            }

            $req = '<ENVELOPE>';
            $req .= '<HEADER>';
            $req .= '<CHANNEL_REFERENCE>' . self::$channel_ref . '</CHANNEL_REFERENCE>';
            $req .= '<ENTITY>' . self::$entity . '</ENTITY>';
            $req .= "<TRANSACTION_ID>$cid</TRANSACTION_ID>";
            $req .= '</HEADER>';

            $req .= '<BODY>';
            $req .= '<GET_PORTIN_DETAILS_REQUEST>';
            $req .= '<DETAILS>';
            $req .= "<REFERENCE_NUMBER>$min</REFERENCE_NUMBER>";
            $req .= "<REFERENCE_TYPE>PMSISDN</REFERENCE_TYPE>";
            $req .= '</DETAILS>';
            $req .= '</GET_PORTIN_DETAILS_REQUEST>';
            $req .= '</BODY>';
            $req .= '</ENVELOPE>';

            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"GET_PORTIN_DETAILS\""
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

            Helper::log('### getPortinDetail ###', [
                'req' => $req,
                'res' => $output,
                'info' => $info
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);

            $header = $ret->children('soapenv', true)->Header[0]->children('header', true);

            if (!isset($header)) {
                return [
                    'error_code' => -1,
                    'error_msg' => 'Empty response returned from vendor'
                ];
            }

            $code = '';
            $msg = '';

            if (isset($header) && isset($header->ERROR_CODE)) {
                $code = $header->ERROR_CODE->__toString();
            }

            if (isset($header) && isset($header->ERROR_DESC)) {
                $msg = $header->ERROR_DESC->__toString();
            }

            if (!empty($code)) {
                return [
                    'error_code' => $code,
                    'error_msg' => $msg
                ];
            }

            $body = $ret->xpath('//soapenv:Body');
            $res = null;

            if (isset($body) && count($body) > 0) {
                $res = $body[0]->GET_PORTIN_DETAILS_RESPONSE;
            }

            $return_desc = '';
            $reject_code = '';
            $reject_reason = '';

            if (isset($res) && isset($res->RETURN_DESC)) {
                $return_desc = $res->RETURN_DESC->__toString();
            }
            if (isset($res) && isset($res->REJECT_CODE)) {
                $reject_code = $res->REJECT_CODE->__toString();
            }

            if (isset($res) && isset($res->REJECT_REASON)) {
                $reject_reason = $res->REJECT_REASON->__toString();
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'return_desc' => $return_desc,
                'reject_code' => $reject_code,
                'reject_reason' => $reject_reason
            ];

        } catch (\Exception $ex) {

            $message = " - cid : " . $cid . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[LYCA][' . getenv('APP_ENV') . '] getPortinDetail Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function modifyPortin($cid, $portin_ref_no, $sim, $zip, $mdn, $acct_no, $pin) {
        try {

            self::init();

            if (getenv('APP_ENV') != 'production' && !self::$call_live_on_demo) {
                return [
                    'error_code' => '',
                    'error_msg' => '',
                    'min' => '4432516180',
                    'portin_ref_no' => 'MNPPI0000665288'
                ];
            }

            $req = '<ENVELOPE>';
            $req .= '<HEADER>';
            $req .= '<CHANNEL_REFERENCE>' . self::$channel_ref . '</CHANNEL_REFERENCE>';
            $req .= '<ENTITY>' . self::$entity . '</ENTITY>';
            $req .= "<TRANSACTION_ID>$cid</TRANSACTION_ID>";
            $req .= '</HEADER>';

            $req .= '<BODY>';
            $req .= '<MODIFY_PORTIN_REQUEST>';
            $req .= '<DETAILS>';
            $req .= '<REFERENCE_CODE>' . $portin_ref_no . '</REFERENCE_CODE>';
            $req .= "<ICC_ID>$sim</ICC_ID>";
            $req .= "<P_MSISDN>$mdn</P_MSISDN>";
            $req .= "<ACCOUNT_NUMBER>$acct_no</ACCOUNT_NUMBER>";
            $req .= "<PWD_PIN>$pin</PWD_PIN>";
            $req .= "<ZIP_CODE>$zip</ZIP_CODE>";
            $req .= '<CHANNEL>' . self::$entity . '</CHANNEL>';
            $req .= '</DETAILS>';
            $req .= '</MODIFY_PORTIN_REQUEST>';
            $req .= '</BODY>';

            $req .= '</ENVELOPE>';

            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"MODIFY_PORTIN\""
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

            Helper::log('### modifyPortin ###', [
                'req' => $req,
                'res' => $output,
                'info' => $info
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);

            $header = $ret->children('soapenv', true)->Header[0]->children('header', true);

            if (!isset($header)) {
                return [
                    'error_code' => -1,
                    'error_msg' => 'Empty response returned from vendor'
                ];
            }

            $code = '';
            $msg = '';

            if (isset($header) && isset($header->ERROR_CODE)) {
                $code = $header->ERROR_CODE->__toString();
            }

            if (isset($header) && isset($header->ERROR_DESC)) {
                $msg = $header->ERROR_DESC->__toString();
            }

            if (!empty($code)) {
                return [
                    'error_code' => $code,
                    'error_msg' => $msg
                ];
            }

            $body = $ret->xpath('//soapenv:Body');
            $res = null;

            if (isset($body) && count($body) > 0) {
                $res = $body[0]->MODIFY_PORTIN_RESPONSE;
            }

            $ref_code = '';
            $return_desc = '';
            if (isset($res) && isset($res->REFERENCE_CODE)) {
                $ref_code = $res->REFERENCE_CODE->__toString();
            }
            if (isset($res) && isset($res->RETURN_DESC)) {
                $return_desc = $res->RETURN_DESC->__toString();
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'ref_code' => $ref_code,
                'return_desc' => $return_desc
            ];

        } catch (\Exception $ex) {

            $message = " - cid : " . $cid . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[LYCA][' . getenv('APP_ENV') . '] modifyPortin Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function cancelPortIn($cid, $portin_ref_no, $mdn) {
        try {

            self::init();

            if (getenv('APP_ENV') != 'production' && !self::$call_live_on_demo) {
                return [
                    'error_code' => '',
                    'error_msg' => '',
                    'min' => '4432516180',
                    'portin_ref_no' => 'MNPPI0000665288'
                ];
            }

            $req = '<ENVELOPE>';
            $req .= '<HEADER>';
            $req .= '<CHANNEL_REFERENCE>' . self::$channel_ref . '</CHANNEL_REFERENCE>';
            $req .= '<ENTITY>' . self::$entity . '</ENTITY>';
            $req .= "<TRANSACTION_ID>$cid</TRANSACTION_ID>";
            $req .= '</HEADER>';

            $req .= '<BODY>';
            $req .= '<CANCEL_PORTIN_REQUEST>';
            $req .= '<DETAILS>';
            $req .= '<REFERENCE_CODE>' . $portin_ref_no . '</REFERENCE_CODE>';
            $req .= "<P_MSISDN>$mdn</P_MSISDN>";
            $req .= '<CHANNEL>' . self::$entity . '</CHANNEL>';
            $req .= '</DETAILS>';
            $req .= '</CANCEL_PORTIN_REQUEST>';
            $req .= '</BODY>';

            $req .= '</ENVELOPE>';

            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"CANCEL_PORTIN\""
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

            Helper::log('### cancelPortIn ###', [
                'req' => $req,
                'res' => $output,
                'info' => $info
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);

            $header = $ret->children('soapenv', true)->Header[0]->children('header', true);

            if (!isset($header)) {
                return [
                    'error_code' => -1,
                    'error_msg' => 'Empty response returned from vendor'
                ];
            }

            $code = '';
            $msg = '';

            if (isset($header) && isset($header->ERROR_CODE)) {
                $code = $header->ERROR_CODE->__toString();
            }

            if (isset($header) && isset($header->ERROR_DESC)) {
                $msg = $header->ERROR_DESC->__toString();
            }

            if (!empty($code)) {
                return [
                    'error_code' => $code,
                    'error_msg' => $msg
                ];
            }

            $body = $ret->xpath('//soapenv:Body');
            $res = null;

            if (isset($body) && count($body) > 0) {
                $res = $body[0]->CANCEL_PORTIN_RESPONSE;
            }

            $ref_code = '';
            $return_desc = '';
            if (isset($res) && isset($res->REFERENCE_CODE)) {
                $ref_code = $res->REFERENCE_CODE->__toString();
            }
            if (isset($res) && isset($res->RETURN_DESC)) {
                $return_desc = $res->RETURN_DESC->__toString();
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'ref_code' => $ref_code,
                'return_desc' => $return_desc
            ];

        } catch (\Exception $ex) {

            $message = " - cid : " . $cid . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[LYCA][' . getenv('APP_ENV') . '] modifyPortin Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function activate($cid, $sim, $zip, $sku, $amt, $email) {

        $ret = self::activateUsimPortinBundle($cid, $sim, $zip, $sku, $amt, $email);
        if (!empty($ret['error_code'])) {
            return [
                'error_code' => $ret['error_code'],
                'error_msg' => $ret['error_msg']
            ];
        }

        if (empty($ret['min'])) {
            return [
                'error_code' => -1,
                'error_msg' => 'Lyca returned empty MIN'
            ];
        }

        return $ret;

    }

    public static function portin($cid, $sim, $zip, $sku, $amt, $email, $mdn, $acct_no, $pin, $first_name, $last_name) {
        $ret = self::activateUsimPortinBundle($cid, $sim, $zip, $sku, $amt, $email, $mdn, $acct_no, $pin, $first_name, $last_name);
        if (!empty($ret['error_code'])) {
            return [
                'error_code' => $ret['error_code'],
                'error_msg' => $ret['error_msg']
            ];
        }

        if (empty($ret['min'])) {
            return [
                'error_code' => -1,
                'error_msg' => 'Lyca returned empty MIN'
            ];
        }

        if (empty($ret['portin_ref_no'])) {
            return [
                'error_code' => -1,
                'error_msg' => 'Lyca returned empty port-in reference number'
            ];
        }

        return $ret;
    }

    private static function activateUsimPortinBundle($cid, $sim, $zip, $sku, $amt, $email, $mdn = '', $acct_no = '', $pin = '', $first_name = '', $last_name = '') {
        try {

            self::init();

            if (getenv('APP_ENV') != 'production' && !self::$call_live_on_demo) {
                return [
                    'error_code' => '',
                    'error_msg' => '',
                    'min' => empty($mdn) ? '4432516180' : $mdn,
                    'portin_ref_no' =>  empty($mdn) ? '' : 'MNPPI0000665288'
                ];
            }

            $req = '<ENVELOPE>';
            $req .= '<HEADER>';
            $req .= '<CHANNEL_REFERENCE>' . self::$channel_ref . '</CHANNEL_REFERENCE>';
            $req .= '<ENTITY>' . self::$entity . '</ENTITY>';
            $req .= "<TRANSACTION_ID>$cid</TRANSACTION_ID>";
            $req .= '</HEADER>';

            $req .= '<BODY>';
            $req .= '<ACTIVATE_USIM_PORTIN_BUNDLE_REQUEST>';
            $req .= '<DETAILS>';
            $req .= "<ICC_ID>$sim</ICC_ID>";
            $req .= "<ZIP_CODE>$zip</ZIP_CODE>";
            $req .= '<PREFERRED_LANGUAGE>ENGLISH</PREFERRED_LANGUAGE>';
            $req .= "<P_MSISDN>$mdn</P_MSISDN>";
            $req .= "<ACCOUNT_NUMBER>$acct_no</ACCOUNT_NUMBER>";
            $req .= "<PASSWORD_PIN>$pin</PASSWORD_PIN>";
            $req .= '<NO_OF_MONTHS>1</NO_OF_MONTHS>';
            $req .= "<NATIONAL_BUNDLE_CODE>$sku</NATIONAL_BUNDLE_CODE>";
            $req .= "<NATIONAL_BUNDLE_AMOUNT>$amt</NATIONAL_BUNDLE_AMOUNT>";
            $req .= '<INTERNATIONAL_BUNDLE_CODE></INTERNATIONAL_BUNDLE_CODE>';
            $req .= '<INTERNATIONAL_BUNDLE_AMOUNT></INTERNATIONAL_BUNDLE_AMOUNT>';
            $req .= '<TOPUP_AMOUNT></TOPUP_AMOUNT>';
            $req .= '<TOPUP_CARD_ID></TOPUP_CARD_ID>';
            $req .= '<VOUCHER_PIN></VOUCHER_PIN>';
            $req .= '<CHANNEL_ID>' . self::$entity . '</CHANNEL_ID>';
            $req .= "<EMAIL_ID>$email</EMAIL_ID>";
            $req .= '<DISCOUNT_TRANSACTION_ID></DISCOUNT_TRANSACTION_ID>';
            $req .= "<FIRST_NAME>$first_name</FIRST_NAME>";
            $req .= "<LAST_NAME>$last_name</LAST_NAME>";
            $req .= '</DETAILS>';
            $req .= '</ACTIVATE_USIM_PORTIN_BUNDLE_REQUEST>';
            $req .= '</BODY>';
            $req .= '</ENVELOPE>';

            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"ACTIVATE_USIM_PORTIN_BUNDLE\""
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

            Helper::log('### activateUsimPortinBundle ###', [
                'req' => $req,
                'res' => $output
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);

            $header = $ret->children('soapenv', true)->Header[0]->children('header', true);

            if (!isset($header)) {
                return [
                    'error_code' => -1,
                    'error_msg' => 'Empty response returned from vendor'
                ];
            }

            $code = '';
            $msg = '';

            if (isset($header) && isset($header->ERROR_CODE)) {
                $code = $header->ERROR_CODE->__toString();
            }

            if (isset($header) && isset($header->ERROR_DESC)) {
                $msg = $header->ERROR_DESC->__toString();
            }

            if (!empty($code)) {
                return [
                    'error_code' => $code,
                    'error_msg' => $msg
                ];
            }

            $body = $ret->xpath('//soapenv:Body');
            $res = null;

            if (isset($body) && count($body) > 0) {
                $res = $body[0]->ACTIVATE_USIM_PORTIN_BUNDLE_RESPONSE;
            }

            $min = '';
            $ref_no = '';
            if (isset($res) && isset($res->ALLOCATED_MSISDN)) {
                $min = $res->ALLOCATED_MSISDN->__toString();
            }
            if (isset($res) && isset($res->PORTIN_REFERENCE_NUMBER)) {
                $ref_no = $res->PORTIN_REFERENCE_NUMBER->__toString();
            }

            if (!empty($min) && starts_with($min, '1')) {
                $min = substr($min, 1);
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'min' => $min,
                'portin_ref_no' => $ref_no
            ];

        } catch (\Exception $ex) {

            $message = " - cid : " . $cid . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[LYCA][' . getenv('APP_ENV') . '] activateUsimPortinBundle Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }
}