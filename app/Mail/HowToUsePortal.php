<?php

namespace App\Mail;

use App\Model\ACHPosting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class HowToUsePortal extends Mailable
{
    use Queueable, SerializesModels;

    public $request;

    /**
     *
     *
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
        return $this->view('emails.users.how-to-use-portal');
    }
}
