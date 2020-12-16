<?php

namespace App\Lib;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
use \Exception;

/**
 * Description of SoapClientTimeout
 *
 * @author yongj
 */
class SoapClientTimeout extends SoapClient {

    private $timeout;
    private $cookie;

    /**
     * @param $timeout
     * @throws \Exception
     */
    public function __setTimeout($timeout) {
        if (!is_int($timeout) && !is_null($timeout)) {
            throw new Exception("Invalid timeout value");
        }

        $this->timeout = $timeout;
    }

    public function __setManualCookie($key, $val) {
        $this->cookie = $key . '=' . $val;
    }

    public function __doRequest($request, $location, $action, $version, $one_way = FALSE) {
        if (!$this->timeout) { // Call via parent because we require no timeout 
            $response = parent::__doRequest($request, $location, $action, $version, $one_way);
        } else { // Call via Curl and use the timeout 
            $curl = curl_init($location);
            curl_setopt($curl, CURLOPT_VERBOSE, FALSE);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($curl, CURLOPT_POST, TRUE);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
            //curl_setopt($curl, CURLOPT_PORT, 443);
            //curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);

            curl_setopt($curl, CURLOPT_HEADER, FALSE);

            if (!$this->cookie) {
                curl_setopt($curl, CURLOPT_COOKIE, $this->cookie);
            }

            Helper::log('### CURLOPT_TIMEOUT ###', $this->timeout);

            //curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: text/xml"));
            curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: text/xml", "SOAPAction: {$action}"));
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_NOSIGNAL, 1);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->timeout);
            curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($curl);

            if (curl_errno($curl)) {
                throw new Exception(curl_error($curl));
            }

            if (empty($response)) {
                $info= curl_getinfo($curl);

                $msg = ' - REQUEST: ' . var_export($request, true) . "\n";
                $msg .= ' - INFO: ' . var_export($info, true) . "\n";

                Helper::send_mail('tech@black011.com', '[SOAP] Empty response found', $msg);
                throw new Exception('We got empty response');
            }

            Helper::log('### __doRequest response ###', var_export($request, true));

            curl_close($curl);
        }

        // Return? 
        if (!$one_way) {
            return ($response);
        }
    }
}
