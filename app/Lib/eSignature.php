<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 3/2/17
 * Time: 2:54 PM
 */

namespace App\Lib;

use DocuSign\eSign\Api\AuthenticationApi;
use DocuSign\eSign\Api\AuthenticationApi\LoginOptions;
use DocuSign\eSign\Api\EnvelopesApi;
use DocuSign\eSign\ApiClient;
use DocuSign\eSign\Configuration;
use DocuSign\eSign\Model\DateSigned;
use DocuSign\eSign\Model\Document;
use DocuSign\eSign\Model\EnvelopeDefinition;
use DocuSign\eSign\Model\Recipients;
use DocuSign\eSign\Model\RecipientViewRequest;
use DocuSign\eSign\Model\Signer;
use DocuSign\eSign\Model\SignHere;
use DocuSign\eSign\Model\Tabs;
use DocuSign\eSign\Model\Text;

class eSignature
{
    private static $user = 'jyk2000@gmail.com';
    private static $pwd = 'Jyk5183!!';
    private static $key = '08aa3b64-1a60-4d3f-afc0-ea0cff56875e';
    private static $host = 'https://demo.docusign.net/restapi';
    private static $return_url = 'http://demo.softpayplus.com/esig/completed';

    private static function init()
    {

        if (getenv('APP_ENV') == 'production') {
            self::$user = 'docusign@perfectmobileinc.com';
            self::$pwd = 'Perfect4119';
            self::$key = 'cf5b4043-dd09-4353-8681-0760e414a00a';

            self::$host = 'https://www.docusign.net/restapi';
            self::$return_url = 'https://www.softpayplus.com/esig/completed';
        }

    }

