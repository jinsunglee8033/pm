<?php
/**
 * Created by PhpStorm.
 * User: Jin
 * Date: 10/12/20
 * Time: 10:39 AM
 */

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use URL;

class EsnOrder extends Mailable
{
    use Queueable, SerializesModels;

    private $request;

    /**
     * UserCreated constructor.
     * @param request
     */
    public function __construct($request)
    {
        $this->request = $request;
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
        return $this->subject('Here is your order' . $env)
          ->markdown('emails.users.esn-order-history', [
            'r' => $this->request,
            'url' => URL::to('/')
          ]);
    }
}
