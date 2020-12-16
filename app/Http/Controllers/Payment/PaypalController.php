<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 12/24/18
 * Time: 9:35 AM
 */

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\ATT\RechargeController;
use App\Http\Controllers\Controller;

use App\Model\Payment;
use App\Model\PaymentRequest;
use App\Model\PaypalLog;
use App\Model\VRRequest;
use App\Model\VRPayment;
use Illuminate\Http\Request;
use Validator;

use Carbon\Carbon;
use App\Lib\Helper;

class PaypalController extends Controller
{
    public function log(Request $request) {
        Helper::log('#### PAYPAL LOG ####');
        try {

            $pallog = new PaypalLog();
            $pallog->url = $request->path();
            $pallog->log = json_encode($request->all());
            $pallog->cdate = Carbon::now();
            $pallog->save();

            // STEP 1: read POST data
            // Reading POSTed data directly from $_POST causes serialization issues with array data in the POST.
            // Instead, read raw POST data from the input stream.
            $raw_post_data = file_get_contents('php://input');
            $raw_post_array = explode('&', $raw_post_data);
            $myPost = array();
            foreach ($raw_post_array as $keyval) {
                $keyval = explode ('=', $keyval);
                if (count($keyval) == 2)
                    $myPost[$keyval[0]] = urldecode($keyval[1]);
            }

            if (!empty($request->invoice)) {
                $pallog->invoice_number = $request->invoice;
            }
            if (!empty($request->payment_status)) {
                $pallog->payment_status = $request->payment_status;
            }
            if (!empty($request->mc_gross)) {
                $pallog->mc_gross = $request->mc_gross;
            }
            if (!empty($request->mc_fee)) {
                $pallog->mc_fee = $request->mc_fee;
            }
            $pallog->update();

            if (strpos($pallog->invoice_number, 'E-') !== false && $pallog->payment_status == 'Completed'){
                Helper::log('#### PAYPAL LOG (E Commerce Start)####');
//                RechargeController::process_after_pay($pallog->invoice_number);
                Helper::process_after_pay($pallog->invoice_number);
            }

            if (strpos($pallog->invoice_number, 'VR-') !== false && $pallog->payment_status == 'Completed'){

                Helper::log('#### PAYPAL LOG (VR Order Start)####');
                $vr_id = substr($request->invoice,10);

                $vr = VRRequest::where('id', $vr_id)->first();

                $payment = new VRPayment;
                $payment->vr_id = $vr_id;
                $payment->invoice_number = $request->invoice;
                $payment->paypal_txn_id  = $request->txn_id;
                $payment->account_id = $vr->account_id;
                $payment->type = 'PayPal'; # paypal always for now.
                $payment->amt = $vr->total;
                $payment->comments = $vr->memo;

                $payment->payer_id = $request->payer_id;
                $payment->payment_id = $request->payment_id;
                $payment->payment_token = $request->payment_token;

                $payment->created_by = $vr->created_by;
                $payment->cdate = Carbon::now();
                $payment->save();

//                    $vr->order = $request->order_notes;
//                    $vr->promo_code = $request->promo_code;
                $vr->pay_method = 'PayPal';
                $vr->status = 'PC'; // Change status to 'Paid'
                $vr->mdate = Carbon::now();
                $vr->update();

                # insert promotion
                $res = Helper::addPromotion($vr->id);
                if (!empty($res)) {
                    return response()->json([
                        'msg' => $res
                    ]);
                }

                # Send payment success email to balance@softpayplus.com
                $subject = "Success Payment - VR Request (Acct.ID : " . $payment->account_id . ", Amount : $" . $payment->amt . ")";
                $msg = "<b>Success Payment</b> <br/><br/>";
                $msg .= "Acct.ID - " . $payment->account_id . "<br/>";
                $msg .= "VR.ID - " . $payment->vr_id . "<br/>";
                $msg .= "Type - " . $payment->type . "<br/>";
                $msg .= "Amount - $" . $payment->amt . "<br/>";
                $msg .= "Comment - " . $payment->comments . "<br/>";
                $msg .= "Payer.ID - " . $payment->payer_id . "<br/>";
                $msg .= "Payment.ID - " . $payment->payment_id . "<br/>";
                $msg .= "Payment.Token - " . $payment->payment_token . "<br/>";
                $msg .= "Created.By - " . $payment->created_by . "<br/>";
                $msg .= "Date - " . $payment->cdate . "<br/>";


                if (getenv('APP_ENV') == 'production') {
                    Helper::send_mail('balance@softpayplus.com', $subject, $msg);
                } else {
                    Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . ']' . $subject, $msg);
                }

            }

            if (strpos($pallog->invoice_number, 'P-') !== false && $pallog->payment_status == 'Completed'){

                Helper::log('#### PAYPAL LOG (Prepaid Start)####');
                $pr_id = substr($request->invoice,9);
                $res = PaymentRequest::where('id', $pr_id)->first();

                $payment = new Payment();
                $payment->account_id = $res->account_id;
                $payment->type      = 'P'; # prepay always for now.
                $payment->method    = 'P';
                $payment->category  = '';

                $payment->deposit_amt = $res->deposit;
                $payment->fee       = $res->fee;
                $payment->amt       = $res->amt;
                $payment->comments  = $res->comments;

                $payment->payer_id  = $request->payer_id;
//                                $payment->payment_id = $request->payment_id;
//                                $payment->payment_token = $request->payment_token;

                $payment->paypal_txn_id = $request->txn_id;
                $payment->pr_id     = $pr_id;

                $payment->created_by = $res->created_by;
                $payment->cdate = Carbon::now();
                $payment->invoice_number = $request->invoice;

                // Double Check same data exist
                $d_check = Payment::where('account_id', $res->account_id)
                                    ->where('invoice_number', $request->invoice)
                                    ->where('payer_id', $request->payer_id)
                                    ->where('paypal_txn_id', $request->txn_id)
                                    ->where('pr_id', $pr_id)
                                    ->first();

                // Avoid Double Inserting on payment
                if(!$d_check){
                    $payment->save();
                }

                # Send payment success email to balance@softpayplus.com
                $subject = "Success Payment (Acct.ID : " . $payment->account_id . ", Amount : $" . $payment->amt . ")";
                $msg = "<b>Success Payment</b> <br/><br/>";
                $msg .= "Acct.ID - " . $payment->account_id . "<br/>";
                $msg .= "Type - " . $payment->getTypeNameAttribute() . "<br/>";
                $msg .= "Method - " . $payment->getMethodNameAttribute() . "<br/>";
                $msg .= "Deposit.Amt - $" . $payment->deposit_amt . "<br/>";
                $msg .= "Fee - $" . $payment->fee . "<br/>";
                $msg .= "Amount - $" . $payment->amt . "<br/>";
                $msg .= "Comment - " . $payment->comments . "<br/>";
                $msg .= "Payer.ID - " . $payment->payer_id . "<br/>";
//                $msg .= "Payment.ID - " . $payment->payment_id . "<br/>";
//                $msg .= "Payment.Token - " . $payment->payment_token . "<br/>";
                $msg .= "Created.By - " . $payment->created_by . "<br/>";
                $msg .= "Date - " . $payment->cdate . "<br/>";


                if (getenv('APP_ENV') == 'production') {
                    Helper::send_mail('balance@softpayplus.com', $subject, $msg);
                } else {
                    Helper::send_mail('it@jjonbp.com', '[PM][' . getenv('APP_ENV') . ']' . $subject, $msg);
                }
                Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . ']' . $subject, $msg);
            }


            // read the IPN message sent from PayPal and prepend 'cmd=_notify-validate'
            $req = 'cmd=_notify-validate';
            if (function_exists('get_magic_quotes_gpc')) {
                $get_magic_quotes_exists = true;
            }
            foreach ($myPost as $key => $value) {
                if ($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
                    $value = urlencode(stripslashes($value));
                } else {
                    $value = urlencode($value);
                }
                $req .= "&$key=$value";
            }

            // Step 2: POST IPN data back to PayPal to validate
            $ch = curl_init('https://ipnpb.paypal.com/cgi-bin/webscr');
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
            // In wamp-like environments that do not come bundled with root authority certificates,
            // please download 'cacert.pem' from "https://curl.haxx.se/docs/caextract.html" and set
            // the directory path of the certificate as shown below:
            // curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/cacert.pem');
            if ( !($res = curl_exec($ch)) ) {
                // error_log("Got " . curl_error($ch) . " when processing IPN data");
                curl_close($ch);
                exit;
            }
            curl_close($ch);

        } catch (\Exception $ex) {
            Helper::log('#### EXCEPTION ####', $ex->getTraceAsString());
        }
    }
}