<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 3/13/17
 * Time: 3:50 PM
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Validator;
use App\Model\ContactUs;
use App\Model\State;
use Carbon\Carbon;
use App\Lib\Helper;
use Illuminate\Support\Facades\Session;

class ContactUsController extends Controller
{
    public function show() {

        $code = Helper::generate_code(6);
        Session::put('verification-code', $code);

        $states = State::get();
        return view('contact-us')->with([
            'states'    => $states,
            'verification_code' => $code
        ]);
    }

    public function post(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'business_name' => 'required',
                'state_in' => 'required',
                'name' => 'required',
                'email' => 'required|email',
                'subject' => 'required',
                'message' => 'required',
                'verification_code' => 'required'
            ]);

            if ($v->fails()) {
                return back()->withErrors($v)->withInput();
            }

            $scode = Session::get('verification-code');
            if ($request->verification_code != $scode) {

                return back()->withErrors([
                    'exception' => 'Invalid Verification Code Provided !!'
                ])->withInput();
            }

            $item = new ContactUs;
            $item->business_name = $request->business_name;
            $item->state_in = $request->state_in;
            $item->name = $request->name;
            $item->email = $request->email;
            $item->subject = $request->subject;
            $item->message = $request->message;
            $item->status = 'N';
            $item->ip = $request->ip();
            $item->cdate = Carbon::now();
            $item->save();

            $email_body = "";
            //$email_body .= " - Contact Us ID: $item->id.\n";
            $email_body .= " - Business Name: $item->business_name.\n";
            $email_body .= " - State In: $item->state_in.\n";
            $email_body .= " - Name: $item->name.\n";
            $email_body .= " - Email: $item->email.\n";
            $email_body .= " - Subject: $item->subject.\n";
            $email_body .= " - Message: $item->message.\n";
            $email_body .= " - Date: $item->cdate.\n";

            $email_subject  = "[Contact-Us] You have new inquiry from customer";
            $ret = Helper::send_mail(env('CONTACT_US_NOTIFY_EMAIL'), $email_subject, $email_body);
            if (!empty($ret)) {
                return back()->withErrors([
                    'exception' => $ret
                ])->withInput();
            }

            return back()->with([
                'success' => 'Y'
            ]);

        } catch (\Exception $ex) {
            return back()->withErrors([
                'exception' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ])->withInput();
        }
    }
}