<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use URL;

class SendPhoneNumber extends Mailable
{
    use Queueable, SerializesModels;

    private $request;
    private $account;

    /**
     * UserCreated constructor.
     * @param request
     */
    public function __construct($request, $account)
    {
        $this->request = $request;
        $this->account = $account;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $env = '';
        if (getenv('APP_ENV') != 'production') {
            $env = ' [DEMO]';
        }
        return $this->subject('New Phone Number' . $env)
                    ->markdown('emails.users.send-phone-number', [
                        'r' => $this->request,
                        'account' => $this->account,
                        'url' => URL::to('/')
                    ]);
    }
}
