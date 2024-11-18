<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 9/25/17
 * Time: 5:11 PM
 */

namespace App\Lib;

use Carbon\Carbon;

class boom
{
    private static $api_url = 'https://api-prod.boom.us/BOOMWebAPI/BOOMWebService.svc/api';
    private static $uid = '';
    private static $pwd = '';

    public static function deviceInquery($esn)
    {
        try {
            $time = new \DateTime();
            $pon = 'PMI'.date_format($time, "Ymdhis") . "red";

            $args =
                '<?xml version="1.0" encoding="utf-8"?>'
                . '<DataSet>'
                . '<xs:schema id="BOOMWebAPI" xmlns="" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:msdata="urn:schemas-microsoft-com:xml-msdata">'
                . '<xs:element name="BOOMWebAPI" msdata:IsDataSet="true" msdata:UseCurrentLocale="true" msdata:EnforceConstraints="False">'
                . '<xs:complexType>'
                . '<xs:choice minOccurs="0" maxOccurs="unbounded">'
                . '<xs:element name="DeviceInquiry2">'
                . '<xs:complexType>'
                . '<xs:sequence>'
                . '<xs:element name="RequestType" type="xs:string" minOccurs="0" />'
                . '<xs:element name="UName" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PWD" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PON" type="xs:string" minOccurs="0" />'
                . '<xs:element name="NETWORK" type="xs:string" minOccurs="0" />'
                . '<xs:element name="ServiceType" type="xs:string" minOccurs="0" />'
                . '<xs:element name="MEID" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PlanCode" type="xs:string" minOccurs="0" />'
                . '<xs:element name="ReqDateTime" type="xs:dateTime" minOccurs="0" />'
                . '</xs:sequence>'
                . '</xs:complexType>'
                . '</xs:element>'
                . '</xs:choice>'
                . '</xs:complexType>'
                . '</xs:element>'
                . '</xs:schema>'
                . '<diffgr:diffgram xmlns:msdata="urn:schemas-microsoft-com:xml-msdata" xmlns:diffgr="urn:schemas-microsoft-com:xml-diffgram-v1">'
                . '<BOOMWebAPI>'
                . '<DeviceInquiry2 diffgr:id="DeviceInquiry2" msdata:rowOrder="0" diffgr:hasChanges="inserted">'
                . '<RequestType>DeviceInquiry2</RequestType>'
                . '<UName>' . self::$uid . '</UName>'
                . '<PWD>' . self::$pwd . '</PWD>'
                . '<PON>' . $pon . '</PON>'
                . '<NETWORK>RED</NETWORK>'
                . '<ServiceType>4G</ServiceType>'
                . '<MEID>' . $esn . '</MEID>'
                . '<PlanCode> </PlanCode>'
                . '<ReqDateTime>' . $time->format(DATE_RFC3339) . '</ReqDateTime>'
                . '</DeviceInquiry2>'
                . '</BOOMWebAPI>'
                . '</diffgr:diffgram>'
                . '</DataSet>';

            if (getenv('APP_ENV') == 'production') {

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, self::$api_url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/xml',
                    'Cache-Control: no-cache, must-revalidate'
                ));
                curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_TIMEOUT, 120);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $args);

                $output = curl_exec($ch);
                $info = curl_errno($ch) > 0 ? array("curl_error_" . curl_errno($ch) => curl_error($ch)) : curl_getinfo($ch);
                curl_close($ch);

                Helper::log('### deviceInquery() ###', [
                    'req' => $args,
                    'res' => $output,
                    'info' => $info
                ]);

                $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);

                $error_code = isset($ret->ResultCode) ? $ret->ResultCode->__toString() : '-0';
                $error_msg = isset($ret->ResultDesc) ? $ret->ResultDesc->__toString() : '';
                $deviceValidity = isset($ret->deviceValidity) ? $ret->deviceValidity->__toString() : '';
                $service_type = isset($ret->ServiceType) ? $ret->ServiceType->__toString() : '';
                $r_code = isset($ret->DeviceRestrictions->RRestriction->Code) ? $ret->DeviceRestrictions->RRestriction->Code->__toString() : '';
                $f_code = isset($ret->AvailableFeatures->RFeature->Code) ? $ret->AvailableFeatures->RFeature->Code->__toString() : '';
            } else {
                // Try in Demo
                $error_code = '22_0';
                $error_msg = 'Success [Demo]';
                $deviceValidity ='VALID';
                $service_type = '4G';
                $f_code = 'HD';
                $r_code = '';
            }

            if (empty($error_code)) {
                return [
                    'error_code' => -4,
                    'error_msg' => 'Unable to get response code from vendor'
                ];
            }
            if ($error_code != '22_0') {
                return [
                    'error_code' => $error_code,
                    'error_msg' => $error_msg
                ];
            }
            if ($deviceValidity != 'VALID') {
                return [
                    'error_code' => $error_code,
                    'error_msg' => 'Device is not VALID'
                ];
            }
            if ($f_code != 'HD') {
                return [
                    'error_code' => $error_code,
                    'error_msg' => 'Only HD available'
                ];
            }
            if ($service_type != '4G') {
                return [
                    'error_code' => $error_code,
                    'error_msg' => 'Only available for 4G ESN'
                ];
            }
            if ($r_code == 'DISCOUNT_1' || $r_code == 'DISCOUNT_2') {
                return [
                    'error_code' => $error_code,
                    'error_msg' => 'There is DISCOUNT code'
                ];
            }
            return [
                'error_code' => '',
                'error_msg' => $error_msg
            ];

        } catch (\Exception $ex) {
            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();
            Helper::send_mail('it@jjonbp.com', '[BOOM][' . getenv('APP_ENV') . '] Boom deviceInquery() Exception', $message);
            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function deviceInquery_blue($meid, $plan_code)
    {
        try {
            $time = new \DateTime();
            $pon = 'PMI'.date_format($time, "Ymdhis") . "blue";

            $args =
                '<?xml version="1.0" encoding="utf-8"?>'
                . '<DataSet>'
                . '<xs:schema id="BOOMWebAPI" xmlns="" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:msdata="urn:schemas-microsoft-com:xml-msdata">'
                . '<xs:element name="BOOMWebAPI" msdata:IsDataSet="true" msdata:UseCurrentLocale="true" msdata:EnforceConstraints="False">'
                . '<xs:complexType>'
                . '<xs:choice minOccurs="0" maxOccurs="unbounded">'
                . '<xs:element name="DeviceInquiry2">'
                . '<xs:complexType>'
                . '<xs:sequence>'
                . '<xs:element name="RequestType" type="xs:string" minOccurs="0" />'
                . '<xs:element name="UName" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PWD" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PON" type="xs:string" minOccurs="0" />'
                . '<xs:element name="NETWORK" type="xs:string" minOccurs="0" />'
                . '<xs:element name="ServiceType" type="xs:string" minOccurs="0" />'
                . '<xs:element name="MEID" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PlanCode" type="xs:string" minOccurs="0" />'
                . '<xs:element name="ReqDateTime" type="xs:dateTime" minOccurs="0" />'
                . '</xs:sequence>'
                . '</xs:complexType>'
                . '</xs:element>'
                . '</xs:choice>'
                . '</xs:complexType>'
                . '</xs:element>'
                . '</xs:schema>'
                . '<diffgr:diffgram xmlns:msdata="urn:schemas-microsoft-com:xml-msdata" xmlns:diffgr="urn:schemas-microsoft-com:xml-diffgram-v1">'
                . '<BOOMWebAPI>'
                . '<DeviceInquiry2 diffgr:id="DeviceInquiry2" msdata:rowOrder="0" diffgr:hasChanges="inserted">'
                . '<RequestType>DeviceInquiry2</RequestType>'
                . '<UName>' . self::$uid . '</UName>'
                . '<PWD>' . self::$pwd . '</PWD>'
                . '<PON>' . $pon . '</PON>'
                . '<NETWORK>BLUE</NETWORK>'
                . '<ServiceType>4G</ServiceType>'
                . '<MEID>' . $meid . '</MEID>'
                . '<PlanCode>' . $plan_code . '</PlanCode>'
                . '<ReqDateTime>' . $time->format(DATE_RFC3339) . '</ReqDateTime>'
                . '</DeviceInquiry2>'
                . '</BOOMWebAPI>'
                . '</diffgr:diffgram>'
                . '</DataSet>';

            if (getenv('APP_ENV') == 'production') {

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, self::$api_url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/xml',
                    'Cache-Control: no-cache, must-revalidate'
                ));
                curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_TIMEOUT, 120);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $args);

                $output = curl_exec($ch);
                $info = curl_errno($ch) > 0 ? array("curl_error_" . curl_errno($ch) => curl_error($ch)) : curl_getinfo($ch);
                curl_close($ch);

                Helper::log('### deviceInquery() ###', [
                    'req' => $args,
                    'res' => $output,
                    'info' => $info
                ]);

                $ret = simplexml_load_string($output);

                $error_code = isset($ret->ResultCode) ? $ret->ResultCode->__toString() : '-0';
                $error_msg = isset($ret->ResultDesc) ? $ret->ResultDesc->__toString() : '';
                $deviceValidity = isset($ret->deviceValidity) ? $ret->deviceValidity->__toString() : '';
//                $AvailableFeatures = $ret->AvailableFeatures->count();

                $DeviceRestrictions = isset($ret->DeviceRestrictions->RRestriction) ? $ret->DeviceRestrictions->RRestriction->Name->__toString() : '';

            } else {
                // Try in Demo
                return [
                    'error_code' => '',
                    'error_msg' => 'Success [Demo]'
                ];
            }

            if (empty($error_code)) {
                return [
                    'error_code' => -4,
                    'error_msg' => 'Unable to get response code from vendor'
                ];
            }
            if ($error_code != '22_0') {
                return [
                    'error_code' => $error_code,
                    'error_msg' => $error_msg
                ];
            }
            if ($deviceValidity != 'VALID') {
                return [
                    'error_code' => $error_code,
                    'error_msg' => 'Device is not VALID'
                ];
            }

            if ($DeviceRestrictions != ''){
                return [
                    'error_code' => '-2',
                    'error_msg' => $DeviceRestrictions
                ];
            }

