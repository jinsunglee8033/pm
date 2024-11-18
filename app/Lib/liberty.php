<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 9/25/17
 * Time: 5:11 PM
 */

namespace App\Lib;


use App\Model\LbtActivation;

class liberty
{
    private static $api_url = 'https://www.libertymobileinc.com/libertyservices/api/';
    private static $uid = '';
    private static $pwd = '';

    private static function init() {
        if (getenv('APP_ENV') == 'production') {
            self::$api_url = 'https:///www.libertymobileinc.com/libertyservices/api/';
            self::$uid = '';
            self::$pwd = '';
        }
    }

    public static function byod($esn)
    {
        try {

            $data = [
                "esn" => $esn, //270113180406696490
                "iccid" => ""
            ];

            $data_string = json_encode($data);

            $curl = curl_init();
            curl_setopt( $curl, CURLOPT_URL, 'https://www.libertymobileinc.com/libertyservices/api/Byod');
            curl_setopt( $curl, CURLOPT_HTTPHEADER, [
                "Authorization: Basic " . base64_encode('PerfectMobile' . ':' . 'PerfectMobile@123!'),
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

            $code       = $res->Code;
            $message    = $res->Message;
            $esn        = $res->esn;

            if($code != 1){
                return [
                    'code'      => $code,
                    'message'   => $message,
                    'esn'       => $esn,
                    'result'    => $result
                ];
            } else {
                return [
                    'code'      => $code,
                    'message'   => $message,
                    'esn'       => $esn,
                    'result'    => $result
                ];
            }

        } catch (\Exception $ex) {
            return [
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ];
        }
    }

    public static function serviceActivation($params)
    {
        try {

//            $data = [
//                "ZipCode"   => "92844",
//                "esn"       => "268435462500842133",
//                "iccid"     => ""
//            ];

            $data = [
                "ZipCode"   => $params->zip,
                "esn"       => $params->esn,
                "iccid"     => $params->sim
            ];

            $data_string = json_encode($data);

            $curl = curl_init();
            curl_setopt( $curl, CURLOPT_URL, 'https://www.libertymobileinc.com/libertyservices/api/ServiceActivation');
            curl_setopt( $curl, CURLOPT_HTTPHEADER, [
                "Authorization: Basic " . base64_encode('PerfectMobile' . ':' . 'PerfectMobile@123!'),
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

            Helper::log('### [LBT] ServiceActivation response ###', [
                'request'   => $data_string,
                'result'    => $result
            ]);

            if ($error) {
                return [
                    'msg'       => 'Error : ' . $error
                ];
            }

            $res = json_decode($result);

            if($res->StatusCode == '1'){
                $lbt = new LbtActivation();
                $lbt->trans_id  = $params->trans_id;
                $lbt->make      = $res->make;
                $lbt->model     = $res->model;
                $lbt->msl       = $res->msl;
                $lbt->iccid     = $res->iccid;
                $lbt->mdn       = $res->mdn;
                $lbt->msid      = $res->MSID;

                $lbt->cdate = \Carbon\Carbon::now();
                $lbt->save();
            }

            return [
                'msg'           => '',
                'result'        => $res
            ];

        } catch (\Exception $ex) {
            return [
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ];
        }
    }

    public static function refillByLot($mdn, $lotNum)
    {
        try {

//            $data = [
//                "Mdn"     => "7147201562",
//                "Lotnum"  => "UPM3020"
//            ];

            $data = [
                "Mdn"       => $mdn,
                "Lotnum"    => $lotNum
            ];

            $data_string = json_encode($data);

            $curl = curl_init();
            curl_setopt( $curl, CURLOPT_URL, 'https://www.libertymobileinc.com/libertyservices/api/RefillByLot');
            curl_setopt( $curl, CURLOPT_HTTPHEADER, [
                "Authorization: Basic " . base64_encode('PerfectMobile' . ':' . 'PerfectMobile@123!'),
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

            Helper::log('### [LBT] RefillByLot response ###', [
                'request'   => $data_string,
                'result'    => $result
            ]);

            if ($error) {
                return [
                    'msg'           => 'Error : ' . $error,
                    'error_code'    => '-1',
                    'error_msg'     => $error
                ];
            }

            $res = json_decode($result);

            if($res->StatusCode != '1'){
                return [
                    'msg'           => '',
                    'error_code'    => $res->StatusCode,
                    'error_msg'     => $res->StatusCodeName,
                    'result'        => $res
                ];
            }else{

                return [
                    'msg'           => '',
                    'error_code'    => '',
                    'error_msg'     => '',
                    'result'        => $res
                ];
            }

        } catch (\Exception $ex) {
            return [
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ];
        }
    }

    public static function Portin($params)
    {
        try {

//            $data = [
//                "Esn"           => "268435459403590498",
//                "Mdn"           => "4437268452",
//                "FirstName"     => "Samuel",
//                "LastName"      => "Sperling",
//                "PortInMdn"     => "4439341721",
//                "Carrier"       =>"Verizon",
//                "AccountNo"     => "520256574",
//                "Password"      => "7408",
//                "StreetNumber"  => "6300",
//                "StreetName"    => "Lincoln",
//                "City"          => "Baltimore",
//                "State"         => "CA",
//                "Zip"           => "21208",
//                "CallBackNo"    => "",
//                "Email"         => "E"
//            ];

            $data = [
                "Esn"           => $params->esn,
                "Mdn"           => $params->mdn,
                "FirstName"     => $params->first_name,
                "LastName"      => $params->last_name,
                "PortInMdn"     => $params->port_in_mdn,
                "Carrier"       => $params->carrier,
                "AccountNo"     => $params->account_no,
                "Password"      => $params->password,
                "StreetNumber"  => $params->street_number,
                "StreetName"    => $params->street_name,
                "City"          => $params->city,
                "State"         => $params->state,
                "Zip"           => $params->portin_zip,
                "CallBackNo"    => $params->call_back_number,
                "Email"         => $params->email
            ];

            $data_string = json_encode($data);

            $curl = curl_init();
            curl_setopt( $curl, CURLOPT_URL, 'https://www.libertymobileinc.com/libertyservices/api/Portin');
            curl_setopt( $curl, CURLOPT_HTTPHEADER, [
                "Authorization: Basic " . base64_encode('PerfectMobile' . ':' . 'PerfectMobile@123!'),
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

            Helper::log('### PortIn response ###', [
                'request'   => $data_string,
                'result'    => $result
            ]);

            if ($error) {
                return [
                    'msg'           => 'Error : ' . $error
                ];
            }

            $res = json_decode($result);

            return [
                'msg' => '',
                'result' => $res
            ];

        } catch (\Exception $ex) {
            return [
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ];
        }
    }

    public static function getmdninfo($mdn)
    {
        try {

            $data = [
                "mdn" => $mdn // 8163791995
            ];
//            $data = [
//                "mdn" => "8163791994"
//            ];

            $data_string = json_encode($data);

            $curl = curl_init();
            curl_setopt( $curl, CURLOPT_URL, 'https://www.libertymobileinc.com/libertyservices/api/getmdninfo');
            curl_setopt( $curl, CURLOPT_HTTPHEADER, [
                "Authorization: Basic " . base64_encode('PerfectMobile' . ':' . 'PerfectMobile@123!'),
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

            Helper::log('### getmdninfo response ###', [
                'request'   => $data_string,
                'result'    => $result
            ]);

            if ($error) {
                return [
                    'msg' => 'Error : ' . $error
                ];
            }

            $res = json_decode($result);

            //output: {"mdn":"8163791995","StatusCode":"1","StatusCodeName":"OK",
            //          "Language":"1","ILD":"N","BalanceData":"100000000.00",
            //          "BalanceText":"10000.00","BalanceVoice":"9830.00",
            //          "ExpirationDate":"10/29/2019"}

            $mdn        = $res->mdn;
            $code       = $res->StatusCode;
            $message    = $res->StatusCodeName;

            if($code != 6){
                return [
                    'error_code'    => $code,
                    'message'       => $message,
                    'mdn'           => $mdn,
                    'result'        => $res
                ];
            } else {

                return [
                    'error_code'    => '',
                    'message'       => $message,
                    'mdn'           => $mdn,
                    'result'        => $res
                ];
            }

        } catch (\Exception $ex) {
            return [
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ];
        }
    }

}