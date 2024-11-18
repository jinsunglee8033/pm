<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 9/25/17
 * Time: 11:55 AM
 */

namespace App\Lib;


use Carbon\Carbon;

class epay
{

    private static $api_url = 'https://et-up.epayworldwide.com/up';
    private static $uid = '';
    private static $pwd = '';
    private static $tid = '';

    private static function init() {
        if (getenv('APP_ENV') === 'production') {
            self::$api_url = 'https://up.epayworldwide.com/up';
            self::$uid = '';
            self::$pwd = '';
            self::$tid = '';
        }
    }

    public static function rtr($cid, $sku, $mdn, $amt, $fee) {

        try {

            self::init();

            $local_time = Carbon::now()->format('Y-m-d H:i:s');

            $req = '<REQUEST TYPE="SALE">';
            $req .= '<TERMINALID>' . self::$tid . '</TERMINALID>';
            $req .= '<REQUESTTYPE>SALE</REQUESTTYPE>';
            $req .= '<AUTHORIZATION>';
            $req .= '<USERNAME>' . self::$uid . '</USERNAME>';
            $req .= '<PASSWORD>' . self::$pwd . '</PASSWORD>';
            $req .= '</AUTHORIZATION>';
            $req .= '<LOCALDATETIME>' . $local_time . '</LOCALDATETIME>';
            $req .= '<MSGNO>0</MSGNO>';
            $req .= '<TXID>' . $cid . '</TXID>';
            $req .= '<AMOUNT>' . number_format(($amt + $fee) * 100, 0, '.', '') . '</AMOUNT>';
            $req .= '<SERVICEFEE>'  . number_format($fee * 100, 0, '.', '') . '</SERVICEFEE>';
            $req .= '<SEQUENCE>0</SEQUENCE>';
            $req .= '<PRODUCTID>' . $sku . '</PRODUCTID>';
            $req .= '<CUSTOMDATA>';
            $req .= '<ITEM>';
            $req .= '<VALUE KEY="PROMPT1">' . $mdn . '</VALUE>';
            $req .= '</ITEM>';
            $req .= '</CUSTOMDATA>';
            $req .= '</REQUEST>';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
            //curl_setopt($ch, CURLOPT_HTTPHEADER, array('SOAPAction', 'MySoapAction'));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            Helper::log('### rtr() detail ###', [
                'req' => $req,
                'res' => $output
            ]);

            $curl_error_code = curl_errno($ch);
            $curl_error_msg = curl_error($ch);

            if ($curl_error_code) {

                Helper::log('### CURL_ERROR ##', $curl_error_code);

                $void_vendor_tx_id = '';
                if ($curl_error_code == CURLE_OPERATION_TIMEDOUT) {
                    Helper::log('### Timed Out ###', 'Auto-Cancelling');

                    ### timeout occurred - need cancel request auto ###
                    for ($i = 0; $i < 5; $i++) {
                        $ret = self::rtr_cancel($cid, $sku, $mdn, $amt, $fee, $local_time);
                        if (empty($ret['error_msg'])) {
                            $void_vendor_tx_id = $ret['vendor_tx_id'];
                            break;
                        }
                    }
                }

                curl_close($ch);

                ### timeout occurred - need cancel request auto ###
                return [
                    'error_code' => $curl_error_code,
                    'error_msg' => 'curl error : ' . $curl_error_msg . ' - auto void done.',
                    'void_vendor_tx_id' => $void_vendor_tx_id
                ];
            }

