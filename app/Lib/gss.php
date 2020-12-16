<?php
/**
 * User: Royce
 * Date: 06/18/18
 */

namespace App\Lib;


use App\Model\Denom;

class gss
{

    private static $api_url = 'h1.gotprepaidpins.com';
    private static $api_port = '714';
    private static $api_user = 'MobilePM4';
    private static $api_pwd = 'PerE4119';

    private static function init() {
        if (getenv('APP_ENV') == 'production') {
            self::$api_url = 'h1.gotprepaidpins.com';
            self::$api_port = '713';
            self::$api_user = 'MobilePM4';
            self::$api_pwd = 'PerE4119';
        }
    }

    public static function Balance() {
        try {

            self::init();

            $req = '<GSSRequest>';
            $req .=     '<CustomerInfo>';
            $req .=         '<AccountName>' . self::$api_user . '</AccountName>';
            $req .=         '<Password>' . self::$api_pwd . '</Password>';
            $req .=     '</CustomerInfo>';
            $req .=     '<RequestType>Balance</RequestType>';
            $req .= '</GSSRequest>';

            $s = fsockopen(self::$api_url, self::$api_port);
            if ($s) {
                fputs($s, $req);
                $return = '';
                while (!feof($s)) {
                    $return .= fgets($s);
                }
                fclose($s);

                // Log::info('GSS Rec: '.json_encode($return, JSON_PRETTY_PRINT));

                // xml special characters escape
                $return = str_replace(array('&'), array('&amp;'), $return);
                $ret = simplexml_load_string($return, null, LIBXML_NOCDATA);
                Helper::log('### GSS: Balance() ###', [
                    'req' => $req,
                    'res' => $ret
                ]);

                $ret = json_decode(json_encode($ret), TRUE);
                $ret = (object) $ret;

                // Helper::log('### GSS: Balance() ###', [
                //     'req' => $req,
                //     'res' => $ret
                // ]);

                return [
                    'error_code' => $ret->{'Result-Code'},
                    'error_msg'  => $ret->{'Result-Description'},
                    'balance'    => $ret->Balance
                ];

            } else {
                Helper::log('### GSS: Network error. ###', [
                    'req' => $req,
                    'res' => ['Result-Code' => -1, 'Result-Description' => 'Network Error']
                ]);

                return [
                    'error_code' => -1,
                    'error_msg'  => 'Network error.',
                    'balance'    => 0
                ];
            }

        } catch (\Exception $ex) {

            return [
                'error_code' => 'EX-' . $ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'balance' => 0
            ];

        }
    }

    public static function ProductInfo() {
        try {

            self::init();

            $req = '<GSSRequest>';
            $req .=     '<CustomerInfo>';
            $req .=         '<AccountName>' . self::$api_user . '</AccountName>';
            $req .=         '<Password>' . self::$api_pwd . '</Password>';
            $req .=     '</CustomerInfo>';
            $req .=     '<RequestType>ProductInfo</RequestType>';
            $req .= '</GSSRequest>';

            $s = fsockopen(self::$api_url, self::$api_port);
            if ($s) {
                fputs($s, $req);
                $return = '';
                while (!feof($s)) {
                    $return .= fgets($s);
                }
                fclose($s);

                // Log::info('GSS Rec: '.json_encode($return, JSON_PRETTY_PRINT));
                // xml special characters escape
                $return = str_replace(array('&'), array('&amp;'), $return);
                $ret = simplexml_load_string($return, null, LIBXML_NOCDATA);

                Helper::log('### GSS: ProductInfo() SimpleObject ###', [
                    'req' => $req,
                    'res' => $ret->Category->Type
                ]);

                // $ret = json_decode(json_encode($ret), TRUE);
                // $ret = (object) $ret;

                // Helper::log('### GSS: ProductInfo() ###', [
                //     'req' => $req,
                //     'res' => $ret->Category->Type
                // ]);

                return [
                    'error_code' => 0,
                    'error_msg'  => '',
                    'products'   => $ret->Category->Type
                ];

            } else {
                Helper::log('### GSS: Network error. ###', [
                    'req' => $req,
                    'res' => ['Result-Code' => -1, 'Result-Description' => 'Network Error']
                ]);

                return [
                    'error_code' => -1,
                    'error_msg'  => 'Network error.',
                    'products'   => null
                ];
            }

        } catch (\Exception $ex) {

            return [
                'error_code' => 'EX-' . $ex->getCode(),
                'error_msg'  => $ex->getMessage(),
                'products'   => null
            ];

        }
    }

