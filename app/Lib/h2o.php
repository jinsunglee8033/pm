<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 1/25/17
 * Time: 4:55 PM
 */

namespace App\Lib;

use Log;
use App\Lib\Helper;
use App\Model\Account;
use App\Model\DealerCode;

class h2o
{
    public static $api_url = 'https://www.locusapi.com/pcs/af/dev.php';
    private static $dealer_code = '';
    private static $dealer_pwd = '';
    private static $user = '';
    private static $password = '';
    private static $key = '';
    private static $call_api_on_demo = false;

    public static function init() {

        Helper::log('APP_ENV: ' .getenv('APP_ENV'));

        if (getenv('APP_ENV') == 'production' || self::$call_api_on_demo) {
            self::$api_url = 'https://www.locusapi.com/pcs/af/';
            self::$key = '';
        }
    }

    public static function getAccountDetail($cid, $min) {
        try {
            self::init();

            if (getenv('APP_ENV') != 'production' && self::$call_api_on_demo) {
                return [
                    'error_code' => '',
                    'error_msg' => '',
                    'cid' => time(),
                    'serial' => time(),
                    'activation_status' => 'Success',
                    'min' => '5512024069',
                    'msid' => ''
                ];
            }

            $req = "<req>";

            $req .= "<action>GetAccountDetail</action>";
            $req .= "<key>" . self::$key . "</key>";
            $req .= "<user>" . self::$user . "</user>";
            $req .= "<pass>" . self::$password . "</pass>";
            $req .= "<cid>" . (getenv('APP_ENV') == 'production' ? 'P' : 'D'). '-' . $cid . "</cid>";

            $req .= "<data>";
            $req .= "<dc>" . self::$dealer_code . "</dc>";
            $req .= "<dp>" . self::$dealer_pwd . "</dp>";
            $req .= "<min>" . $min . "</esn>";
            $req .= "</data>";

            $req .= "</req>";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
            //curl_setopt($ch, CURLOPT_HTTPHEADER, array('SOAPAction', 'MySoapAction'));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            curl_close($ch);

            Helper::log('### getAccountDetail ###', [
                'req' => $req,
                'res' => $output
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);

            if ($ret->errcode != 0) {
                return [
                    'error_code' => $ret->errcode,
                    'error_msg' => $ret->error
                ];
            }

            $data = $ret->data;
            if (empty($data)) {
                Helper::send_mail('it@perfectmobileinc.com', '[H2O][' . getenv('APP_ENV') . '] Partial Success Message Found', $cid);

                return [
                    'error_code' => -1,
                    'error_msg' => 'Partial success message returned'
                ];
            }

            if (!isset($data->activationstatus) || $data->activationstatus != 'Success') {
                Helper::send_mail('it@perfectmobileinc.com', '[H2O][' . getenv('APP_ENV') . '] Empty or non-success status returned from vendor', $cid);

                return [
                    'error_code' => -1,
                    'error_msg' => 'Empty or non-success activation status returned : ' . $data->activationstatus
                ];
            }

            if (!isset($data->min) || empty($data->min)) {
                Helper::send_mail('it@perfectmobileinc.com', '[H2O][' . getenv('APP_ENV') . '] Empty phone number returned from vendor', $cid);
                return [
                    'error_code' => -1,
                    'error_msg' => 'Empty phone number returned from vendor'
                ];
            }

            if (!isset($data->msid) || empty($data->msid)) {
                Helper::send_mail('it@perfectmobileinc.com', '[H2O][' . getenv('APP_ENV') . '] Empty MSID returned from vendor', $cid);
                return [
                    'error_code' => -1,
                    'error_msg' => 'Empty MSID returned from vendor'
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'cid' => $ret->cid,
                'serial' => $ret->serial,
                'activation_status' => $data->activationstatus,
                'min' => $data->min,
                'msid' => $data->msid
            ];


        } catch (\Exception $ex) {

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function activateCDMAesn($cid, $product, $esn, $npa, $city) {
        try {

            self::init();

            if (getenv('APP_ENV') != 'production' && !self::$call_api_on_demo) {
                return [
                    'error_code' => '',
                    'error_msg' => '',
                    'cid' => time(),
                    'serial' => time(),
                    'activation_status' => 'Success',
                    'min' => '5512024069',
                    'msid' => ''
                ];
            }

            $req = "<req>";

            $req .= "<action>ActivateCDMAesn</action>";
            $req .= "<key>" . self::$key . "</key>";
            $req .= "<user>" . self::$user . "</user>";
            $req .= "<pass>" . self::$password . "</pass>";
            $req .= "<cid>" . (getenv('APP_ENV') == 'production' ? 'P' : 'D'). '-' . $cid . "</cid>";
            $req .= "<product>" . $product . "</product>";

            $req .= "<data>";
            $req .= "<dc>" . self::$dealer_code . "</dc>";
            $req .= "<dp>" . self::$dealer_pwd . "</dp>";
            $req .= "<esn>" . $esn . "</esn>";
            $req .= "<npa>" . $npa . "</npa>";
            $req .= "<city>" . $city . "</city>";
            //$req .= "<zip>" . $zip . "</zip>";
            $req .= "</data>";

            $req .= "</req>";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
            //curl_setopt($ch, CURLOPT_HTTPHEADER, array('SOAPAction', 'MySoapAction'));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            curl_close($ch);

            Helper::log('### activateCDMAesn ###', [
                'req' => $req,
                'res' => $output
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);

            //return $ret;

            if ($ret->errcode != 0) {
                return [
                    'error_code' => $ret->errcode,
                    'error_msg' => $ret->error
                ];
            }

            $data = $ret->data;
            if (empty($data)) {
                Helper::send_mail('it@perfectmobileinc.com', '[H2O][' . getenv('APP_ENV') . '] Partial Success Message Found', $cid);

                return [
                    'error_code' => -1,
                    'error_msg' => 'Partial success message returned'
                ];
            }

            if (!isset($data->activationstatus) || $data->activationstatus != 'Success') {
                Helper::send_mail('it@perfectmobileinc.com', '[H2O][' . getenv('APP_ENV') . '] Empty or non-success status returned from vendor', $cid);

                return [
                    'error_code' => -1,
                    'error_msg' => 'Empty or non-success activation status returned : ' . $data->activationstatus
                ];
            }

            if (!isset($data->min) || empty($data->min)) {
                Helper::send_mail('it@perfectmobileinc.com', '[H2O][' . getenv('APP_ENV') . '] Empty phone number returned from vendor', $cid);
                return [
                    'error_code' => -1,
                    'error_msg' => 'Empty phone number returned from vendor'
                ];
            }

            if (!isset($data->msid) || empty($data->msid)) {
                Helper::send_mail('it@perfectmobileinc.com', '[H2O][' . getenv('APP_ENV') . '] Empty MSID returned from vendor', $cid);
                return [
                    'error_code' => -1,
                    'error_msg' => 'Empty MSID returned from vendor'
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'cid' => $ret->cid,
                'serial' => $ret->serial,
                'activation_status' => $data->activationstatus,
                'min' => $data->min,
                'msid' => $data->msid
            ];


        } catch (\Exception $ex) {

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function activateCDMAsim($cid, $product, $esn, $npa, $sim, $city) {
        try {

            self::init();

            if (getenv('APP_ENV') != 'production' && !self::$call_api_on_demo) {
                return [
                    'error_code' => '',
                    'error_msg' => '',
                    'cid' => time(),
                    'serial' => time(),
                    'activation_status' => 'Success',
                    'min' => '5512024069',
                    'msid' => ''
                ];
            }

            $req = "<req>";

            $req .= "<action>ActivateCDMAsim</action>";
            $req .= "<key>" . self::$key . "</key>";
            $req .= "<user>" . self::$user . "</user>";
            $req .= "<pass>" . self::$password . "</pass>";
            $req .= "<cid>" . (getenv('APP_ENV') == 'production' ? 'P' : 'D'). '-' . $cid . "</cid>";
            $req .= "<product>" . $product . "</product>";

            $req .= "<data>";
            $req .= "<dc>" . self::$dealer_code . "</dc>";
            $req .= "<dp>" . self::$dealer_pwd . "</dp>";
            $req .= "<esn>" . $esn . "</esn>";
            $req .= "<npa>" . $npa . "</npa>";
            $req .= "<sim>" . $sim . "</sim>";
            $req .= "<city>" . $city . "</city>";
            //$req .= "<zip>" . $zip . "</zip>";
            $req .= "</data>";

            $req .= "</req>";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
            //curl_setopt($ch, CURLOPT_HTTPHEADER, array('SOAPAction', 'MySoapAction'));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            curl_close($ch);

            Helper::log('### activateCDMAesn ###', [
                'req' => $req,
                'res' => $output
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);

            //return $ret;

            if ($ret->errcode != 0) {
                return [
                    'error_code' => $ret->errcode,
                    'error_msg' => $ret->error
                ];
            }

            $data = $ret->data;
            if (empty($data)) {
                Helper::send_mail('it@perfectmobileinc.com', '[H2O][' . getenv('APP_ENV') . '] Partial Success Message Found', $cid);

                return [
                    'error_code' => -1,
                    'error_msg' => 'Partial success message returned'
                ];
            }

            if (!isset($data->activationstatus) || $data->activationstatus != 'Success') {
                Helper::send_mail('it@perfectmobileinc.com', '[H2O][' . getenv('APP_ENV') . '] Empty or non-success status returned from vendor', $cid);

                return [
                    'error_code' => -1,
                    'error_msg' => 'Empty or non-success activation status returned : ' . $data->activationstatus
                ];
            }

            if (!isset($data->min) || empty($data->min)) {
                Helper::send_mail('it@perfectmobileinc.com', '[H2O][' . getenv('APP_ENV') . '] Empty phone number returned from vendor', $cid);
                return [
                    'error_code' => -1,
                    'error_msg' => 'Empty phone number returned from vendor'
                ];
            }

            if (!isset($data->msid) || empty($data->msid)) {
                Helper::send_mail('it@perfectmobileinc.com', '[H2O][' . getenv('APP_ENV') . '] Empty MSID returned from vendor', $cid);
                return [
                    'error_code' => -1,
                    'error_msg' => 'Empty MSID returned from vendor'
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'cid' => $ret->cid,
                'serial' => $ret->serial,
                'activation_status' => $data->activationstatus,
                'min' => $data->min,
                'msid' => $data->msid
            ];


        } catch (\Exception $ex) {

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function activateGSMafcode($cid, $product, $afcode, $npa, $city) {
        try {

            self::init();

            if (getenv('APP_ENV') != 'production' && !self::$call_api_on_demo) {
                return [
                    'error_code' => '',
                    'error_msg' => '',
                    'cid' => time(),
                    'serial' => time(),
                    'activation_status' => 'Success',
                    'min' => '5512024069',
                    'msid' => ''
                ];
            }

            $req = "<req>";

            $req .= "<action>ActivateGSMafcode</action>";
            $req .= "<key>" . self::$key . "</key>";
            $req .= "<user>" . self::$user . "</user>";
            $req .= "<pass>" . self::$password . "</pass>";
            $req .= "<cid>" . (getenv('APP_ENV') == 'production' ? 'P' : 'D'). '-' . $cid . "</cid>";
            $req .= "<product>" . $product . "</product>";

            $req .= "<data>";
            $req .= "<afcode>" . $afcode . "</afcode>";
            $req .= "<dc>" . self::$dealer_code . "</dc>";
            $req .= "<dp>" . self::$dealer_pwd . "</dp>";
            $req .= "<npa>" . $npa . "</npa>";
            $req .= "<city>" . $city . "</city>";
            $req .= "<zip>07024</zip>";
            $req .= "</data>";

            $req .= "</req>";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
            //curl_setopt($ch, CURLOPT_HTTPHEADER, array('SOAPAction', 'MySoapAction'));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            curl_close($ch);

            Helper::log('### activateGSMafcode ###', [
                'req' => $req,
                'res' => $output
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);

            if ($ret->errcode != 0) {
                return [
                    'error_code' => $ret->errcode,
                    'error_msg' => $ret->error
                ];
            }

            $data = $ret->data;
            if (empty($data)) {
                Helper::send_mail('it@perfectmobileinc.com', '[H2O][' . getenv('APP_ENV') . '] Partial Success Message Found', $cid);

                return [
                    'error_code' => -1,
                    'error_msg' => 'Partial success message returned'
                ];
            }

            if (!isset($data->activationstatus) || $data->activationstatus != 'Success') {
                Helper::send_mail('it@perfectmobileinc.com', '[H2O][' . getenv('APP_ENV') . '] Empty or non-success status returned from vendor', $cid);

                return [
                    'error_code' => -1,
                    'error_msg' => 'Empty or non-success activation status returned : ' . $data->activationstatus
                ];
            }

            if (!isset($data->min) || empty($data->min)) {
                Helper::send_mail('it@perfectmobileinc.com', '[H2O][' . getenv('APP_ENV') . '] Empty phone number returned from vendor', $cid);
                return [
                    'error_code' => -1,
                    'error_msg' => 'Empty phone number returned from vendor'
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'cid' => $ret->cid,
                'serial' => $ret->serial,
                'activation_status' => $data->activationstatus,
                'min' => $data->min
            ];


        } catch (\Exception $ex) {

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function rotateDealerCode(Account $acct) {
        try {

            if (!empty($acct->dealer_code) && !empty($acct->dealer_password)) {
                return [
                    'msg' => '',
                    'dc' => $acct->dealer_code,
                    'dp' => $acct->dealer_password
                ];
            }

            ### find with state & zip first ###
            $code = DealerCode::where('state', $acct->state)
                ->where('zip', $acct->zip)
                ->where('valid', 'Y')
                ->inRandomOrder()
                ->first();

            if (!empty($code)) {
                Helper::log('### dealer code : state & zip ###', $code);
                return [
                    'msg' => '',
                    'dc' => $code->dealer_code,
                    'dp' => $code->dealer_pwd
                ];
            }

            ### find with state only next ###
            $code = DealerCode::where('state', $acct->state)
                ->where('valid', 'Y')
                ->inRandomOrder()
                ->first();

            if (!empty($code)) {
                Helper::log('### dealer code : state only ###', $code);
                return [
                    'msg' => '',
                    'dc' => $code->dealer_code,
                    'dp' => $code->dealer_pwd
                ];
            }

            ### if not found, go with default codes ###
            $default_codes = [
                ['dc' => '33301', 'dp' => '1234'],
                ['dc' => '81955', 'dp' => '1234'],
                ['dc' => '94869', 'dp' => '3173']
            ];

            $key = array_rand($default_codes, 1);
            $code = $default_codes[$key];
            Helper::log('### dealer code : default ###', $code);
            return [
                'msg' => '',
                'dc' => $code['dc'],
                'dp' => $code['dp']
            ];

        } catch (\Exception $ex) {
            return [
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ];
        }
    }

    public static function activateGSMSim($dc, $dp, $cid, $product, $sim, $npa, $zip) {
        try {

            self::init();

            if (getenv('APP_ENV') != 'production' && !self::$call_api_on_demo) {
                return [
                    'error_code' => '',
                    'error_msg' => '',
                    'cid' => time(),
                    'serial' => time(),
                    'activation_status' => 'Success',
                    'min' => '5512024069',
                    'msid' => ''
                ];
            }

            ### if empty rotate dealer code ###
            if (empty($dc) || empty($dp)) {

            }

            $req = "<req>";

            $req .= "<action>ActivateGSMsim</action>";
            $req .= "<key>" . self::$key . "</key>";
            $req .= "<user>" . self::$user . "</user>";
            $req .= "<pass>" . self::$password . "</pass>";
            $req .= "<cid>" . (getenv('APP_ENV') == 'production' ? 'P' : 'D'). '-' . $cid . "</cid>";
            $req .= "<product>" . $product . "</product>";

            $req .= "<data>";
            $req .= "<dc>" . $dc . "</dc>";
            $req .= "<dp>" . $dp . "</dp>";
            $req .= "<npa>" . $npa . "</npa>";
            $req .= "<sim>" . $sim . "</sim>";
            //$req .= "<city>" . $city . "</city>";
            $req .= "<zip>" . $zip . "</zip>";
            $req .= "</data>";

            $req .= "</req>";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
            //curl_setopt($ch, CURLOPT_HTTPHEADER, array('SOAPAction', 'MySoapAction'));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            curl_close($ch);

            Helper::log('### activateGSMSim ###', [
                'req' => $req,
                'res' => $output
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);

            if ($ret->errcode != 0) {
                return [
                    'error_code' => $ret->errcode,
                    'error_msg' => $ret->error
                ];
            }

            $data = $ret->data;
            if (empty($data)) {
                Helper::send_mail('it@perfectmobileinc.com', '[H2O][' . getenv('APP_ENV') . '] Partial Success Message Found', $cid);

                return [
                    'error_code' => -1,
                    'error_msg' => 'Partial success message returned'
                ];
            }

            if (!isset($data->activationstatus) || $data->activationstatus != 'Success') {
                Helper::send_mail('it@perfectmobileinc.com', '[H2O][' . getenv('APP_ENV') . '] Empty or non-success status returned from vendor', $cid);

                return [
                    'error_code' => -1,
                    'error_msg' => 'Empty or non-success activation status returned : ' . $data->activationstatus
                ];
            }

            if (!isset($data->min) || empty($data->min)) {
                Helper::send_mail('it@perfectmobileinc.com', '[H2O][' . getenv('APP_ENV') . '] Empty phone number returned from vendor', $cid);
                return [
                    'error_code' => -1,
                    'error_msg' => 'Empty phone number returned from vendor'
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'cid' => (string)$ret->cid,
                'serial' => (string)$ret->serial,
                'activation_status' => (string)$data->activationstatus,
                'min' => (string)$data->min
            ];


        } catch (\Exception $ex) {

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }


    public static function updateMDNPort(
        $cid, $product, $acctno, $pass,
        $street, $city, $state, $zip, $name,
        $email, $phone, $dc, $dp,
        $imei, $sim, $ip, $min, $osp, $osp_contract
    ) {
        try {

            self::init();

            if (getenv('APP_ENV') != 'production' && !self::$call_api_on_demo) {
                return [
                    'error_code' => '',
                    'error_msg' => '',
                    'cid' => $cid,
                    'serial' => time(),
                    'status' => 'Port-Created',
                    'min' => $phone
                ];
            }

            $ret = self::getMDNPortability('G-'.$cid, $product, $min);
            if (!empty($ret['error_code'])) {
                return $ret;
            }

            $allowed_activity = $ret['allowedactivity'];
            if (!in_array($allowed_activity, ['U', 'M'])) {
                return [
                    'error_code' => -1,
                    'error_msg' => 'Requested number is not in Port-In update allowed status'
                ];
            }

            $req = "<req>";

            $req .= "<action>UpdateMDNPort</action>";
            $req .= "<key>" . self::$key . "</key>";
            $req .= "<user>" . self::$user . "</user>";
            $req .= "<pass>" . self::$password . "</pass>";
            $req .= "<cid>" . (getenv('APP_ENV') == 'production' ? 'UP' : 'UD'). '-' . $cid . "</cid>";
            $req .= "<product>" . $product . "</product>";

            $req .= "<data>";
            $req .= "<a_acctno>" . $acctno . "</a_acctno>";
            $req .= "<a_pass>" . $pass . "</a_pass>";
            $req .= "<a_street>" . $street . "</a_street>";
            $req .= "<a_city>" . $city . "</a_city>";
            $req .= "<a_state>" . $state . "</a_state>";
            $req .= "<a_zip>" . $zip . "</a_zip>";
            $req .= "<a_name>" . $name . "</a_name>";

            $email = str_replace('@', '#', $email);
            $req .= "<cb_email>" . $email . "</cb_email>";
            $req .= "<cb_phone>" . $phone . "</cb_phone>";

            $req .= "<dc>" . $dc . "</dc>";
            $req .= "<dp>" . $dp . "</dp>";
            $req .= "<imei>" . $imei . "</imei>";
            $req .= "<sim>" . $sim . "</sim>";
            $req .= "<ip>" . $ip . "</ip>";
            $req .= "<min>" . $min . "</min>";
            $req .= "<osp>" . $osp . "</osp>";
            $req .= "<osp_contract>" . $osp_contract . "</osp_contract>";

            $req .= "</data>";

            $req .= "</req>";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
            //curl_setopt($ch, CURLOPT_HTTPHEADER, array('SOAPAction', 'MySoapAction'));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            curl_close($ch);

            Helper::log('### UpdateMDNPort ###', [
                'req' => $req,
                'res' => $output
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);

            if ($ret->errcode != 0) {
                return [
                    'error_code' => (string)$ret->errcode,
                    'error_msg' => (string)$ret->error
                ];
            }

            $data = $ret->data;
            if (empty($data)) {
                Helper::send_mail('it@perfectmobileinc.com', '[H2O][' . getenv('APP_ENV') . '] Partial Success Message Found', $cid);

                return [
                    'error_code' => -1,
                    'error_msg' => 'Partial success message returned'
                ];
            }

            if (!isset($data->status) || trim($data->status) == '') {
                Helper::send_mail('it@perfectmobileinc.com', '[H2O][' . getenv('APP_ENV') . '] Empty or invalid status from vendor', $cid);

                return [
                    'error_code' => -1,
                    'error_msg' => 'Empty or invalid statusfrom vendor returned : ' . $data->status
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'cid' => (string)$ret->cid,
                'serial' => (string)$ret->serial,
                'status' => (string)$data->status,
                'min' => $phone
            ];

        } catch (\Exception $ex) {
            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function createMDNPort(
        $cid, $product, $acctno, $pass,
        $street, $city, $state, $zip, $name,
        $email, $phone, $dc, $dp,
        $imei, $sim, $ip, $min, $osp, $osp_contract,
        $require_portability_check = true
    ) {
        try {

            self::init();

            if (getenv('APP_ENV') != 'production' && !self::$call_api_on_demo) {
                return [
                    'error_code' => '',
                    'error_msg' => '',
                    'cid' => $cid,
                    'serial' => time(),
                    'status' => 'Port-Created',
                    'min' => $min
                ];
            }

            if ($require_portability_check) {
                $ret = self::getMDNPortability('G-'.$cid, $product, $min);
                if (!empty($ret['error_code'])) {
                    return $ret;
                }

                $allowed_activity = $ret['allowedactivity'];
                if (!in_array($allowed_activity, ['C', 'R'])) {
                    return [
                        'error_code' => -1,
                        'error_msg' => 'Requested number is not in Port-In allowed status'
                    ];
                }

                $portable = $ret['portable'];
                if ($portable != 'Y') {
                    return [
                        'error_code' => -2,
                        'error_msg' => 'Requested number is not in Port-In allowed status'
                    ];
                }

                /*$portstatus = $ret['portstatus'];
                if ($portstatus != 'U') {
                    return [
                        'error_code' => -3,
                        'error_msg' => 'Request number is already in Port-In progress. ' . $ret['portstatus']
                    ];
                }*/
            }



            $req = "<req>";

            $req .= "<action>CreateMDNPort</action>";
            $req .= "<key>" . self::$key . "</key>";
            $req .= "<user>" . self::$user . "</user>";
            $req .= "<pass>" . self::$password . "</pass>";
            $req .= "<cid>" . (getenv('APP_ENV') == 'production' ? 'CP' : 'CD'). '-' . $cid . "</cid>";
            $req .= "<product>" . $product . "</product>";

            $req .= "<data>";
            $req .= "<a_acctno>" . $acctno . "</a_acctno>";
            $req .= "<a_pass>" . $pass . "</a_pass>";
            $req .= "<a_street>" . $street . "</a_street>";
            $req .= "<a_city>" . $city . "</a_city>";
            $req .= "<a_state>" . $state . "</a_state>";
            $req .= "<a_zip>" . $zip . "</a_zip>";
            $req .= "<a_name>" . $name . "</a_name>";

            $email = str_replace('@', '#', $email);
            $req .= "<cb_email>" . $email . "</cb_email>";
            $req .= "<cb_phone>" . $phone . "</cb_phone>";

            $req .= "<dc>" . $dc . "</dc>";
            $req .= "<dp>" . $dp . "</dp>";
            $req .= "<imei>" . $imei . "</imei>";
            $req .= "<sim>" . $sim . "</sim>";
            $req .= "<ip>" . $ip . "</ip>";
            $req .= "<min>" . $min . "</min>";
            $req .= "<osp>" . $osp . "</osp>";
            $req .= "<osp_contract>" . $osp_contract . "</osp_contract>";

            $req .= "</data>";

            $req .= "</req>";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
            //curl_setopt($ch, CURLOPT_HTTPHEADER, array('SOAPAction', 'MySoapAction'));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            curl_close($ch);

            Helper::log('### createMDNPort ###', [
                'req' => $req,
                'res' => $output
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);

            if ($ret->errcode != 0) {
                return [
                    'error_code' => (string)$ret->errcode,
                    'error_msg' => (string)$ret->error
                ];
            }

            $data = $ret->data;
            if (empty($data)) {
                Helper::send_mail('it@perfectmobileinc.com', '[H2O][' . getenv('APP_ENV') . '] Partial Success Message Found', $cid);

                return [
                    'error_code' => -1,
                    'error_msg' => 'Partial success message returned'
                ];
            }

            if (!isset($data->status) || trim($data->status) == '') {
                Helper::send_mail('it@perfectmobileinc.com', '[H2O][' . getenv('APP_ENV') . '] Empty or invalid status from vendor', $cid);

                return [
                    'error_code' => -1,
                    'error_msg' => 'Empty or invalid statusfrom vendor returned : ' . $data->status
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'cid' => (string)$ret->cid,
                'serial' => (string)$ret->serial,
                'status' => (string)$data->status,
                'min' => $min
            ];

        } catch (\Exception $ex) {
            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function getMDNPortability($cid, $product, $min) {
        try {

            self::init();

            if (getenv('APP_ENV') != 'production' && !self::$call_api_on_demo) {
                return [
                    'error_code' => '',
                    'error_msg' => '',
                    'allowedactivity' => 'C',
                    //'allowedactivity' => 'N',
                    'portable' => 'Y',
                    'portable_reason' => '',
                    'portstatus' => 'U'
                    //'portstatus' => 'B'
                ];
            }

            $req = "<req>";

            $req .= "<action>GetMDNPortability</action>";
            $req .= "<key>" . self::$key . "</key>";
            $req .= "<user>" . self::$user . "</user>";
            $req .= "<pass>" . self::$password . "</pass>";
            $req .= "<cid>" . (getenv('APP_ENV') == 'production' ? 'MP' : 'MD'). '-' . $cid . "</cid>";
            $req .= "<product>" . $product . "</product>";

            $req .= "<data>";
            $req .= "<min>" . $min . "</min>";
            $req .= "</data>";

            $req .= "</req>";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
            //curl_setopt($ch, CURLOPT_HTTPHEADER, array('SOAPAction', 'MySoapAction'));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            curl_close($ch);

            Helper::log('### getMDNPortability ###', [
                'req' => $req,
                'res' => $output
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);

            if ($ret->errcode != 0) {
                return [
                    'error_code' => (string)$ret->errcode,
                    'error_msg' => (string)$ret->error
                ];
            }

            $data = $ret->data;
            if (empty($data)) {
                Helper::send_mail('it@perfectmobileinc.com', '[H2O][' . getenv('APP_ENV') . '] Partial Success Message Found', $cid);

                return [
                    'error_code' => -1,
                    'error_msg' => 'Partial success message returned'
                ];
            }

            if (!isset($data->allowedactivity) || !in_array($data->allowedactivity, ['C', 'U', 'R', 'N', 'M'])) {
                Helper::send_mail('it@perfectmobileinc.com', '[H2O][' . getenv('APP_ENV') . '] Empty or invalid allowed activity from vendor', $cid);

                return [
                    'error_code' => -1,
                    'error_msg' => 'Empty or invalid allowed activity from vendor returned : ' . $data->allowedactivity
                ];
            }

            if (!isset($data->portable) || empty($data->portable) || !in_array($data->portable, ['Y', 'N'])) {
                Helper::send_mail('it@perfectmobileinc.com', '[H2O][' . getenv('APP_ENV') . '] Empty portable returned from vendor', $cid);
                return [
                    'error_code' => -1,
                    'error_msg' => 'Empty portable returned from vendor : ' . $data->portable
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'allowedactivity' => (string)$data->allowedactivity,
                'portable' => (string)$data->portable,
                'portable_reason' => (string)$data->portable_reason,
                'portstatus' => (string)$data->portstatus
            ];

        } catch (\Exception $ex) {
            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function CreateLineSet2($cid, $prod, $sim1, $sim2) {
        try {

            self::init();

            if (getenv('APP_ENV') != 'production' && !self::$call_api_on_demo) {
                return [
                    'error_code' => '',
                    'error_msg' => '',
                    'status' => 'C'
                ];
            }

            $req = "<req>";

            $req .= "<action>CreateLineSet2</action>";
            $req .= "<key>" . self::$key . "</key>";
            $req .= "<user>" . self::$user . "</user>";
            $req .= "<pass>" . self::$password . "</pass>";
            $req .= "<cid>" . (getenv('APP_ENV') == 'production' ? 'CP' : 'CD'). '-' . $cid . "</cid>";
            $req .= "<product>MLL</product>";

            $req .= "<data>";
            $req .= "<prod>" . $prod . "</prod>";
            $req .= "<sim1>" . $sim1 . "</sim1>";
            $req .= "<sim2>" . $sim2 . "</sim2>";
            $req .= "</data>";

            $req .= "</req>";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
            //curl_setopt($ch, CURLOPT_HTTPHEADER, array('SOAPAction', 'MySoapAction'));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            curl_close($ch);

            Helper::log('### CreateLineSet2 ###', [
                'req' => $req,
                'res' => $output
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);

            if ($ret->errcode != 0) {
                return [
                    'error_code' => (string)$ret->errcode,
                    'error_msg' => (string)$ret->error
                ];
            }

            $data = $ret->data;
            if (empty($data)) {
                Helper::send_mail('it@perfectmobileinc.com', '[H2O][' . getenv('APP_ENV') . '] CreateLineSet2 - Partial Success Message Found', $cid);

                return [
                    'error_code' => -1,
                    'error_msg' => 'Partial success message returned'
                ];
            }

            /*if (!isset($data->status) || !in_array($data->status, ['C', 'U', 'R', 'N', 'M'])) {
                Helper::send_mail('it@perfectmobileinc.com', '[H2O][' . getenv('APP_ENV') . '] Empty or invalid allowed activity from vendor', $cid);

                return [
                    'error_code' => -1,
                    'error_msg' => 'Empty or invalid allowed activity from vendor returned : ' . $data->allowedactivity
                ];
            }*/

            return [
                'error_code' => '',
                'error_msg' => '',
                'status' => (string)$data->status
            ];

        } catch (\Exception $ex) {
            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }

    public static function CreateLineSet4($cid, $prod, $sim1, $sim2, $sim3, $sim4) {
        try {

            self::init();

            if (getenv('APP_ENV') != 'production' && !self::$call_api_on_demo) {
                return [
                    'error_code' => '',
                    'error_msg' => '',
                    'status' => 'C'
                ];
            }

            $req = "<req>";

            $req .= "<action>CreateLineSet4</action>";
            $req .= "<key>" . self::$key . "</key>";
            $req .= "<user>" . self::$user . "</user>";
            $req .= "<pass>" . self::$password . "</pass>";
            $req .= "<cid>" . (getenv('APP_ENV') == 'production' ? 'CP' : 'CD'). '-' . $cid . "</cid>";
            $req .= "<product>MLL</product>";

            $req .= "<data>";
            $req .= "<prod>" . $prod . "</min>";
            $req .= "<sim1>" . $sim1 . "</sim1>";
            $req .= "<sim2>" . $sim2 . "</sim2>";
            $req .= "<sim3>" . $sim3 . "</sim3>";
            $req .= "<sim4>" . $sim4 . "</sim4>";
            $req .= "</data>";

            $req .= "</req>";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$api_url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
            //curl_setopt($ch, CURLOPT_HTTPHEADER, array('SOAPAction', 'MySoapAction'));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            curl_close($ch);

            Helper::log('### CreateLineSet4 ###', [
                'req' => $req,
                'res' => $output
            ]);

            $ret = simplexml_load_string($output, null, LIBXML_NOCDATA);

            if ($ret->errcode != 0) {
                return [
                    'error_code' => (string)$ret->errcode,
                    'error_msg' => (string)$ret->error
                ];
            }

            $data = $ret->data;
            if (empty($data)) {
                Helper::send_mail('it@perfectmobileinc.com', '[H2O][' . getenv('APP_ENV') . '] CreateLineSet4 - Partial Success Message Found', $cid);

                return [
                    'error_code' => -1,
                    'error_msg' => 'Partial success message returned'
                ];
            }

            /*if (!isset($data->status) || !in_array($data->status, ['C', 'U', 'R', 'N', 'M'])) {
                Helper::send_mail('it@perfectmobileinc.com', '[H2O][' . getenv('APP_ENV') . '] Empty or invalid allowed activity from vendor', $cid);

                return [
                    'error_code' => -1,
                    'error_msg' => 'Empty or invalid allowed activity from vendor returned : ' . $data->allowedactivity
                ];
            }*/

            return [
                'error_code' => '',
                'error_msg' => '',
                'status' => (string)$data->status
            ];

        } catch (\Exception $ex) {
            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ];
        }
    }
}