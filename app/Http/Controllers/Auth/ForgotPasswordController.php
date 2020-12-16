<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use App\User;
use Illuminate\Http\Request;
use Password;
use App\Lib\Helper;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function sendResetLinkEmail(Request $request)
    {
        if (empty($request->user_id)) {
            return back()->withErrors([
              'user_id' => 'Please provided user ID !!'
            ])->withInput();
        }

        $user = User::find($request->user_id);
        if (empty($user)) {
            return back()->withErrors([
                'user_id' => 'Provided user ID does not exist in our system'
            ])->withInput();
        }

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $response = $this->broker()->sendResetLink([
            'email' => $user->email
        ]);

        Helper::log('### sendResetLinkEmail response ###', [
            'response' => $response,
            'email' => $user->email
        ]);

        return $response == Password::RESET_LINK_SENT
            ? $this->sendResetLinkResponse($response)
            : $this->sendResetLinkFailedResponse($request, $response);
    }
}
