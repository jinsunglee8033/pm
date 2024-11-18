<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 2/28/19
 * Time: 11:39 AM
 */

namespace App\Lib;


use App\Model\Denom;
use App\Model\GenActivation;
use App\Model\Transaction;

class gen
{
    private static $api_url = 'https://www.vcareapi.com/vcareOssApi/';
    private static $VENDORID = '';
    private static $USERNAME = '';
    private static $PASSWORD = '';
    private static $PIN = '';
    private static $AGENTID = '';
    private static $AGENTPASSWORD = '';
    private static $COMPANYID = '42';

    private static function init() {
        if (getenv('APP_ENV') == 'production') {
            self::$api_url = 'https://www.vcareapi.com/vcareOssApi/';
            self::$VENDORID = '';
            self::$USERNAME = '';
            self::$PASSWORD = '';
            self::$PIN = '';
            self::$AGENTID = '';
            self::$AGENTPASSWORD = '';
            self::$COMPANYID = '';
        }
    }


    public static function ValidateBYOD($device_id) {
        try {

            self::init();

            $uid = uniqid();

            $req = '<?xml version="1.0" encoding="utf-8"?>';
            $req .= '<VCAREOSSAPI xmlns="http://www.oss.vcarecorporation.com/oss" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
            $req .=    '<CREDENTIALS>';
            $req .=        '<VENDORID>' . self::$VENDORID . '</VENDORID>';
            $req .=        '<USERNAME>' . self::$USERNAME . '</USERNAME>';
            $req .=        '<PASSWORD>' . self::$PASSWORD . '</PASSWORD>';
            $req .=        '<PIN>' . self::$PIN . '</PIN>';
            $req .=        '<REFERENCENUMBER>' . $uid . '</REFERENCENUMBER>';
            $req .=    '</CREDENTIALS>';
            $req .=    '<VCAREOSS>';
            $req .=    '<VALIDATEBYOD>';
            $req .=        '<DEVICETYPE>CDMA</DEVICETYPE>';
            $req .=        '<CARRIER>SPR</CARRIER>';
            $req .=        '<COMPANYID>' . self::$COMPANYID . '</COMPANYID>';
            $req .=        '<ESN>' . $device_id . '</ESN>';
            $req .=        '<IMEI></IMEI>';
            $req .=        '<AGENTID>' . self::$AGENTID . '</AGENTID>';
            $req .=        '<AGENTPASSWORD>' . self::$AGENTPASSWORD . '</AGENTPASSWORD>';
            $req .=        '<SOURCE>API</SOURCE>';
            $req .=    '</VALIDATEBYOD>';
            $req .=    '</VCAREOSS>';
            $req .= '</VCAREOSSAPI>';

            $ret = self::sendRequest($req, 'ValidateByod/');

            $resp_xml = simplexml_load_string($ret, null, LIBXML_NOCDATA);
            $resp = $resp_xml->ValidateByod;

            if (in_array($resp->statusCode, ['00', '675', '676', '800'])) {

                if($resp->devicefedmetind == 'false' || $resp->pocswapind == 'false'){
                    return [
                        'code' => '-1',
                        'error_msg' => 'FINANCIAL INELIGIBILITY: CONTACT ORIGINAL CARRIER TO DISPUTE OR REQUEST RELEASE.  CALL 833-436-6624  FOR ASSISTANCE.'
                    ];
                }

                return [
                    'code' => '0',
                    'devicefedmetind' => $resp->devicefedmetind,
                    'pocswapind' => $resp->pocswapind,
                    'model_number' => (empty($resp->modelnumber)? '' : $resp->modelnumber),
                    'error_msg' => (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription)
                ];
            }

//            if ($resp->statusCode == '800') {
//                return [
//                  'code' => '800',
//                  'error_msg' => 'P-NonBYOD, Call  703-256-3456 to Activate'
//                ];
//            }

            return [
              'code' => $resp->statusCode,
              'error_msg'  => (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription)
            ];


        } catch (\Exception $ex) {

            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@jjonbp.com', '[GEN][' . getenv('APP_ENV') . '] ValidateBYOD() Exception', $message);

            return [
              'code' => $ex->getCode(),
              'error_msg' => $ex->getMessage(),
              'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function CheckServiceAvailability($zip) {
        try {

            self::init();

            $uid = uniqid();

            $req = '<?xml version="1.0" encoding="utf-8"?>';
            $req .= '<VCAREOSSAPI xmlns="http://www.oss.vcarecorporation.com/oss" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
            $req .=    '<CREDENTIALS>';
            $req .=        '<VENDORID>' . self::$VENDORID . '</VENDORID>';
            $req .=        '<USERNAME>' . self::$USERNAME . '</USERNAME>';
            $req .=        '<PASSWORD>' . self::$PASSWORD . '</PASSWORD>';
            $req .=        '<PIN>' . self::$PIN . '</PIN>';
            $req .=        '<REFERENCENUMBER>' . $uid . '</REFERENCENUMBER>';
            $req .=    '</CREDENTIALS>';
            $req .=    '<VCAREOSS>';
            $req .=    '<CHECKSERVICEAVAILABILITY>';
            $req .=        '<ENROLLMENTTYPE>NONLIFELINE</ENROLLMENTTYPE>';
            $req .=        '<ZIPCODE>' . $zip . '</ZIPCODE>';
            $req .=        '<COMPANYID>' . self::$COMPANYID . '</COMPANYID>';
            $req .=        '<ISENROLLMENT>Y</ISENROLLMENT>';
            $req .=        '<ISWEBPARTNER>Y</ISWEBPARTNER>';
            $req .=        '<SOURCE>API</SOURCE>';
            $req .=        '<AGENTID>' . self::$AGENTID . '</AGENTID>';
            $req .=        '<AGENTPASSWORD>' . self::$AGENTPASSWORD . '</AGENTPASSWORD>';
            $req .=    '</CHECKSERVICEAVAILABILITY>';
            $req .=    '</VCAREOSS>';
            $req .= '</VCAREOSSAPI>';

            $ret = self::sendRequest($req, 'CheckServiceAvailability/');

            $resp_xml = simplexml_load_string($ret, null, LIBXML_NOCDATA);
            $resp = $resp_xml->CheckServiceAvailability;

            if ($resp->statusCode == '00') {
                return [
                  'code' => '0',
                  'error_msg' => (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription)
                ];
            }


            return [
              'code' => $resp->statusCode,
              'error_msg'  => (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription)
            ];


        } catch (\Exception $ex) {

            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@jjonbp.com', '[GEN][' . getenv('APP_ENV') . '] CheckServiceAvailability() Exception', $message);

            return [
              'code' => $ex->getCode(),
              'error_msg' => $ex->getMessage(),
              'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function getCustomerInfo_for_esn($mdn) {
        try {
            self::init();

            $req = '<?xml version="1.0" encoding="utf-8"?>';
            $req .= '<VCAREOSSAPI xmlns="http://www.oss.vcarecorporation.com/oss" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
            $req .=    '<CREDENTIALS>';
            $req .=        '<VENDORID>' . self::$VENDORID . '</VENDORID>';
            $req .=        '<USERNAME>' . self::$USERNAME . '</USERNAME>';
            $req .=        '<PASSWORD>' . self::$PASSWORD . '</PASSWORD>';
            $req .=        '<PIN>' . self::$PIN . '</PIN>';
            $req .=        '<REFERENCENUMBER>' . 9191 . '</REFERENCENUMBER>';
            $req .=    '</CREDENTIALS>';
            $req .=    '<VCAREOSS>';
            $req .=    '<GETCUSTOMERINFO>';
            $req .=        '<AGENTID>' . self::$AGENTID . '</AGENTID>';
            $req .=        '<AGENTPASSWORD>' . self::$AGENTPASSWORD . '</AGENTPASSWORD>';
            $req .=        '<TELEPHONENUMBER>' . $mdn . '</TELEPHONENUMBER>';
            $req .=        '<ILDINFO>N</ILDINFO>';
            $req .=        '<PLANINFOREQUIRED>N</PLANINFOREQUIRED>';
            $req .=        '<GETUSAGES>N</GETUSAGES>';
            $req .=        '<BALANCEENQUIRY>N</BALANCEENQUIRY>';
            $req .=        '<SECRETINFOREQUIRED>Y</SECRETINFOREQUIRED>';
            $req .=    '</GETCUSTOMERINFO>';
            $req .=    '</VCAREOSS>';
            $req .= '</VCAREOSSAPI>';

            $ret = self::sendRequest($req, 'GetCustomerInfo/');

            if(empty($ret)){
                return [
                    'error_code' => '-1',
                    'error_msg'  => 'The Vendor Service is not available'
                ];
            }

            $resp_xml = simplexml_load_string($ret, null, LIBXML_NOCDATA);
            $resp = $resp_xml->GetCustomerInfo;

            if ($resp->statusCode == '00') {
                $customer_id = self::getTextBetweenTags($ret, 'CustomerID');
                $esn_number = self::getTextBetweenTags($ret, 'ESNNumber');
                $telephone_number = self::getTextBetweenTags($ret, 'TelephoneNumber');
                $uiccid = self::getTextBetweenTags($ret, 'uiccid');
                $account_password = self::getTextBetweenTags($ret, 'accountPassword');

                return [
                    'error_code' => '',
                    'error_msg' => (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription),
                    'customer_id'   => $customer_id,
                    'esn_number'    => $esn_number,
                    'telephone_number' => $telephone_number,
                    'uiccid' => $uiccid,
                    'account_password' => $account_password
                ];
            }

            return [
                'error_code' => $resp->statusCode,
                'error_msg'  => (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription)
            ];

        } catch (\Exception $ex) {

            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@jjonbp.com', '[GEN][' . getenv('APP_ENV') . '] getCustomerInfo_for_esn() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function send_notification($mdn, $pin) {
        try {
            self::init();

            $req = '<?xml version="1.0" encoding="utf-8"?>';
            $req .= '<VCAREOSSAPI xmlns="http://www.oss.vcarecorporation.com/oss" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
            $req .=    '<CREDENTIALS>';
            $req .=        '<VENDORID>' . self::$VENDORID . '</VENDORID>';
            $req .=        '<USERNAME>' . self::$USERNAME . '</USERNAME>';
            $req .=        '<PASSWORD>' . self::$PASSWORD . '</PASSWORD>';
            $req .=        '<PIN>' . self::$PIN . '</PIN>';
            $req .=        '<REFERENCENUMBER>' . 9191 . '</REFERENCENUMBER>';
            $req .=    '</CREDENTIALS>';
            $req .=    '<VCAREOSS>';
            $req .=    '<SENDNOTIFICATION>';
            $req .=        '<AGENTID>' . self::$AGENTID . '</AGENTID>';
            $req .=        '<AGENTPASSWORD>' . self::$AGENTPASSWORD . '</AGENTPASSWORD>';
            $req .=        '<SOURCE>API</SOURCE>';
            $req .=        '<MDN>' . $mdn . '</MDN>';
            $req .=        '<MESSAGE>Your Gen Mobile password is ' . $pin . ' If you did not request your password, call us at *611 to change your password.</MESSAGE>';
            $req .=    '</SENDNOTIFICATION>';
            $req .=    '</VCAREOSS>';
            $req .= '</VCAREOSSAPI>';

            $ret = self::sendRequest($req, 'SendNotification/');

            if(empty($ret)){
                return [
                    'error_code' => '-1',
                    'error_msg'  => 'The Vendor Service is not available'
                ];
            }
            $resp_xml = simplexml_load_string($ret, null, LIBXML_NOCDATA);
            $resp = $resp_xml->SendNotification;
            if ($resp->statusCode == '00') {
                return [
                    'error_code' => '',
                    'error_msg' => (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription)
                ];
            }
            return [
                'error_code' => $resp->statusCode,
                'error_msg'  => (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription)
            ];
        } catch (\Exception $ex) {

            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@jjonbp.com', '[GEN][' . getenv('APP_ENV') . '] SendNotification() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function swap_esn($customer_id, $mdn, $old_esn, $olduiccid, $new_esn) {
        try {
            self::init();

            $req = '<?xml version="1.0" encoding="utf-8"?>';
            $req .= '<VCAREOSSAPI xmlns="http://www.oss.vcarecorporation.com/oss" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
            $req .=    '<CREDENTIALS>';
            $req .=        '<VENDORID>' . self::$VENDORID . '</VENDORID>';
            $req .=        '<USERNAME>' . self::$USERNAME . '</USERNAME>';
            $req .=        '<PASSWORD>' . self::$PASSWORD . '</PASSWORD>';
            $req .=        '<PIN>' . self::$PIN . '</PIN>';
            $req .=        '<REFERENCENUMBER>' . 9191 . '</REFERENCENUMBER>';
            $req .=    '</CREDENTIALS>';
            $req .=    '<VCAREOSS>';
            $req .=    '<SWAPESN>';
            $req .=        '<CUSTOMERID>' . $customer_id . '</CUSTOMERID>';
            $req .=        '<MDN>' . $mdn . '</MDN>';
            $req .=        '<OLDESN>' . $old_esn . '</OLDESN>';
            $req .=        '<OLDUICCID>' . $olduiccid . '</OLDUICCID>';
            $req .=        '<NEWESN>' . $new_esn . '</NEWESN>';
            $req .=        '<NEWUICCID></NEWUICCID>';
            $req .=        '<CARRIER>SPR</CARRIER>';
            $req .=        '<CHANGETYPE>ELECTRONIC</CHANGETYPE>';
            $req .=        '<CHANGEREASON>REMOVED</CHANGEREASON>';
            $req .=        '<CHANGENOTES></CHANGENOTES>';
            $req .=        '<MODELID>73</MODELID>';
            $req .=        '<STORETYPE>Employee</STORETYPE>';
            $req .=        '<STOREID>PERFECT_EMPLOYEE</STOREID>';
            $req .=        '<AGENTID>' . self::$AGENTID . '</AGENTID>';
            $req .=        '<AGENTPASSWORD>' . self::$AGENTPASSWORD . '</AGENTPASSWORD>';
            $req .=        '<SOURCE>API</SOURCE>';
            $req .=    '</SWAPESN>';
            $req .=    '</VCAREOSS>';
            $req .= '</VCAREOSSAPI>';

            $ret = self::sendRequest($req, 'SwapESN/');

            if(empty($ret)){
                return [
                    'error_code' => '-1',
                    'error_msg'  => 'The Vendor Service is not available'
                ];
            }
            $resp_xml = simplexml_load_string($ret, null, LIBXML_NOCDATA);
            $resp = $resp_xml->SwapESN;

            if ($resp->statusCode == '00') {
                return [
                    'error_code' => '',
                    'error_msg' => (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription)
                ];
            }
            return [
                'error_code' => $resp->statusCode,
                'error_msg'  => (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription)
            ];
        } catch (\Exception $ex) {

            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@jjonbp.com', '[GEN][' . getenv('APP_ENV') . '] swap_esn() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function swap_mdn($customer_id, $old_esn, $mdn, $zip) {
        try {
            self::init();

            $req = '<?xml version="1.0" encoding="utf-8"?>';
            $req .= '<VCAREOSSAPI xmlns="http://www.oss.vcarecorporation.com/oss" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
            $req .=    '<CREDENTIALS>';
            $req .=        '<VENDORID>' . self::$VENDORID . '</VENDORID>';
            $req .=        '<USERNAME>' . self::$USERNAME . '</USERNAME>';
            $req .=        '<PASSWORD>' . self::$PASSWORD . '</PASSWORD>';
            $req .=        '<PIN>' . self::$PIN . '</PIN>';
            $req .=        '<REFERENCENUMBER>' . 9191 . '</REFERENCENUMBER>';
            $req .=    '</CREDENTIALS>';
            $req .=    '<VCAREOSS>';
            $req .=    '<SWAPMDN>';
            $req .=        '<CUSTOMERID>' . $customer_id . '</CUSTOMERID>';
            $req .=        '<ESN>' . $old_esn . '</ESN>';
            $req .=        '<OLDMDN>' . $mdn . '</OLDMDN>';
            $req .=        '<NEWMDN></NEWMDN>';
            $req .=        '<COMPANYID>' . self::$COMPANYID . '</COMPANYID>';
            $req .=        '<CARRIER>SPR</CARRIER>';
            $req .=        '<CHANGETYPE>ELECTRONIC</CHANGETYPE>';
            $req .=        '<ZIPCODE>' . $zip . '</ZIPCODE>';
            $req .=        '<AGENTID>' . self::$AGENTID . '</AGENTID>';
            $req .=        '<AGENTPASSWORD>' . self::$AGENTPASSWORD . '</AGENTPASSWORD>';
            $req .=        '<SOURCE>API</SOURCE>';
            $req .=    '</SWAPMDN>';
            $req .=    '</VCAREOSS>';
            $req .= '</VCAREOSSAPI>';

            $ret = self::sendRequest($req, 'SwapMDN/');

            if(empty($ret)){
                return [
                    'error_code' => '-1',
                    'error_msg'  => 'The Vendor Service is not available'
                ];
            }
            $resp_xml = simplexml_load_string($ret, null, LIBXML_NOCDATA);
            $resp = $resp_xml->SwapMDN;

            if ($resp->statusCode == '00') {
                return [
                    'error_code' => '',
                    'error_msg' => (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription)
                ];
            }
            return [
                'error_code' => $resp->statusCode,
                'error_msg'  => (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription)
            ];
        } catch (\Exception $ex) {

            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@jjonbp.com', '[GEN][' . getenv('APP_ENV') . '] swap_mdn() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function GetCustomerInfo($mdn) {
        try {

            self::init();

            $req = '<?xml version="1.0" encoding="utf-8"?>';
            $req .= '<VCAREOSSAPI xmlns="http://www.oss.vcarecorporation.com/oss" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
            $req .=    '<CREDENTIALS>';
            $req .=        '<VENDORID>' . self::$VENDORID . '</VENDORID>';
            $req .=        '<USERNAME>' . self::$USERNAME . '</USERNAME>';
            $req .=        '<PASSWORD>' . self::$PASSWORD . '</PASSWORD>';
            $req .=        '<PIN>' . self::$PIN . '</PIN>';
            $req .=        '<REFERENCENUMBER>' . 9191 . '</REFERENCENUMBER>';
            $req .=    '</CREDENTIALS>';
            $req .=    '<VCAREOSS>';
            $req .=    '<GETCUSTOMERINFO>';
//            $req .=        '<CUSTOMERID></CUSTOMERID>';
//		    $req .=        '<ENROLLMENTID></ENROLLMENTID>';
            $req .=        '<TELEPHONENUMBER>' . $mdn . '</TELEPHONENUMBER>';
            $req .=        '<LASTNAME></LASTNAME>';
            $req .=        '<ADDITIONALINFORMATION>N</ADDITIONALINFORMATION>';
            $req .=        '<GETEMAIL>N</GETEMAIL>';
            $req .=        '<GETRECERTIFICATION>N</GETRECERTIFICATION>';
            $req .=        '<ILDINFO>N</ILDINFO>';
            $req .=        '<PLANINFOREQUIRED>Y</PLANINFOREQUIRED>';
            $req .=        '<FEDERALDISCOUNT>N</FEDERALDISCOUNT>';
            $req .=        '<GETUSAGES>N</GETUSAGES>';
            $req .=        '<BALANCEENQUIRY>Y</BALANCEENQUIRY>';
            $req .=        '<AGENTID>' . self::$AGENTID . '</AGENTID>';
            $req .=        '<AGENTPASSWORD>' . self::$AGENTPASSWORD . '</AGENTPASSWORD>';
            $req .=        '<SOURCE>API</SOURCE>';
            $req .=    '</GETCUSTOMERINFO>';
            $req .=    '</VCAREOSS>';
            $req .= '</VCAREOSSAPI>';

//            sleep(1);
            $ret = self::sendRequest($req, 'GetCustomerInfo/');

            if(empty($ret)){
                return [
                    'error_code' => '-1',
                    'error_msg'  => 'The Vendor Service is not available'
                ];
            }

            $resp_xml = simplexml_load_string($ret, null, LIBXML_NOCDATA);
            $resp = $resp_xml->GetCustomerInfo;

            if ($resp->statusCode == '00') {
                $customer_id = self::getTextBetweenTags($ret, 'CustomerID');
                $plancode = self::getTextBetweenTags($ret, 'currentplancode');
                $balance_wallet = self::getTextBetweenTags($ret, 'balance_inwallet');
                $balance = self::getTextBetweenTags($ret, 'balance');
                $databalance = self::getTextBetweenTags($ret, 'databalance');
                $expirydate = self::getTextBetweenTags($ret, 'expirydate');
                $smsbalance = self::getTextBetweenTags($ret, 'smsbalance');
                $esn_number = self::getTextBetweenTags($ret, 'ESNNumber');
                $carrier = self::getTextBetweenTags($ret, 'Carrier');

                return [
                    'error_code' => '',
                    'error_msg' => (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription),
                    'customer_id'   => $customer_id,
                    'plancode'      => $plancode,
                    'balance_wallet' => $balance_wallet,
                    'balance'       => $balance,
                    'databalance'   => $databalance,
                    'expirydate'    => $expirydate,
                    'smsbalance'    => $smsbalance,
                    'esn_number'    => $esn_number,
                    'network'       => $carrier,
                ];
            }

            return [
                'error_code' => $resp->statusCode,
                'error_msg'  => (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription)
            ];

        } catch (\Exception $ex) {

            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@jjonbp.com', '[GEN][' . getenv('APP_ENV') . '] GetCustomerInfo() Exception', $message);

            return [
              'error_code' => 'E:' .$ex->getCode(),
              'error_msg' => $ex->getMessage(),
              'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function GetPuk($esn) {
        try {

            self::init();

            $req = '<?xml version="1.0" encoding="utf-8"?>';
            $req .= '<VCAREOSSAPI xmlns="http://www.oss.vcarecorporation.com/oss" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
            $req .=    '<CREDENTIALS>';
            $req .=        '<VENDORID>' . self::$VENDORID . '</VENDORID>';
            $req .=        '<USERNAME>' . self::$USERNAME . '</USERNAME>';
            $req .=        '<PASSWORD>' . self::$PASSWORD . '</PASSWORD>';
            $req .=        '<PIN>' . self::$PIN . '</PIN>';
            $req .=        '<REFERENCENUMBER>' . 9191 . '</REFERENCENUMBER>';
            $req .=    '</CREDENTIALS>';
            $req .=    '<VCAREOSS>';
            $req .=    '<GETPUK>';
            $req .=        '<ESN>' . $esn . '</ESN>';
            $req .=        '<AGENTID>' . self::$AGENTID . '</AGENTID>';
            $req .=        '<AGENTPASSWORD>' . self::$AGENTPASSWORD . '</AGENTPASSWORD>';
            $req .=        '<SOURCE>API</SOURCE>';
            $req .=    '</GETPUK>';
            $req .=    '</VCAREOSS>';
            $req .= '</VCAREOSSAPI>';

//            sleep(1);
            $ret = self::sendRequest($req, 'GetPuk/');

            $resp_xml = simplexml_load_string($ret, null, LIBXML_NOCDATA);
            $resp = $resp_xml->GetPuk;

            if ($resp->statusCode == '00') {
                $esn = self::getTextBetweenTags($ret, 'esn');
                $msl = self::getTextBetweenTags($ret, 'msl');
                $msid = self::getTextBetweenTags($ret, 'msid');
                $mdn = self::getTextBetweenTags($ret, 'mdn');

                return [
                    'error_code' => '',
                    'error_msg' => (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription),
                    'esn'       => $esn,
                    'msl'       => $msl,
                    'msid'      => $msid,
                    'mdn'       => $mdn,
                ];
            }

            return [
                'error_code' => $resp->statusCode,
                'error_msg'  => (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription)
            ];

        } catch (\Exception $ex) {

            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@jjonbp.com', '[GEN][' . getenv('APP_ENV') . '] GetPuk() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function Activate($params) {
        try {

//            return [
//                'error_code' => '',
//                'error_msg' => 'success - test',
//                'mdn'   => '9179171234'
//            ];

            self::init();

            $req = '<?xml version="1.0" encoding="utf-8"?>';
            $req .= '<VCAREOSSAPI xmlns="http://www.oss.vcarecorporation.com/oss" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
            $req .=    '<CREDENTIALS>';
            $req .=        '<VENDORID>' . self::$VENDORID . '</VENDORID>';
            $req .=        '<USERNAME>' . self::$USERNAME . '</USERNAME>';
            $req .=        '<PASSWORD>' . self::$PASSWORD . '</PASSWORD>';
            $req .=        '<PIN>' . self::$PIN . '</PIN>';
            $req .=        '<REFERENCENUMBER>' . $params->trans_id . '</REFERENCENUMBER>';
            $req .=    '</CREDENTIALS>';
            $req .=    '<VCAREOSS>';
            $req .=    '<CREATECUSTOMERPREPAID>';
            $req .=        '<ACTIVATION_TYPE>NEWACTIVATION</ACTIVATION_TYPE>';
            $req .=        '<FIRSTNAME>UNKNOWN</FIRSTNAME>';
            $req .=        '<LASTNAME>UNKNOWN</LASTNAME>';
//            $req .=        '<ALTERNATIVECONTACTNUMBER>' . $params->contact_phone . '</ALTERNATIVECONTACTNUMBER>';
//            $req .=        '<EMAIL>' . $params->contact_email . '</EMAIL>';
            $req .=        '<PHYSICALADDRESS>';
            $req .=             '<ADDRESS1>123 UNKNOWN</ADDRESS1>';
            $req .=             '<ADDRESS2></ADDRESS2>';
            $req .=             '<CITY>' . $params->city . '</CITY>';
            $req .=             '<STATE>' . $params->state . '</STATE>';
            $req .=             '<ZIP>' . $params->zip . '</ZIP>';
            $req .=        '</PHYSICALADDRESS>';
            $req .=        '<PLAN_CODE>' . $params->act_pid . '</PLAN_CODE>';
            $req .=        '<UICCID>' . (empty($params->sim) ? '' : $params->sim) . '</UICCID>';
            $req .=        '<ESN>' . $params->esn . '</ESN>';
            $req .=        '<CARRIER>' . $params->network . '</CARRIER>';
            $req .=        '<COMPANY_ID>42</COMPANY_ID>';
            $req .=        '<ENROLLMENTTYPE>HANDOVER</ENROLLMENTTYPE>';
            $req .=        '<HANDOVER_TYPE>cbe</HANDOVER_TYPE>';

            $req .=        '<AGENTID>' . self::$AGENTID . '</AGENTID>';
            $req .=        '<AGENTPASSWORD>' . self::$AGENTPASSWORD . '</AGENTPASSWORD>';
            $req .=        '<SOURCE>API</SOURCE>';
            $req .=    '</CREATECUSTOMERPREPAID>';
            $req .=    '</VCAREOSS>';
            $req .= '</VCAREOSSAPI>';

//            sleep(1);
            $ret = self::sendRequest($req, 'CreateCustomerPrepaid/');

/*            $xml_string = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\r\t\t\t\t\t\t\t<VcareOssApi xmlns=\"http://www.oss.vcarecorporation.com/oss\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">\r\t\t\t\t\t\t\t<credentials> \r\t\t\t\t\t\t\t\t<vendorId>Demo-genmobile</vendorId> \r\t\t\t\t\t\t\t\t<referenceNumber>9191</referenceNumber> \r\t\t\t\t\t\t\t</credentials>\r\t\t\t\t\t\t\t<CreateCustomerPrepaid>\r\t\t\t\t\t\t\t\t<statusCode>00</statusCode>\r\t\t\t\t\t\t\t\t<description>SUCCESS</description><errorDescription>SUCCESS</errorDescription><enrollmentId>APT4251</enrollmentId><customerid>3643</customerid>\r\t\t\t\t\t\t\t\t\t\t\t\t\t<mdn>4346889709</mdn>\r\t\t\t\t\t\t\t\t\t\t\t\t\t<msid>8043651978</msid>\r\t\t\t\t\t\t\t\t\t\t\t\t\t<msl>643924</msl>\r\t\t\t\t\t\t\t\t\t\t\t\t\t<NotProvisionMsg>success:-:Request Successfully Submitted To System.</NotProvisionMsg><accountpassword>0000</accountpassword>\r\t\t\t\t\t\t\t</CreateCustomerPrepaid>\r\t\t\t\t\t\t\t\r\t\t\t\t\t\t\t</VcareOssApi>";*/
//
//            $ret = simplexml_load_string($xml_string, null, LIBXML_NOCDATA);

            $resp_xml = simplexml_load_string($ret, null, LIBXML_NOCDATA);
            $resp = $resp_xml->CreateCustomerPrepaid;

            if ($resp->statusCode == '00') {
                $gen = new GenActivation();
                $gen->trans_id = $params->trans_id;
                $gen->enrollment_id = $resp->enrollmentId;
                $gen->customer_id = $resp->customerid;
                $gen->mdn = $resp->mdn;
                $gen->msid = $resp->msid;
                $gen->msl = $resp->msl;
                $gen->cdate = \Carbon\Carbon::now();
                $gen->save();

                return [
                    'error_code' => '',
                    'error_msg' => (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription),
                    'mdn'   => $resp->mdn
                ];
            }


            return [
              'error_code' => $resp->statusCode,
              'error_msg'  => (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription)
            ];

        } catch (\Exception $ex) {

            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@jjonbp.com', '[GEN][' . getenv('APP_ENV') . '] Activate() Exception', $message);

            return [
              'error_code' => 'E:' .$ex->getCode(),
              'error_msg' => $ex->getMessage(),
              'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function Portin($params) {
        try {

            self::init();

            $req = '<?xml version="1.0" encoding="utf-8"?>';
            $req .= '<VCAREOSSAPI xmlns="http://www.oss.vcarecorporation.com/oss" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
            $req .=    '<CREDENTIALS>';
            $req .=        '<VENDORID>' . self::$VENDORID . '</VENDORID>';
            $req .=        '<USERNAME>' . self::$USERNAME . '</USERNAME>';
            $req .=        '<PASSWORD>' . self::$PASSWORD . '</PASSWORD>';
            $req .=        '<PIN>' . self::$PIN . '</PIN>';
            $req .=        '<REFERENCENUMBER>' . $params->trans_id . '</REFERENCENUMBER>';
            $req .=    '</CREDENTIALS>';
            $req .=    '<VCAREOSS>';
            $req .=    '<CREATECUSTOMERPREPAID>';
            $req .=        '<ACTIVATION_TYPE>PORTIN</ACTIVATION_TYPE>';
            $req .=        '<FIRSTNAME>UNKNOWN</FIRSTNAME>';
            $req .=        '<LASTNAME>UNKNOWN</LASTNAME>';
            $req .=        '<ALTERNATIVECONTACTNUMBER>' . $params->call_back_phone . '</ALTERNATIVECONTACTNUMBER>';
            $req .=        '<EMAIL>' . $params->email . '</EMAIL>';
            $req .=        '<PHYSICALADDRESS>';
            $req .=             '<ADDRESS1>123 UNKNOWN</ADDRESS1>';
            $req .=             '<ADDRESS2></ADDRESS2>';
            $req .=             '<CITY>' . $params->city . '</CITY>';
            $req .=             '<STATE>' . $params->state . '</STATE>';
            $req .=             '<ZIP>' . $params->zip . '</ZIP>';
            $req .=        '</PHYSICALADDRESS>';
            $req .=        '<PLAN_CODE>' . $params->act_pid . '</PLAN_CODE>';
            $req .=        '<UICCID>' . (empty($params->sim) ? '' : $params->sim) . '</UICCID>';
            $req .=        '<MDN>' . $params->phone . '</MDN>';
            $req .=        '<ESN>' . $params->esn . '</ESN>';
            $req .=        '<CARRIER>' . $params->network . '</CARRIER>';
            $req .=        '<COMPANY_ID>42</COMPANY_ID>';
            $req .=        '<ENROLLMENTTYPE>HANDOVER</ENROLLMENTTYPE>';
            $req .=        '<HANDOVER_TYPE>cbe</HANDOVER_TYPE>';

            $req .=        '<PORTIN>';
		    $req .=             '<OSP_CARRIER>OTHER</OSP_CARRIER>';
            $req .=             '<OSP_FIRSTNAME>' . $params->first_name . '</OSP_FIRSTNAME>';
            $req .=             '<OSP_MIDDLENAME></OSP_MIDDLENAME>';
            $req .=             '<OSP_LASTNAME>' . $params->last_name . '</OSP_LASTNAME>';
            $req .=             '<OSP_ADDRESS1>' . $params->address1 . '</OSP_ADDRESS1>';
            $req .=             '<OSP_ADDRESS2>' . $params->address2 . '</OSP_ADDRESS2>';
            $req .=             '<OSP_CITY>' . $params->account_city . '</OSP_CITY>';
            $req .=             '<OSP_STATE>' . $params->account_state . '</OSP_STATE>';
            $req .=             '<OSP_ZIP>' . $params->account_zip . '</OSP_ZIP>';
            $req .=             '<ACCOUNTNUMBER>' . $params->account_no . '</ACCOUNTNUMBER>';
            $req .=             '<PASSWORDPIN>' . $params->account_pin . '</PASSWORDPIN>';
            $req .=        '</PORTIN>';

            $req .=        '<AGENTID>' . self::$AGENTID . '</AGENTID>';
            $req .=        '<AGENTPASSWORD>' . self::$AGENTPASSWORD . '</AGENTPASSWORD>';
            $req .=        '<SOURCE>API</SOURCE>';
            $req .=    '</CREATECUSTOMERPREPAID>';
            $req .=    '</VCAREOSS>';
            $req .= '</VCAREOSSAPI>';

//            sleep(1);
            $ret = self::sendRequest($req, 'CreateCustomerPrepaid/');

/*            $xml_string = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\r\t\t\t\t\t\t\t<VcareOssApi xmlns=\"http://www.oss.vcarecorporation.com/oss\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">\r\t\t\t\t\t\t\t<credentials> \r\t\t\t\t\t\t\t\t<vendorId>Demo-genmobile</vendorId> \r\t\t\t\t\t\t\t\t<referenceNumber>9191</referenceNumber> \r\t\t\t\t\t\t\t</credentials>\r\t\t\t\t\t\t\t<CreateCustomerPrepaid>\r\t\t\t\t\t\t\t\t<statusCode>00</statusCode>\r\t\t\t\t\t\t\t\t<description>SUCCESS</description><errorDescription>SUCCESS</errorDescription><enrollmentId>APT4268</enrollmentId><customerid>3652</customerid>\r\t\t\t\t\t\t\t\t\t\t\t\t\t<mdn>4346889911</mdn>\r\t\t\t\t\t\t\t\t\t\t\t\t\t<msid>8043652506</msid>\r\t\t\t\t\t\t\t\t\t\t\t\t\t<msl>550390</msl>\r\t\t\t\t\t\t\t\t\t\t\t\t\t<NotProvisionMsg>success:-:Request Successfully Submitted To System.</NotProvisionMsg><accountpassword>0000</accountpassword>\r\t\t\t\t\t\t\t</CreateCustomerPrepaid>\r\t\t\t\t\t\t\t\r\t\t\t\t\t\t\t</VcareOssApi>";*/
//
//            $resp_xml = simplexml_load_string($xml_string, null, LIBXML_NOCDATA);

            $resp_xml = simplexml_load_string($ret, null, LIBXML_NOCDATA);
            $resp = $resp_xml->CreateCustomerPrepaid;


            if ($resp->statusCode == '00') {
                $gen = new GenActivation();
                $gen->trans_id = $params->trans_id;
                $gen->enrollment_id = $resp->enrollmentId;
                $gen->customer_id = $resp->customerid;
                $gen->mdn = $resp->mdn;
                $gen->msid = $resp->msid;
                $gen->msl = $resp->msl;
                $gen->cdate = \Carbon\Carbon::now();
                $gen->save();

                return [
                  'error_code' => '',
                  'error_msg' => (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription),
                  'mdn'   => $resp->mdn
                ];
            }

            return [
              'error_code' => $resp->statusCode,
              'error_msg'  => (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription)
            ];

        } catch (\Exception $ex) {

            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@jjonbp.com', '[GEN][' . getenv('APP_ENV') . '] Portin() Exception', $message);

            return [
              'error_code' => 'E:' .$ex->getCode(),
              'error_msg' => $ex->getMessage(),
              'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function QueryPortin($trans) {
        try {

            self::init();

            $gen = GenActivation::where('trans_id', $trans->id)->first();

            if (empty($gen)) {
                return [
                  'error_code' => '-9',
                  'error_msg' => 'Portin data not found.'
                ];
            }

            $req = '<?xml version="1.0" encoding="utf-8"?>';
            $req .= '<VCAREOSSAPI xmlns="http://www.oss.vcarecorporation.com/oss" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
            $req .=    '<CREDENTIALS>';
            $req .=        '<VENDORID>' . self::$VENDORID . '</VENDORID>';
            $req .=        '<USERNAME>' . self::$USERNAME . '</USERNAME>';
            $req .=        '<PASSWORD>' . self::$PASSWORD . '</PASSWORD>';
            $req .=        '<PIN>' . self::$PIN . '</PIN>';
            $req .=        '<REFERENCENUMBER>' . $trans->id . '</REFERENCENUMBER>';
            $req .=    '</CREDENTIALS>';
            $req .=    '<VCAREOSS>';
            $req .=    '<QUERYPORTIN>';

            $req .=        '<CUSTOMERID></CUSTOMERID>';
            $req .=        '<ESN></ESN>';
            $req .=        '<CARRIER>' . $trans->network . '</CARRIER>';
            $req .=        '<MDN>' . $trans->phone . '</MDN>';
            $req .=        '<RETURNURL></RETURNURL>';
            $req .=        '<COMPANYID>42</COMPANYID>';
            $req .=        '<TRANSACTIONID></TRANSACTIONID>';

            $req .=        '<AGENTID>' . self::$AGENTID . '</AGENTID>';
            $req .=        '<AGENTPASSWORD>' . self::$AGENTPASSWORD . '</AGENTPASSWORD>';
            $req .=        '<SOURCE>API</SOURCE>';
            $req .=    '</QUERYPORTIN>';
            $req .=    '</VCAREOSS>';
            $req .= '</VCAREOSSAPI>';

//            sleep(1);
            $ret = self::sendRequest($req, 'QueryPortin/');

            /*            $xml_string = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\r\t\t\t\t\t\t\t<VcareOssApi xmlns=\"http://www.oss.vcarecorporation.com/oss\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">\r\t\t\t\t\t\t\t<credentials> \r\t\t\t\t\t\t\t\t<vendorId>Demo-genmobile</vendorId> \r\t\t\t\t\t\t\t\t<referenceNumber>9191</referenceNumber> \r\t\t\t\t\t\t\t</credentials>\r\t\t\t\t\t\t\t<CreateCustomerPrepaid>\r\t\t\t\t\t\t\t\t<statusCode>00</statusCode>\r\t\t\t\t\t\t\t\t<description>SUCCESS</description><errorDescription>SUCCESS</errorDescription><enrollmentId>APT4251</enrollmentId><customerid>3643</customerid>\r\t\t\t\t\t\t\t\t\t\t\t\t\t<mdn>4346889709</mdn>\r\t\t\t\t\t\t\t\t\t\t\t\t\t<msid>8043651978</msid>\r\t\t\t\t\t\t\t\t\t\t\t\t\t<msl>643924</msl>\r\t\t\t\t\t\t\t\t\t\t\t\t\t<NotProvisionMsg>success:-:Request Successfully Submitted To System.</NotProvisionMsg><accountpassword>0000</accountpassword>\r\t\t\t\t\t\t\t</CreateCustomerPrepaid>\r\t\t\t\t\t\t\t\r\t\t\t\t\t\t\t</VcareOssApi>";*/

            $resp_xml = simplexml_load_string($ret, null, LIBXML_NOCDATA);
            $resp = $resp_xml->QueryPortin;

            ## Status Code	Description
            ## 00	SUCCESS
            ## 01	Error Received from Carrier.
            ## 13	Password is invalid.
            ## 16	Vendor not found. Wrong credentials.
            ## 356	Agent ID cannot be blank.
            ## 457	Invalid Source.
            ## 751	Custom message.

            if ($resp->statusCode == '00') {
                $responsetype   = $resp->responsetype;
                $reasoncode     = $resp->reasoncode;

                return [
                  'error_code' => '',
                    'responsetype'  => $responsetype,
                    'reasoncode'    => $reasoncode,
                  'error_msg' => (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription)
                ];
            }

            return [
              'error_code' => $resp->statusCode,
              'error_msg'  => (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription)
            ];

        } catch (\Exception $ex) {

            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@jjonbp.com', '[GEN][' . getenv('APP_ENV') . '] QueryPortin() Exception', $message);

            return [
              'error_code' => 'E:' .$ex->getCode(),
              'error_msg' => $ex->getMessage(),
              'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function ValidatePortin($trans) {
        try {

            self::init();


            $req = '<?xml version="1.0" encoding="utf-8"?>';
            $req .= '<VCAREOSSAPI xmlns="http://www.oss.vcarecorporation.com/oss" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
            $req .=    '<CREDENTIALS>';
            $req .=        '<VENDORID>' . self::$VENDORID . '</VENDORID>';
            $req .=        '<USERNAME>' . self::$USERNAME . '</USERNAME>';
            $req .=        '<PASSWORD>' . self::$PASSWORD . '</PASSWORD>';
            $req .=        '<PIN>' . self::$PIN . '</PIN>';
            $req .=        '<REFERENCENUMBER>' . $trans->id . '</REFERENCENUMBER>';
            $req .=    '</CREDENTIALS>';
            $req .=    '<VCAREOSS>';
            $req .=    '<VALIDATEPORTIN>';

            $req .=        '<MDN>' . $trans->phone . '</MDN>';
            $req .=        '<CARRIER>SPR</CARRIER>';
            $req .=        '<COMPANYID>42</COMPANYID>';

            $req .=        '<AGENTID>' . self::$AGENTID . '</AGENTID>';
            $req .=        '<AGENTPASSWORD>' . self::$AGENTPASSWORD . '</AGENTPASSWORD>';
            $req .=        '<SOURCE>API</SOURCE>';
            $req .=    '</VALIDATEPORTIN>';
            $req .=    '</VCAREOSS>';
            $req .= '</VCAREOSSAPI>';

//            sleep(1);
            $ret = self::sendRequest($req, 'ValidatePortin/');

            /*            $xml_string = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\r\t\t\t\t\t\t\t<VcareOssApi xmlns=\"http://www.oss.vcarecorporation.com/oss\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">\r\t\t\t\t\t\t\t<credentials> \r\t\t\t\t\t\t\t\t<vendorId>Demo-genmobile</vendorId> \r\t\t\t\t\t\t\t\t<referenceNumber>9191</referenceNumber> \r\t\t\t\t\t\t\t</credentials>\r\t\t\t\t\t\t\t<CreateCustomerPrepaid>\r\t\t\t\t\t\t\t\t<statusCode>00</statusCode>\r\t\t\t\t\t\t\t\t<description>SUCCESS</description><errorDescription>SUCCESS</errorDescription><enrollmentId>APT4251</enrollmentId><customerid>3643</customerid>\r\t\t\t\t\t\t\t\t\t\t\t\t\t<mdn>4346889709</mdn>\r\t\t\t\t\t\t\t\t\t\t\t\t\t<msid>8043651978</msid>\r\t\t\t\t\t\t\t\t\t\t\t\t\t<msl>643924</msl>\r\t\t\t\t\t\t\t\t\t\t\t\t\t<NotProvisionMsg>success:-:Request Successfully Submitted To System.</NotProvisionMsg><accountpassword>0000</accountpassword>\r\t\t\t\t\t\t\t</CreateCustomerPrepaid>\r\t\t\t\t\t\t\t\r\t\t\t\t\t\t\t</VcareOssApi>";*/

            $resp_xml = simplexml_load_string($ret, null, LIBXML_NOCDATA);
            $resp = $resp_xml->ValidatePortin;

            ## Status Code	Description
            ## 00	SUCCESS
            ## 01	Error Received from Carrier.
            ## 13	Password is invalid.
            ## 16	Vendor not found. Wrong credentials.
            ## 41	Carrier is invalid.
            ## 130	Invalid Company ID.
            ## 146	MDN should not be empty.
            ## 169	Company ID is required.
            ## 170	Carrier ID is required.
            ## 356	Agent ID cannot be blank.
            ## 367	MDN should be 10 digits.
            ## 457	Invalid Source.

            if ($resp->statusCode == '00') {
                return [
                  'error_code' => '',
                  'error_msg' => (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription)
                ];
            }

            return [
              'error_code' => $resp->statusCode,
              'error_msg'  => (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription)
            ];

        } catch (\Exception $ex) {

            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@jjonbp.com', '[GEN][' . getenv('APP_ENV') . '] ValidatePortin() Exception', $message);

            return [
              'error_code' => 'E:' .$ex->getCode(),
              'error_msg' => $ex->getMessage(),
              'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function UpdatePortin($trans) {
        try {

            self::init();

            $req = '<?xml version="1.0" encoding="utf-8"?>';
            $req .= '<VCAREOSSAPI xmlns="http://www.oss.vcarecorporation.com/oss" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
            $req .=    '<CREDENTIALS>';
            $req .=        '<VENDORID>' . self::$VENDORID . '</VENDORID>';
            $req .=        '<USERNAME>' . self::$USERNAME . '</USERNAME>';
            $req .=        '<PASSWORD>' . self::$PASSWORD . '</PASSWORD>';
            $req .=        '<PIN>' . self::$PIN . '</PIN>';
            $req .=        '<REFERENCENUMBER>' . $trans->id . '</REFERENCENUMBER>';
            $req .=    '</CREDENTIALS>';
            $req .=    '<VCAREOSS>';
            $req .=    '<UPDATEPORTIN>';

            $req .=        '<CUSTOMERID></CUSTOMERID>';
	        $req .=        '<COMPANYID>42</COMPANYID>';
	        $req .=        '<SIM>' . $trans->sim . '</SIM>';
	        $req .=        '<MDN>' . $trans->phone . '</MDN>';
	        $req .=        '<FIRSTNAME>' . $trans->first_name . '</FIRSTNAME>';
	        $req .=        '<LASTNAME>' . $trans->last_name . '</LASTNAME>';
	        $req .=        '<STREETNUMBER>' . $trans->address1 . '</STREETNUMBER>';
	        $req .=        '<STREETNAME>' . $trans->address2 . '</STREETNAME>';
	        $req .=        '<ADD2></ADD2>';
	        $req .=        '<CITY>' . $trans->account_city . '</CITY>';
	        $req .=        '<STATE>' . $trans->account_state . '</STATE>';
	        $req .=        '<ZIP>' . $trans->account_zip . '</ZIP>';
	        $req .=        '<OSPACCOUNTNUMBER>' . $trans->account_no . '</OSPACCOUNTNUMBER>';
	        $req .=        '<OSPACCOUNTPASSWORD>' . $trans->account_pin . '</OSPACCOUNTPASSWORD>';
	        $req .=        '<OLDESN></OLDESN>';
	        $req .=        '<DOB></DOB>';
	        $req .=        '<CARRIER>SPR</CARRIER>';
	        $req .=        '<RETURNURL></RETURNURL>';

            $req .=        '<AGENTID>' . self::$AGENTID . '</AGENTID>';
            $req .=        '<AGENTPASSWORD>' . self::$AGENTPASSWORD . '</AGENTPASSWORD>';
            $req .=        '<SOURCE>API</SOURCE>';
            $req .=    '</UPDATEPORTIN>';
            $req .=    '</VCAREOSS>';
            $req .= '</VCAREOSSAPI>';

//            sleep(1);
            $ret = self::sendRequest($req, 'UpdatePortin/');

            $resp_xml = simplexml_load_string($ret, null, LIBXML_NOCDATA);
            $resp = $resp_xml->UpdatePortin;

            if ($resp->statusCode == '00') {
                return [
                  'error_code' => '',
                  'error_msg' => (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription),
                  'mdn'   => $resp->mdn
                ];
            }

            return [
              'error_code' => $resp->statusCode,
              'error_msg'  => (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription)
            ];

        } catch (\Exception $ex) {

            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@jjonbp.com', '[GEN][' . getenv('APP_ENV') . '] UpdatePortin() Exception', $message);

            return [
              'error_code' => 'E:' .$ex->getCode(),
              'error_msg' => $ex->getMessage(),
              'error_trace' => $ex->getTraceAsString()
            ];
        }
    }


    public static function ChangePlan($trans, $customer_id, $cur_plan_code, $new_plan_code) {
        try {

            self::init();

            $req = '<?xml version="1.0" encoding="utf-8"?>';
            $req .= '<VCAREOSSAPI xmlns="http://www.oss.vcarecorporation.com/oss" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
            $req .=    '<CREDENTIALS>';
            $req .=        '<VENDORID>' . self::$VENDORID . '</VENDORID>';
            $req .=        '<USERNAME>' . self::$USERNAME . '</USERNAME>';
            $req .=        '<PASSWORD>' . self::$PASSWORD . '</PASSWORD>';
            $req .=        '<PIN>' . self::$PIN . '</PIN>';
            $req .=        '<REFERENCENUMBER>' . $trans->id . '</REFERENCENUMBER>';
            $req .=    '</CREDENTIALS>';
            $req .=    '<VCAREOSS>';
            $req .=    '<CHANGEPLAN>';

            $req .=        '<CUSTOMERID>' . $customer_id . '</CUSTOMERID>';
            $req .=        '<PLANID></PLANID>';
            $req .=        '<PLANCODE>' . $new_plan_code . '</PLANCODE>';
            $req .=        '<CHANGETYPE>' . ($cur_plan_code == 63 ? 'IMMEDIATE' : 'ONEXPIRY') . '</CHANGETYPE>';
            $req .=        '<KEEPORIGINALEXPIRY>' . ($cur_plan_code == 63 ? '' : 'Y') . '</KEEPORIGINALEXPIRY>';
            $req .=        '<PRORATEDPLANPRICE>' . ($cur_plan_code == 63 ? '' : 'N') . '</PRORATEDPLANPRICE>';

            $req .=        '<AGENTID>' . self::$AGENTID . '</AGENTID>';
            $req .=        '<AGENTPASSWORD>' . self::$AGENTPASSWORD . '</AGENTPASSWORD>';
            $req .=        '<SOURCE>API</SOURCE>';
            $req .=    '</CHANGEPLAN>';
            $req .=    '</VCAREOSS>';
            $req .= '</VCAREOSSAPI>';

//            sleep(1);

            Helper::log('### GEN: ChangePlan() ###', [
                'req' => $req
            ]);

            $ret = self::sendRequest($req, 'ChangePlan/');

            Helper::log('### GEN: ChangePlan() ###', [
                'ret' => $ret
            ]);

/*            $ret = "<?xml version=\"1.0\" encoding=\"utf - 8\"?>\r\t\t\t\t\t\t\t<VcareOssApi xmlns=\"http://www.oss.vcarecorporation.com/oss\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">\r\t\t\t\t\t\t\t<credentials> \r\t\t\t\t\t\t\t\t<vendorId>Demo-genmobile</vendorId> \r\t\t\t\t\t\t\t\t<referenceNumber>20734</referenceNumber> \r\t\t\t\t\t\t\t</credentials>\r\t\t\t\t\t\t\t<ChangePlan>\r\t\t\t\t\t\t\t\t<statusCode>00</statusCode>\r\t\t\t\t\t\t\t\t<description>SUCCESS</description><errorDescription>Request sent successfully to plan renew $10 - 300 mins + U Text + 1GB To $10 - 300 mins + U Text + 1GB. New plan will be effective from 2019-03-22 03:00:00  </errorDescription>\r\t\t\t\t\t\t\t</ChangePlan>\r\t\t\t\t\t\t\t\r\t\t\t\t\t\t\t</VcareOssApi>";*/

            $resp_xml = simplexml_load_string($ret, null, LIBXML_NOCDATA);
            $resp = $resp_xml->ChangePlan;

            Helper::log('### GEN: ChangePlan() ###', [
                'res' => $resp
            ]);

            if ($resp->statusCode == '00') {
                return [
                  'error_code' => '',
                  'error_msg' => null, //check empty at Process RTR, so make it null, not '', as of now.
                  'mdn'   => ''
                ];
            }

            Helper::log('### GEN::ChangePlan: Network error. ###', [
                'req' => $req,
                'res' => 'Error:'. (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription)
            ]);

            return [
              'error_code' => $resp->statusCode,
              'error_msg'  => 'Error:' . (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription)
            ];

        } catch (\Exception $ex) {

            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@jjonbp.com', '[GEN][' . getenv('APP_ENV') . '] ChangePlan() Exception', $message);

            return [
              'error_code' => 'E:' .$ex->getCode(),
              'error_msg' => 'ERROR:' .$ex->getMessage(),
              'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function AddDataTopup($trans, $customer_id, $plan_code) {
        try {

            self::init();

            $btype = 'DATA';
            switch ($plan_code) {
                case 'GENTALK05':
                    $btype = 'TOPUP';
                    break;
                case 'RILD':
                    $btype = 'ILD';
                    break;
            }

            $req = '<?xml version="1.0" encoding="utf-8"?>';
            $req .= '<VCAREOSSAPI xmlns="http://www.oss.vcarecorporation.com/oss" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
            $req .=    '<CREDENTIALS>';
            $req .=        '<VENDORID>' . self::$VENDORID . '</VENDORID>';
            $req .=        '<USERNAME>' . self::$USERNAME . '</USERNAME>';
            $req .=        '<PASSWORD>' . self::$PASSWORD . '</PASSWORD>';
            $req .=        '<PIN>' . self::$PIN . '</PIN>';
            $req .=        '<REFERENCENUMBER>' . $trans->id . '</REFERENCENUMBER>';
            $req .=    '</CREDENTIALS>';
            $req .=    '<VCAREOSS>';
            $req .=    '<ADDDATATOPUP>';

            $req .=        '<CUSTOMERID>' . $customer_id . '</CUSTOMERID>';
            $req .=        '<PLANID>' . $plan_code . '</PLANID>';
            $req .=        '<BALANCETYPE>' . $btype . '</BALANCETYPE>';
            $req .=        '<TAXREQUIRED>N</TAXREQUIRED>';

            $req .=        '<AGENTID>' . self::$AGENTID . '</AGENTID>';
            $req .=        '<AGENTPASSWORD>' . self::$AGENTPASSWORD . '</AGENTPASSWORD>';
            $req .=        '<SOURCE>API</SOURCE>';
            $req .=    '</ADDDATATOPUP>';
            $req .=    '</VCAREOSS>';
            $req .= '</VCAREOSSAPI>';

//            sleep(1);
            $ret = self::sendRequest($req, 'AddDataTopup/');

            /*            $ret = "<?xml version=\"1.0\" encoding=\"utf - 8\"?>\r\t\t\t\t\t\t\t<VcareOssApi xmlns=\"http://www.oss.vcarecorporation.com/oss\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">\r\t\t\t\t\t\t\t<credentials> \r\t\t\t\t\t\t\t\t<vendorId>Demo-genmobile</vendorId> \r\t\t\t\t\t\t\t\t<referenceNumber>20734</referenceNumber> \r\t\t\t\t\t\t\t</credentials>\r\t\t\t\t\t\t\t<ChangePlan>\r\t\t\t\t\t\t\t\t<statusCode>00</statusCode>\r\t\t\t\t\t\t\t\t<description>SUCCESS</description><errorDescription>Request sent successfully to plan renew $10 - 300 mins + U Text + 1GB To $10 - 300 mins + U Text + 1GB. New plan will be effective from 2019-03-22 03:00:00  </errorDescription>\r\t\t\t\t\t\t\t</ChangePlan>\r\t\t\t\t\t\t\t\r\t\t\t\t\t\t\t</VcareOssApi>";*/

            $resp_xml = simplexml_load_string($ret, null, LIBXML_NOCDATA);
            $resp = $resp_xml->Adddatatopup;

            if ($resp->statusCode == '00') {
                return [
                  'error_code' => '',
                  'error_msg' => (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription)
                ];
            }

            return [
              'error_code' => $resp->statusCode,
              'error_msg'  => (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription)
            ];

        } catch (\Exception $ex) {

            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@jjonbp.com', '[GEN][' . getenv('APP_ENV') . '] AddDataTopup() Exception', $message);

            return [
              'error_code' => 'E:' .$ex->getCode(),
              'error_msg' => $ex->getMessage(),
              'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function AddWallet($trans) {
        try {

            self::init();

            $req = '<?xml version="1.0" encoding="utf-8"?>';
            $req .= '<VCAREOSSAPI xmlns="http://www.oss.vcarecorporation.com/oss" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
            $req .=    '<CREDENTIALS>';
            $req .=        '<VENDORID>' . self::$VENDORID . '</VENDORID>';
            $req .=        '<USERNAME>' . self::$USERNAME . '</USERNAME>';
            $req .=        '<PASSWORD>' . self::$PASSWORD . '</PASSWORD>';
            $req .=        '<PIN>' . self::$PIN . '</PIN>';
            $req .=        '<REFERENCENUMBER>' . $trans->id . '</REFERENCENUMBER>';
            $req .=    '</CREDENTIALS>';
            $req .=    '<VCAREOSS>';
            $req .=    '<ADDWALLET>';

            $req .=        '<AMOUNT>' . $trans->denom . '</AMOUNT>';
            $req .=        '<MDN>' . $trans->phone . '</MDN>';
            $req .=        '<PAYMENTTYPE>CASH</PAYMENTTYPE>';
            $req .=        '<TAXREQUIRED>N</TAXREQUIRED>';
            $req .=        '<DEBITCREDIT>CR</DEBITCREDIT>';

            $req .=        '<AGENTID>' . self::$AGENTID . '</AGENTID>';
            $req .=        '<AGENTPASSWORD>' . self::$AGENTPASSWORD . '</AGENTPASSWORD>';
            $req .=        '<SOURCE>API</SOURCE>';
            $req .=    '</ADDWALLET>';
            $req .=    '</VCAREOSS>';
            $req .= '</VCAREOSSAPI>';

//            sleep(1);
            $ret = self::sendRequest($req, 'AddWallet/');

            /*            $ret = "<?xml version=\"1.0\" encoding=\"utf - 8\"?>\r\t\t\t\t\t\t\t<VcareOssApi xmlns=\"http://www.oss.vcarecorporation.com/oss\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">\r\t\t\t\t\t\t\t<credentials> \r\t\t\t\t\t\t\t\t<vendorId>Demo-genmobile</vendorId> \r\t\t\t\t\t\t\t\t<referenceNumber>20734</referenceNumber> \r\t\t\t\t\t\t\t</credentials>\r\t\t\t\t\t\t\t<ChangePlan>\r\t\t\t\t\t\t\t\t<statusCode>00</statusCode>\r\t\t\t\t\t\t\t\t<description>SUCCESS</description><errorDescription>Request sent successfully to plan renew $10 - 300 mins + U Text + 1GB To $10 - 300 mins + U Text + 1GB. New plan will be effective from 2019-03-22 03:00:00  </errorDescription>\r\t\t\t\t\t\t\t</ChangePlan>\r\t\t\t\t\t\t\t\r\t\t\t\t\t\t\t</VcareOssApi>";*/

            $resp_xml = simplexml_load_string($ret, null, LIBXML_NOCDATA);
            $resp = $resp_xml->AddWallet;

            if ($resp->statusCode == '00') {
                return [
                    'error_code' => '',
                    'error_msg' => (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription),
                    'transactionid' => $resp->transactionid
                ];
            }

            return [
                'error_code' => $resp->statusCode,
                'error_msg'  => (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription)
            ];

        } catch (\Exception $ex) {

            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@jjonbp.com', '[GEN][' . getenv('APP_ENV') . '] AddWallet() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function ValidatePin($trans) {
        try {

            self::init();

            $req = '<?xml version="1.0" encoding="utf-8"?>';
            $req .= '<VCAREOSSAPI xmlns="http://www.oss.vcarecorporation.com/oss" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
            $req .=    '<CREDENTIALS>';
            $req .=        '<VENDORID>' . self::$VENDORID . '</VENDORID>';
            $req .=        '<USERNAME>' . self::$USERNAME . '</USERNAME>';
            $req .=        '<PASSWORD>' . self::$PASSWORD . '</PASSWORD>';
            $req .=        '<PIN>' . self::$PIN . '</PIN>';
            $req .=        '<REFERENCENUMBER>' . $trans->id . '</REFERENCENUMBER>';
            $req .=    '</CREDENTIALS>';
            $req .=    '<VCAREOSS>';
            $req .=    '<VALIDATEPIN>';

            $req .=        '<PINNUMBER>' . $trans->pin . '</PINNUMBER>';

            $req .=        '<AGENTID>' . self::$AGENTID . '</AGENTID>';
            $req .=        '<AGENTPASSWORD>' . self::$AGENTPASSWORD . '</AGENTPASSWORD>';
            $req .=        '<SOURCE>API</SOURCE>';
            $req .=    '</VALIDATEPIN>';
            $req .=    '</VCAREOSS>';
            $req .= '</VCAREOSSAPI>';

            $ret = self::sendRequest($req, 'ValidatePin/');

            $resp_xml = simplexml_load_string($ret, null, LIBXML_NOCDATA);
            $resp = $resp_xml->ValidatePin;

            if ($resp->statusCode == '00') {
                return [
                  'error_code' => '',
                  'error_msg' => (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription),
                  'transactionid' => $resp->transactionid
                ];
            }

            return [
              'error_code' => $resp->statusCode,
              'error_msg'  => (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription)
            ];

        } catch (\Exception $ex) {

            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@jjonbp.com', '[GEN][' . getenv('APP_ENV') . '] ValidatePin() Exception', $message);

            return [
              'error_code' => 'E:' .$ex->getCode(),
              'error_msg' => $ex->getMessage(),
              'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function RedeemPin($trans) {
        try {

            self::init();

            $req = '<?xml version="1.0" encoding="utf-8"?>';
            $req .= '<VCAREOSSAPI xmlns="http://www.oss.vcarecorporation.com/oss" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
            $req .=    '<CREDENTIALS>';
            $req .=        '<VENDORID>' . self::$VENDORID . '</VENDORID>';
            $req .=        '<USERNAME>' . self::$USERNAME . '</USERNAME>';
            $req .=        '<PASSWORD>' . self::$PASSWORD . '</PASSWORD>';
            $req .=        '<PIN>' . self::$PIN . '</PIN>';
            $req .=        '<REFERENCENUMBER>' . $trans->id . '</REFERENCENUMBER>';
            $req .=    '</CREDENTIALS>';
            $req .=    '<VCAREOSS>';
            $req .=    '<REDEEMPIN>';

            $req .=        '<PINNUMBER>' . $trans->pin . '</PINNUMBER>';
            $req .=        '<MDN>' . $trans->phone . '</MDN>';
            $req .=        '<CATEGORYID>4</CATEGORYID>';

            $req .=        '<AGENTID>' . self::$AGENTID . '</AGENTID>';
            $req .=        '<AGENTPASSWORD>' . self::$AGENTPASSWORD . '</AGENTPASSWORD>';
            $req .=        '<SOURCE>API</SOURCE>';
            $req .=    '</REDEEMPIN>';
            $req .=    '</VCAREOSS>';
            $req .= '</VCAREOSSAPI>';

            $ret = self::sendRequest($req, 'RedeemPin/');

            $resp_xml = simplexml_load_string($ret, null, LIBXML_NOCDATA);
            $resp = $resp_xml->RedeemPin;

            if ($resp->statusCode == '00') {
                return [
                  'error_code' => '',
                  'error_msg' => (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription),
                  'transactionid' => $resp->transactionid
                ];
            }

            return [
              'error_code' => $resp->statusCode,
              'error_msg'  => (empty($resp->description) ? '' : $resp->description . ' ') . (empty($resp->errorDescription) ? '' : $resp->errorDescription)
            ];

        } catch (\Exception $ex) {

            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@jjonbp.com', '[GEN][' . getenv('APP_ENV') . '] RedeemPin() Exception', $message);

            return [
              'error_code' => 'E:' .$ex->getCode(),
              'error_msg' => $ex->getMessage(),
              'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    private static function sendRequest($req, $url) {

        $headers = array(
          "Connection: Keep-Alive",
          "User-Agent: PHPSoapClient",
          "Content-Type: text/xml; charset=utf-8",
          "SOAPAction: \"urn:void\""
        ); //SOAPAction: your op URL

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::$api_url . $url);

        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 600);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);

        $info = curl_getinfo($ch);

        curl_close($ch);

//        dd('req => '.$req. ' res => '.$output);
        Helper::log('### Send Request ###', [
          'req' => $req,
          'res' => $output,
          'info' => $info
        ]);

        $output = preg_replace("/[\n\r]/","", $output);
        $output = str_replace('&', ' ', $output);

        return $output;
    }

    public static function getTextBetweenTags($string, $tagname) {
        $pattern = "/<$tagname>([\w\W]*?)<\/$tagname>/";
        preg_match($pattern, $string, $matches);
        return $matches[1];
    }
}