            curl_close($ch);



            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);
            if ($ret->RESULT != 0) {
                return [
                    'error_code' => $ret->RESULT,
                    'error_msg' => $ret->RESULTTEXT
                ];
            }

            $vendor_tx_id = $ret->HOSTTXID;
            if (empty($vendor_tx_id)) {
                return [
                    'error_code' => -2,
                    'error_msg' => 'Vendor returned empty host transaction ID'
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'vendor_tx_id' => $vendor_tx_id
            ];

        } catch (\Exception $ex) {

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage()
            ];

        }
    }

    public static function rtr_cancel($cid, $sku, $mdn, $amt, $fee, $local_time) {
        try {

            self::init();

            $req = '<REQUEST TYPE="CANCEL">';
            $req .= '<TERMINALID>' . self::$tid . '</TERMINALID>';
            $req .= '<REQUESTTYPE>CANCEL</REQUESTTYPE>';
            $req .= '<AUTHORIZATION>';
            $req .= '<USERNAME>' . self::$uid . '</USERNAME>';
            $req .= '<PASSWORD>' . self::$pwd . '</PASSWORD>';
            $req .= '</AUTHORIZATION>';
            $req .= '<LOCALDATETIME>' . $local_time . '</LOCALDATETIME>';
            $req .= '<MSGNO>0</MSGNO>';
            $req .= '<TXID>' . $cid . '</TXID>';
            $req .= '<AMOUNT>' . number_format(($amt + $fee) * 100, 0, '.', '') . '</AMOUNT>';
            $req .= '<SERVICEFEE>'  . number_format($fee * 100, 0, '.', '') . '</SERVICEFEE>';
            $req .= '<SEQUENCE>0</SEQUENCE>';
            $req .= '<PRODUCTID>' . $sku . '</PRODUCTID>';
            $req .= '<CUSTOMDATA>';
            $req .= '<ITEM>';
            $req .= '<VALUE KEY="PROMPT1">' . $mdn . '</VALUE>';
            $req .= '</ITEM>';
            $req .= '</CUSTOMDATA>';
            $req .= '</REQUEST>';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
            //curl_setopt($ch, CURLOPT_HTTPHEADER, array('SOAPAction', 'MySoapAction'));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            Helper::log('### rtr_cancel() detail ###', [
                'req' => $req,
                'res' => $output
            ]);

            if (curl_error($ch)) {
                curl_close($ch);

                ### timeout occurred - need cancel request auto ###
                return [
                    'error_code' => curl_error($ch),
                    'error_msg' => 'curl error : ' . curl_error($ch)
                ];
            }

            curl_close($ch);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);
            if ($ret->RESULT != 0) {
                return [
                    'error_code' => $ret->RESULT,
                    'error_msg' => $ret->RESULTTEXT
                ];
            }

            $vendor_tx_id = $ret->HOSTTXID;
            if (empty($vendor_tx_id)) {
                return [
                    'error_code' => -2,
                    'error_msg' => 'Vendor returned empty host transaction ID'
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'vendor_tx_id' => $vendor_tx_id
            ];

        } catch (\Exception $ex) {

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage()
            ];

        }
    }

    public static function get_pin($cid, $sku, $amt, $fee) {

        try {

            self::init();

            $local_time = Carbon::now()->format('Y-m-d H:i:s');

            $req = '<REQUEST TYPE="SALE">';
            $req .= '<TERMINALID>' . self::$tid . '</TERMINALID>';
            $req .= '<REQUESTTYPE>SALE</REQUESTTYPE>';
            $req .= '<AUTHORIZATION>';
            $req .= '<USERNAME>' . self::$uid . '</USERNAME>';
            $req .= '<PASSWORD>' . self::$pwd .'</PASSWORD>';
            $req .= '</AUTHORIZATION>';
            $req .= '<LOCALDATETIME>' . $local_time . '</LOCALDATETIME>';
            $req .= '<MSGNO>0</MSGNO>';
            $req .= '<TXID>' . $cid . '</TXID>';
            $req .= '<AMOUNT>' . number_format(($amt + $fee)* 100, 0, '.', '') . '</AMOUNT>';
            $req .= '<SERVICEFEE>' . number_format($fee* 100, 0, '.', '') .  '</SERVICEFEE>';
            $req .= '<SEQUENCE>0</SEQUENCE>';
            $req .= '<PRODUCTID>' . $sku . '</PRODUCTID>';
            $req .= '</REQUEST>';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
            //curl_setopt($ch, CURLOPT_HTTPHEADER, array('SOAPAction', 'MySoapAction'));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            Helper::log('### get_pin() detail ###', [
                'req' => $req,
                'res' => $output
            ]);

            $curl_error_code = curl_errno($ch);
            $curl_error_msg = curl_error($ch);

            if ($curl_error_code) {

                Helper::log('### CURL_ERROR ##', $curl_error_code);

                $void_vendor_tx_id = '';
                if ($curl_error_code == CURLE_OPERATION_TIMEDOUT) {
                    Helper::log('### Timed Out ###', 'Auto-Cancelling');

                    ### timeout occurred - need cancel request auto ###
                    for ($i = 0; $i < 5; $i++) {
                        $ret = self::pin_cancel($cid, $sku, $amt, $fee, $local_time);
                        if (empty($ret['error_msg'])) {
                            $void_vendor_tx_id = $ret['vendor_tx_id'];
                            break;
                        }
                    }
                }

                curl_close($ch);

                ### timeout occurred - need cancel request auto ###
                return [
                    'error_code' => $curl_error_code,
                    'error_msg' => 'curl error : ' . $curl_error_msg . ' - auto void done.',
                    'void_vendor_tx_id' => $void_vendor_tx_id
                ];
            }



            curl_close($ch);



            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);
            if ($ret->RESULT != 0) {
                return [
                    'error_code' => $ret->RESULT,
                    'error_msg' => $ret->RESULTTEXT
                ];
            }

            $vendor_tx_id = $ret->HOSTTXID;
            if (empty($vendor_tx_id)) {
                return [
                    'error_code' => -2,
                    'error_msg' => 'Vendor returned empty host transaction ID'
                ];
            }

            $pin_info = $ret->PINCREDENTIALS;
            if (empty($pin_info)) {
                return [
                    'error_code' => -3,
                    'error_msg' => 'Vendor returned empty PIN structure'
                ];
            }

            $pin = $pin_info->PIN;
            if (empty($pin)) {
                return [
                    'error_code' => -4,
                    'error_msg' => 'Vendor returned empty PIN'
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'vendor_tx_id' => $vendor_tx_id,
                'pin' => $pin
            ];

        } catch (\Exception $ex) {

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage()
            ];

        }
    }

    public static function pin_cancel($cid, $sku, $amt, $fee, $local_time) {

        try {

            self::init();

            $req = '<REQUEST TYPE="CANCEL">';
            $req .= '<TERMINALID>' . self::$tid . '</TERMINALID>';
            $req .= '<REQUESTTYPE>CANCEL</REQUESTTYPE>';
            $req .= '<AUTHORIZATION>';
            $req .= '<USERNAME>' . self::$uid . '</USERNAME>';
            $req .= '<PASSWORD>' . self::$pwd .'</PASSWORD>';
            $req .= '</AUTHORIZATION>';
            $req .= '<LOCALDATETIME>' . $local_time . '</LOCALDATETIME>';
            $req .= '<MSGNO>0</MSGNO>';
            $req .= '<TXID>' . $cid . '</TXID>';
            $req .= '<AMOUNT>' . number_format(($amt + $fee)* 100, 0, '.', '') . '</AMOUNT>';
            $req .= '<SERVICEFEE>' . number_format($fee* 100, 0, '.', '') .  '</SERVICEFEE>';
            $req .= '<SEQUENCE>0</SEQUENCE>';
            $req .= '<PRODUCTID>' . $sku . '</PRODUCTID>';
            $req .= '</REQUEST>';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
            //curl_setopt($ch, CURLOPT_HTTPHEADER, array('SOAPAction', 'MySoapAction'));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);

            Helper::log('### pin_cancel() detail ###', [
                'req' => $req,
                'res' => $output
            ]);

            if (curl_error($ch)) {
                curl_close($ch);
                return [
                    'error_code' => curl_error($ch),
                    'error_msg' => 'curl error : ' . curl_error($ch)
                ];
            }

            curl_close($ch);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);
            if ($ret->RESULT != 0) {
                return [
                    'error_code' => $ret->RESULT,
                    'error_msg' => $ret->RESULTTEXT
                ];
            }

            $vendor_tx_id = $ret->HOSTTXID;
            if (empty($vendor_tx_id)) {
                return [
                    'error_code' => -2,
                    'error_msg' => 'Vendor returned empty host transaction ID'
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'vendor_tx_id' => $vendor_tx_id,
            ];

        } catch (\Exception $ex) {

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage()
            ];

        }
    }

}