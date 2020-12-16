<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\User;
use URL;

class UserCreated extends Mailable
{
    use Queueable, SerializesModels;

    private $user;
    private $plain_password;

    /**
     * UserCreated constructor.
     * @param User $user
     */
    public function __construct(User $user, $password)
    {
        $this->user = $user;
        $this->plain_password = $password;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.users.created', [
            'user' => $this->user,
            'url' => URL::to('/'),
            'plain_password' => $this->plain_password
        ]);
    }
}
