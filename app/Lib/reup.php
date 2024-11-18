<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 11/9/17
 * Time: 4:54 PM
 */

namespace App\Lib;


use App\Model\Denom;

class reup
{

    private static $api_url = 'https://api.sandbox.reupmobile.com';
    private static $api_user = '';
    private static $api_pwd = '';
    private static $dealer_id = '';
    private static $dba = '';
    private static $company_name = '';
    private static $contact_name = '';
    private static $phone = '';
    private static $email = '';

    private static function init() {
        // if (getenv('APP_ENV') == 'production') {
            self::$api_url = 'https://api.reupmobile.com';
            self::$api_user = '';
            self::$api_pwd = '';
        // }
    }

    public static function get_balances() {
        try {

            self::init();

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url . '/api/query/balances');
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                "dealer-id: " . self::$dealer_id,
                "Authorization: Basic " . base64_encode(self::$api_user . ':' . self::$api_pwd)
            ));
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            //curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPGET, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            //curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            $info = curl_getinfo($ch);

            Helper::log('### get_balances() ###', [
                'req' => '',
                'res' => $output,
                'info' => $info
            ]);

        } catch (\Exception $ex) {

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString()
            ];

        }
    }

    public static function get_mdn_info($mdn) {
        try {

            self::init();

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url . '/api/service/mdninfo/52/' . $mdn);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                "dealer-id: " . self::$dealer_id,
                "Authorization: Basic " . base64_encode(self::$api_user . ':' . self::$api_pwd)
            ));
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            //curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPGET, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            //curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            $info = curl_getinfo($ch);

            Helper::log('### get_mdn_info() ###', [
                'req' => '',
                'res' => $output,
                'info' => $info
            ]);

            $curl_error = curl_error($ch);
            $curl_errno = curl_errno($ch);

            if ($curl_errno) {
                return [
                    'error_code' => $curl_errno,
                    'error_msg' => $curl_error
                ];
            }

            curl_close($ch);

            $ret = json_decode($output);
            if (!isset($ret->isSuccess) || $ret->isSuccess != "true") {
                return [
                    'error_code' => -2,
                    'error_msg' => isset($ret->description) ? $ret->description : 'Unknown Error'
                ];
            }

            $new_product_id = '';
            if (isset($ret->carrierId)) {
                switch ($ret->carrierId) {
                    case 52:
                        $new_product_id = 'WROKC';
                        break;
                    case 53:
                        $new_product_id = 'WROKG';
                        break;
                    case 57:
                        $new_product_id = 'WROKS';
                        break;
                    default:
                        return [
                            'error_code' => -3,
                            'error_msg' => 'Unable to get carrier ID information'
                        ];
                }
            }

            if (!isset($ret->planCost)) {
                return [
                    'error_code' => -4,
                    'error_msg' => 'Unable to get plan cost information'
                ];
            }

            $denom = Denom::where('product_id', $new_product_id)
                ->where('denom', $ret->planCost)
                ->where('status', 'A')
                ->first();

            if (empty($denom)) {
                return [
                    'error_code' => -5,
                    'error_msg' => 'Unable to get plan amount information'
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'carrier_id' => isset($ret->carrierId) ? $ret->carrierId : '',
                'carrier_name' => isset($ret->carrierName) ? $ret->carrierName : '',
                'plan_id' => isset($ret->planId) ? $ret->planId : '',
                'product_id' => $new_product_id,
                'denom' => $denom,
                'plan_cost' => isset($ret->planCost) ? $ret->planCost : '',
                'last_payment_date' => isset($ret->lastPaymentDate) ? $ret->lastPaymentDate : '',
                'description' => isset($ret->description) ? $ret->description : '',
                'reference_id' => isset($ret->referenceId) ? $ret->referenceId : ''
            ];

        } catch (\Exception $ex) {

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString()
            ];

        }
    }

    public static function get_rtr_status($reference_id) {
        try {

            self::init();

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url . '/api/query/topup/' . $reference_id . '/status');
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                "dealer-id: " . self::$dealer_id,
                "Authorization: Basic " . base64_encode(self::$api_user . ':' . self::$api_pwd)
            ));
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            //curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPGET, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            //curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            $info = curl_getinfo($ch);

            Helper::log('### get rtr status ###', [
                'req' => '',
                'res' => $output,
                'info' => $info
            ]);

            $curl_error = curl_error($ch);
            $curl_errno = curl_errno($ch);

            if ($curl_errno) {
                return [
                    'error_code' => $curl_errno,
                    'error_msg' => $curl_error
                ];
            }

            curl_close($ch);

            $ret = json_decode($output);
            if (!isset($ret->isSuccess) || $ret->isSuccess != "true") {
                return [
                    'error_code' => -2,
                    'error_msg' => isset($ret->description) ? $ret->description : 'Unknown Error'
                ];
            }

            if (isset($ret->statusCode) && $ret->statusCode == 'Failed') {
                return [
                    'error_code' => -3,
                    'error_msg' => 'RTR Failed at vendor : ' . $ret->rtrMessage
                ];
            }

            if (!isset($ret->referenceId)) {
                return [
                    'error_code' => -1,
                    'error_msg' => 'Empty reference ID returned'
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'status' => $ret->statusCode
            ];


        } catch (\Exception $ex) {

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString()
            ];

        }
    }

    public static function rtr($sku, $mdn) {
        try {

            self::init();

            list($carrier_id, $plan_id) = explode(":", $sku);

            if (getenv('APP_ENV') != 'production') {
                return [
                    'error_code' => '',
                    'error_msg' => '',
                    'tx_id' => time()
                ];
            }

            $req = [
                'internalDealerInformation' => [
                    'internalDealerId' => self::$dealer_id,
                    'dba' => self::$dba,
                    'companyName' => self::$company_name,
                    'contactName' => self::$contact_name,
                    'phone' => self::$phone,
                    'email' => self::$email
                ],
                "carrierId" => $carrier_id,
                "planId" => $plan_id,
                "mdn" => $mdn
            ];

            $req = json_encode($req);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url . '/api/action/topup');
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                "dealer-id: " . self::$dealer_id,
                "Authorization: Basic " . base64_encode(self::$api_user . ':' . self::$api_pwd)
            ));
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            curl_setopt($ch, CURLOPT_POST, 1);
            //curl_setopt($ch, CURLOPT_HTTPGET, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            $info = curl_getinfo($ch);

            Helper::log('### rtr ###', [
                'req' => $req,
                'res' => $output,
                'info' => $info
            ]);

            $curl_error = curl_error($ch);
            $curl_errno = curl_errno($ch);

            if ($curl_errno) {
                return [
                    'error_code' => $curl_errno,
                    'error_msg' => $curl_error
                ];
            }

            curl_close($ch);

            $ret = json_decode($output);
            if (!isset($ret->isSuccess) || $ret->isSuccess != "true") {
                return [
                    'error_code' => -2,
                    'error_msg' => isset($ret->description) ? $ret->description : 'Unknown Error'
                ];
            }

            if (!isset($ret->referenceId)) {
                return [
                    'error_code' => -1,
                    'error_msg' => 'Empty reference ID returned'
                ];
            }

            if (getenv('APP_ENV') == 'production') {
                ### sleep 5 seconds ###
                sleep(5);

                $done = false;
                $cnt = 0;
                while(!$done) {
                    $ret_status = self::get_rtr_status($ret->referenceId);
                    if (!empty($ret_status['error_code'])) {
                        return [
                            'error_code' => $ret_status['error_code'],
                            'error_msg' => $ret_status['error_msg']
                        ];
                    }

                    $status = $ret_status['status'];
                    if ($status == 'Success') {
                        $done = true;
                    } else {

                        $cnt++;
                        if ($cnt > 20) {
                            Helper::send_mail('it@perfectmobileinc.com', '[ROK][' . getenv('APP_ENV') . '] RTR Error', ' - Unable to get status within 20 attempts: ' . $ret->referenceId);
                            break;
                        }

                        ### wait 5 seconds and try again ###
                        sleep(6);
                    }
                }
            }


            return [
                'error_code' => '',
                'error_msg' => '',
                'tx_id' => $ret->referenceId
            ];

        } catch (\Exception $ex) {

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString()
            ];

        }
    }

    public static function get_portin_status($reference_id) {
        try {

            self::init();

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url . '/api/query/portin/' . $reference_id . '/status');
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                "dealer-id: " . self::$dealer_id,
                "Authorization: Basic " . base64_encode(self::$api_user . ':' . self::$api_pwd)
            ));
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            //curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPGET, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            //curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            $info = curl_getinfo($ch);

            Helper::log('### port-in status ###', [
                'req' => '',
                'res' => $output,
                'info' => $info
            ]);

            $curl_error = curl_error($ch);
            $curl_errno = curl_errno($ch);

            if ($curl_errno) {
                return [
                    'error_code' => $curl_errno,
                    'error_msg' => $curl_error
                ];
            }

            curl_close($ch);

            $ret = json_decode($output);
            if (!isset($ret->isSuccess) || $ret->isSuccess != "true") {
                return [
                    'error_code' => -2,
                    'error_msg' => isset($ret->description) ? $ret->description : 'Unknown Error'
                ];
            }

            if (!isset($ret->referenceId)) {
                return [
                    'error_code' => -1,
                    'error_msg' => 'Empty reference ID returned'
                ];
            }

            if (!isset($ret->activationDetails)) {
                return [
                    'error_code' => -3,
                    'error_msg' => 'Empty activation detail returned'
                ];
            }

            if (!isset($ret->activationDetails->mdn)) {
                return [
                    'error_code' => -4,
                    'error_msg' => 'Empty MDN returned'
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'status' => $ret->status,
                'status_message' => $ret->processorResponseMessage,
                'min' => $ret->activationDetails->mdn
            ];


        } catch (\Exception $ex) {

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString()
            ];

        }
    }

    public static function portin(
        $sim, $esn, $sku, $phone_type,
        $current_carrier_id, $number_to_port, $account_no, $account_pass,
        $first_name, $last_name, $address1, $address2,
        $city, $state, $zip, $npa, $email
    ) {
        try {

            self::init();

            if (getenv('APP_ENV') != 'production') {
                return [
                    'error_code' => '',
                    'error_msg' => '',
                    'tx_id' => time(),
                    'min' => '1112223333'
                ];
            }

            list($carrier_id, $plan_id) = explode(":", $sku);

            if (empty($first_name)) {
                $first_name = 'Perfect';
            }

            if (empty($last_name)) {
                $last_name = 'Mobile';
            }

            if (empty($address1)) {
                $address1 = '4119 John Marr Dr';
            }

            if (empty($city)) {
                $city = 'Annandale';
            }

            if (empty($state)) {
                $state = 'VA';
            }

            if (empty($zip)) {
                $zip = '22003';
            }

            if (empty($npa)) {
                $npa = '000';
            }

            if (empty($email)) {
                $email = 'sales@perfectmobileinc.com';
            }

            $req = [
                "portinInformation" => [
                    "simNumber" => $sim,
                    "deviceIdOrESN" => $esn,
                    "carrierId" => $carrier_id,
                    "planId" => $plan_id,
                    "phoneType" => $phone_type,
                    "currentCarrierDetails" => [
                        "currentCarrierId" => $current_carrier_id,
                        "mdnToPort" => $number_to_port,
                        "accountNumber" => $account_no,
                        "accountPassword" => $account_pass
                    ]
                ],
                "subscriberInformation" => [
                    "firstName" => $first_name,
                    "lastName" => $last_name,
                    "address1" => $address1,
                    "address2" => $address2,
                    "city" => $city,
                    "state" => $state,
                    "zipCode" => $zip,
                    "areaCode" => $npa,
                    "country" => "US",
                    "email" => $email
                ],
                'internalDealerInformation' => [
                    'internalDealerId' => self::$dealer_id,
                    'dba' => self::$dba,
                    'companyName' => self::$company_name,
                    'contactName' => self::$contact_name,
                    'phone' => self::$phone,
                    'email' => self::$email
                ]
            ];

            $req = json_encode($req);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url . '/api/action/portin');
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                "dealer-id: " . self::$dealer_id,
                "Authorization: Basic " . base64_encode(self::$api_user . ':' . self::$api_pwd)
            ));
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            curl_setopt($ch, CURLOPT_POST, 1);
            //curl_setopt($ch, CURLOPT_HTTPGET, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            $info = curl_getinfo($ch);

            Helper::log('### port-in ###', [
                'req' => $req,
                'res' => $output,
                'info' => $info
            ]);

            $curl_error = curl_error($ch);
            $curl_errno = curl_errno($ch);

            if ($curl_errno) {
                return [
                    'error_code' => $curl_errno,
                    'error_msg' => $curl_error
                ];
            }

            curl_close($ch);

            $ret = json_decode($output);
            if (!isset($ret->isSuccess) || $ret->isSuccess != "true") {
                return [
                    'error_code' => -2,
                    'error_msg' => isset($ret->description) ? $ret->description : 'Unknown Error'
                ];
            }

            if (!isset($ret->referenceId)) {
                return [
                    'error_code' => -1,
                    'error_msg' => 'Empty reference ID returned'
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'tx_id' => $ret->referenceId,
                'min' => $number_to_port
            ];

        } catch (\Exception $ex) {

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString()
            ];

        }
    }

    public static function get_activation_status($reference_id) {
        try {

            self::init();

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url . '/api/query/activation/' . $reference_id . '/status');
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                "dealer-id: " . self::$dealer_id,
                "Authorization: Basic " . base64_encode(self::$api_user . ':' . self::$api_pwd)
            ));
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            //curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPGET, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            //curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            $info = curl_getinfo($ch);

            Helper::log('### activation status ###', [
                'req' => '',
                'res' => $output,
                'info' => $info
            ]);

            $curl_error = curl_error($ch);
            $curl_errno = curl_errno($ch);

            if ($curl_errno) {
                return [
                    'error_code' => $curl_errno,
                    'error_msg' => $curl_error
                ];
            }

            curl_close($ch);

            $ret = json_decode($output);
            if (!isset($ret->isSuccess) || $ret->isSuccess != "true") {
                return [
                    'error_code' => -2,
                    'error_msg' => isset($ret->description) ? $ret->description : 'Unknown Error'
                ];
            }

            if (!isset($ret->activationRequestStatus) || $ret->activationRequestStatus == 'Failure') {
                return [
                    'error_code' => -4,
                    'error_msg' => isset($ret->processorResponseMessage) ? $ret->processorResponseMessage : 'Activation failed with unknown reason'
                ];
            }

            if (!isset($ret->referenceId)) {
                return [
                    'error_code' => -1,
                    'error_msg' => 'Empty reference ID returned'
                ];
            }

            if (!isset($ret->activationDetails)) {
                return [
                    'error_code' => -3,
                    'error_msg' => 'Empty activation detail returned'
                ];
            }

            if ($ret->activationRequestStatus == 'Success' && (!isset($ret->activationDetails->mdn) || empty($ret->activationDetails->mdn))) {
                return [
                    'error_code' => -5,
                    'error_msg' => 'Empty MDN returned'
                ];
            }

            if ($ret->activationRequestStatus == 'Success' && isset($ret->activationDetails->mdn) && !empty($ret->activationDetails->mdn) && !is_numeric($ret->activationDetails->mdn)) {
                return [
                    'error_code' => '',
                    'error_msg' => '',
                    'status' => 'No.MDN.Yet',
                    'min' => ''
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'status' => $ret->activationRequestStatus,
                'min' => $ret->activationDetails->mdn
            ];


        } catch (\Exception $ex) {

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString()
            ];

        }
    }

    public static function activation(
        $sim, $esn, $sku, $phone_type,
        $first_name, $last_name, $address1, $address2,
        $city, $state, $zip, $npa, $email
    ) {
        try {

            self::init();

            list($carrier_id, $plan_id) = explode(":", $sku);

            if (getenv('APP_ENV') != 'production') {
                return [
                    'error_code' => '',
                    'error_msg' => '',
                    'tx_id' => time(),
                    'min' => '1112223333'
                ];
            }

            if (empty($first_name)) {
                $first_name = 'Perfect';
            }

            if (empty($last_name)) {
                $last_name = 'Mobile';
            }

            if (empty($address1)) {
                $address1 = '4119 John Marr Dr';
            }

            if (empty($city)) {
                $city = 'Annandale';
            }

            if (empty($state)) {
                $state = 'VA';
            }

            if (empty($zip)) {
                $zip = '22003';
            }

            if (empty($npa)) {
                $npa = '000';
            }

            if (empty($email)) {
                $email = 'sales@perfectmobileinc.com';
            }

            $req = [
                'activationInformation' => [
                    'simNumber' => $sim,
                    'deviceIdOrESN' => $esn,
                    'carrierId' => $carrier_id,
                    'planId' => $plan_id,
                    'phoneType' => $phone_type
                ],
                'subscriberInformation' => [
                    'firstName' => $first_name,
                    'lastName' => $last_name,
                    'address1' => $address1,
                    'address2' => $address2,
                    'city' => $city,
                    'state' => $state,
                    'zipCode' => $zip,
                    'areaCode' => $npa,
                    'country' => 'US',
                    'email' => $email
                ],
                'internalDealerInformation' => [
                    'internalDealerId' => self::$dealer_id,
                    'dba' => self::$dba,
                    'companyName' => self::$company_name,
                    'contactName' => self::$contact_name,
                    'phone' => self::$phone,
                    'email' => self::$email
                ]
            ];

            $req = json_encode($req);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url . '/api/action/activation');
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                "dealer-id: " . self::$dealer_id,
                "Authorization: Basic " . base64_encode(self::$api_user . ':' . self::$api_pwd)
            ));
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            curl_setopt($ch, CURLOPT_POST, 1);
            //curl_setopt($ch, CURLOPT_HTTPGET, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            $info = curl_getinfo($ch);

            Helper::log('### activation ###', [
                'req' => $req,
                'res' => $output,
                'info' => $info
            ]);

            $curl_error = curl_error($ch);
            $curl_errno = curl_errno($ch);

            if ($curl_errno) {
                return [
                    'error_code' => $curl_errno,
                    'error_msg' => $curl_error
                ];
            }

            curl_close($ch);

            $ret = json_decode($output);
            if (!isset($ret->isSuccess) || $ret->isSuccess != "true") {
                return [
                    'error_code' => -2,
                    'error_msg' => isset($ret->description) ? $ret->description : 'Unknown Error'
                ];
            }

            if (!isset($ret->referenceId)) {
                return [
                    'error_code' => -1,
                    'error_msg' => 'Empty reference ID returned'
                ];
            }

            $min = '';

            if (getenv('APP_ENV') == 'production') {
                ### sleep 10 seconds ###
                sleep(10);

                $done = false;
                $cnt = 0;

                while(!$done) {
                    $ret_status = self::get_activation_status($ret->referenceId);
                    if (!empty($ret_status['error_code'])) {
                        return [
                            'error_code' => $ret_status['error_code'],
                            'error_msg' => $ret_status['error_msg']
                        ];
                    }

                    $status = $ret_status['status'];
                    $min = $ret_status['min'];

                    if ($status == 'Success' && !empty($min) && is_numeric($min)) {
                        $done = true;
                        $min = $ret_status['min'];
                    } else {

                        $cnt++;
                        if ($cnt > 30) {
                            Helper::send_mail('it@perfectmobileinc.com', '[ROK][' . getenv('APP_ENV') . '] Activation Error', ' - Unable to get status within 10 minutes: ' . $ret->referenceId);
                            break;
                        }

                        ### wait 10 seconds and try again ###
                        sleep(11);
                    }
                }
            } else {
                $min = '1112223333';
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'tx_id' => $ret->referenceId,
                'min' => $min
            ];

        } catch (\Exception $ex) {

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString()
            ];

        }
    }

    public static function get_plans($carrier_id) {
        try {

            self::init();

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url . '/api/query/plans/' . $carrier_id);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                "dealer-id: " . self::$dealer_id,
                "Authorization: Basic " . base64_encode(self::$api_user . ':' . self::$api_pwd)
            ));
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            curl_setopt($ch, CURLOPT_POST, 0);
            curl_setopt($ch, CURLOPT_HTTPGET, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            //curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            $info = curl_getinfo($ch);

            Helper::log('### get_carriers ###', [
                'req' => '',
                'res' => $output,
                'info' => $info
            ]);

            $curl_error = curl_error($ch);
            $curl_errno = curl_errno($ch);

            if ($curl_errno) {
                return [
                    'error_code' => $curl_errno,
                    'error_msg' => $curl_error
                ];
            }

            curl_close($ch);

            $ret = json_decode($output);
            if (!isset($ret->isSuccess) || $ret->isSuccess != "true") {
                return [
                    'error_code' => -2,
                    'error_msg' => isset($ret->description) ? $ret->description : 'Unknown Error'
                ];
            }

            if (!isset($ret->plans)) {
                return [
                    'error_code' => -1,
                    'error_msg' => 'Empty carrier list returned'
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'plans' => $ret->plans
            ];


        } catch (\Exception $ex) {

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage()
            ];

        }
    }

    public static function get_carriers() {
        try {

            self::init();

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url . '/api/query/carriers');
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                "dealer-id: " . self::$dealer_id,
                "Authorization: Basic " . base64_encode(self::$api_user . ':' . self::$api_pwd)
            ));
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            curl_setopt($ch, CURLOPT_POST, 0);
            curl_setopt($ch, CURLOPT_HTTPGET, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            //curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            $info = curl_getinfo($ch);

            Helper::log('### get_carriers ###', [
                'req' => '',
                'res' => $output,
                'info' => $info
            ]);

            $curl_error = curl_error($ch);
            $curl_errno = curl_errno($ch);

            if ($curl_errno) {
                return [
                    'error_code' => $curl_errno,
                    'error_msg' => $curl_error
                ];
            }

            curl_close($ch);

            $ret = json_decode($output);
            if (!isset($ret->isSuccess) || $ret->isSuccess != "true") {
                return [
                    'error_code' => -2,
                    'error_msg' => isset($ret->description) ? $ret->description : 'Unknown Error'
                ];
            }

            if (!isset($ret->carriers)) {
                return [
                    'error_code' => -1,
                    'error_msg' => 'Empty carrier list returned'
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'carriers' => $ret->carriers
            ];


        } catch (\Exception $ex) {

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage()
            ];

        }
    }

    public static function simswap($carrier_id, $newSIM, $mdn) {
        try {

            self::init();

            // if (getenv('APP_ENV') != 'production') {
            //     return [
            //         'error_code' => '',
            //         'error_msg' => '',
            //         'referenceId' => time()
            //     ];
            // }

            $req = [
                "carrierId" => $carrier_id,
                "newSIM" => $newSIM,
                "mdn" => $mdn
            ];

            $req = json_encode($req);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url . '/api/service/simswap');
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                "dealer-id: " . self::$dealer_id,
                "Authorization: Basic " . base64_encode(self::$api_user . ':' . self::$api_pwd)
            ));
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            curl_setopt($ch, CURLOPT_POST, 1);
            //curl_setopt($ch, CURLOPT_HTTPGET, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            $info = curl_getinfo($ch);

            Helper::log('### simswap ###', [
                'req' => $req,
                'res' => $output,
                'info' => $info
            ]);

            $curl_error = curl_error($ch);
            $curl_errno = curl_errno($ch);

            if ($curl_errno) {
                return [
                    'error_code' => 'ER-' . $curl_errno,
                    'error_msg' => $curl_error,
                    'referenceId' => ''
                ];
            }

            curl_close($ch);

            $ret = json_decode($output);
            if (!isset($ret->isSuccess) || $ret->isSuccess != "true") {
                return [
                    'error_code' => -2,
                    'error_msg' => (isset($ret->description) ? $ret->description : 'Unknown Error') . '. ' . (isset($ret->processorResponseMessage) ? $ret->processorResponseMessage : ''),
                    'referenceId' => empty($ret->referenceId) ? '' : $ret->referenceId
                ];
            }

            // if (!isset($ret->referenceId)) {
            //     return [
            //         'error_code' => -1,
            //         'error_msg' => 'Empty reference ID returned'
            //     ];
            // }

            return [
                'error_code' => '',
                'error_msg' => (empty($ret->description) ? '' : $ret->description . '. ') . (empty($ret->processorResponseMessage) ? '' : $ret->processorResponseMessage),
                'referenceId' => empty($ret->referenceId) ? '' : $ret->referenceId
            ];

        } catch (\Exception $ex) {

            return [
                'error_code' => 'EX-' . $ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'referenceId' => ''
            ];

        }
    }

}