    public static function get_url($account)
    {

        try {

            self::init();

            $config = new Configuration();
            $config->setHost(self::$host);
            $config->addDefaultHeader("X-DocuSign-Authentication", "{\"Username\":\"" . self::$user . "\",\"Password\":\"" . self::$pwd . "\",\"IntegratorKey\":\"" . self::$key . "\"}");
            // instantiate a new docusign api client
            $apiClient = new ApiClient($config);
            // we will first make the Login() call which exists in the AuthenticationApi...
            $authenticationApi = new AuthenticationApi($apiClient);
            // optional login parameters
            $options = new LoginOptions();
            // call the login() API
            $loginInformation = $authenticationApi->login($options);
            // parse the login results
            if (isset($loginInformation) && count($loginInformation) > 0) {
                // note: defaulting to first account found, user might be a
                // member of multiple accounts

                $loginAccount = $loginInformation->getLoginAccounts()[0];
                $host = $loginAccount->getBaseUrl();
                $host = explode("/v2", $host);
                $host = $host[0];

                // UPDATE configuration object
                $config->setHost($host);

                // instantiate a NEW docusign api client (that has the correct baseUrl/host)
                $apiClient = new ApiClient($config);

                if (isset($loginInformation)) {
                    $accountId = $loginAccount->getAccountId();
                    if (!empty($accountId)) {

                        $documentFileName = "/pdf/dealer_agreement.pdf";
                        $documentName = "dealer_agreement.pdf";
                        // instantiate a new envelopeApi object
                        $envelopeApi = new EnvelopesApi($apiClient);
                        // Add a document to the envelope
                        $document = new Document();
                        $document->setDocumentBase64(base64_encode(file_get_contents(__DIR__ . $documentFileName)));
                        $document->setName($documentName);
                        $document->setDocumentId("1");

                        // Create a |SignHere| tab somewhere on the document for the recipient to sign
                        $signHere = new SignHere();
                        $signHere->setXPosition("350");
                        $signHere->setYPosition("418");
                        $signHere->setDocumentId("1");
                        $signHere->setPageNumber("8");
                        $signHere->setRecipientId("1");

                        // Date Signed
                        $effectDate = new DateSigned();
                        $effectDate->setXPosition("150");
                        $effectDate->setYPosition("225");
                        $effectDate->setDocumentId("1");
                        $effectDate->setPageNumber("8");
                        $effectDate->setRecipientId("1");

                        $signDate = new DateSigned();
                        $signDate->setXPosition("350");
                        $signDate->setYPosition("575");
                        $signDate->setDocumentId("1");
                        $signDate->setPageNumber("8");
                        $signDate->setRecipientId("1");

                        // Text
                        $accountName = new Text();
                        $accountName->setLocked("true");
                        $accountName->setXPosition("320");
                        $accountName->setYPosition("295");
                        $accountName->setDocumentId("1");
                        $accountName->setPageNumber("8");
                        $accountName->setRecipientId("1");
                        $accountName->setValue($account->name);
                        $accountName->setFontSize(4);

                        $address1 = new Text();
                        $address1->setLocked("true");
                        $address1->setXPosition("320");
                        $address1->setYPosition("380");
                        $address1->setDocumentId("1");
                        $address1->setPageNumber("8");
                        $address1->setRecipientId("1");
                        $address1->setValue($account->address1 . ' ' . $account->address2);
                        $address1->setFontSize(4);

                        $address2 = new Text();
                        $address2->setLocked("true");
                        $address2->setXPosition("320");
                        $address2->setYPosition("405");
                        $address2->setDocumentId("1");
                        $address2->setPageNumber("8");
                        $address2->setRecipientId("1");
                        $address2->setValue($account->city . ', ' . $account->state . ' ' . $account->zip);
                        $address2->setFontSize(4);

                        $name = new Text();
                        $name->setTabLabel("name");
                        $name->setLocked("false");
                        $name->setXPosition("355");
                        $name->setYPosition("498");
                        $name->setDocumentId("1");
                        $name->setPageNumber("8");
                        $name->setRecipientId("1");
                        $name->setValue($account->contact);
                        $name->setFontSize(4);

                        $title = new Text();
                        $name->setTabLabel("title");
                        $title->setLocked("false");
                        $title->setXPosition("355");
                        $title->setYPosition("533");
                        $title->setDocumentId("1");
                        $title->setPageNumber("8");
                        $title->setRecipientId("1");
                        $title->setFontSize(4);

                        // add the signature tab to the envelope's list of tabs
                        $tabs = new Tabs();
                        $tabs->setSignHereTabs(array($signHere));
                        $tabs->setDateSignedTabs(array($effectDate, $signDate));
                        $tabs->setTextTabs(array($accountName, $address1, $address2, $name, $title));

                        // add a signer to the envelope
                        $signer = new Signer();
                        $signer->setEmail($account->email);
                        $signer->setName($account->contact);
                        $signer->setRecipientId("1");
                        $signer->setTabs($tabs);
                        $signer->setClientUserId($account->id);  // must set this to embed the recipient!
                        // Add a recipient to sign the document
                        $recipients = new Recipients();
                        $recipients->setSigners(array($signer));
                        $envelop_definition = new EnvelopeDefinition();
                        $envelop_definition->setEmailSubject("[DocuSign PHP SDK] - Please sign this doc");
                        // set envelope status to "sent" to immediately send the signature request
                        $envelop_definition->setStatus("sent");
                        $envelop_definition->setRecipients($recipients);
                        $envelop_definition->setDocuments(array($document));
                        // create and send the envelope! (aka signature request)
                        $envelop_summary = $envelopeApi->createEnvelope($accountId, $envelop_definition, null);
                        //echo "$envelop_summary\n";

                        if (!empty($envelop_summary)) {

                            //dd($envelop_summary);
                            //exit;

                            $recipient_view_request = new RecipientViewRequest();
                            // set where the recipient is re-directed once they are done signing
                            $recipient_view_request->setReturnUrl(self::$return_url . "/" . $envelop_summary->getEnvelopeId());
                            // configure the embedded signer
                            $recipient_view_request->setUserName($account->contact);
                            $recipient_view_request->setEmail($account->email);
                            // must reference the same clientUserId that was set for the recipient when they
                            // were added to the envelope in step 2
                            $recipient_view_request->setClientUserId($account->id);
                            // used to indicate on the certificate of completion how the user authenticated
                            $recipient_view_request->setAuthenticationMethod("email");
                            // generate the recipient view! (aka embedded signing URL)
                            $signingView = $envelopeApi->createRecipientView($accountId, $envelop_summary->getEnvelopeId(), $recipient_view_request);
                            //echo "Signing URL = " . $signingView->getUrl() . "\n";

                            return [
                                'msg' => '',
                                'url' => $signingView->getUrl(),
                                'envelope_id' => $envelop_summary->getEnvelopeId()
                            ];
                        }
                    }
                }

                //return $loginAccount;
            }

            return [
                'msg' => 'Failed to get eSignature URL'
            ];

        } catch (\Exception $ex) {

            return [
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ];
        }

    }

