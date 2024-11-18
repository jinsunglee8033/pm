<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 9/25/17
 * Time: 5:11 PM
 */

namespace App\Lib;


class DollarPhone
{
    private static $api_url = 'https://www.dollarphone.com/pmapi/PinManager.asmx?WSDL';
    private static $uid = '';
    private static $pwd = '';


    private static function init() {
        if (getenv('APP_ENV') == 'production') {
            ### live & test credentials are same ###
        }
    }

    public static function pin($cid, $product_id, $amt, $fee = 0) {
        try {

            self::init();

            $req = '<?xml version="1.0" encoding="utf-8"?>';
            $req .= '<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">';
            $req .=    '<soap:Body>';
            $req .=        '<TopUpRequest xmlns="https://dollarphone.com/PMAPI/PinManager">';
            $req .=            '<TopUpReq>';
            $req .=                '<Action>PurchasePin</Action>';
            $req .=                '<OfferingId>' . $product_id . '</OfferingId>';
            $req .=                '<Amount>' . ($amt + $fee) . '</Amount>';
            $req .=                '<OrderId >' . $cid . '</OrderId >';
            $req .=            '</TopUpReq>';
            $req .=        '</TopUpRequest>';
            $req .=    '</soap:Body>';
            $req .= '</soap:Envelope>';

            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"https://dollarphone.com/PMAPI/PinManager/TopUpRequest\""
                //"Content-length: ".strlen($req)
            ); //SOAPAction: your op URL

            $ret = self::sendRequest($req, $headers);
            $result = $ret->children('soap', true)->Body->children()->TopUpRequestResponse->TopUpRequestResult;

            if (empty($result)) {
                return [
                    'error_code' => -902,
                    'error_msg' => 'Unable to find TopUpRequestResult object'
                ];
            }

            $responseCode = $result->responseCode;
            $responseMessage = $result->responseMessage;
            $TransId = $result->TransId;

            if (empty($TransId) || $TransId <= 0) {
                return [
                    'error_code' => $responseCode,
                    'error_msg' => $responseMessage
                ];
            }

            //echo 'Trans ID: ' . $TransId . ', ';

            $ret = self::confirm($TransId);

            if (!empty($ret['error_code']) || !empty($ret['error_msg'])) {
                return [
                    'error_code' => $ret['error_code'],
                    'error_msg' => $ret['error_msg']
                ];
            }

            if (empty($ret['pin'])) {
                return [
                    'error_code' => -904,
                    'error_msg' => 'Vendor returned no PIN'
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'pin' => $ret['pin'],
                'carrier_trans_id' => $ret['carrier_trans_id'],
                'tx_id' => $TransId
            ];

        } catch (\Exception $ex) {

            $message = " - product_id : " . $product_id . "<br/>";
            $message .= " - amt : " . $amt . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[DollarPhone][' . getenv('APP_ENV') . '] rtr() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function rtr($cid, $product_id, $mdn, $amt, $fee = 0, $pin='' , $amt_and_fee = 'N') {
        try {
            Helper::log('### rtr(DP) ###', [
                'cid' => $cid,
                'product_id' => $product_id,
                'mdn' => $mdn,
                'amt' => $amt,
                'pin' => $pin,
                'fee' => $fee
            ]);

            $final_amt = ($amt_and_fee == 'Y') ? $amt + $fee : $amt;

            self::init();

            $req = '<?xml version="1.0" encoding="utf-8"?>';
            $req .= '<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">';
            $req .=    '<soap:Body>';
            $req .=        '<TopUpRequest xmlns="https://dollarphone.com/PMAPI/PinManager">';
            $req .=            '<TopUpReq>';
            $req .=                '<Action>AddFunds</Action>';
            $req .=                '<OfferingId>' . $product_id . '</OfferingId>';

            $req .=                '<Amount>' . $final_amt . '</Amount>';

            $req .=                '<PhoneNumber>' . $mdn . '</PhoneNumber>';
            $req .=                '<OrderId >' . $cid . '</OrderId >';
            if ($product_id == '30167920') {
                $req .=                '<SubscriberPin>' . $pin . '</SubscriberPin>';
            }
            $req .=            '</TopUpReq>';
            $req .=        '</TopUpRequest>';
            $req .=    '</soap:Body>';
            $req .= '</soap:Envelope>';

            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"https://dollarphone.com/PMAPI/PinManager/TopUpRequest\""
                //"Content-length: ".strlen($req)
            ); //SOAPAction: your op URL

            $ret = self::sendRequest($req, $headers);
            $result = $ret->children('soap', true)->Body->children()->TopUpRequestResponse->TopUpRequestResult;
            if (empty($result)) {
                return [
                    'error_code' => -902,
                    'error_msg' => 'Unable to find TopUpRequestResult object'
                ];
            }

            $responseCode = $result->responseCode;
            $responseMessage = $result->responseMessage;
            $TransId = $result->TransId;

            if (empty($TransId) || $TransId <= 0) {
                return [
                    'error_code' => $responseCode,
                    'error_msg' => $responseMessage
                ];
            }

            //echo 'Trans ID: ' . $TransId . ', ';

            $ret = self::confirm($TransId);

            if (!empty($ret['error_code']) || !empty($ret['error_msg'])) {
                return [
                    'error_code' => $ret['error_code'],
                    'error_msg' => $ret['error_msg']
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'carrier_trans_id' => $ret['carrier_trans_id'],
                'tx_id' => $TransId
            ];

        } catch (\Exception $ex) {

            $message = " - product_id : " . $product_id . "<br/>";
            $message .= " - amt : " . $amt . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[DollarPhone][' . getenv('APP_ENV') . '] rtr() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function confirm($TransId) {
        try {

            self::init();

            $req = '<?xml version="1.0" encoding="utf-8"?>';
            $req .= '<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">';
            $req .=    '<soap:Body>';
            $req .=        '<TopupConfirm xmlns="https://dollarphone.com/PMAPI/PinManager">';
            $req .=            '<TransID>' . $TransId . '</TransID>';
            $req .=        '</TopupConfirm>';
            $req .=    '</soap:Body>';
            $req .= '</soap:Envelope>';


            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"https://dollarphone.com/PMAPI/PinManager/TopupConfirm\""
                //"Content-length: ".strlen($req)
            ); //SOAPAction: your op URL


            do {
                sleep(1);
                $ret = self::sendRequest($req, $headers);
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

            return [
                'error_code' => '',
                'error_msg' => '',
                'pin' => isset($result->PIN) ? $result->PIN : '',
                'carrier_trans_id' => isset($result->CarrierTransId) ? $result->CarrierTransId : ''
            ];


        } catch (\Exception $ex) {

            $message = " - trans_id : " . $TransId . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[DollarPhone][' . getenv('APP_ENV') . '] rtr() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    private static function sendRequest($req, $headers) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::$api_url);

        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, self::$uid . ":" . self::$pwd);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 600);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);

        $info = curl_getinfo($ch);

        curl_close($ch);

        Helper::log('### rtr(Dollar) ###', [
            'req' => $req,
            'res' => $output,
            'info' => $info
        ]);

        $resp_xml = simplexml_load_string($output, null, LIBXML_NOCDATA);
        return $resp_xml;
    }

    public static function get_boost_pin($mdn) {
        try {

            self::init();

            $req = '<?xml version="1.0" encoding="utf-8"?>';
            $req .= '<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">';
            $req .=    '<soap:Body>';
            $req .=        '<RetrieveBoostPin xmlns="https://dollarphone.com/PMAPI/PinManager">';
            $req .=            '<phone>' . $mdn . '</phone>';
            $req .=        '</RetrieveBoostPin>';
            $req .=    '</soap:Body>';
            $req .= '</soap:Envelope>';


            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"https://dollarphone.com/PMAPI/PinManager/RetrieveBoostPin\""
                //"Content-length: ".strlen($req)
            ); //SOAPAction: your op URL

            $ret = self::sendRequest($req, $headers);
            $result = $ret->children('soap', true)->Body->children()->RetrieveBoostPinResponse->children()->RetrieveBoostPinResult->children();

            if (empty($result)) {
                return [
                    'error_code' => -903,
                    'error_msg' => 'Unable to process.'
                ];
            }

            if ($result->success != true) {
                return [
                    'error_code' => '',
                    'error_msg' => $result->description . ' [' . $result->code . ']'
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => 'Successfuly sent the pin by sms. ' . $result->description . ' [' . $result->code . ']'
            ];


        } catch (\Exception $ex) {

            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[DollarPhone][' . getenv('APP_ENV') . '] get_boost_pin() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function activateOrRechargeAccount($cid, $product_id, $mdn, $face_value) {
        Helper::log('##### activateOrRechargeAccount Start ###');
        try {

            self::init();

            $req = '<?xml version="1.0" encoding="utf-8"?>';
            $req .= '<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">';
            $req .=    '<soap:Body>';
            $req .=        '<ActivateOrRechargeAccount xmlns="https://dollarphone.com/PMAPI/PinManager">';
            $req .=            '<reqActAccount>';
            $req .=                 '<OrderId>' . $cid . '</OrderId>';
            $req .=                 '<Company></Company>';
            $req .=                 '<Account>';
            $req .=                     '<Name></Name>';
            $req .=                     '<Email></Email>';
            $req .=                     '<FirstName></FirstName>';
            $req .=                     '<LastName></LastName>';
            $req .=                     '<OfferingId>' . $product_id . '</OfferingId>';
            $req .=                     '<Status></Status>';
            $req .=                     '<LotId></LotId>';
            $req .=                     '<Balance>' . $face_value . '</Balance>';
            $req .=                     '<Pin></Pin>';
            $req .=                     '<Ani>' . $mdn . '</Ani>';
            $req .=                     '<TransactionType></TransactionType>';
            $req .=                     '<Language></Language>';
            $req .=                 '</Account>';
            $req .=                 '<SignupType>WebSignUp</SignupType>';
            $req .=                 '<Language></Language>';
            $req .=                 '<PromoTransaction></PromoTransaction>';
            $req .=                 '<Description></Description>';
            $req .=            '</reqActAccount>';
            $req .=        '</ActivateOrRechargeAccount>';
            $req .=    '</soap:Body>';
            $req .= '</soap:Envelope>';

            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"https://dollarphone.com/PMAPI/PinManager/ActivateOrRechargeAccount\""
                //"Content-length: ".strlen($req)
            ); //SOAPAction: your op URL

            $ret = self::sendRequest($req, $headers);
            $result = $ret->children('soap', true)->Body->children()->ActivateOrRechargeAccountResponse->children()->ActivateOrRechargeAccountResult->children();

            Helper::log('##### activateOrRechargeAccount Result ###',$result);

            if (empty($result)) {
                return [
                    'error_code' => -902,
                    'error_msg' => 'Unable to find ActivateOrRechargeAccount object'
                ];
            }

            $responseCode = $result->responseCode;
            $responseMessage = $result->responseMessage;
            $TransId = $result->TransId;

            if (empty($TransId) || $TransId <= 0) {
                return [
                    'error_code' => $responseCode,
                    'error_msg' => $responseMessage
                ];
            }

            $ret = self::getWebTransactionInfo($TransId);

            if (!empty($ret['error_code']) || !empty($ret['error_msg'])) {
                return [
                    'error_code' => $ret['error_code'],
                    'error_msg' => $ret['error_msg']
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'tx_id' => $TransId
            ];

        } catch (\Exception $ex) {

            $message = " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@perfectmobileinc.com', '[DollarPhone][' . getenv('APP_ENV') . '] activateOrRechargeAccount() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function getWebTransactionInfo($TransId) {
        Helper::log('##### getWebTransactionInfo Start ###');
        try {

            self::init();

            $req = '<?xml version="1.0" encoding="utf-8"?>';
            $req .= '<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">';
            $req .=    '<soap:Body>';
            $req .=        '<GetWebTransactionInfo xmlns="https://dollarphone.com/PMAPI/PinManager">';
            $req .=            '<WebTransId>' . $TransId . '</WebTransId>';
            $req .=        '</GetWebTransactionInfo>';
            $req .=    '</soap:Body>';
            $req .= '</soap:Envelope>';


            $headers = array(
                "Connection: Keep-Alive",
                "User-Agent: PHPSoapClient",
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"https://dollarphone.com/PMAPI/PinManager/GetWebTransactionInfo\""
                //"Content-length: ".strlen($req)
            ); //SOAPAction: your op URL


            do {
                sleep(1);
                $ret = self::sendRequest($req, $headers);
                $result = $ret->children('soap', true)->Body->children()->GetWebTransactionInfoResponse->GetWebTransactionInfoResult;

                Helper::log('##### getWebTransactionInfo Result ###',$result);

                if (empty($result)) {
                    return [
                        'error_code' => -903,
                        'error_msg' => 'Unable to find GetWebTransactionInfo object'
                    ];
                }

            } while ($result->Status == "P");


            //echo 'Confirm Status: ' . $result->Status . '. ';

            if ($result->Status != "S") {
                return [
                    'error_code' => isset($result->ErrorCode) ? $result->ErrorCode->__toString() : '',
                    'error_msg' => isset($result->ErrorMsg) ? $result->ErrorMsg->__toString() : '',
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'pin' => isset($result->PIN) ? $result->PIN : ''
            ];


        } catch (\Exception $ex) {

            $message = " - trans_id : " . $TransId . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@jjonbp.com', '[DollarPhone][' . getenv('APP_ENV') . '] getWebTransactionInfo() Exception', $message);
            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function recharge($trans_id, $phone, $amt, $rtr_pid){

        Helper::log('### recharge (DOLLARPHONE PINLESS) ###');

        try {

            self::init();

            $base_url = "https://www.dollarphone.com/PMAPI_REST/pinapi/v1";
            $req = [
                "activate" => true,
                "offering_id" => $rtr_pid,
                "phone_number" => $phone,
                "amount" => $amt,
                "order_id" => $trans_id
            ];

            $req = json_encode($req);

            $headers = array(
                "Content-type: application/json; charset=utf-8",
                "Cache-Control: no-cache",
            );

            $url = "{$base_url}/account/ani/{$phone}/recharge";
            $res_json = self::sendRequest_recharge($url, $req, $headers, "POST");

            $res = json_decode($res_json);
            Helper::log('### after sendRequest_recharge RES => ###' ,$res);

            if (empty($res)) {
                return [
                    'error_code' => -902,
                    'error_msg' => 'Unable to find recharge object'
                ];
            }

            $responseCode = $res->status->code;
            $responseMessage = $res->status->message;
            $TransId = $res->trans_id;

            if (empty($TransId) || $TransId <= 0) {
                return [
                    'error_code' => $responseCode,
                    'error_msg' => $responseMessage
                ];
            }

            $ret = self::checkStatusRecharge($TransId);

            if (!empty($ret['error_code']) || !empty($ret['error_msg'])) {
                return [
                    'error_code' => $ret['error_code'],
                    'error_msg' => $ret['error_msg']
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'tx_id' => $ret['tx_id']
            ];

        } catch (\Exception $ex) {

            $message = " - trans_id : " . $trans_id . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@jjonbp.com', '[DollarPhone][' . getenv('APP_ENV') . '] recharge() Exception', $message);
            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function checkStatusRecharge($TransId) {
        Helper::log('### start checkStatusRecharge###', $TransId);
        try {

            self::init();

            $base_url = "https://www.dollarphone.com/PMAPI_REST/pinapi/v1";
            $url = "{$base_url}/transaction/{$TransId}";
            $headers = array(
                "Content-type: application/json; charset=utf-8",
                "Cache-Control: no-cache",
            );

            do {
                sleep(1);
                $res_json = self::sendRequest_recharge($url, "", $headers, "GET");
                $stat_res = json_decode($res_json);
                Helper::log('### sendRequest_recharge in checkStatusRecharge ###', $stat_res);
                if (empty($res_json)) {
                    return [
                        'error_code' => -903,
                        'error_msg' => 'Unable to find checkStatusRecharge object'
                    ];
                }
            } while ($stat_res->transaction_status == "Pending");

            if ($stat_res->transaction_status != "Success") {
                return [
                    'error_code' => isset($stat_res->status->code) ? $stat_res->status->code->__toString() : '',
                    'error_msg' => isset($stat_res->status->message) ? $stat_res->status->message->__toString() : '',
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'tx_id' => isset($stat_res->trans_id) ? $stat_res->trans_id : ''
            ];

        } catch (\Exception $ex) {

            $message = " - trans_id : " . $TransId . "<br/>";
            $message .= " - code : " . $ex->getCode() . "<br/>";
            $message .= " - message : " . $ex->getMessage() . "<br/>";
            $message .= " - trace : " . $ex->getTraceAsString();

            Helper::send_mail('it@jjonbp.com', '[DollarPhone][' . getenv('APP_ENV') . '] checkStatusRecharge() Exception', $message);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    private static function sendRequest_recharge($url, $json, $headers, $http_method) {

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, self::$uid . ":" . self::$pwd);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if($http_method == "POST"){
            Helper::log('### recharge - POST (DOLLARPHONE PINLESS) ###');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        }

        $resp = curl_exec($ch);
        curl_close($ch);

        Helper::log('### sendRequest_recharge(DOLLARPHONE PINLESS) ###', [
            'req' => $json,
            'res' => $resp
        ]);
        return $resp;
    }
}