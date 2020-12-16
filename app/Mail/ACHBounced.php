<?php

namespace App\Mail;

use App\Model\ACHPosting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ACHBounced extends Mailable
{
    use Queueable, SerializesModels;

    public $ach;

    /**
     * ACHBounced constructor.
     * @param ACHPosting $ach_posting
     */
    public function __construct(ACHPosting $ach_posting)
    {
        $this->ach = $ach_posting;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.ach.bounced');
    }
}
