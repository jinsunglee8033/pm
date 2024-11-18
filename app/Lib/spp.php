<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 2/20/19
 * Time: 4:06 PM
 */

namespace App\Lib;


use App\Model\Denom;

class spp
{
    private static $api_url = 'https://www.vcareapi.com/vcareOssApi/';
    private static $VENDORID = '-';
    private static $USERNAME = '-';
    private static $PASSWORD = '';
    private static $PIN = '-';
    private static $AGENTID = '';
    private static $AGENTPASSWORD = '';
    private static $COMPANYID = '';


    public static function ValidateBYOD($device_id) {
        return [
            'code'  => '0',
        ];
    }

    public static function CheckServiceAvailability($zip){
        return [
            'code'  => '0',
        ];
    }

    public static function CheckServiceAvailability2($zip) {
        try {

//            self::init();

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


            do {
                sleep(1);
                $ret = self::sendRequest($req, 'CheckServiceAvailability/');
                $result = $ret->children('soap', true)->Body->children()->TopupConfirmResponse->children()->TopupConfirmResult->children();


                if (empty($result)) {
                    return [
                      'error_code' => -903,
                      'error_msg' => 'Unable to find TopupConfirmResult object'
                    ];
                }

            } while ($result->Status == "Pending");


            //echo 'Confirm Status: ' . $result->Status . '. ';

            if ($result->Status != "Success") {
                return [
                  'error_code' => isset($result->ErrorCode) ? $result->ErrorCode->__toString() : '',
                  'error_msg' => isset($result->ErrorMsg) ? $result->ErrorMsg->__toString() : '',
                ];
            }

//            return [
//              'error_code' => '',
//              'error_msg' => '',
//              'pin' => isset($result->PIN) ? $result->PIN : ''
//            ];


        } catch (\Exception $ex) {

            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@jjonbp.com', '[GEN][' . getenv('APP_ENV') . '] rtr() Exception', $message);

//            return [
//              'error_code' => 'E:' .$ex->getCode(),
//              'error_msg' => $ex->getMessage(),
//              'error_trace' => $ex->getTraceAsString()
//            ];
        }

        return [
          'code'  => '0',
        ];
    }

    public static function GetCustomerInfo($mdn) {
        try {

//            self::init();

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
		    $req .=        '<ADDITIONALINFORMATION>Y</ADDITIONALINFORMATION>';
		    $req .=        '<GETEMAIL>Y</GETEMAIL>';
		    $req .=        '<GETRECERTIFICATION>Y</GETRECERTIFICATION>';
		    $req .=        '<ILDINFO>Y</ILDINFO>';
		    $req .=        '<PLANINFOREQUIRED>Y</PLANINFOREQUIRED>';
		    $req .=        '<FEDERALDISCOUNT>Y</FEDERALDISCOUNT>';
		    $req .=        '<GETUSAGES>Y</GETUSAGES>';
		    $req .=        '<BALANCEENQUIRY>Y</BALANCEENQUIRY>';
            $req .=        '<AGENTID>' . self::$AGENTID . '</AGENTID>';
            $req .=        '<AGENTPASSWORD>' . self::$AGENTPASSWORD . '</AGENTPASSWORD>';
            $req .=        '<SOURCE>API</SOURCE>';
            $req .=    '</GETCUSTOMERINFO>';
            $req .=    '</VCAREOSS>';
            $req .= '</VCAREOSSAPI>';

            do {
                sleep(1);
                $ret = self::sendRequest($req, 'GetCustomerInfo/');
                $result = $ret->children('soap', true)->Body->children()->TopupConfirmResponse->children()->TopupConfirmResult->children();


                if (empty($result)) {
                    return [
                      'error_code' => -903,
                      'error_msg' => 'Unable to find TopupConfirmResult object'
                    ];
                }

            } while ($result->Status == "Pending");


            //echo 'Confirm Status: ' . $result->Status . '. ';

            if ($result->Status != "Success") {
                return [
                  'error_code' => isset($result->ErrorCode) ? $result->ErrorCode->__toString() : '',
                  'error_msg' => isset($result->ErrorMsg) ? $result->ErrorMsg->__toString() : '',
                ];
            }

//            return [
//              'error_code' => '',
//              'error_msg' => '',
//              'pin' => isset($result->PIN) ? $result->PIN : ''
//            ];


        } catch (\Exception $ex) {

            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@jjonbp.com', '[GEN][' . getenv('APP_ENV') . '] rtr() Exception', $message);

//            return [
//              'error_code' => 'E:' .$ex->getCode(),
//              'error_msg' => $ex->getMessage(),
//              'error_trace' => $ex->getTraceAsString()
//            ];
        }

        return [
          'code'  => '0',
        ];
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

        Helper::log('### rtr ###', [
          'req' => $req,
          'res' => $output,
          'info' => $info
        ]);

        $resp_xml = simplexml_load_string($output, null, LIBXML_NOCDATA);
        return $resp_xml;
    }
}