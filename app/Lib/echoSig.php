<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 3/10/17
 * Time: 3:05 PM
 */

namespace App\Lib;

use Log;
use Auth;
use URL;
use App\Model\Account;

class echoSig
{

    private static $key = '';
    //private static $user = '';
    //private static $pwd = '';
    private static $url = 'https://api.na2.echosign.com:443/api/rest/v5'; # 52.35.253.83
    //private static $url = 'http://demo.softpayplus.com/';
    private static $return_url = '/esig/completed';

    public static function base_urls() {
        $curl = curl_init();
        //curl_setopt( $curl, CURLOPT_POST, true );

        //curl_setopt( $curl, CURLINFO_HEADER_OUT, true);
        curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'GET' );
        //curl_setopt( $curl, CURLOPT_SAFE_UPLOAD, true);
        //curl_setopt( $curl, CURLOPT_POSTFIELDS, $data);
        //curl_setopt( $curl, CURLOPT_VERBOSE, true);
        curl_setopt( $curl, CURLOPT_HTTPHEADER, [
            'Access-Token: ' . self::$key
        ] );
        curl_setopt( $curl, CURLOPT_URL, self::$url . '/base_uris' );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );

        $result = curl_exec($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);

        dd($result);

    }

    private static function get_agreement_id($widget_id) {
        try {

            $data = [
                'widgetId' => $widget_id
            ];

            $data_string = json_encode($data);

            $curl = curl_init();
            //curl_setopt( $curl, CURLOPT_POST, true );

            curl_setopt( $curl, CURLINFO_HEADER_OUT, true);
            curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'GET' );
            curl_setopt( $curl, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt( $curl, CURLOPT_HTTPHEADER, [
                'Access-Token: ' . self::$key,
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string)
            ] );
            curl_setopt( $curl, CURLOPT_URL, self::$url . '/widgets/' . $widget_id . '/agreements' );
            curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );

            $result = curl_exec($curl);
            $info = curl_getinfo($curl);

            $error = curl_errno($curl);
            curl_close($curl);

            Helper::log('### get_agreement_id response ###', [
                'result' => $result,
                'info' => $info
            ]);

            //return $result;

            if ($error) {
                switch ($http_code = $info['http_code']) {
                    case 200:  # OK
                    case 201:
                        break;
                    default:
                        return [
                            'msg' => 'Adobe Sign returned error with status code : ' . $http_code
                        ];
                }
            }

            $res = json_decode($result);
            if (!isset($res->userAgreementList->agreementId) || empty($res->userAgreementList->agreementId)) {

                return [
                    'msg' => 'Adobe Sign returned empty agreementId'
                ];
            }

            return [
                'msg' => '',
                'agreementId' => $res->userAgreementList->agreementId
            ];

        } catch (\Exception $ex) {
            return [
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ];
        }
    }

    public static function download_document($widget_id) {
        try {
            //$file_path = __DIR__ . '/pdf/' . $file_name;

            $ret = self::get_agreement_id($widget_id);
            if (!empty($ret['msg'])) {
                return $ret;
            }

            $agreement_id = $ret['agreementId'];

            $data = [
                'agreementId' => $agreement_id
            ];

            $data_string = json_encode($data);

            $curl = curl_init();
            curl_setopt( $curl, CURLOPT_POST, true );

            curl_setopt( $curl, CURLINFO_HEADER_OUT, true);
            curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'GET' );
            curl_setopt( $curl, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, 0);
            curl_setopt( $curl, CURLOPT_BINARYTRANSFER, 1);
            curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt( $curl, CURLOPT_HTTPHEADER, [
                'Access-Token: ' . self::$key,
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string),
                'Accept: application/pdf',
                'Accept-Encoding: gzip, deflate, sdch, br',
                'Accept-Language: en-US,en;q=0.8,fr;q=0.6',
                'Cache-Control: no-cache',
                'Connection: keep-alive',
                'DNT: 1',
                'Pragma: no-cache'
            ] );
            curl_setopt( $curl, CURLOPT_URL, self::$url . '/agreements/' . $agreement_id . '/combinedDocument' );
            curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );

            $result = curl_exec($curl);
            $info = curl_getinfo($curl);

            $error = curl_errno($curl);
            curl_close($curl);

            Helper::log('### download_document response ###', [
                'result' => $result
            ]);

            //return $result;

            if ($error) {
                switch ($http_code = $info['http_code']) {
                    case 200:  # OK
                    case 201:
                        break;
                    default:
                        return [
                            'msg' => 'Adobe Sign returned error with status code : ' . $http_code
                        ];
                }
            }

            return [
                'msg' => '',
                'signed_pdf' => $result
            ];

        } catch (\Exception $ex) {
            return [
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ];
        }
    }

    public static function get_url($file_name, $doc_name, $account_name, $address1, $address2, $email, $doc_type = null) {
        try {

            $ret = self::transientDocuments($file_name);
            if (!empty($ret['msg'])) {
                return $ret;
            }

            $transientDocumentId = $ret['transientDocumentId'];

            $url = URL::to('/') . self::$return_url . '/' . Auth::user()->user_id . (empty($doc_type) ? '' : '?doc_type=' . $doc_type);
            \App\Lib\Helper::log('### esig complete start ###', [
                'url', $url
            ]);

            $account = Account::find(Auth::user()->account_id);

            $data = [
                'widgetCreationInfo' => [
                    'fileInfos' => [
                        'transientDocumentId' => $transientDocumentId
                    ],
                    'name' => 'sign-widget',
                    'signatureFlow' => 'SENDER_SIGNATURE_NOT_REQUIRED',
                    'authoringRequested' => false,
                    'callbackInfo' => $url,
                    'formFieldLayerTemplates' => [
                        'libraryDocumentName' => $doc_name
                    ],
                    'mergeFieldInfo' => [
                        [
                            'defaultValue' => Auth::user()->account_id,
                            'fieldName' => 'account_id'
                        ],
                        [
                            'defaultValue' => $account_name,
                            'fieldName' => 'account_name'
                        ],
                        [
                            'defaultValue' => $address1 . ' ' . $address2,
                            'fieldName' => 'address'
                        ],
                        [
                            'defaultValue' => $address1,
                            'fieldName' => 'address1'
                        ],
                        [
                            'defaultValue' => $address2,
                            'fieldName' => 'address2'
                        ],
                        [
                            'defaultValue' => $account->contact,
                            'fieldName' => 'contact_name'
                        ],
                        [
                            'defaultValue' => $account->office_number,
                            'fieldName' => 'office_number'
                        ],
                        [
                            'defaultValue' => $account->city,
                            'fieldName' => 'city'
                        ],
                        [
                            'defaultValue' => $account->state,
                            'fieldName' => 'state'
                        ],
                        [
                            'defaultValue' => $account->zip,
                            'fieldName' => 'zip'
                        ],
                        [
                            'defaultValue' => $account->email,
                            'fieldName' => 'e-mail'
                        ],
                        [
                            'defaultValue' => $email,
                            'fieldName' => 'email'
                        ],
                        [
                            'defaultValue' => $account->tax_id,
                            'fieldName' => 'state_tax_id'
                        ],
                        [
                            'defaultValue' => $account->ach_bank,
                            'fieldName' => 'name_of_bank'
                        ],
                        [
                            'defaultValue' => $account->ach_acctno,
                            'fieldName' => 'bank_account_number'
                        ],
                        [
                            'defaultValue' => $account->ach_routeno,
                            'fieldName' => 'bank_transit_routing_number'
                        ]
                    ]
                ]
            ];

            // name_of_bank, bank_contact, bank_account_number, bank_transit_routing_number

            $data_string = json_encode($data);

            $curl = curl_init();
            curl_setopt( $curl, CURLOPT_POST, true );

            curl_setopt( $curl, CURLINFO_HEADER_OUT, true);
            curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'POST' );
            curl_setopt( $curl, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt( $curl, CURLOPT_HTTPHEADER, [
                'Access-Token: ' . self::$key,
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string)
            ] );
            curl_setopt( $curl, CURLOPT_URL, self::$url . '/widgets' );
            curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );

            $result = curl_exec($curl);
            $info = curl_getinfo($curl);

            $error = curl_errno($curl);
            curl_close($curl);

            Helper::log('### transientDocuments response ###', [
                'result' => $result
            ]);

            //return $result;

            if ($error) {
                switch ($http_code = $info['http_code']) {
                    case 200:  # OK
                    case 201:
                        break;
                    default:
                        return [
                            'msg' => 'Adobe Sign returned error with status code : ' . $http_code
                        ];
                }
            }

            $res = json_decode($result);
            if (empty($res->widgetId)) {

                return [
                    'msg' => 'Adobe Sign returned empty widgetId'
                ];
            }

            return [
                'msg' => '',
                'javascript' => $res->javascript,
                'nextPageEmbeddedCode' => $res->nextPageEmbeddedCode,
                'nextPageUrl' => $res->nextPageUrl,
                'url' => $res->url,
                'widgetId' => $res->widgetId
            ];

        } catch (\Exception $ex) {
            return [
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ];
        }
    }

    private static function transientDocuments($file_name) {
        try {
            $file_path = __DIR__ . '/pdf/' . $file_name;

            $data = [
                'File-Name' => $file_name,
                'Mime-Type' => 'application/pdf',
                'File' => new \CURLFile($file_path)
            ];

            $curl = curl_init();
            curl_setopt( $curl, CURLOPT_POST, true );

            curl_setopt( $curl, CURLINFO_HEADER_OUT, true);
            curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'POST' );
            curl_setopt( $curl, CURLOPT_SAFE_UPLOAD, true);
            curl_setopt( $curl, CURLOPT_POSTFIELDS, $data);
            curl_setopt( $curl, CURLOPT_VERBOSE, true);
            curl_setopt( $curl, CURLOPT_HTTPHEADER, [
                'Access-Token: ' . self::$key,
                'Accept:application/json, text/javascript, */*; q=0.01',
                'Accept-Encoding:gzip, deflate, br',
                'Accept-Language:en-US,en;q=0.8,fr;q=0.6',
                'Cache-Control:no-cache',
                'Connection:keep-alive',
                'User-Agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36',
                'Pragma:no-cache',
                'DNT:1'
            ] );
            curl_setopt( $curl, CURLOPT_URL, self::$url . '/transientDocuments' );
            curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );

            $result = curl_exec($curl);
            $info = curl_getinfo($curl);

            $error = curl_errno($curl);
            curl_close($curl);

            Helper::log('### transientDocuments response ###', [
                'result' => $result
            ]);

            if ($error) {
                switch ($http_code = $info['http_code']) {
                    case 200:  # OK
                    case 201:
                        break;
                    default:
                        return [
                            'msg' => 'Adobe Sign returned error with status code : ' . $http_code
                        ];
                }
            }

            $res = json_decode($result);
            if (empty($res->transientDocumentId)) {

                return [
                    'msg' => 'Adobe Sign returned empty transientDocumentId'
                ];
            }

            return [
                'msg' => '',
                'transientDocumentId' => $res->transientDocumentId
            ];

        } catch (\Exception $ex) {
            return [
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ];
        }
    }
}