    public static function pin($trans_id, $pid) {
        try {

            self::init();

            $req = '<GSSRequest>';
            $req .=     '<CustomerInfo>';
            $req .=         '<AccountName>' . self::$api_user . '</AccountName>';
            $req .=         '<Password>' . self::$api_pwd . '</Password>';
            $req .=     '</CustomerInfo>';
            $req .=     '<RequestType>GetPin</RequestType>';
            $req .=     '<CustRef>' . $trans_id . '</CustRef>';
            $req .=     '<ProductID>' . $pid . '</ProductID>';
            $req .=     '<Count>1</Count>';
            $req .= '</GSSRequest>';

            $s = fsockopen(self::$api_url, self::$api_port);
            if ($s) {
                fputs($s, $req);
                $return = '';
                while (!feof($s)) {
                    $return .= fgets($s);
                }
                fclose($s);

                // Log::info('GSS Rec: '.json_encode($return, JSON_PRETTY_PRINT));

                // xml special characters escape
                $return = str_replace(array('&'), array('&amp;'), $return);
                $ret = simplexml_load_string($return, null, LIBXML_NOCDATA);
                Helper::log('### GSS: GetPin() ###', [
                    'req' => $req,
                    'res' => $ret
                ]);

                $result_code = $ret->{'Result-Code'};

                if ($result_code == 0) {

                    if (empty($ret->PIN) || empty($ret->PIN->Number)) {
                        return [
                            'error_code' => -904,
                            'error_msg' => 'Vendor returned no PIN'
                        ];
                    }

                    return [
                        'error_code' => '',
                        'error_msg'  => '',
                        'pin'        => $ret->PIN->Number,
                        'tx_id'      => $ret->PIN->Reference
                    ];
                } else {
                    return [
                        'error_code' => $result_code,
                        'error_msg'  => $ret->{'Result-Description'}
                    ];
                }

            } else {
                Helper::log('### GSS::GetPin: Network error. ###', [
                    'req' => $req,
                    'res' => ['Result-Code' => -1, 'Result-Description' => 'Network Error']
                ]);

                return [
                    'error_code' => -1,
                    'error_msg'  => 'Network error.'
                ];
            }

        } catch (\Exception $ex) {

            return [
                'error_code' => 'EX-' . $ex->getCode(),
                'error_msg' => $ex->getMessage()
            ];

        }
    }

