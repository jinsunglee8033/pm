<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 3/13/19
 * Time: 10:39 AM
 */

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use URL;

class ApplyMasterAgent extends Mailable
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
        return $this->subject('Apply for Master Agent or ISO' . $env)
          ->markdown('emails.users.apply-masteragent', [
            'r' => $this->request,
            'url' => URL::to('/')
          ]);
    }
}