    public static function download_doc($envelope_id)
    {
        try {

            self::init();


            // copy the envelopeId from an existing envelope in your account that you want
            // to download documents from

            // construct the authentication header:
            $header = "<DocuSignCredentials><Username>" . self::$user . "</Username><Password>" . self::$pwd . "</Password><IntegratorKey>" . self::$key . "</IntegratorKey></DocuSignCredentials>";
            /////////////////////////////////////////////////////////////////////////////////////////////////
            // STEP 1 - Login (retrieves baseUrl and accountId)
            /////////////////////////////////////////////////////////////////////////////////////////////////
            $url = self::$host . "/v2/login_information";
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array("X-DocuSign-Authentication: $header"));
            $json_response = curl_exec($curl);
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($status != 200) {
                return [
                    'msg' => "error calling webservice, status is:" . $status
                ];
            }

            $response = json_decode($json_response, true);
            $accountId = $response["loginAccounts"][0]["accountId"];
            $baseUrl = $response["loginAccounts"][0]["baseUrl"];
            curl_close($curl);
            //--- display results
            //echo "accountId = " . $accountId . "\nbaseUrl = " . $baseUrl . "\n";

            /////////////////////////////////////////////////////////////////////////////////////////////////
            // STEP 2 - Get document information
            /////////////////////////////////////////////////////////////////////////////////////////////////
            $curl = curl_init($baseUrl . "/envelopes/" . $envelope_id . "/documents");
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                    "X-DocuSign-Authentication: $header")
            );
            $json_response = curl_exec($curl);
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($status != 200) {
                echo "error calling webservice, status is:" . $status;
                exit(-1);
            }
            $response = json_decode($json_response, true);
            curl_close($curl);
            //--- display results
            //echo "Envelope has following document(s) information...\n";
            //print_r($response);
            //echo "\n";

            /////////////////////////////////////////////////////////////////////////////////////////////////
            // STEP 3 - Download the envelope's documents
            /////////////////////////////////////////////////////////////////////////////////////////////////
            $signed_pdf = "";
            foreach ($response["envelopeDocuments"] as $document) {
                $docUri = $document["uri"];

                $curl = curl_init($baseUrl . $docUri);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
                curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                        "X-DocuSign-Authentication: $header")
                );

                $data = curl_exec($curl);
                $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                if ($status != 200) {
                    return [
                        'msg' =>  "error calling webservice, status is:" . $status
                    ];
                }

                if ($document["name"] == "dealer_agreement.pdf") {
                    $signed_pdf = $data;
                }

                //file_put_contents("/tmp/" . $envelope_id . "-" . $document["name"], $data);
                curl_close($curl);

                //*** Documents should now be downloaded in the same folder as you ran this program
            }
            //--- display results
            //echo "Envelope document(s) have been downloaded, check your local directory.\n";

            if (empty($signed_pdf)) {
                return [
                    'msg' => 'Failed to download signed PDF'
                ];
            }

            return [
                'msg' => '',
                'signed_pdf' => $signed_pdf
            ];

        } catch (\Exception $ex) {

            return [
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ];
        }
    }
}