    public static function rtr($trans_id, $pid, $mdn, $amt, $att_tid2 = '') {
        try {
            if (getenv('APP_ENV') != 'production') {
                return [
                    'error_code' => '',
                    'error_msg'  => '',
                    'tx_id'      => time()
                ];
            }

            self::init();

            $req = '<GSSRequest>';
            $req .=     '<CustomerInfo>';
            $req .=         '<AccountName>' . self::$api_user . '</AccountName>';
            $req .=         '<Password>' . self::$api_pwd . '</Password>';
            $req .=     '</CustomerInfo>';
            $req .=     '<RequestType>Reload</RequestType>';
            $req .=     '<CustRef>' . $trans_id . '</CustRef>';
            $req .=     '<ProductID>' . $pid . '</ProductID>'; 
            $req .=     '<PhoneNum>' . $mdn . '</PhoneNum>';
            $req .=     '<Amount>' . $amt . '</Amount>';
            if($att_tid2 != '') {
                $req .= '<TID>' . $att_tid2 . '</TID>';
            }
            $req .= '</GSSRequest>';

            $s = fsockopen(self::$api_url, self::$api_port);
            if ($s) {
                fputs($s, $req);
                $return = '';
                while (!feof($s)) {
                    $return .= fgets($s);
                }
                fclose($s);

                // Log::info('GSS Rec: '.json_encode($return, JSON_PRETTY_PRINT));

                // xml special characters escape
                $return = str_replace(array('&'), array('&amp;'), $return);
                $ret = simplexml_load_string($return, null, LIBXML_NOCDATA);
                Helper::log('### GSS: Reload() ###', [
                    'req' => $req,
                    'res' => $ret
                ]);

                $result_code = $ret->{'Result-Code'};

                if ($result_code == 0) {
                    return [
                        'error_code' => '',
                        'error_msg'  => '',
                        'tx_id'      => $ret->TransactionID
                    ];
                } else {
                    return [
                        'error_code' => $result_code,
                        'error_msg'  => $ret->{'Result-Description'}
                    ];
                }

            } else {
                Helper::log('### GSS::Reload: Network error. ###', [
                    'req' => $req,
                    'res' => ['Result-Code' => -1, 'Result-Description' => 'Network Error']
                ]);

                return [
                    'error_code' => -1,
                    'error_msg'  => 'Network error.',
                ];
            }

        } catch (\Exception $ex) {

            return [
                'error_code' => 'EX-' . $ex->getCode(),
                'error_msg' => $ex->getMessage()
            ];

        }
    }

    public static function rtr_boost($trans_id, $pid, $mdn, $amt, $pin) {
        try {
            if (getenv('APP_ENV') != 'production') {
                return [
                    'error_code' => '',
                    'error_msg'  => '',
                    'tx_id'      => time()
                ];
            }

            self::init();

            $req = '<GSSRequest>';
            $req .=     '<CustomerInfo>';
            $req .=         '<AccountName>' . self::$api_user . '</AccountName>';
            $req .=         '<Password>' . self::$api_pwd . '</Password>';
            $req .=     '</CustomerInfo>';
            $req .=     '<RequestType>Reload</RequestType>';
            $req .=     '<CustRef>' . $trans_id . '</CustRef>';
            $req .=     '<ProductID>' . $pid . '</ProductID>';
            $req .=     '<PhoneNum>' . $mdn . '</PhoneNum>';
            $req .=     '<Amount>' . $amt . '</Amount>';
            $req .=     '<PIN>' . $pin . '</PIN>';
            $req .= '</GSSRequest>';

            $s = fsockopen(self::$api_url, self::$api_port);
            if ($s) {
                fputs($s, $req);
                $return = '';
                while (!feof($s)) {
                    $return .= fgets($s);
                }
                fclose($s);

                // Log::info('GSS Rec: '.json_encode($return, JSON_PRETTY_PRINT));

                // xml special characters escape
                $return = str_replace(array('&'), array('&amp;'), $return);
                $ret = simplexml_load_string($return, null, LIBXML_NOCDATA);
                Helper::log('### GSS: Reload_Boost() ###', [
                    'req' => $req,
                    'res' => $ret
                ]);

                $result_code = $ret->{'Result-Code'};

                if ($result_code == 0) {
                    return [
                        'error_code' => '',
                        'error_msg'  => '',
                        'tx_id'      => $ret->TransactionID
                    ];
                } else {
                    return [
                        'error_code' => $result_code,
                        'error_msg'  => $ret->{'Result-Description'}
                    ];
                }

            } else {
                Helper::log('### GSS::Reload: Network error. ###', [
                    'req' => $req,
                    'res' => ['Result-Code' => -1, 'Result-Description' => 'Network Error']
                ]);

                return [
                    'error_code' => -1,
                    'error_msg'  => 'Network error.',
                ];
            }

        } catch (\Exception $ex) {

            return [
                'error_code' => 'EX-' . $ex->getCode(),
                'error_msg' => $ex->getMessage()
            ];

        }
    }

