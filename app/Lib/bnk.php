<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 9/25/17
 * Time: 5:11 PM
 */

namespace App\Lib;


use App\Model\LbtActivation;

class bnk
{
    private static $clientCode = 'PMOBINC';

    private static function init() {
        if (getenv('APP_ENV') == 'production') {
            self::$clientCode = 'PMOBINC';
        }
    }

    public static function activation($params) {
        try{
//            $data = [
//                "ClientCode" => self::$clientCode,
//                "TXNID" => $params->txnid,
//                "ICCID" => $params->iccid,
//                "BundleCode" => $params->bundle_code,
//                "Months" => $params->months,
//                "PlanAmount" => $params->plan_amount,
//                "BundleCode" => $params->bundle_code,
//                "Months" => $params->months,
//                "InternationalTopUpCode" => '1005',
//                "PlanAmount" => $params->plan_amount,
//                "RegulatoryFee" => 1  ,           // $params->regulatory_fee,
//                "ZipCode" => $params->zip_code
//            ];

            $data_string = "ClientCode=" . self::$clientCode .
                            "&TXNID=" . $params->txnid .
                            "&ICCID=" . $params->iccid .
                            "&BundleCode=" . $params->bundle_code .
                            "&Months=" . $params->months .
                            "&PlanAmount=" . 23 .  // $params->plan_amount .
//                            "&BundleCode=" . $params->bundle_code .
//                            "&Months=" . $params->months .
                            "&InternationalTopUpCode=" . '1005' .  //Tests
                            "&PlanAmount=" . $params->plan_amount .
                            "&RegulatoryFee=" . 1  .           // $params->regulatory_fee,
                            "&ZipCode=" . $params->zip_code;


//            $data_string = "ClientCode=" . self::$clientCode . "&TXNID=" . $params->txnid . "&ICCID=" . $params->iccid ;


            $full_input = 'http://api.bnkcorp.us/Lycaapi/Activation' . '?' . $data_string ;
            //$full_input = 'http://api.bnkcorp.us/Lycaapi/GetTransactionStatusBySim' . '?' . $data_string ;
            $curl = curl_init();
            //curl_setopt($curl, CURLOPT_URL,  $full_input );
            curl_setopt($curl, CURLOPT_URL, 'http://api.bnkcorp.us/Lycaapi/Activation?' . $data_string ) ;
            //curl_setopt($curl, CURLOPT_URL, 'http://api.bnkcorp.us/Lycaapi/GetTransactionStatusBySim?' . $data_string );

            curl_setopt($curl, CURLINFO_HEADER_OUT, true);
//            curl_setopt( $curl, CURLOPT_HTTPHEADER, [
////                "Content-type: application/json; charset=utf-8",
//                "Cache-Control: no-cache"
//            ] );

            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt( $curl, CURLOPT_POSTFIELDS, $full_input );

            curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true);
            //curl_setopt($curl, CURLOPT_PORT , 443);
            curl_setopt($curl, CURLOPT_TIMEOUT, 600);


            Helper::log('### BNK Activation Response Before ###', [
                'req'   => $data_string
            ]);

            $result = curl_exec($curl);
            $error = curl_errno($curl) > 0 ? array("curl_error_" . curl_errno($curl) => curl_error($curl)) : curl_getinfo($curl);
            curl_close($curl);

            Helper::log('### BNK Activation Response1 ###', [
                'req'   => $data_string,
                'res'   => $result,
                'error' => $error
            ]);

            dd("req=> ". $data_string. " res=> ". $result . " error=> ". $error);

            if ($error) {
                return [
                    'msg'           => 'Error : ' . $error,
                    'error_code'    => '-1',
                    'error_msg'     => $error
                ];
            }

            $res = json_decode($result);

            $error_code     = $res->Response->ERROR_CODE;
            $error_desc     = $res->Response->ERROR_DESC;
            $mdn            = $res->ACTIVATE_USIM_PORTIN_BUNDLE_RESPONSE->ALLOCATED_MSISDN;

            if($error_code != 0){
                return [
                    'error_code'    => $error_code,
                    'error_desc'    => $error_desc,
                    'mdn'           => $mdn
                ];
            } else {
                return [
                    'error_code'    => '',
                    'error_desc'    => $error_desc,
                    'mdn'           => $mdn
                ];
            }

        } catch (\Exception $ex) {
            return [
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ];
        }
    }

    public static function get_transaction_status_by_sim($sim) {
        try {
            $data = [
                "ClientCode" => self::$clientCode,
                "TXNID" => '111652',
                "ICCID" => $sim
            ];

            $data_string = json_encode($data);

            $curl = curl_init();
            curl_setopt( $curl, CURLOPT_URL, 'https://pos.bnkcorp.us/api/Lycaapi/GetTransactionStatusBySim');
            curl_setopt( $curl, CURLOPT_HTTPHEADER, [
//                "Authorization: Basic " . base64_encode('PerfectMobile' . ':' . 'PerfectMobile@123!'),
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string)
            ] );

            curl_setopt( $curl, CURLINFO_HEADER_OUT, true);

            curl_setopt( $curl, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($curl, CURLOPT_PORT , 443);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt($curl, CURLOPT_TIMEOUT, 600);
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

            $result = curl_exec($curl);
            $info = curl_getinfo($curl);
            $error = curl_errno($curl);
            curl_close($curl);

            Helper::log('### byod response ###', [
                'request'   => $data_string,
                'result'    => $result
            ]);

            if ($error) {
                switch ($http_code = $info['http_code']) {
                    case 200:  # OK
                    case 201:
                        break;
                    default:
                        return [
                            'msg' => 'Error : ' . $http_code
                        ];
                }
            }

            $res = json_decode($result);

            $error_code     = $res->Response->ERROR_CODE;
            $error_desc     = $res->Response->ERROR_DESC;
            $mdn            = $res->ACTIVATE_USIM_PORTIN_BUNDLE_RESPONSE->ALLOCATED_MSISDN;

            if($error_code != 0){
                return [
                    'error_code'    => $error_code,
                    'error_desc'    => $error_desc,
                    'mdn'           => $mdn
                ];
            } else {
                return [
                    'error_code'    => '',
                    'error_desc'    => $error_desc,
                    'mdn'           => $mdn
                ];
            }
        } catch (\Exception $ex) {
            return [
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ];
        }
    }

}