//            $phone_type = '';
//            if($AvailableFeatures > 0){
//                $RFeature = $ret->AvailableFeatures->RFeature->count();
//                if($RFeature > 0){
//                    for($i=0; $i<$RFeature; $i++){
//                        $phone_type = $ret->AvailableFeatures->RFeature[$i]->Name->__toString();
//                    }
//                }
//            }

            return [
                'error_code' => '',
                'error_msg' => $error_msg
            ];

        } catch (\Exception $ex) {
            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();
            Helper::send_mail('it@jjonbp.com', '[BOOM][' . getenv('APP_ENV') . '] Boom deviceInquery_blue() Exception', $message);
            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function getServiceStatus($mdn, $network)
    {
        try {
            $time = new \DateTime();
            $pon = 'PMI'.date_format($time, "Ymdhis");

            $args =
                '<?xml version="1.0" encoding="utf-8"?>'
                . '<DataSet>'
                . '<xs:schema id="BOOMWebAPI" xmlns="" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:msdata="urn:schemas-microsoft-com:xml-msdata">'
                . '<xs:element name="BOOMWebAPI" msdata:IsDataSet="true" msdata:UseCurrentLocale="true" msdata:EnforceConstraints="False">'
                . '<xs:complexType>'
                . '<xs:choice minOccurs="0" maxOccurs="unbounded">'
                . '<xs:element name="GetServiceStatus">'
                . '<xs:complexType>'
                . '<xs:sequence>'
                . '<xs:element name="RequestType" type="xs:string" minOccurs="0" />'
                . '<xs:element name="UName" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PWD" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PON" type="xs:string" minOccurs="0" />'
                . '<xs:element name="NETWORK" type="xs:string" minOccurs="0" />'
                . '<xs:element name="MDN" type="xs:string" minOccurs="0" />'
                . '<xs:element name="ReqDateTime" type="xs:dateTime" minOccurs="0" />'
                . '</xs:sequence>'
                . '</xs:complexType>'
                . '</xs:element>'
                . '</xs:choice>'
                . '</xs:complexType>'
                . '</xs:element>'
                . '</xs:schema>'
                . '<diffgr:diffgram xmlns:msdata="urn:schemas-microsoft-com:xml-msdata" xmlns:diffgr="urn:schemas-microsoft-com:xml-diffgram-v1">'
                . '<BOOMWebAPI>'
                . '<GetServiceStatus diffgr:id="GetServiceStatus" msdata:rowOrder="0" diffgr:hasChanges="inserted">'
                . '<RequestType>GetServiceStatus</RequestType>'
                . '<UName>' . self::$uid . '</UName>'
                . '<PWD>' . self::$pwd . '</PWD>'
                . '<PON>' . $pon . '</PON>'
                . '<NETWORK>' . $network . '</NETWORK>'
                . '<MDN>' . $mdn . '</MDN>'
                . '<ReqDateTime>' . $time->format(DATE_RFC3339) . '</ReqDateTime>'
                . '</GetServiceStatus>'
                . '</BOOMWebAPI>'
                . '</diffgr:diffgram>'
                . '</DataSet>';

            if (getenv('APP_ENV') == 'production') {

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, self::$api_url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/xml',
                    'Cache-Control: no-cache, must-revalidate'
                ));
                curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_TIMEOUT, 600);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $args);

                $output = curl_exec($ch);
                $info = curl_errno($ch) > 0 ? array("curl_error_" . curl_errno($ch) => curl_error($ch)) : curl_getinfo($ch);
                curl_close($ch);

                Helper::log('### getServiceStatus() ###', [
                    'req' => $args,
                    'res' => $output,
                    'info' => $info
                ]);

                $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);

                $lineStatus = isset($rer->lineStatus) ? $ret->lineStatus->__toString() : '';
                $error_code = isset($ret->ResultCode) ? $ret->ResultCode->__toString() : '-0';
                $error_msg = isset($ret->ResultDesc) ? $ret->ResultDesc->__toString() : '';

            } else {
                $lineStatus = 'ACTIVATED';
                $error_code = '000';
                $error_msg = 'Is checked [demo]';
            }

            if (empty($error_code)) {
                return [
                    'error_code' => -4,
                    'error_msg' => 'Unable to get response code from vendor'
                ];
            }

            $lineStatus = strtoupper($lineStatus);
            if ($lineStatus != 'ACTIVATED') {
                return [
                    'error_code' => $error_code,
                    'error_msg' => $error_msg
                ];
            }
            return [
                'error_code' => '',
                'error_msg' => $error_msg
            ];
        } catch (\Exception $ex) {
            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();
            Helper::send_mail('it@jjonbp.com', '[BOOM][' . getenv('APP_ENV') . '] [Boom] getServiceStatus() Exception', $message);
            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function checkPortStatus($network, $mdn)
    {
        try {
            $time = new \DateTime();
            $pon = 'PMI'.date_format($time, "Ymdhis");

            $args =
                '<?xml version="1.0" encoding="utf-8"?>'
                . '<DataSet>'
                . '<xs:schema id="BOOMWebAPI" xmlns="" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:msdata="urn:schemas-microsoft-com:xml-msdata">'
                . '<xs:element name="BOOMWebAPI" msdata:IsDataSet="true" msdata:UseCurrentLocale="true" msdata:EnforceConstraints="False">'
                . '<xs:complexType>'
                . '<xs:choice minOccurs="0" maxOccurs="unbounded">'
                . '<xs:element name="CheckPortStatus">'
                . '<xs:complexType>'
                . '<xs:sequence>'
                . '<xs:element name="RequestType" type="xs:string" minOccurs="0" />'
                . '<xs:element name="UName" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PWD" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PON" type="xs:string" minOccurs="0" />'
                . '<xs:element name="NETWORK" type="xs:string" minOccurs="0" />'
                . '<xs:element name="MDN" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PIN" type="xs:string" minOccurs="0" />'
                . '<xs:element name="ReqDateTime" type="xs:dateTime" minOccurs="0" />'
                . '</xs:sequence>'
                . '</xs:complexType>'
                . '</xs:element>'
                . '</xs:choice>'
                . '</xs:complexType>'
                . '</xs:element>'
                . '</xs:schema>'
                . '<diffgr:diffgram xmlns:msdata="urn:schemas-microsoft-com:xml-msdata" xmlns:diffgr="urn:schemas-microsoft-com:xml-diffgram-v1">'
                . '<BOOMWebAPI>'
                . '<CheckPortStatus diffgr:id="CheckPortStatus" msdata:rowOrder="0" diffgr:hasChanges="inserted">'
                . '<RequestType>CheckPortStatus</RequestType>'
                . '<UName>' . self::$uid . '</UName>'
                . '<PWD>' . self::$pwd . '</PWD>'
                . '<PON>' . $pon . '</PON>'
                . '<NETWORK>' . $network . '</NETWORK>'
                . '<MDN>' . $mdn . '</MDN>'
                . '<PIN>9999</PIN>'
                . '<ReqDateTime>' . $time->format(DATE_RFC3339) . '</ReqDateTime>'
                . '</CheckPortStatus>'
                . '</BOOMWebAPI>'
                . '</diffgr:diffgram>'
                . '</DataSet>';

            if (getenv('APP_ENV') == 'production') {
                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, self::$api_url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/xml',
                    'Cache-Control: no-cache, must-revalidate'
                ));
                curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_TIMEOUT, 600);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $args);

                $output = curl_exec($ch);
                $info = curl_errno($ch) > 0 ? array("curl_error_" . curl_errno($ch) => curl_error($ch)) : curl_getinfo($ch);
                curl_close($ch);

                Helper::log('### checkPortStatus() ###', [
                    'req' => $args,
                    'res' => $output,
                    'info' => $info
                ]);

                $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);

                $error_code = isset($ret->ResultCode) ? $ret->ResultCode->__toString() : '-0';
                $error_msg = isset($ret->ResultDesc) ? $ret->ResultDesc->__toString() : '';
                $port_status = isset($ret->PortStatus) ? $ret->PortStatus->__toString() : '';
                $comments    = isset($ret->Comments0) ? $ret->Comments0->__toString() : '';


            } else {
                $error_code =  '11_0';
                $error_msg = 'Success [Demo]';
                $port_status = 'COMPLETED';
                $comments = 'comments';
            }

            if (empty($error_code)) {
                return [
                    'error_code' => -4,
                    'error_msg' => 'Unable to get response code from vendor'
                ];
            }

            if ($error_code != '11_0') {
                return [
                    'error_code' => $error_code,
                    'error_msg' => $error_msg,
                    'port_status' => $port_status,
                    'comments' => $comments
                ];
            }
            return [
                'error_code' => '',
                'error_msg' => $error_msg,
                'port_status' => $port_status,
                'comments' => $comments
            ];
        } catch (\Exception $ex) {
            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();
            Helper::send_mail('it@jjonbp.com', '[BOOM][' . getenv('APP_ENV') . '] [Boom] checkPortStatus() Exception', $message);
            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function changeSim($network, $phone, $sim)
    {
        try {
            $time = new \DateTime();
            $pon = 'PMI'.date_format($time, "Ymdhis");

            $args =
                '<?xml version="1.0" encoding="utf-8"?>'
                . '<DataSet>'
                . '<xs:schema id="BOOMWebAPI" xmlns="" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:msdata="urn:schemas-microsoft-com:xml-msdata">'
                . '<xs:element name="BOOMWebAPI" msdata:IsDataSet="true" msdata:UseCurrentLocale="true" msdata:EnforceConstraints="False">'
                . '<xs:complexType>'
                . '<xs:choice minOccurs="0" maxOccurs="unbounded">'
                . '<xs:element name="ChangeSIMorMEID">'
                . '<xs:complexType>'
                . '<xs:sequence>'
                . '<xs:element name="RequestType" type="xs:string" minOccurs="0" />'
                . '<xs:element name="UName" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PWD" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PON" type="xs:string" minOccurs="0" />'
                . '<xs:element name="NETWORK" type="xs:string" minOccurs="0" />'
                . '<xs:element name="MDN" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PIN" type="xs:string" minOccurs="0" />'
                . '<xs:element name="ICCID" type="xs:string" minOccurs="0" />'
                . '<xs:element name="MEID" type="xs:string" minOccurs="0" />'
                . '<xs:element name="ModelName" type="xs:string" minOccurs="0" />'
                . '<xs:element name="ModelNumber" type="xs:string" minOccurs="0" />'
                . '<xs:element name="ReqDateTime" type="xs:dateTime" minOccurs="0" />'
                . '</xs:sequence>'
                . '</xs:complexType>'
                . '</xs:element>'
                . '</xs:choice>'
                . '</xs:complexType>'
                . '</xs:element>'
                . '</xs:schema>'
                . '<diffgr:diffgram xmlns:msdata="urn:schemas-microsoft-com:xml-msdata" xmlns:diffgr="urn:schemas-microsoft-com:xml-diffgram-v1">'
                . '<BOOMWebAPI>'
                . '<ChangeSIMorMEID diffgr:id="ChangeSIMorMEID" msdata:rowOrder="0" diffgr:hasChanges="inserted">'
                . '<RequestType>ChangeSIMorMEID</RequestType>'
                . '<UName>' . self::$uid . '</UName>'
                . '<PWD>' . self::$pwd . '</PWD>'
                . '<PON>' . $pon . '</PON>'
                . '<NETWORK>' . $network . '</NETWORK>'
                . '<MDN>' . $phone . '</MDN>'
                . '<PIN>9999</PIN>'
                . '<ICCID>' . $sim . '</ICCID>'
                . '<MEID> </MEID>'
                . '<ModelName> </ModelName>'
                . '<ModelNumber> </ModelNumber>'
                . '<ReqDateTime>' . $time->format(DATE_RFC3339) . '</ReqDateTime>'
                . '</ChangeSIMorMEID>'
                . '</BOOMWebAPI>'
                . '</diffgr:diffgram>'
                . '</DataSet>';

            if (getenv('APP_ENV') == 'production') {
                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, self::$api_url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/xml',
                    'Cache-Control: no-cache, must-revalidate'
                ));
                curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_TIMEOUT, 600);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $args);

                $output = curl_exec($ch);
                $info = curl_errno($ch) > 0 ? array("curl_error_" . curl_errno($ch) => curl_error($ch)) : curl_getinfo($ch);
                curl_close($ch);

                Helper::log('### ChangeSIMorMEID() ###', [
                    'req' => $args,
                    'res' => $output,
                    'info' => $info
                ]);

                $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);

                $error_code = isset($ret->ResultCode) ? $ret->ResultCode->__toString() : '-0';
                $error_msg = isset($ret->ResultDesc) ? $ret->ResultDesc->__toString() : '';

            } else {
                $error_code =  '9_0';
                $error_msg = 'Success [Demo]';
            }

            if (empty($error_code)) {
                return [
                    'error_code' => -4,
                    'error_msg' => 'Unable to get response code from vendor'
                ];
            }

            if ($error_code != '9_0') {
                return [
                    'error_code' => $error_code,
                    'error_msg' => $error_msg
                ];
            }
            return [
                'error_code' => '',
                'error_msg' => $error_msg
            ];
        } catch (\Exception $ex) {
            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();
            Helper::send_mail('it@jjonbp.com', '[BOOM][' . getenv('APP_ENV') . '] [Boom] checkPortStatus() Exception', $message);
            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }



    public static function finalizeActivation($esn, $sim, $plan_code, $cust_nbr, $first_name, $last_name, $address1, $city, $state, $zip, $email, $phone, $account_zip, $acct, $pw, $cur_car)
    {
        try {
            $time = new \DateTime();
            $pon = 'PMI'.date_format($time, "Ymdhis");

            $args =
                '<?xml version="1.0" encoding="utf-8"?>'
                . '<DataSet>'
                . '<xs:schema id="BOOMWebAPI" xmlns="" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:msdata="urn:schemas-microsoft-com:xml-msdata">'
                . '<xs:element name="BOOMWebAPI" msdata:IsDataSet="true" msdata:UseCurrentLocale="true" msdata:EnforceConstraints="False">'
                . '<xs:complexType>'
                . '<xs:choice minOccurs="0" maxOccurs="unbounded">'
                . '<xs:element name="FinalizeActivation">'
                . '<xs:complexType>'
                . '<xs:sequence>'
                . '<xs:element name="RequestType" type="xs:string" minOccurs="0" />'
                . '<xs:element name="UName" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PWD" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PON" type="xs:string" minOccurs="0" />'
                . '<xs:element name="ReqDateTime" type="xs:dateTime" minOccurs="0" />'
                . '<xs:element name="NETWORK" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PortIn" type="xs:string" minOccurs="0" />'
                . '<xs:element name="ModelName" type="xs:string" minOccurs="0" />'
                . '<xs:element name="ModelNumber" type="xs:string" minOccurs="0" />'
                . '<xs:element name="ServiceType" type="xs:string" minOccurs="0" />'
                . '<xs:element name="MEID" type="xs:string" minOccurs="0" />'
                . '<xs:element name="ICCID" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PlanCode" type="xs:string" minOccurs="0" />'
                . '<xs:element name="FriendlyName" type="xs:string" minOccurs="0" />'
                . '<xs:element name="ContactNumber" type="xs:string" minOccurs="0" />'
                . '<xs:element name="NewCustFirstName" type="xs:string" minOccurs="0" />'
                . '<xs:element name="NewCustMidInitial" type="xs:string" minOccurs="0" />'
                . '<xs:element name="NewCustLastName" type="xs:string" minOccurs="0" />'
                . '<xs:element name="NewCustAddress1" type="xs:string" minOccurs="0" />'
                . '<xs:element name="NewCustCity" type="xs:string" minOccurs="0" />'
                . '<xs:element name="NewCustState" type="xs:string" minOccurs="0" />'
                . '<xs:element name="ZIPCODE" type="xs:string" minOccurs="0" />'
                . '<xs:element name="NewCustEmail" type="xs:string" minOccurs="0" />'
                . '<xs:element name="Comments" type="xs:string" minOccurs="0" />'
                . '<xs:element name="DelayedActivation" type="xs:string" minOccurs="0" />'
                . '<xs:element name="TUM" type="xs:string" minOccurs="0" />'
                . '<xs:element name="TUT" type="xs:string" minOccurs="0" />'
                . '<xs:element name="TUD" type="xs:string" minOccurs="0" />'
                . '<xs:element name="BillingNumber" type="xs:string" minOccurs="0" />'
                . '<xs:element name="CustNbr" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PortFromMDN" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PortFromFirstName" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PortFromMidInitial" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PortFromLastName" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PortFromAddress1" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PortFromAddress2" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PortFromCity" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PortFromState" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PortFromZIPCODE" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PortFromEmail" type="xs:string" minOccurs="0" />'
                . '<xs:element name="AuthorizedSigner" type="xs:string" minOccurs="0" />'
                . '<xs:element name="OspAcctNumber" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PortFromPwdPIN" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PortFromNetwork" type="xs:string" minOccurs="0" />'
                . '</xs:sequence>'
                . '</xs:complexType>'
                . '</xs:element>'
                . '</xs:choice>'
                . '</xs:complexType>'
                . '</xs:element>'
                . '</xs:schema>'
                . '<diffgr:diffgram xmlns:msdata="urn:schemas-microsoft-com:xml-msdata" xmlns:diffgr="urn:schemas-microsoft-com:xml-diffgram-v1">'
                . '<BOOMWebAPI>'
                . '<FinalizeActivation diffgr:id="FinalizeActivation1" msdata:rowOrder="0" diffgr:hasChanges="inserted">'
                . '<RequestType>FinalizeActivation</RequestType>'
                . '<UName>' . self::$uid . '</UName>'
                . '<PWD>' . self::$pwd . '</PWD>'
                . '<PON>' . $pon . '</PON>'
                . '<ReqDateTime>' . $time->format(DATE_RFC3339) . '</ReqDateTime>'
                . '<NETWORK>BLUE</NETWORK>'
                . '<PortIn>TRUE</PortIn>'
                . '<ModelName>EMPTY</ModelName>'
                . '<ModelNumber>EMPTY</ModelNumber>'
                . '<ServiceType>4G</ServiceType>'
                . '<MEID>' . $esn . '</MEID>'
                . '<ICCID>' . $sim . '</ICCID>'
                . '<PlanCode>' . $plan_code . '</PlanCode>'
                . '<FriendlyName>EMPTY</FriendlyName>'
                . '<ContactNumber>EMPTY</ContactNumber>'
                . '<NewCustFirstName>' . $first_name . '</NewCustFirstName>'
                . '<NewCustMidInitial> </NewCustMidInitial>'
                . '<NewCustLastName>' . $last_name . '</NewCustLastName>'
                . '<NewCustAddress1>' . $address1 . '</NewCustAddress1>'
                . '<NewCustCity>' . $city . '</NewCustCity>'
                . '<NewCustState>' . $state . '</NewCustState>'
                . '<ZIPCODE>' . $zip . '</ZIPCODE>'
                . '<NewCustEmail>' . $email . '</NewCustEmail>'
                . '<Comments>EMPTY</Comments>'
                . '<DelayedActivation>FALSE</DelayedActivation>'
                . '<TUM>EMPTY</TUM>'
                . '<TUT>EMPTY</TUT>'
                . '<TUD>EMPTY</TUD>'
                . '<BillingNumber>EMPTY</BillingNumber>'
                . '<CustNbr>' . $cust_nbr . '</CustNbr>'
                . '<PortFromMDN>' . $phone . '</PortFromMDN>'
                . '<PortFromFirstName>' . $first_name . '</PortFromFirstName>'
                . '<PortFromMidInitial> </PortFromMidInitial>'
                . '<PortFromLastName>' . $last_name . '</PortFromLastName>'
                . '<PortFromAddress1>' . $address1 . '</PortFromAddress1>'
                . '<PortFromAddress2>EMPTY</PortFromAddress2>'
                . '<PortFromCity>' . $city . '</PortFromCity>'
                . '<PortFromState>' . $state . '</PortFromState>'
                . '<PortFromZIPCODE>' . $account_zip . '</PortFromZIPCODE>'
                . '<PortFromEmail>' . $email . '</PortFromEmail>'
                . '<AuthorizedSigner> </AuthorizedSigner>'
                . '<OspAcctNumber>' . $acct . '</OspAcctNumber>'
                . '<PortFromPwdPIN>' . $pw . '</PortFromPwdPIN>'
                . '<PortFromNetwork>' . $cur_car . '</PortFromNetwork>'
                . '</FinalizeActivation>'
                . '</BOOMWebAPI>'
                . '</diffgr:diffgram>'
                . '</DataSet>';

            if (getenv('APP_ENV') == 'production') {

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, self::$api_url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/xml',
                    'Cache-Control: no-cache, must-revalidate'
                ));
                curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_TIMEOUT, 600);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $args);

                $output = curl_exec($ch);
                $info = curl_errno($ch) > 0 ? array("curl_error_" . curl_errno($ch) => curl_error($ch)) : curl_getinfo($ch);
                curl_close($ch);

                Helper::log('### FinalizeActivation() ###', [
                    'req' => $args,
                    'res' => $output,
                    'info' => $info
                ]);

                $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);

                $error_code = isset($ret->ResultCode) ? $ret->ResultCode->__toString() : '-0';
                $error_msg = isset($ret->ResultDesc) ? $ret->ResultDesc->__toString() : '';
            } else {
                $error_code = '5_3';
                $error_msg = 'Success [Demo]';
            }

            if (empty($error_code)) {
                return [
                    'error_code' => -4,
                    'error_msg' => 'Unable to get response code from vendor'
                ];
            }
            // 5_3 : Success , 5_4 : Error , 5_5 : Pending
            return [
                'error_code' => $error_code,
                'error_msg' => $error_msg
            ];
        } catch (\Exception $ex) {
            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();
            Helper::send_mail('it@jjonbp.com', '[BOOM][' . getenv('APP_ENV') . '] [Boom] finalizeActivation() Exception', $message);
            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function updatePendingPort($network, $mdn, $f_name, $l_name, $addr1, $addr2, $city, $state, $zip_code, $email, $acct, $pw, $carrier)
    {
        try {
            $time = new \DateTime();
            $pon = 'PMI'.date_format($time, "Ymdhis");

            $args =
                '<?xml version="1.0" encoding="utf-8"?>'
                . '<DataSet>'
                . '<xs:schema id="BOOMWebAPI" xmlns="" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:msdata="urn:schemas-microsoft-com:xml-msdata">'
                . '<xs:element name="BOOMWebAPI" msdata:IsDataSet="true" msdata:UseCurrentLocale="true" msdata:EnforceConstraints="False">'
                . '<xs:complexType>'
                . '<xs:choice minOccurs="0" maxOccurs="unbounded">'
                . '<xs:element name="UpdatePendingPort">'
                . '<xs:complexType>'
                . '<xs:sequence>'
                . '<xs:element name="RequestType" type="xs:string" minOccurs="0" />'
                . '<xs:element name="UName" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PWD" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PON" type="xs:string" minOccurs="0" />'
                . '<xs:element name="ReqDateTime" type="xs:dateTime" minOccurs="0" />'
                . '<xs:element name="NETWORK" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PortReferenceNum" type="xs:string" minOccurs="0" />'
                . '<xs:element name="MDN" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PIN" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PortFromFirstName" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PortFromMidInitial" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PortFromLastName" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PortFromAddress1" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PortFromAddress2" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PortFromCity" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PortFromState" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PortFromZIPCODE" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PortFromEmail" type="xs:string" minOccurs="0" />'
                . '<xs:element name="AuthorizedSigner" type="xs:string" minOccurs="0" />'
                . '<xs:element name="OspAcctNumber" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PortFromPwdPIN" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PortFromNetwork" type="xs:string" minOccurs="0" />'
                . '<xs:element name="Comments" type="xs:string" minOccurs="0" />'
                . '</xs:sequence>'
                . '</xs:complexType>'
                . '</xs:element>'
                . '</xs:choice>'
                . '</xs:complexType>'
                . '</xs:element>'
                . '</xs:schema>'
                . '<diffgr:diffgram xmlns:msdata="urn:schemas-microsoft-com:xml-msdata" xmlns:diffgr="urn:schemas-microsoft-com:xml-diffgram-v1">'
                . '<BOOMWebAPI>'
                . '<UpdatePendingPort diffgr:id="UpdatePendingPort1" msdata:rowOrder="0" diffgr:hasChanges="inserted">'
                . '<RequestType>UpdatePendingPort</RequestType>'
                . '<UName>' . self::$uid . '</UName>'
                . '<PWD>' . self::$pwd . '</PWD>'
                . '<PON>' . $pon . '</PON>'
                . '<ReqDateTime>' . $time->format(DATE_RFC3339) . '</ReqDateTime>'
                . '<NETWORK>' . $network . '</NETWORK>'
                . '<PortReferenceNum>EMPTY</PortReferenceNum>'
                . '<MDN>' . $mdn . '</MDN>'
                . '<PIN>9999</PIN>'
                . '<PortFromFirstName>' . $f_name . '</PortFromFirstName>'
                . '<PortFromMidInitial> </PortFromMidInitial>'
                . '<PortFromLastName>' . $l_name . '</PortFromLastName>'
                . '<PortFromAddress1>' . $addr1 . '</PortFromAddress1>'
                . '<PortFromAddress2> </PortFromAddress2>'
                . '<PortFromCity>' . $city . '</PortFromCity>'
                . '<PortFromState>' . $state . '</PortFromState>'
                . '<PortFromZIPCODE>' . $zip_code . '</PortFromZIPCODE>'
                . '<PortFromEmail>' . $email . '</PortFromEmail>'
                . '<AuthorizedSigner> </AuthorizedSigner>'
                . '<OspAcctNumber>' . $acct . '</OspAcctNumber>'
                . '<PortFromPwdPIN>' . $pw . '</PortFromPwdPIN>'
                . '<PortFromNetwork>' . $carrier . '</PortFromNetwork>'
                . '<Comments> </Comments>'
                . '</UpdatePendingPort>'
                . '</BOOMWebAPI>'
                . '</diffgr:diffgram>'
                . '</DataSet>';

            if (getenv('APP_ENV') == 'production') {

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, self::$api_url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/xml',
                    'Cache-Control: no-cache, must-revalidate'
                ));
                curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_TIMEOUT, 600);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $args);

                $output = curl_exec($ch);
                $info = curl_errno($ch) > 0 ? array("curl_error_" . curl_errno($ch) => curl_error($ch)) : curl_getinfo($ch);
                curl_close($ch);

                Helper::log('### updatePendingPort() ###', [
                    'req' => $args,
                    'res' => $output,
                    'info' => $info
                ]);

                $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);

                $error_code = isset($ret->ResultCode) ? $ret->ResultCode->__toString() : '-0';
                $error_msg = isset($ret->ResultDesc) ? $ret->ResultDesc->__toString() : '';
                $request_number = isset($ret->RequestNumber) ? $ret->RequestNumber->__toString() : '';
            } else {
                // Try in Demo
                $error_code     = '14_0';
                $error_msg      = 'Success [Demo]';
                $request_number = '1234567';
            }

            if (empty($error_code)) {
                return [
                    'error_code' => -4,
                    'error_msg' => 'Unable to get response code from vendor'
                ];
            }

            if ($error_code != '14_0') {
                return [
                    'error_code' => $error_code,
                    'error_msg' => $error_msg,
                    'request_number' => $request_number
                ];
            }
            return [
                'error_code' => '',
                'error_msg' => $error_msg,
                'request_number' => $request_number
            ];
        } catch (\Exception $ex) {
            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();
            Helper::send_mail('it@jjonbp.com', '[BOOM][' . getenv('APP_ENV') . '] [Boom] updatePendingPort() Exception', $message);
            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function getCustomerInfo($mdn, $network)
    {
        try {
            $time = new \DateTime();
            $pon = 'PMI'.date_format($time, "Ymdhis");

            $args =
                '<?xml version="1.0" encoding="utf-8"?>'
                . '<DataSet>'
                . '<xs:schema id="BOOMWebAPI" xmlns="" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:msdata="urn:schemas-microsoft-com:xml-msdata">'
                . '<xs:element name="BOOMWebAPI" msdata:IsDataSet="true" msdata:UseCurrentLocale="true" msdata:EnforceConstraints="False">'
                . '<xs:complexType>'
                . '<xs:choice minOccurs="0" maxOccurs="unbounded">'
                . '<xs:element name="GetCustomerInfo">'
                . '<xs:complexType>'
                . '<xs:sequence>'
                . '<xs:element name="RequestType" type="xs:string" minOccurs="0" />'
                . '<xs:element name="UName" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PWD" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PON" type="xs:string" minOccurs="0" />'
                . '<xs:element name="NETWORK" type="xs:string" minOccurs="0" />'
                . '<xs:element name="MDN" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PIN" type="xs:string" minOccurs="0" />'
                . '<xs:element name="ReqDateTime" type="xs:dateTime" minOccurs="0" />'
                . '</xs:sequence>'
                . '</xs:complexType>'
                . '</xs:element>'
                . '</xs:choice>'
                . '</xs:complexType>'
                . '</xs:element>'
                . '</xs:schema>'
                . '<diffgr:diffgram xmlns:msdata="urn:schemas-microsoft-com:xml-msdata" xmlns:diffgr="urn:schemas-microsoft-com:xml-diffgram-v1">'
                . '<BOOMWebAPI>'
                . '<GetCustomerInfo diffgr:id="GetCustomerInfo" msdata:rowOrder="0" diffgr:hasChanges="inserted">'
                . '<RequestType>GetCustomerInfo</RequestType>'
                . '<UName>' . self::$uid . '</UName>'
                . '<PWD>' . self::$pwd . '</PWD>'
                . '<PON>' . $pon . '</PON>'
                . '<NETWORK>' . $network . '</NETWORK>'
                . '<MDN>' . $mdn . '</MDN>'
                . '<PIN>9999</PIN>'
                . '<ReqDateTime>' . $time->format(DATE_RFC3339) . '</ReqDateTime>'
                . '</GetCustomerInfo>'
                . '</BOOMWebAPI>'
                . '</diffgr:diffgram>'
                . '</DataSet>';

            if (getenv('APP_ENV') == 'production') {

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, self::$api_url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/xml',
                    'Cache-Control: no-cache, must-revalidate'
                ));
                curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_TIMEOUT, 600);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $args);

                $output = curl_exec($ch);
                $info = curl_errno($ch) > 0 ? array("curl_error_" . curl_errno($ch) => curl_error($ch)) : curl_getinfo($ch);
                curl_close($ch);

                Helper::log('### Boom GetCustomerInfo() ###', [
                    'req' => $args,
                    'res' => $output,
                    'info' => $info
                ]);

                $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);

                $error_code = isset($ret->ResultCode) ? $ret->ResultCode->__toString() : '-0';
                $error_msg = isset($ret->ResultDesc) ? $ret->ResultDesc->__toString() : '';
                $reload_date = isset($ret->ReloadDate) ? $ret->ReloadDate->__toString() : '';
                $plan_code = isset($ret->PlanCode) ? $ret->PlanCode->__toString() : '';

            }else{

                $error_code =  '3_0';
                $error_msg = 'Success [Demo]';
                $reload_date = '2020-08-20';
                $plan_code = 'B4GUTT1GBD30';

//                $error_code =  '3_1';
//                $error_msg = 'No Data [Demo]';
//                $reload_date = '';
//                $plan_code = '';
            }

            if (empty($error_code)) {
                return [
                    'error_code' => -4,
                    'error_msg' => 'Unable to get response code from vendor'
                ];
            }

            if ($error_code != '3_0') {
                return [
                    'error_code' => $error_code,
                    'error_msg' => $error_msg,
                    'reload_date' => $reload_date,
                    'plan_code' => $plan_code
                ];
            }
            return [
                'error_code' => '',
                'error_msg' => $error_msg,
                'reload_date' => $reload_date,
                'plan_code' => $plan_code
            ];
        } catch (\Exception $ex) {
            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();
            Helper::send_mail('it@jjonbp.com', '[BOOM][' . getenv('APP_ENV') . '] [Boom] getCustomerInfo() Exception', $message);
            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function validateMDN($mdn, $network, $zip)
    {
        try {
            $time = new \DateTime();
            $pon = 'PMI'.date_format($time, "Ymdhis");

            $args =
                '<?xml version="1.0" encoding="utf-8"?>'
                . '<DataSet>'
                . '<xs:schema id="BOOMWebAPI" xmlns="" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:msdata="urn:schemas-microsoft-com:xml-msdata">'
                . '<xs:element name="BOOMWebAPI" msdata:IsDataSet="true" msdata:UseCurrentLocale="true" msdata:EnforceConstraints="False">'
                . '<xs:complexType>'
                . '<xs:choice minOccurs="0" maxOccurs="unbounded">'
                . '<xs:element name="ValidateMDN">'
                . '<xs:complexType>'
                . '<xs:sequence>'
                . '<xs:element name="RequestType" type="xs:string" minOccurs="0" />'
                . '<xs:element name="UName" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PWD" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PON" type="xs:string" minOccurs="0" />'
                . '<xs:element name="NETWORK" type="xs:string" minOccurs="0" />'
                . '<xs:element name="MDN" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PortFromZIPCODE" type="xs:string" minOccurs="0" />'
                . '<xs:element name="ReqDateTime" type="xs:dateTime" minOccurs="0" />'
                . '</xs:sequence>'
                . '</xs:complexType>'
                . '</xs:element>'
                . '</xs:choice>'
                . '</xs:complexType>'
                . '</xs:element>'
                . '</xs:schema>'
                . '<diffgr:diffgram xmlns:msdata="urn:schemas-microsoft-com:xml-msdata" xmlns:diffgr="urn:schemas-microsoft-com:xml-diffgram-v1">'
                . '<BOOMWebAPI>'
                . '<ValidateMDN diffgr:id="ValidateMDN" msdata:rowOrder="0" diffgr:hasChanges="inserted">'
                . '<RequestType>ValidateMDN</RequestType>'
                . '<UName>' . self::$uid . '</UName>'
                . '<PWD>' . self::$pwd . '</PWD>'
                . '<PON>' . $pon . '</PON>'
                . '<NETWORK>' . $network . '</NETWORK>'
                . '<MDN>' . $mdn . '</MDN>'
                . '<PortFromZIPCODE>' . $zip . '</PortFromZIPCODE>'
                . '<ReqDateTime>' . $time->format(DATE_RFC3339) . '</ReqDateTime>'
                . '</ValidateMDN>'
                . '</BOOMWebAPI>'
                . '</diffgr:diffgram>'
                . '</DataSet>';

            if (getenv('APP_ENV') == 'production') {

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, self::$api_url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/xml',
                    'Cache-Control: no-cache, must-revalidate'
                ));
                curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_TIMEOUT, 600);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $args);

                $output = curl_exec($ch);
                $info = curl_errno($ch) > 0 ? array("curl_error_" . curl_errno($ch) => curl_error($ch)) : curl_getinfo($ch);
                curl_close($ch);

                Helper::log('### Boom ValidateMDN() ###', [
                    'req' => $args,
                    'res' => $output,
                    'info' => $info
                ]);

                $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);

                $error_code = isset($ret->ResultCode) ? $ret->ResultCode->__toString() : '-0';
                $error_msg = isset($ret->ResultDesc) ? $ret->ResultDesc->__toString() : '';
                $port_status = isset($ret->PortStatus) ? $ret->PortStatus->__toString() : '';
                $port_desc = isset($ret->PortStatusDescription) ? $ret->PortStatusDescription->__toString() : '';

            }else{

                $error_code =  '4_2';
                $error_msg = 'Success [Demo]';
                $port_status = 'ELIGIBLE';
                $port_desc = '';
            }

            if (empty($error_code)) {
                return [
                    'error_code' => -4,
                    'error_msg' => 'Unable to get response code from vendor'
                ];
            }

            if ($error_code != '4_2') {
                return [
                    'error_code' => $error_code,
                    'error_msg' => $error_msg,
                    'port_status' => $port_status,
                    'port_desc' => $port_desc
                ];
            }
            return [
                'error_code' => '',
                'error_msg' => $error_msg,
                'port_status' => $port_status,
                'port_desc' => $port_desc
            ];
        } catch (\Exception $ex) {
            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();
            Helper::send_mail('it@jjonbp.com', '[BOOM][' . getenv('APP_ENV') . '] [Boom] validateMDN() Exception', $message);
            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function updatePlan($mdn, $plan, $renew, $network, $pin)
    {
        try {
            $time = new \DateTime();
            $pon = 'PMI'.date_format($time, "Ymdhis");

            if($network == 'PURPLE'){
                $network == 'PINK';
            }

            $args =
                '<?xml version="1.0" encoding="utf-8"?>'
                . '<DataSet>'
                . '<xs:schema id="BOOMWebAPI" xmlns="" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:msdata="urn:schemas-microsoft-com:xml-msdata">'
                . '<xs:element name="BOOMWebAPI" msdata:IsDataSet="true" msdata:UseCurrentLocale="true" msdata:EnforceConstraints="False">'
                . '<xs:complexType>'
                . '<xs:choice minOccurs="0" maxOccurs="unbounded">'
                . '<xs:element name="UpdatePlan">'
                . '<xs:complexType>'
                . '<xs:sequence>'
                . '<xs:element name="RequestType" type="xs:string" minOccurs="0" />'
                . '<xs:element name="UName" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PWD" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PON" type="xs:string" minOccurs="0" />'
                . '<xs:element name="NETWORK" type="xs:string" minOccurs="0" />'
                . '<xs:element name="MDN" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PIN" type="xs:string" minOccurs="0" />'
                . '<xs:element name="PlanCode" type="xs:string" minOccurs="0" />'
                . '<xs:element name="RenewNow" type="xs:string" minOccurs="0" />'
                . '<xs:element name="TUM" type="xs:string" minOccurs="0" />'
                . '<xs:element name="TUT" type="xs:string" minOccurs="0" />'
                . '<xs:element name="TUD" type="xs:string" minOccurs="0" />'
                . '<xs:element name="ReqDateTime" type="xs:dateTime" minOccurs="0" />'
                . '</xs:sequence>'
                . '</xs:complexType>'
                . '</xs:element>'
                . '</xs:choice>'
                . '</xs:complexType>'
                . '</xs:element>'
                . '</xs:schema>'
                . '<diffgr:diffgram xmlns:msdata="urn:schemas-microsoft-com:xml-msdata" xmlns:diffgr="urn:schemas-microsoft-com:xml-diffgram-v1">'
                . '<BOOMWebAPI>'
                . '<UpdatePlan diffgr:id="UpdatePlan" msdata:rowOrder="0" diffgr:hasChanges="inserted">'
                . '<RequestType>UpdatePlan</RequestType>'
                . '<UName>' . self::$uid . '</UName>'
                . '<PWD>' . self::$pwd . '</PWD>'
                . '<PON>' . $pon . '</PON>'
                . '<NETWORK>' . $network . '</NETWORK>'
                . '<MDN>' . $mdn . '</MDN>'
                . '<PIN>' . $pin . '</PIN>'
                . '<PlanCode>' . $plan . '</PlanCode>'
                . '<RenewNow>' . $renew . '</RenewNow>'
                . '<TUM>EMPTY</TUM>'
                . '<TUT>EMPTY</TUT>'
                . '<TUD>EMPTY</TUD>'
                . '<ReqDateTime>' . $time->format(DATE_RFC3339) . '</ReqDateTime>'
                . '</UpdatePlan>'
                . '</BOOMWebAPI>'
                . '</diffgr:diffgram>'
                . '</DataSet>';

            if (getenv('APP_ENV') == 'production') {

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, self::$api_url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/xml',
                    'Cache-Control: no-cache, must-revalidate'
                ));
                curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_TIMEOUT, 600);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $args);

                $output = curl_exec($ch);
                $info = curl_errno($ch) > 0 ? array("curl_error_" . curl_errno($ch) => curl_error($ch)) : curl_getinfo($ch);
                curl_close($ch);

                Helper::log('### updatePlan() ###', [
                    'req' => $args,
                    'res' => $output,
                    'info' => $info
                ]);

                $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);

                $error_code = isset($ret->ResultCode) ? $ret->ResultCode->__toString() : '-0';
                $error_msg = isset($ret->ResultDesc) ? $ret->ResultDesc->__toString() : '';
                $request_number = isset($ret->RequestNumber) ? $ret->RequestNumber->__toString() : '';
                $cust_nbr = isset($ret->CustNbr) ? $ret->CustNbr->__toString() : '';
            } else {
                // Try in Demo
                $error_code = "6_0";
                $error_msg = "Success! [Demo]";
                $request_number = "123213123";
                $cust_nbr = '112211';
            }

            if (empty($error_code)) {
                return [
                    'error_code' => -4,
                    'error_msg' => 'Unable to get response code from vendor'
                ];
            }

            if ($error_code != '6_0') {
                return [
                    'error_code' => $error_code,
                    'error_msg' => $error_msg,
                    'request_number' => $request_number,
                    'cust_nbr' => $cust_nbr
                ];
            }
            return [
                'error_code' => '',
                'error_msg' => $error_msg,
                'request_number' => $request_number,
                'cust_nbr' => $cust_nbr
            ];
        } catch (\Exception $ex) {
            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();
            Helper::send_mail('it@jjonbp.com', '[BOOM][' . getenv('APP_ENV') . '] [Boom] updatePlan() Exception', $message);
            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }



    public static function activationBlue($params)
    {
        try {

            $time = new \DateTime();
            $pon = 'PMI'.date_format($time, "Ymdhis") . $params->trans_id . "blue";

            // ESN is optional in BLUE
            if($params->esn == ''){
                $params->esn = '999999999999999';
            }

            $args =
                '<?xml version="1.0" encoding="utf-8"?>'
                . '<DataSet>'
                . '<xs:schema id="BOOMWebAPI" xmlns="" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:msdata="urn:schemas-microsoft-com:xml-msdata">'
                . '<xs:element name="BOOMWebAPI" msdata:IsDataSet="true" msdata:UseCurrentLocale="true" msdata:EnforceConstraints="False">'
                . '<xs:complexType>'
                . '<xs:choice minOccurs="0" maxOccurs="unbounded">'
                . '<xs:element name="ActivateCustomer">'
                . '<xs:complexType>'
                . '<xs:sequence>'
                . '<xs:element name="RequestType" type="xs:string" />'
                . '<xs:element name="UName" type="xs:string" />'
                . '<xs:element name="PWD" type="xs:string" />'
                . '<xs:element name="PON" type="xs:string" />'
                . '<xs:element name="NETWORK" type="xs:string" />'
                . '<xs:element name="PortIn" type="xs:string" />'
                . '<xs:element name="ModelName" type="xs:string" />'
                . '<xs:element name="ModelNumber" type="xs:string" />'
                . '<xs:element name="ServiceType" type="xs:string" />'
                . '<xs:element name="MEID" type="xs:string" />'
                . '<xs:element name="ICCID" type="xs:string" />'
                . '<xs:element name="PlanCode" type="xs:string" />'
                . '<xs:element name="DelayedActivation" type="xs:string" />'
                . '<xs:element name="FriendlyName" type="xs:string" />'
                . '<xs:element name="ContactNumber" type="xs:string" />'
                . '<xs:element name="NewCustFirstName" type="xs:string" />'
                . '<xs:element name="NewCustMidInitial" type="xs:string" />'
                . '<xs:element name="NewCustLastName" type="xs:string" />'
                . '<xs:element name="NewCustAddress1" type="xs:string" />'
                . '<xs:element name="NewCustCity" type="xs:string" />'
                . '<xs:element name="NewCustState" type="xs:string" />'
                . '<xs:element name="ZIPCODE" type="xs:string" />'
                . '<xs:element name="NewCustEmail" type="xs:string" />'
                . '<xs:element name="Comments" type="xs:string" />'
                . '<xs:element name="ReqDateTime" type="xs:dateTime" />'
                . '</xs:sequence>'
                . '</xs:complexType>'
                . '</xs:element>'
                . '</xs:choice>'
                . '</xs:complexType>'
                . '</xs:element>'
                . '</xs:schema>'
                . '<diffgr:diffgram xmlns:msdata="urn:schemas-microsoft-com:xml-msdata" xmlns:diffgr="urn:schemas-microsoft-com:xml-diffgram-v1">'
                . '<BOOMWebAPI>'
                . '<ActivateCustomer diffgr:id="ActivateCustomer" msdata:rowOrder="0" diffgr:hasChanges="inserted">'
                . '<RequestType>ActivateCustomer</RequestType>'
                . '<UName>' . self::$uid . '</UName>'
                . '<PWD>' . self::$pwd . '</PWD>'
                . '<PON>' . $pon . '</PON>'
                . '<NETWORK>BLUE</NETWORK>'
                . '<PortIn>FALSE</PortIn>'
                . '<ModelName> </ModelName>'
                . '<ModelNumber> </ModelNumber>'
                . '<ServiceType>4G</ServiceType>'
                . '<MEID>' . $params->esn . '</MEID>'
                . '<ICCID>' . $params->sim . '</ICCID>'
                . '<PlanCode>' . $params->act_pid . '</PlanCode>'
                . '<DelayedActivation>FALSE</DelayedActivation>'
                . '<FriendlyName> </FriendlyName>'
                . '<ContactNumber> </ContactNumber>'
                . '<NewCustFirstName>' . $params->first_name . '</NewCustFirstName>'
                . '<NewCustMidInitial> </NewCustMidInitial>'
                . '<NewCustLastName>' . $params->last_name . '</NewCustLastName>'
                . '<NewCustAddress1>' . $params->address . '</NewCustAddress1>'
                . '<NewCustCity>' . $params->city . '</NewCustCity>'
                . '<NewCustState>' . $params->state . '</NewCustState>'
                . '<ZIPCODE>' . $params->zip . '</ZIPCODE>'
                . '<NewCustEmail>' . $params->email . '</NewCustEmail>'
                . '<Comments> </Comments>'
                . '<ReqDateTime>' . $time->format(DATE_RFC3339) . '</ReqDateTime>'
                . '</ActivateCustomer>'
                . '</BOOMWebAPI>'
                . '</diffgr:diffgram>'
                . '</DataSet>';

            if (getenv('APP_ENV') == 'production') {

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, self::$api_url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/xml',
                    'Cache-Control: no-cache, must-revalidate'
                ));
                curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_TIMEOUT, 600);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $args);

                $response = curl_exec($ch);
                $info = curl_errno($ch) > 0 ? array("curl_error_" . curl_errno($ch) => curl_error($ch)) : curl_getinfo($ch);
                curl_close($ch);

                Helper::log('### activationBlue() ###', [
                    'req' => $args,
                    'res' => $response,
                    'info' => $info
                ]);

                $ret = simplexml_load_string($response);

                $error_code = isset($ret->ResultCode) ? $ret->ResultCode->__toString() : '-0';
                $error_msg = isset($ret->ResultDesc) ? $ret->ResultDesc->__toString() : '';

                $customer = '1';
                if( (strpos($error_msg, 'Failed to create customer') !== false)
                    || (strpos($error_msg, '5004-MDN') !== false) ){
                    // Failed to create customer
                    $customer = '-1';
                }
                $vendor_tx_id = isset($ret->RequestNumber) ? $ret->RequestNumber->__toString() : '';

                /*
                 * MDN can be null even if get success code. But we will go as 'C' with MDN = ''
                 */
                $mdn = isset($ret->newMdn) ? $ret->newMdn->__toString() : '';

                /*
                 * We only accept when... will do after meeting. can be changed..
                 * deviceValidity : 'VALID'
                 * DeviceRestrictions : not have DISCOUNT_1, DISCOUNT_2
                 * ServiceType : 4G
                 *
                 */
            } else {
                // Try in Demo
                $error_code = '5_0';
                $error_msg = 'Activation BLUE Success [Demo]';
                $vendor_tx_id = '1231231';
                $mdn = '2221114444';
            }

            if (empty($error_code)) {
                return [
                    'error_code' => -4,
                    'error_msg' => 'Unable to get response code from vendor'
                ];
            }

            /*
             *  Assumed that (5_0, 5_2) both are no error
             */
            if ($error_code != '5_0' && $error_code != '5_2' && $customer != '-1') { // 5_1:error | 5_2:pending
                return [
                    'error_code' => $error_code,
                    'error_msg' => $error_msg,
                    'vendor_tx_id' => $vendor_tx_id,
                    'mdn' => ''
                ];
            }

            /*
             * Send PM when it Pending. So he could contact to Boom directly
             */
            if ($error_code == '5_2'){
                Helper::send_mail('tom@perfectmobileinc.com', '[BOOM_Blue] Activation status is Pending ', 'Transaction ID : '.$params->trans_id);
                Helper::send_mail('it@perfectmobileinc.com', '[BOOM_Blue] Activation status is Pending ', 'Transaction ID : '.$params->trans_id);
            }

            return [
                'error_code' => '',
                'error_msg' => $error_msg,
                'vendor_tx_id' => $vendor_tx_id,
                'mdn' => $mdn
            ];

        } catch (\Exception $ex) {

            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();
            Helper::send_mail('it@jjonbp.com', '[BOOM][' . getenv('APP_ENV') . '] [Boom] activationBlue() Exception', $message);
            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function activationRed($params)
    {
        try {

            $time = new \DateTime();
            $pon = 'PMI'.date_format($time, "Ymdhis") . $params->trans_id . "red";

            $args =
                '<?xml version="1.0" encoding="utf-8"?>'
                . '<DataSet>'
                . '<xs:schema id="BOOMWebAPI" xmlns="" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:msdata="urn:schemas-microsoft-com:xml-msdata">'
                . '<xs:element name="BOOMWebAPI" msdata:IsDataSet="true" msdata:UseCurrentLocale="true" msdata:EnforceConstraints="False">'
                . '<xs:complexType>'
                . '<xs:choice minOccurs="0" maxOccurs="unbounded">'
                . '<xs:element name="ActivateCustomer">'
                . '<xs:complexType>'
                . '<xs:sequence>'
                . '<xs:element name="RequestType" type="xs:string" />'
                . '<xs:element name="UName" type="xs:string" />'
                . '<xs:element name="PWD" type="xs:string" />'
                . '<xs:element name="PON" type="xs:string" />'
                . '<xs:element name="NETWORK" type="xs:string" />'
                . '<xs:element name="PortIn" type="xs:string" />'
                . '<xs:element name="ModelName" type="xs:string" />'
                . '<xs:element name="ModelNumber" type="xs:string" />'
                . '<xs:element name="ServiceType" type="xs:string" />'
                . '<xs:element name="MEID" type="xs:string" />'
                . '<xs:element name="ICCID" type="xs:string" />'
                . '<xs:element name="PlanCode" type="xs:string" />'
                . '<xs:element name="DelayedActivation" type="xs:string" />'
                . '<xs:element name="FriendlyName" type="xs:string" />'
                . '<xs:element name="ContactNumber" type="xs:string" />'
                . '<xs:element name="NewCustFirstName" type="xs:string" />'
                . '<xs:element name="NewCustMidInitial" type="xs:string" />'
                . '<xs:element name="NewCustLastName" type="xs:string" />'
                . '<xs:element name="NewCustAddress1" type="xs:string" />'
                . '<xs:element name="NewCustCity" type="xs:string" />'
                . '<xs:element name="NewCustState" type="xs:string" />'
                . '<xs:element name="ZIPCODE" type="xs:string" />'
                . '<xs:element name="NewCustEmail" type="xs:string" />'
                . '<xs:element name="Comments" type="xs:string" />'
                . '<xs:element name="ReqDateTime" type="xs:dateTime" />'
                . '</xs:sequence>'
                . '</xs:complexType>'
                . '</xs:element>'
                . '</xs:choice>'
                . '</xs:complexType>'
                . '</xs:element>'
                . '</xs:schema>'
                . '<diffgr:diffgram xmlns:msdata="urn:schemas-microsoft-com:xml-msdata" xmlns:diffgr="urn:schemas-microsoft-com:xml-diffgram-v1">'
                . '<BOOMWebAPI>'
                . '<ActivateCustomer diffgr:id="ActivateCustomer" msdata:rowOrder="0" diffgr:hasChanges="inserted">'
                . '<RequestType>ActivateCustomer</RequestType>'
                . '<UName>' . self::$uid . '</UName>'
                . '<PWD>' . self::$pwd . '</PWD>'
                . '<PON>' . $pon . '</PON>'
                . '<NETWORK>RED</NETWORK>'
                . '<PortIn>FALSE</PortIn>'
                . '<ModelName> </ModelName>'
                . '<ModelNumber> </ModelNumber>'
                . '<ServiceType>4G</ServiceType>'
                . '<MEID>' . $params->esn . '</MEID>'
                . '<ICCID>' . $params->sim . '</ICCID>'
                . '<PlanCode>' . $params->act_pid . '</PlanCode>'
                . '<DelayedActivation>FALSE</DelayedActivation>'
                . '<FriendlyName> </FriendlyName>'
                . '<ContactNumber> </ContactNumber>'
                . '<NewCustFirstName> </NewCustFirstName>'
                . '<NewCustMidInitial> </NewCustMidInitial>'
                . '<NewCustLastName> </NewCustLastName>'
                . '<NewCustAddress1> </NewCustAddress1>'
                . '<NewCustCity> </NewCustCity>'
                . '<NewCustState> </NewCustState>'
                . '<ZIPCODE>' . $params->zip . '</ZIPCODE>'
                . '<NewCustEmail> </NewCustEmail>'
                . '<Comments> </Comments>'
                . '<ReqDateTime>' . $time->format(DATE_RFC3339) . '</ReqDateTime>'
                . '</ActivateCustomer>'
                . '</BOOMWebAPI>'
                . '</diffgr:diffgram>'
                . '</DataSet>';

            if (getenv('APP_ENV') == 'production') {

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, self::$api_url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/xml',
                    'Cache-Control: no-cache, must-revalidate'
                ));
                curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_TIMEOUT, 300);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $args);

                $response = curl_exec($ch);
                $info = curl_errno($ch) > 0 ? array("curl_error_" . curl_errno($ch) => curl_error($ch)) : curl_getinfo($ch);
                curl_close($ch);


                Helper::log('### activationRed() ###', [
                    'req' => $args,
                    'res' => $response,
                    'info' => $info
                ]);

                $ret = simplexml_load_string($response);

                $error_code = isset($ret->ResultCode) ? $ret->ResultCode->__toString() : '-0';
                $error_msg = isset($ret->ResultDesc) ? $ret->ResultDesc->__toString() : '';
                $customer = '1';
                if( (strpos($error_msg, 'Failed to create customer') !== false)
                    || (strpos($error_msg, '5004-MDN') !== false) ){
                    // Failed to create customer
                    $customer = '-1';
                }
                $vendor_tx_id = isset($ret->RequestNumber) ? $ret->RequestNumber->__toString() : '';
                /*
                 * MDN can be null even if get success code. But we will go as 'C' with MDN = ''
                 */
                $mdn = isset($ret->newMdn) ? $ret->newMdn->__toString() : '';
                /*
                 * We only accept when... will do after meeting. can be changed..
                 * deviceValidity : 'VALID'
                 * DeviceRestrictions : not have DISCOUNT_1, DISCOUNT_2
                 * ServiceType : 4G
                 *
                 */
            } else {
                $error_code     = '5_0';
                $error_msg      = 'Success Red Activation [Demo]';
                $vendor_tx_id   = '123212321';
                $mdn            = '3332221234';
            }

            if (empty($error_code)) {
                return [
                    'error_code' => -4,
                    'error_msg' => 'Unable to get response code from vendor'
                ];
            }

            /*
             *  Assumed that (5_0, 5_2) both are no error
             */
            if ($error_code != '5_0' && $error_code != '5_2' && $customer != '-1') { // 5_1:error | 5_2:pending
                return [
                    'error_code' => $error_code,
                    'error_msg' => $error_msg,
                    'vendor_tx_id' => $vendor_tx_id,
                    'mdn' => ''
                ];
            }

            /*
             * Send PM when it Pending. So he could contact to Boom directly
             */
            if ($error_code == '5_2'){
                Helper::send_mail('tom@perfectmobileinc.com', '[BOOM_Red] Activation status is Pending ', 'Transaction ID : '.$params->trans_id);
                Helper::send_mail('it@perfectmobileinc.com', '[BOOM_Red] Activation status is Pending ', 'Transaction ID : '.$params->trans_id);
            }

            return [
                'error_code' => '',
                'error_msg' => $error_msg,
                'vendor_tx_id' => $vendor_tx_id,
                'mdn' => $mdn
            ];

        } catch (\Exception $ex) {

            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();
            Helper::send_mail('it@jjonbp.com', '[BOOM][' . getenv('APP_ENV') . '] [Boom] activationRed() Exception', $message);
            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function activationPurple($params)
    {

        try {

            $time = new \DateTime();
            $pon = 'PMI'.date_format($time, "Ymdhis") . $params->trans_id . "purple";

            // ESN is optional in Purple
            if($params->esn == ''){
                $params->esn = '999999999999999';
            }

            $args =
                '<?xml version="1.0" encoding="utf-8"?>'
                . '<DataSet>'
                . '<xs:schema id="BOOMWebAPI" xmlns="" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:msdata="urn:schemas-microsoft-com:xml-msdata">'
                . '<xs:element name="BOOMWebAPI" msdata:IsDataSet="true" msdata:UseCurrentLocale="true" msdata:EnforceConstraints="False">'
                . '<xs:complexType>'
                . '<xs:choice minOccurs="0" maxOccurs="unbounded">'
                . '<xs:element name="ActivateCustomer">'
                . '<xs:complexType>'
                . '<xs:sequence>'
                . '<xs:element name="RequestType" type="xs:string" />'
                . '<xs:element name="UName" type="xs:string" />'
                . '<xs:element name="PWD" type="xs:string" />'
                . '<xs:element name="PON" type="xs:string" />'
                . '<xs:element name="NETWORK" type="xs:string" />'
                . '<xs:element name="PortIn" type="xs:string" />'
                . '<xs:element name="ModelName" type="xs:string" />'
                . '<xs:element name="ModelNumber" type="xs:string" />'
                . '<xs:element name="ServiceType" type="xs:string" />'
                . '<xs:element name="MEID" type="xs:string" />'
                . '<xs:element name="ICCID" type="xs:string" />'
                . '<xs:element name="PlanCode" type="xs:string" />'
                . '<xs:element name="DelayedActivation" type="xs:string" />'
                . '<xs:element name="FriendlyName" type="xs:string" />'
                . '<xs:element name="ContactNumber" type="xs:string" />'
                . '<xs:element name="NewCustFirstName" type="xs:string" />'
                . '<xs:element name="NewCustMidInitial" type="xs:string" />'
                . '<xs:element name="NewCustLastName" type="xs:string" />'
                . '<xs:element name="NewCustAddress1" type="xs:string" />'
                . '<xs:element name="NewCustCity" type="xs:string" />'
                . '<xs:element name="NewCustState" type="xs:string" />'
                . '<xs:element name="ZIPCODE" type="xs:string" />'
                . '<xs:element name="NewCustEmail" type="xs:string" />'
                . '<xs:element name="Comments" type="xs:string" />'
                . '<xs:element name="ReqDateTime" type="xs:dateTime" />'
                . '</xs:sequence>'
                . '</xs:complexType>'
                . '</xs:element>'
                . '</xs:choice>'
                . '</xs:complexType>'
                . '</xs:element>'
                . '</xs:schema>'
                . '<diffgr:diffgram xmlns:msdata="urn:schemas-microsoft-com:xml-msdata" xmlns:diffgr="urn:schemas-microsoft-com:xml-diffgram-v1">'
                . '<BOOMWebAPI>'
                . '<ActivateCustomer diffgr:id="ActivateCustomer" msdata:rowOrder="0" diffgr:hasChanges="inserted">'
                . '<RequestType>ActivateCustomer</RequestType>'
                . '<UName>' . self::$uid . '</UName>'
                . '<PWD>' . self::$pwd . '</PWD>'
                . '<PON>' . $pon . '</PON>'
                . '<NETWORK>PINK</NETWORK>'
                . '<PortIn>FALSE</PortIn>'
                . '<ModelName> </ModelName>'
                . '<ModelNumber> </ModelNumber>'
                . '<ServiceType>4G</ServiceType>'
                . '<MEID>' . $params->esn . '</MEID>'
                . '<ICCID>' . $params->sim . '</ICCID>'
                . '<PlanCode>' . $params->act_pid . '</PlanCode>'
                . '<DelayedActivation>FALSE</DelayedActivation>'
                . '<FriendlyName> </FriendlyName>'
                . '<ContactNumber> </ContactNumber>'
                . '<NewCustFirstName>' . $params->first_name . '</NewCustFirstName>'
                . '<NewCustMidInitial> </NewCustMidInitial>'
                . '<NewCustLastName>' . $params->last_name . '</NewCustLastName>'
                . '<NewCustAddress1>' . $params->address . '</NewCustAddress1>'
                . '<NewCustCity>' . $params->city . '</NewCustCity>'
                . '<NewCustState>' . $params->state . '</NewCustState>'
                . '<ZIPCODE>' . $params->zip . '</ZIPCODE>'
                . '<NewCustEmail>' . $params->email . '</NewCustEmail>'
                . '<Comments> </Comments>'
                . '<ReqDateTime>' . $time->format(DATE_RFC3339) . '</ReqDateTime>'
                . '</ActivateCustomer>'
                . '</BOOMWebAPI>'
                . '</diffgr:diffgram>'
                . '</DataSet>';

            if (getenv('APP_ENV') == 'production') {

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, self::$api_url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/xml',
                    'Cache-Control: no-cache, must-revalidate'
                ));
                curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_TIMEOUT, 600);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $args);

                $response = curl_exec($ch);
                $info = curl_errno($ch) > 0 ? array("curl_error_" . curl_errno($ch) => curl_error($ch)) : curl_getinfo($ch);
                curl_close($ch);


                Helper::log('### activationPurple() ###', [
                    'req' => $args,
                    'res' => $response,
                    'info' => $info
                ]);

                $ret = simplexml_load_string($response);

                $error_code = isset($ret->ResultCode) ? $ret->ResultCode->__toString() : '-0';
                $error_msg = isset($ret->ResultDesc) ? $ret->ResultDesc->__toString() : '';
                $customer = '1';
                if( (strpos($error_msg, 'Failed to create customer') !== false)
                    || (strpos($error_msg, '5004-MDN') !== false) ){
                    // Failed to create customer
                    $customer = '-1';
                }
                $vendor_tx_id = isset($ret->RequestNumber) ? $ret->RequestNumber->__toString() : '';
                /*
                 * MDN can be null even if get success code. But we will go as 'C' with MDN = ''
                 */
                $mdn = isset($ret->newMdn) ? $ret->newMdn->__toString() : '';

                /*
                 * deviceValidity : 'VALID'
                 * DeviceRestrictions : not have DISCOUNT_1, DISCOUNT_2
                 * ServiceType : 4G
                 */
            } else {
                // Try in Demo
                $error_code = '5_0';
                $error_msg = 'Purple Activation Success [Demo]';
                $vendor_tx_id = '42132';
                $mdn = '3332221234';
            }

            if (empty($error_code)) {
                return [
                    'error_code' => -4,
                    'error_msg' => 'Unable to get response code from vendor'
                ];
            }

            /*
             *  Assumed that (5_0, 5_2) both are no error
             */
            if ($error_code != '5_0' && $error_code != '5_2' && $customer != '-1') { // 5_1:error | 5_2:pending
                return [
                    'error_code' => $error_code,
                    'error_msg' => $error_msg,
                    'vendor_tx_id' => $vendor_tx_id,
                    'mdn' => ''
                ];
            }

            /*
             * Send PM when it Pending. So he could contact to Boom directly
             */
            if ($error_code == '5_2'){
                Helper::send_mail('tom@perfectmobileinc.com', '[BOOM_Purple] Activation status is Pending ', 'Transaction ID : '.$params->trans_id);
                Helper::send_mail('it@perfectmobileinc.com', '[BOOM_Purple] Activation status is Pending ', 'Transaction ID : '.$params->trans_id);
            }

            return [
                'error_code' => '',
                'error_msg' => $error_msg,
                'vendor_tx_id' => $vendor_tx_id,
                'mdn' => $mdn
            ];

        } catch (\Exception $ex) {

            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();
            Helper::send_mail('it@jjonbp.com', '[BOOM][' . getenv('APP_ENV') . '] [Boom] activationPurple() Exception', $message);
            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function activationBluePortIn($params)
    {
        try {

            $time = new \DateTime();
            $pon = 'PMI'.date_format($time, "Ymdhis") . $params->trans_id . "blueportin";

            // ESN is optional in Blue
            if($params->esn == ''){
                $params->esn = '9999999999';
            }

            $args =
                '<?xml version="1.0"?>'
                . '<DataSet>'
                . '<xs:schema id="BOOMWebAPI" xmlns="" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:msdata="urn:schemas-microsoft-com:xml-msdata">'
                . '<xs:element name="BOOMWebAPI" msdata:IsDataSet="true" msdata:UseCurrentLocale="true" msdata:EnforceConstraints="False">'
                . '<xs:complexType>'
                . '<xs:choice minOccurs="0" maxOccurs="unbounded">'
                . '<xs:element name="ActivateCustomer">'
                . '<xs:complexType>'
                . '<xs:sequence>'
                . '<xs:element name="RequestType" type="xs:string" />'
                . '<xs:element name="UName" type="xs:string" />'
                . '<xs:element name="PWD" type="xs:string" />'
                . '<xs:element name="PON" type="xs:string" />'
                . '<xs:element name="NETWORK" type="xs:string" />'
                . '<xs:element name="PortIn" type="xs:string" />'
                . '<xs:element name="ModelName" type="xs:string" />'
                . '<xs:element name="ModelNumber" type="xs:string" />'
                . '<xs:element name="ServiceType" type="xs:string" />'
                . '<xs:element name="MEID" type="xs:string" />'
                . '<xs:element name="ICCID" type="xs:string" />'
                . '<xs:element name="PlanCode" type="xs:string" />'
                . '<xs:element name="DelayedActivation" type="xs:string" />'
                . '<xs:element name="FriendlyName" type="xs:string" />'
                . '<xs:element name="ContactNumber" type="xs:string" />'
                . '<xs:element name="NewCustFirstName" type="xs:string" />'
                . '<xs:element name="NewCustMidInitial" type="xs:string" />'
                . '<xs:element name="NewCustLastName" type="xs:string" />'
                . '<xs:element name="NewCustAddress1" type="xs:string" />'
                . '<xs:element name="NewCustCity" type="xs:string" />'
                . '<xs:element name="NewCustState" type="xs:string" />'
                . '<xs:element name="ZIPCODE" type="xs:string" />'
                . '<xs:element name="NewCustEmail" type="xs:string" />'
                . '<xs:element name="PortFromMDN" type="xs:string" />'
                . '<xs:element name="PortFromFirstName" type="xs:string" />'
                . '<xs:element name="PortFromMidInitial" type="xs:string" />'
                . '<xs:element name="PortFromLastName" type="xs:string" />'
                . '<xs:element name="PortFromAddress1" type="xs:string" />'
                . '<xs:element name="PortFromAddress2" type="xs:string" />'
                . '<xs:element name="PortFromCity" type="xs:string" />'
                . '<xs:element name="PortFromState" type="xs:string" />'
                . '<xs:element name="PortFromZIPCODE" type="xs:string" />'
                . '<xs:element name="PortFromEmail" type="xs:string" />'
                . '<xs:element name="AuthorizedSigner" type="xs:string" />'
                . '<xs:element name="OspAcctNumber" type="xs:string" />'
                . '<xs:element name="PortFromPwdPIN" type="xs:string" />'
                . '<xs:element name="PortFromNetwork" type="xs:string" />'
                . '<xs:element name="Comments" type="xs:string" />'
                . '<xs:element name="ReqDateTime" type="xs:dateTime" />'
                . '</xs:sequence>'
                . '</xs:complexType>'
                . '</xs:element>'
                . '</xs:choice>'
                . '</xs:complexType>'
                . '</xs:element>'
                . '</xs:schema>'
                . '<diffgr:diffgram xmlns:msdata="urn:schemas-microsoft-com:xml-msdata" xmlns:diffgr="urn:schemas-microsoft-com:xml-diffgram-v1">'
                . '<BOOMWebAPI>'
                . '<ActivateCustomer diffgr:id="ActivateCustomer1" msdata:rowOrder="0" diffgr:hasChanges="inserted">'
                . '<RequestType>ActivateCustomer</RequestType>'
                . '<UName>' . self::$uid . '</UName>'
                . '<PWD>' . self::$pwd . '</PWD>'
                . '<PON>' . $pon . '</PON>'
                . '<NETWORK>BLUE</NETWORK>'
                . '<PortIn>TRUE</PortIn>'
                . '<ModelName> </ModelName>'
                . '<ModelNumber> </ModelNumber>'
                . '<ServiceType>4G</ServiceType>'
                . '<MEID>' . $params->esn . '</MEID>'
                . '<ICCID>' . $params->sim . '</ICCID>'
                . '<PlanCode>' . $params->act_pid . '</PlanCode>'
                . '<DelayedActivation>FALSE</DelayedActivation>'
                . '<FriendlyName> </FriendlyName>'
                . '<ContactNumber> </ContactNumber>'
                . '<NewCustFirstName>' . $params->first_name . '</NewCustFirstName>'
                . '<NewCustMidInitial> </NewCustMidInitial>'
                . '<NewCustLastName>' . $params->last_name . '</NewCustLastName>'
                . '<NewCustAddress1>' . $params->address . '</NewCustAddress1>'
                . '<NewCustAddress2> </NewCustAddress2>'
                . '<NewCustCity>' . $params->city . '</NewCustCity>'
                . '<NewCustState>' . $params->state . '</NewCustState>'
                . '<ZIPCODE>' . $params->zip . '</ZIPCODE>'
                . '<NewCustEmail>' . $params->email . '</NewCustEmail>'
                . '<PortFromMDN>' . $params->portFromMDN . '</PortFromMDN>'
                . '<PortFromFirstName>' . $params->first_name . '</PortFromFirstName>'
                . '<PortFromMidInitial> </PortFromMidInitial>'
                . '<PortFromLastName>' . $params->last_name . '</PortFromLastName>'
                . '<PortFromAddress1>' . $params->address . '</PortFromAddress1>'
                . '<PortFromAddress2> </PortFromAddress2>'
                . '<PortFromCity>' . $params->city . '</PortFromCity>'
                . '<PortFromState>' . $params->state . '</PortFromState>'
                . '<PortFromZIPCODE>' . $params->zip . '</PortFromZIPCODE>'
                . '<PortFromEmail>' . $params->email . '</PortFromEmail>'
                . '<AuthorizedSigner> </AuthorizedSigner>'
                . '<OspAcctNumber>' . $params->ospAcctNumber . '</OspAcctNumber>'
                . '<PortFromPwdPIN>' . $params->portFromPwdPin . '</PortFromPwdPIN>'
                . '<PortFromNetwork>' . $params->portFromNetwork . '</PortFromNetwork>'
                . '<Comments> </Comments>'
                . '<ReqDateTime>' . $time->format(DATE_RFC3339) . '</ReqDateTime>'
                . '</ActivateCustomer>'
                . '</BOOMWebAPI>'
                . '</diffgr:diffgram>'
                . '</DataSet>';

            if (getenv('APP_ENV') == 'production') {

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, self::$api_url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/xml',
                    'Cache-Control: no-cache, must-revalidate'
                ));
                curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_TIMEOUT, 600);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $args);

                $response = curl_exec($ch);
                $info = curl_errno($ch) > 0 ? array("curl_error_" . curl_errno($ch) => curl_error($ch)) : curl_getinfo($ch);
                curl_close($ch);

                Helper::log('### activationBluePortIn() ###', [
                    'req' => $args,
                    'res' => $response,
                    'info' => $info
                ]);

                $ret = simplexml_load_string($response);

                $error_code = isset($ret->ResultCode) ? $ret->ResultCode->__toString() : '-0';
                $error_msg = isset($ret->ResultDesc) ? $ret->ResultDesc->__toString() : '';
                $port_reference_num = isset($ret->PortReferenceNum) ? $ret->PortReferenceNum->__toString() : '';
                $custNbr = isset($ret->CustNbr) ? $ret->CustNbr->__toString() : '';

            } else {
                $error_code = '5_3';
                $error_msg = 'Port Blue Success [Demo]';
                $port_reference_num = '4231231';
                $custNbr = '160109';
            }
            if (empty($error_code)) {
                return [
                    'error_code' => -4,
                    'error_msg' => 'Unable to get response code from vendor'
                ];
            }

            /*
             *  5_3 : Success
             *  5_5 : Pending
             *  Assumed that those two codes No error
             */
            if ($error_code != '5_3' && $error_code != '5_5') { // 5_4:error | 5_5:pending
                return [
                    'error_code' => $error_code,
                    'error_msg' => $error_msg,
                    'port_reference_num' => $port_reference_num,
                    'custNbr'   => $custNbr
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => $error_msg,
                'port_reference_num' => $port_reference_num,
                'custNbr'   => $custNbr
            ];

        } catch (\Exception $ex) {

            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();
            Helper::send_mail('it@jjonbp.com', '[BOOM][' . getenv('APP_ENV') . '] Boomblabla() Exception', $message);
            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function activationRedPortIn($params)
    {
        try {
            $time = new \DateTime();
            $pon = 'PMI'.date_format($time, "Ymdhis") . $params->trans_id . "redportin";

            $args =
                '<?xml version="1.0"?>'
                . '<DataSet>'
                . '<xs:schema id="BOOMWebAPI" xmlns="" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:msdata="urn:schemas-microsoft-com:xml-msdata">'
                . '<xs:element name="BOOMWebAPI" msdata:IsDataSet="true" msdata:UseCurrentLocale="true" msdata:EnforceConstraints="False">'
                . '<xs:complexType>'
                . '<xs:choice minOccurs="0" maxOccurs="unbounded">'
                . '<xs:element name="ActivateCustomer">'
                . '<xs:complexType>'
                . '<xs:sequence>'
                . '<xs:element name="RequestType" type="xs:string" />'
                . '<xs:element name="UName" type="xs:string" />'
                . '<xs:element name="PWD" type="xs:string" />'
                . '<xs:element name="PON" type="xs:string" />'
                . '<xs:element name="NETWORK" type="xs:string" />'
                . '<xs:element name="PortIn" type="xs:string" />'
                . '<xs:element name="ModelName" type="xs:string" />'
                . '<xs:element name="ModelNumber" type="xs:string" />'
                . '<xs:element name="ServiceType" type="xs:string" />'
                . '<xs:element name="MEID" type="xs:string" />'
                . '<xs:element name="ICCID" type="xs:string" />'
                . '<xs:element name="PlanCode" type="xs:string" />'
                . '<xs:element name="DelayedActivation" type="xs:string" />'
                . '<xs:element name="FriendlyName" type="xs:string" />'
                . '<xs:element name="ContactNumber" type="xs:string" />'
                . '<xs:element name="NewCustFirstName" type="xs:string" />'
                . '<xs:element name="NewCustMidInitial" type="xs:string" />'
                . '<xs:element name="NewCustLastName" type="xs:string" />'
                . '<xs:element name="NewCustAddress1" type="xs:string" />'
                . '<xs:element name="NewCustCity" type="xs:string" />'
                . '<xs:element name="NewCustState" type="xs:string" />'
                . '<xs:element name="ZIPCODE" type="xs:string" />'
                . '<xs:element name="NewCustEmail" type="xs:string" />'
                . '<xs:element name="PortFromMDN" type="xs:string" />'
                . '<xs:element name="PortFromFirstName" type="xs:string" />'
                . '<xs:element name="PortFromMidInitial" type="xs:string" />'
                . '<xs:element name="PortFromLastName" type="xs:string" />'
                . '<xs:element name="PortFromAddress1" type="xs:string" />'
                . '<xs:element name="PortFromAddress2" type="xs:string" />'
                . '<xs:element name="PortFromCity" type="xs:string" />'
                . '<xs:element name="PortFromState" type="xs:string" />'
                . '<xs:element name="PortFromZIPCODE" type="xs:string" />'
                . '<xs:element name="PortFromEmail" type="xs:string" />'
                . '<xs:element name="AuthorizedSigner" type="xs:string" />'
                . '<xs:element name="OspAcctNumber" type="xs:string" />'
                . '<xs:element name="PortFromPwdPIN" type="xs:string" />'
                . '<xs:element name="PortFromNetwork" type="xs:string" />'
                . '<xs:element name="Comments" type="xs:string" />'
                . '<xs:element name="ReqDateTime" type="xs:dateTime" />'
                . '</xs:sequence>'
                . '</xs:complexType>'
                . '</xs:element>'
                . '</xs:choice>'
                . '</xs:complexType>'
                . '</xs:element>'
                . '</xs:schema>'
                . '<diffgr:diffgram xmlns:msdata="urn:schemas-microsoft-com:xml-msdata" xmlns:diffgr="urn:schemas-microsoft-com:xml-diffgram-v1">'
                . '<BOOMWebAPI>'
                . '<ActivateCustomer diffgr:id="ActivateCustomer1" msdata:rowOrder="0" diffgr:hasChanges="inserted">'
                . '<RequestType>ActivateCustomer</RequestType>'
                . '<UName>' . self::$uid . '</UName>'
                . '<PWD>' . self::$pwd . '</PWD>'
                . '<PON>' . $pon . '</PON>'
                . '<NETWORK>RED</NETWORK>'
                . '<PortIn>TRUE</PortIn>'
                . '<ModelName> </ModelName>'
                . '<ModelNumber> </ModelNumber>'
                . '<ServiceType>4G</ServiceType>'
                . '<MEID>' . $params->esn . '</MEID>'
                . '<ICCID>' . $params->sim . '</ICCID>'
                . '<PlanCode>' . $params->act_pid . '</PlanCode>'
                . '<DelayedActivation>FALSE</DelayedActivation>'
                . '<FriendlyName> </FriendlyName>'
                . '<ContactNumber> </ContactNumber>'
                . '<NewCustFirstName>' . $params->first_name . '</NewCustFirstName>'
                . '<NewCustMidInitial> </NewCustMidInitial>'
                . '<NewCustLastName>' . $params->last_name . '</NewCustLastName>'
                . '<NewCustAddress1>' . $params->street_number . '</NewCustAddress1>'
                . '<NewCustAddress2>' . $params->street_name . '</NewCustAddress2>'
                . '<NewCustCity>' . $params->city . '</NewCustCity>'
                . '<NewCustState>' . $params->state . '</NewCustState>'
                . '<ZIPCODE>' . $params->zip . '</ZIPCODE>'
                . '<NewCustEmail>' . $params->email . '</NewCustEmail>'
                . '<PortFromMDN>' . $params->portFromMDN . '</PortFromMDN>'
                . '<PortFromFirstName>' . $params->first_name . '</PortFromFirstName>'
                . '<PortFromMidInitial> </PortFromMidInitial>'
                . '<PortFromLastName>' . $params->last_name . '</PortFromLastName>'
                . '<PortFromAddress1>' . $params->street_number . '</PortFromAddress1>'
                . '<PortFromAddress2>' . $params->street_name . '</PortFromAddress2>'
                . '<PortFromCity>' . $params->city . '</PortFromCity>'
                . '<PortFromState>' . $params->state . '</PortFromState>'
                . '<PortFromZIPCODE>' . $params->portin_zip . '</PortFromZIPCODE>'
                . '<PortFromEmail>' . $params->email . '</PortFromEmail>'
                . '<AuthorizedSigner> </AuthorizedSigner>'
                . '<OspAcctNumber>' . $params->ospAcctNumber . '</OspAcctNumber>'
                . '<PortFromPwdPIN>' . $params->portFromPwdPin . '</PortFromPwdPIN>'
                . '<PortFromNetwork>' . $params->portFromNetwork . '</PortFromNetwork>'
                . '<Comments> </Comments>'
                . '<ReqDateTime>' . $time->format(DATE_RFC3339) . '</ReqDateTime>'
                . '</ActivateCustomer>'
                . '</BOOMWebAPI>'
                . '</diffgr:diffgram>'
                . '</DataSet>';

            if (getenv('APP_ENV') == 'production') {

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, self::$api_url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/xml',
                    'Cache-Control: no-cache, must-revalidate'
                ));
                curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_TIMEOUT, 600);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $args);

                $response = curl_exec($ch);
                $info = curl_errno($ch) > 0 ? array("curl_error_" . curl_errno($ch) => curl_error($ch)) : curl_getinfo($ch);
                curl_close($ch);

                Helper::log('### activationRedPortIn() ###', [
                    'req' => $args,
                    'res' => $response,
                    'info' => $info
                ]);

                $ret = simplexml_load_string($response);

                $error_code = isset($ret->ResultCode) ? $ret->ResultCode->__toString() : '-0';
                $error_msg = isset($ret->ResultDesc) ? $ret->ResultDesc->__toString() : '';
                $port_reference_num = isset($ret->PortReferenceNum) ? $ret->PortReferenceNum->__toString() : '';

            } else {
                // Try in Demo
                $error_code = '5_3';
                $error_msg = 'Port Red Success [Demo]';
                $port_reference_num = '4231231';
            }

            if (empty($error_code)) {
                return [
                    'error_code' => -4,
                    'error_msg' => 'Unable to get response code from vendor'
                ];
            }

            /*
             *  5_3 : Success
             *  5_5 : Pending
             *  Assumed that those two codes No error
             */
            if ($error_code != '5_3' && $error_code != '5_5') { // 5_4:error | 5_5:pending
                return [
                    'error_code' => $error_code,
                    'error_msg' => $error_msg,
                    'port_reference_num' => $port_reference_num
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => $error_msg,
                'port_reference_num' => $port_reference_num
            ];
        } catch (\Exception $ex) {

            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();
            Helper::send_mail('it@jjonbp.com', '[BOOM][' . getenv('APP_ENV') . '] Boomblabla() Exception', $message);
            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function activationPurplePortIn($params)
    {

        try {
            $time = new \DateTime();
            $pon = 'PMI'.date_format($time, "Ymdhis") . $params->trans_id . "purpleportin";

            // ESN is optional in PINK
            if($params->esn == ''){
                $params->esn = '9999999999';
            }

            $args =
                '<?xml version="1.0"?>'
                . '<DataSet>'
                . '<xs:schema id="BOOMWebAPI" xmlns="" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:msdata="urn:schemas-microsoft-com:xml-msdata">'
                . '<xs:element name="BOOMWebAPI" msdata:IsDataSet="true" msdata:UseCurrentLocale="true" msdata:EnforceConstraints="False">'
                . '<xs:complexType>'
                . '<xs:choice minOccurs="0" maxOccurs="unbounded">'
                . '<xs:element name="ActivateCustomer">'
                . '<xs:complexType>'
                . '<xs:sequence>'
                . '<xs:element name="RequestType" type="xs:string" />'
                . '<xs:element name="UName" type="xs:string" />'
                . '<xs:element name="PWD" type="xs:string" />'
                . '<xs:element name="PON" type="xs:string" />'
                . '<xs:element name="NETWORK" type="xs:string" />'
                . '<xs:element name="PortIn" type="xs:string" />'
                . '<xs:element name="ModelName" type="xs:string" />'
                . '<xs:element name="ModelNumber" type="xs:string" />'
                . '<xs:element name="ServiceType" type="xs:string" />'
                . '<xs:element name="MEID" type="xs:string" />'
                . '<xs:element name="ICCID" type="xs:string" />'
                . '<xs:element name="PlanCode" type="xs:string" />'
                . '<xs:element name="DelayedActivation" type="xs:string" />'
                . '<xs:element name="FriendlyName" type="xs:string" />'
                . '<xs:element name="ContactNumber" type="xs:string" />'
                . '<xs:element name="NewCustFirstName" type="xs:string" />'
                . '<xs:element name="NewCustMidInitial" type="xs:string" />'
                . '<xs:element name="NewCustLastName" type="xs:string" />'
                . '<xs:element name="NewCustAddress1" type="xs:string" />'
                . '<xs:element name="NewCustCity" type="xs:string" />'
                . '<xs:element name="NewCustState" type="xs:string" />'
                . '<xs:element name="ZIPCODE" type="xs:string" />'
                . '<xs:element name="NewCustEmail" type="xs:string" />'
                . '<xs:element name="PortFromMDN" type="xs:string" />'
                . '<xs:element name="PortFromFirstName" type="xs:string" />'
                . '<xs:element name="PortFromMidInitial" type="xs:string" />'
                . '<xs:element name="PortFromLastName" type="xs:string" />'
                . '<xs:element name="PortFromAddress1" type="xs:string" />'
                . '<xs:element name="PortFromAddress2" type="xs:string" />'
                . '<xs:element name="PortFromCity" type="xs:string" />'
                . '<xs:element name="PortFromState" type="xs:string" />'
                . '<xs:element name="PortFromZIPCODE" type="xs:string" />'
                . '<xs:element name="PortFromEmail" type="xs:string" />'
                . '<xs:element name="AuthorizedSigner" type="xs:string" />'
                . '<xs:element name="OspAcctNumber" type="xs:string" />'
                . '<xs:element name="PortFromPwdPIN" type="xs:string" />'
                . '<xs:element name="PortFromNetwork" type="xs:string" />'
                . '<xs:element name="Comments" type="xs:string" />'
                . '<xs:element name="ReqDateTime" type="xs:dateTime" />'
                . '</xs:sequence>'
                . '</xs:complexType>'
                . '</xs:element>'
                . '</xs:choice>'
                . '</xs:complexType>'
                . '</xs:element>'
                . '</xs:schema>'
                . '<diffgr:diffgram xmlns:msdata="urn:schemas-microsoft-com:xml-msdata" xmlns:diffgr="urn:schemas-microsoft-com:xml-diffgram-v1">'
                . '<BOOMWebAPI>'
                . '<ActivateCustomer diffgr:id="ActivateCustomer1" msdata:rowOrder="0" diffgr:hasChanges="inserted">'
                . '<RequestType>ActivateCustomer</RequestType>'
                . '<UName>' . self::$uid . '</UName>'
                . '<PWD>' . self::$pwd . '</PWD>'
                . '<PON>' . $pon . '</PON>'
                . '<NETWORK>PINK</NETWORK>'
                . '<PortIn>TRUE</PortIn>'
                . '<ModelName> </ModelName>'
                . '<ModelNumber> </ModelNumber>'
                . '<ServiceType>4G</ServiceType>'
                . '<MEID>' . $params->esn . '</MEID>'
                . '<ICCID>' . $params->sim . '</ICCID>'
                . '<PlanCode>' . $params->act_pid . '</PlanCode>'
                . '<DelayedActivation>FALSE</DelayedActivation>'
                . '<FriendlyName> </FriendlyName>'
                . '<ContactNumber> </ContactNumber>'
                . '<NewCustFirstName>' . $params->first_name . '</NewCustFirstName>'
                . '<NewCustMidInitial> </NewCustMidInitial>'
                . '<NewCustLastName>' . $params->last_name . '</NewCustLastName>'
                . '<NewCustAddress1>' . $params->address . '</NewCustAddress1>'
                . '<NewCustAddress2> </NewCustAddress2>'
                . '<NewCustCity>' . $params->city . '</NewCustCity>'
                . '<NewCustState>' . $params->state . '</NewCustState>'
                . '<ZIPCODE>' . $params->zip . '</ZIPCODE>'
                . '<NewCustEmail>' . $params->email . '</NewCustEmail>'
                . '<PortFromMDN>' . $params->portFromMDN . '</PortFromMDN>'
                . '<PortFromFirstName>' . $params->first_name . '</PortFromFirstName>'
                . '<PortFromMidInitial> </PortFromMidInitial>'
                . '<PortFromLastName>' . $params->last_name . '</PortFromLastName>'
                . '<PortFromAddress1>' . $params->address . '</PortFromAddress1>'
                . '<PortFromAddress2> </PortFromAddress2>'
                . '<PortFromCity>' . $params->city . '</PortFromCity>'
                . '<PortFromState>' . $params->state . '</PortFromState>'
                . '<PortFromZIPCODE>' . $params->zip . '</PortFromZIPCODE>'
                . '<PortFromEmail>' . $params->email . '</PortFromEmail>'
                . '<AuthorizedSigner> </AuthorizedSigner>'
                . '<OspAcctNumber>' . $params->ospAcctNumber . '</OspAcctNumber>'
                . '<PortFromPwdPIN>' . $params->portFromPwdPin . '</PortFromPwdPIN>'
                . '<PortFromNetwork>' . $params->portFromNetwork . '</PortFromNetwork>'
                . '<Comments> </Comments>'
                . '<ReqDateTime>' . $time->format(DATE_RFC3339) . '</ReqDateTime>'
                . '</ActivateCustomer>'
                . '</BOOMWebAPI>'
                . '</diffgr:diffgram>'
                . '</DataSet>';

            if (getenv('APP_ENV') == 'production') {

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, self::$api_url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/xml',
                    'Cache-Control: no-cache, must-revalidate'
                ));
                curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_TIMEOUT, 600);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $args);

                $response = curl_exec($ch);
                $info = curl_errno($ch) > 0 ? array("curl_error_" . curl_errno($ch) => curl_error($ch)) : curl_getinfo($ch);
                curl_close($ch);

                Helper::log('### activationPurplePortIn() ###', [
                    'req' => $args,
                    'res' => $response,
                    'info' => $info
                ]);

                $ret = simplexml_load_string($response);

                $error_code = isset($ret->ResultCode) ? $ret->ResultCode->__toString() : '-0';
                $error_msg = isset($ret->ResultDesc) ? $ret->ResultDesc->__toString() : '';
                $port_reference_num = isset($ret->PortReferenceNum) ? $ret->PortReferenceNum->__toString() : '';

            } else {
                // Try in Demo
                $error_code = '5_3';
                $error_msg = 'Port Purple Success [Demo]';
                $port_reference_num = '4231231';
            }

            if (empty($error_code)) {
                return [
                    'error_code' => -4,
                    'error_msg' => 'Unable to get response code from vendor'
                ];
            }

            /*
             *  5_3 : Success
             *  5_5 : Pending
             *  Assumed that those two codes No error
             */
            if ($error_code != '5_3' && $error_code != '5_5') { // 5_4:error | 5_5:pending
                return [
                    'error_code' => $error_code,
                    'error_msg' => $error_msg,
                    'port_reference_num' => $port_reference_num
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => $error_msg,
                'port_reference_num' => $port_reference_num
            ];
        } catch (\Exception $ex) {

            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();
            Helper::send_mail('it@jjonbp.com', '[BOOM][' . getenv('APP_ENV') . '] Boom Purple() Exception', $message);
            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

}