    public static function ActivatePhone($trans_id, $pid, $sim, $imei, $zip, $area_code, $tid) {
        try {
            if (getenv('APP_ENV') !== 'production') {
                return [
                    'error_code' => '',
                    'error_msg'  => 'DEMO ACTIVATION',
                    'mdn'        => '1112223333'
                ];
            }

            self::init();

            $req = '<GSSRequest>';
            $req .=     '<CustomerInfo>';
            $req .=         '<AccountName>' . self::$api_user . '</AccountName>';
            $req .=         '<Password>' . self::$api_pwd . '</Password>';
            $req .=     '</CustomerInfo>';
            $req .=     '<RequestType>ActivatePhone</RequestType>';
            $req .=     '<CustRef>' . $trans_id . '</CustRef>';
            $req .=     '<ProductID>' . $pid . '</ProductID>';
            $req .=     '<SIM>' . $sim . '</SIM>';
            $req .=     '<IMEI>' . $imei . '</IMEI>';
            $req .=     '<CustomerZip>' . $zip . '</CustomerZip>';
            $req .=     '<AreaCode>' . $area_code . '</AreaCode>';
            $req .=     '<TID>' . $tid .'</TID>';
            $req .= '</GSSRequest>';

            $s = fsockopen(self::$api_url, self::$api_port);
            if ($s) {
                fputs($s, $req);
                $return = '';
                while (!feof($s)) {
                    $return .= fgets($s);
                }
                fclose($s);

                // Log::info('GSS Rec: '.json_encode($return, JSON_PRETTY_PRINT));
                // xml special characters escape
                $return = str_replace(array('&'), array('&amp;'), $return);
                $ret = simplexml_load_string($return, null, LIBXML_NOCDATA);

                Helper::log('### GSS: ActivatePhone() ###', [
                    'req' => $req,
                    'res' => $ret
                ]);

                // $ret = json_decode(json_encode($ret), TRUE);
                // $ret = (object) $ret;

                // Helper::log('### GSS: ProductInfo() ###', [
                //     'req' => $req,
                //     'res' => $ret->Category->Type
                // ]);

                $result_code = $ret->{'Result-Code'};

                if ($result_code == 0) {
                    return [
                        'error_code' => '',
                        'error_msg'  => $ret->{'Result-Description'},
                        'mdn'        => $ret->PhoneNumber
                    ];
                } else {
                    return [
                        'error_code' => $result_code,
                        'error_msg'  => $ret->{'Result-Description'}
                    ];
                }

            } else {
                Helper::log('### GSS::ActivatePhone: Network error. ###', [
                    'req' => $req,
                    'res' => ['Result-Code' => -1, 'Result-Description' => 'Network Error']
                ]);

                return [
                    'error_code' => -1,
                    'error_msg'  => 'Network error.',
                    'products'   => null
                ];
            }

        } catch (\Exception $ex) {

            return [
                'error_code' => 'EX-' . $ex->getCode(),
                'error_msg'  => $ex->getMessage(),
                'products'   => null
            ];

        }
    }

    public static function PortPhone($trans_id, $mdn, $pid, $sim, $imei, $zip, $area_code, $tid, $first_name, $last_name, $city, $state, $street_number, $street_name, $account_no, $pin) {
        try {

            self::init();

            if (!empty($street_number)) {
                $stmns = explode(' ', trim($street_number));
                if (count($stmns) > 0) {
                    $street_number = $stmns[0];
                }
            }

            $req = '<GSSRequest>';
            $req .=     '<CustomerInfo>';
            $req .=         '<AccountName>' . self::$api_user . '</AccountName>';
            $req .=         '<Password>' . self::$api_pwd . '</Password>';
            $req .=     '</CustomerInfo>';
            $req .=     '<RequestType>PortPhone</RequestType>';
            $req .=     '<PhoneNum>' . $mdn . '</PhoneNum>';
            $req .=     '<CustRef>' . $trans_id . '</CustRef>';
            $req .=     '<ProductID>' . $pid . '</ProductID>';
            $req .=     '<AreaCode>' . $area_code . '</AreaCode>';
            $req .=     '<CustomerZip>' . $zip . '</CustomerZip>';
            $req .=     '<TID>' . $tid .'</TID>';
            $req .=     '<SIM>' . $sim . '</SIM>';
            $req .=     '<FirstName>' . $first_name . '</FirstName>';
            $req .=     '<LastName>' . $last_name . '</LastName>';
            $req .=     '<City>' . $city . '</City>';
            $req .=     '<State>' . $state . '</State>';
            $req .=     '<StreetNumber>' . $street_number . '</StreetNumber>';
            $req .=     '<StreetName>' . $street_name . '</StreetName>';
            $req .=     '<BillingAccountNumber>' . $account_no . '</BillingAccountNumber>';
            $req .=     '<BillingAccountPassword>' . $pin . '</BillingAccountPassword>';
            $req .= '</GSSRequest>';

            $s = fsockopen(self::$api_url, self::$api_port);
            if ($s) {
                fputs($s, $req);
                $return = '';
                while (!feof($s)) {
                    $return .= fgets($s);
                }
                fclose($s);

                // Log::info('GSS Rec: '.json_encode($return, JSON_PRETTY_PRINT));
                Helper::log('### GSS PortPhone Response: ', $return);

                $return = str_replace(array('&'), array('&amp;'), $return);
                $ret = simplexml_load_string($return, null, LIBXML_NOCDATA);

                Helper::log('### GSS: PortPhone() ###', [
                    'req' => $req,
                    'res' => $ret
                ]);

                $result_code = $ret->{'Result-Code'};

                if ($result_code == 0) {
                    return [
                        'error_code' => '',
                        'error_msg'  => $ret->{'Result-Description'},
                        'req_number' => $ret->PortRequestNumber
                    ];
                } else {
                    return [
                        'error_code' => $result_code,
                        'error_msg'  => $ret->{'Result-Description'}
                    ];
                }

            } else {
                Helper::log('### GSS::PortPhone: Network error. ###', [
                    'req' => $req,
                    'res' => ['Result-Code' => -1, 'Result-Description' => 'Network Error']
                ]);

                return [
                    'error_code' => -1,
                    'error_msg'  => 'Network error.',
                    'products'   => null
                ];
            }

        } catch (\Exception $ex) {

            return [
                'error_code' => 'EX-' . $ex->getCode(),
                'error_msg'  => $ex->getMessage(),
                'products'   => null
            ];

        }
    }

    public static function port_status($trans_id, $pid, $req_number, $mdn) {
        try {

            self::init();

            $req = '<GSSRequest>';
            $req .=     '<CustomerInfo>';
            $req .=         '<AccountName>' . self::$api_user . '</AccountName>';
            $req .=         '<Password>' . self::$api_pwd . '</Password>';
            $req .=     '</CustomerInfo>';
            $req .=     '<RequestType>InquirePort</RequestType>';
            $req .=     '<CustRef>' . $trans_id . '</CustRef>';
            $req .=     '<ProductID>' . $pid . '</ProductID>';
            $req .=     '<PortRequestNumber>' . $req_number . '</PortRequestNumber>';
            $req .=     '<PhoneNum>' . $mdn . '</PhoneNum>';
            $req .= '</GSSRequest>';

            $s = fsockopen(self::$api_url, self::$api_port);
            if ($s) {
                fputs($s, $req);
                $return = '';
                while (!feof($s)) {
                    $return .= fgets($s);
                }
                fclose($s);

                // Log::info('GSS Rec: '.json_encode($return, JSON_PRETTY_PRINT));

                // xml special characters escape
                $return = str_replace(array('&'), array('&amp;'), $return);
                $ret = simplexml_load_string($return, null, LIBXML_NOCDATA);

                Helper::log('### GSS: InquirePort() ###', [
                    'req' => $req,
                    'res' => $ret
                ]);

                $result_code = $ret->{'Result-Code'};
                $status = !empty($ret->{'Port-Status'}) ? $ret->{'Port-Status'} : '';

                if ($result_code == 0) {
                    ### status ('Complete', 'Conflict', 'Failed')
                    return [
                        'error_code' => '',
                        'error_msg'  => $ret->{'Result-Description'},
                        'status'     => $status
                    ];
                } else {
                    return [
                        'error_code' => $result_code,
                        'error_msg'  => $ret->{'Result-Description'},
                        'status'     => $status
                    ];
                }

            } else {
                Helper::log('### GSS::InquirePort: Network error. ###', [
                    'req' => $req,
                    'res' => ['Result-Code' => -1, 'Result-Description' => 'Network Error']
                ]);

                return [
                    'error_code' => -1,
                    'error_msg'  => 'Network error.',
                    'status'   => 'Error'
                ];
            }

        } catch (\Exception $ex) {

            return [
                'error_code' => 'EX-' . $ex->getCode(),
                'error_msg'  => $ex->getMessage(),
                'status'   => 'Exception'
            ];

        }
    }

    public static function UpdatePort($pid, $req_number, $mdn, $first_name, $last_name, $street_number, $street_name, $city, $state, $zip, $account_no, $pin) {
        try {

            self::init();

            if (!empty($street_number)) {
                $stmns = explode(' ', trim($street_number));
                if (count($stmns) > 0) {
                    $street_number = $stmns[0];
                }
            }

            $req = '<GSSRequest>';
            $req .=     '<CustomerInfo>';
            $req .=         '<AccountName>' . self::$api_user . '</AccountName>';
            $req .=         '<Password>' . self::$api_pwd . '</Password>';
            $req .=     '</CustomerInfo>';
            $req .=     '<RequestType>UpdatePort</RequestType>';
            $req .=     '<ProductID>' . $pid . '</ProductID>';
            $req .=     '<PortRequestNumber>' . $req_number . '</PortRequestNumber>';
            $req .=     '<PhoneNum>' . $mdn . '</PhoneNum>';
            $req .=     '<FirstName>' . $first_name . '</FirstName>';
            $req .=     '<LastName>' . $last_name . '</LastName>';
            $req .=     '<StreetNumber>' . $street_number . '</StreetNumber>';
            $req .=     '<StreetName>' . $street_name . '</StreetName>';
            $req .=     '<City>' . $city . '</City>';
            $req .=     '<State>' . $state . '</State>';
            $req .=     '<ZIP>' . $zip . '</ZIP>';
            $req .=     '<BillingAccountNumber>' . $account_no . '</BillingAccountNumber>';
            $req .=     '<BillingAccountPassword>' . $pin . '</BillingAccountPassword>';
            $req .= '</GSSRequest>';

            $s = fsockopen(self::$api_url, self::$api_port);
            if ($s) {
                fputs($s, $req);
                $return = '';
                while (!feof($s)) {
                    $return .= fgets($s);
                }
                fclose($s);

                // Log::info('GSS Rec: '.json_encode($return, JSON_PRETTY_PRINT));

                // xml special characters escape
                $return = str_replace(array('&'), array('&amp;'), $return);
                $ret = simplexml_load_string($return, null, LIBXML_NOCDATA);

                Helper::log('### GSS: UpdatePort() ###', [
                    'req' => $req,
                    'res' => $ret
                ]);

                // $ret = json_decode(json_encode($ret), TRUE);
                // $ret = (object) $ret;

                // Helper::log('### GSS: ProductInfo() ###', [
                //     'req' => $req,
                //     'res' => $ret->Category->Type
                // ]);

                $result_code = $ret->{'Result-Code'};

                if ($result_code == 0) {
                    return [
                        'error_code' => '',
                        'error_msg'  => $ret->{'Result-Description'}
                    ];
                } else {
                    return [
                        'error_code' => $result_code,
                        'error_msg'  => $ret->{'Result-Description'}
                    ];
                }

            } else {
                Helper::log('### GSS::UpdatePort: Network error. ###', [
                    'req' => $req,
                    'res' => ['Result-Code' => -1, 'Result-Description' => 'Network Error']
                ]);

                return [
                    'error_code' => -1,
                    'error_msg'  => 'Network error.'
                ];
            }

        } catch (\Exception $ex) {

            return [
                'error_code' => 'EXP-' . $ex->getCode(),
                'error_msg'  => $ex->getMessage()
            ];

        }
    }

    public static function SwapEquipment($trans_id, $pid, $phone, $sim, $imei, $tid) {
        try {

            self::init();

            $req = '<GSSRequest>';
            $req .=     '<CustomerInfo>';
            $req .=         '<AccountName>' . self::$api_user . '</AccountName>';
            $req .=         '<Password>' . self::$api_pwd . '</Password>';
            $req .=     '</CustomerInfo>';
            $req .=     '<RequestType>SwapEquipment</RequestType>';
            $req .=     '<CustRef>' . $trans_id . '</CustRef>';
            $req .=     '<ProductID>' . $pid . '</ProductID>';
            $req .=     '<PhoneNum>' . $phone . '</PhoneNum>';
            $req .=     '<SIM>' . $sim . '</SIM>';
            $req .=     '<IMEI>' . $imei . '</IMEI>';
            $req .=     '<TID>' . $tid .'</TID>';
            $req .= '</GSSRequest>';

            Helper::log('### GSS: SwapEquipment() ###', [
                'req' => $req
            ]);

            $s = fsockopen(self::$api_url, self::$api_port);
            if ($s) {
                Helper::log('### GSS: SwapEquipment() ### CONNECTED ###');

                fputs($s, $req);
                $return = '';
                while (!feof($s)) {
                    $return .= fgets($s);
                }
                fclose($s);

                // Log::info('GSS Rec: '.json_encode($return, JSON_PRETTY_PRINT));

                // xml special characters escape
                $return = str_replace(array('&'), array('&amp;'), $return);
                $ret = simplexml_load_string($return, null, LIBXML_NOCDATA);

                Helper::log('### GSS: SwapEquipment() ###', [
                    'res' => $ret
                ]);

                $result_code = $ret->{'Result-Code'};

                if ($result_code == 0) {
                    return [
                        'error_code' => '',
                        'error_msg'  => $ret->{'Result-Description'}
                    ];
                } else {
                    return [
                        'error_code' => $result_code,
                        'error_msg'  => $ret->{'Result-Description'}
                    ];
                }

            } else {
                Helper::log('### GSS::SwapEquipment: Network error. ###', [
                    'req' => $req,
                    'res' => ['Result-Code' => -1, 'Result-Description' => 'Network Error']
                ]);

                return [
                    'error_code' => -1,
                    'error_msg'  => 'Network error.'
                ];
            }

        } catch (\Exception $ex) {

            return [
                'error_code' => 'EX-' . $ex->getCode(),
                'error_msg'  => $ex->getMessage()
            ];

        }
    }

    public static function UpgradePlan($trans_id, $pid, $phone, $tid) {
        try {

            self::init();

            $req = '<GSSRequest>';
            $req .=     '<CustomerInfo>';
            $req .=         '<AccountName>' . self::$api_user . '</AccountName>';
            $req .=         '<Password>' . self::$api_pwd . '</Password>';
            $req .=     '</CustomerInfo>';
            $req .=     '<RequestType>UpgradePlan</RequestType>';
            $req .=     '<CustRef>' . $trans_id . '</CustRef>';
            $req .=     '<ProductID>' . $pid . '</ProductID>';
            $req .=     '<PhoneNum>' . $phone . '</PhoneNum>';
            $req .=     '<TID>' . $tid .'</TID>';
            $req .= '</GSSRequest>';

            Helper::log('### GSS: UpgradePlan() ###', [
                'req' => $req
            ]);

            $s = fsockopen(self::$api_url, self::$api_port);
            if ($s) {
                Helper::log('### GSS: UpgradePlan() ### CONNECTED ###');

                fputs($s, $req);
                $return = '';
                while (!feof($s)) {
                    $return .= fgets($s);
                }
                fclose($s);

                $rcode = self::getTextBetweenTags($return, 'Result-Code');
                $rdesc = self::getTextBetweenTags($return, 'Result-Description');

                $rdesc = str_replace('<', '', $rdesc);
                $rdesc = str_replace('>', '', $rdesc);

                Helper::log('### GSS: UpgradePlan() ### RETURN ###', [
                    'return' => $return,
                    'Result-Code' => $rcode,
                    'Result-Description' => $rdesc
                ]);

                if ($rcode == 0) {
                    return [
                        'error_code' => '',
                        'error_msg'  => $rdesc
                    ];
                } else {
                    return [
                        'error_code' => $rcode,
                        'error_msg'  => $rdesc
                    ];
                }

            } else {
                Helper::log('### GSS::UpgradePlan: Network error. ###', [
                    'req' => $req,
                    'res' => ['Result-Code' => -1, 'Result-Description' => 'Network Error']
                ]);

                return [
                    'error_code' => -1,
                    'error_msg'  => 'Network error.'
                ];
            }

        } catch (\Exception $ex) {
            Helper::log('### GSS: UpgradePlan() ### EXCEPTION ###', [
                'exception' => $ex->getMessage()
            ]);

            return [
                'error_code' => 'EX-' . $ex->getCode(),
                'error_msg'  => $ex->getMessage()
            ];

        }
    }

    public static function UpgradeFeature($trans_id, $pid, $phone, $tid) {
        try {

            self::init();

            $req = '<GSSRequest>';
            $req .=     '<CustomerInfo>';
            $req .=         '<AccountName>' . self::$api_user . '</AccountName>';
            $req .=         '<Password>' . self::$api_pwd . '</Password>';
            $req .=     '</CustomerInfo>';
            $req .=     '<RequestType>UpgradeFeature</RequestType>';
            $req .=     '<CustRef>' . $trans_id . '</CustRef>';
            $req .=     '<ProductID>' . $pid . '</ProductID>';
            $req .=     '<PhoneNum>' . $phone . '</PhoneNum>';
            $req .=     '<TID>' . $tid .'</TID>';
            $req .= '</GSSRequest>';

            Helper::log('### GSS: UpgradeFeature() ###', [
                'req' => $req
            ]);

            $s = fsockopen(self::$api_url, self::$api_port);
            if ($s) {
                Helper::log('### GSS: UpgradeFeature() ### CONNECTED ###');

                fputs($s, $req);
                $return = '';
                while (!feof($s)) {
                    $return .= fgets($s);
                }
                fclose($s);

                $rcode = self::getTextBetweenTags($return, 'Result-Code');
                $rdesc = self::getTextBetweenTags($return, 'Result-Description');

                $rdesc = str_replace('<', '', $rdesc);
                $rdesc = str_replace('>', '', $rdesc);

                Helper::log('### GSS: UpgradeFeature() ### RETURN ###', [
                    'return' => $return,
                    'Result-Code' => $rcode,
                    'Result-Description' => $rdesc
                ]);

                if ($rcode == 0) {
                    return [
                        'error_code' => '',
                        'error_msg'  => ''
                    ];
                } else {
                    return [
                        'error_code' => $rcode,
                        'error_msg'  => $rdesc
                    ];
                }

            } else {
                Helper::log('### GSS::UpgradeFeature: Network error. ###', [
                    'req' => $req,
                    'res' => ['Result-Code' => -1, 'Result-Description' => 'Network Error']
                ]);

                return [
                    'error_code' => -1,
                    'error_msg'  => 'Network error.'
                ];
            }

        } catch (\Exception $ex) {

            return [
                'error_code' => 'EX-' . $ex->getCode(),
                'error_msg'  => $ex->getMessage()
            ];

        }
    }

    public static function getTextBetweenTags($string, $tagname) {
        $pattern = "/<$tagname>([\w\W]*?)<\/$tagname>/";
        preg_match($pattern, $string, $matches);
        return $matches[1];
    }

}