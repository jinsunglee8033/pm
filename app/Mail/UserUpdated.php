<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\User;
use URL;

class UserUpdated extends Mailable
{
    use Queueable, SerializesModels;

    private $old_user;
    private $new_user;
    private $plain_password;
    private $comment;
    private $account_id;

    /**
     * UserUpdated constructor.
     * @param User $old_user
     * @param User $new_user
     * @param $plain_password
     */
    public function __construct(User $old_user, User $new_user, $plain_password, $comment, $account_id)
    {
        $this->old_user = $old_user;
        $this->new_user = $new_user;
        $this->plain_password = $plain_password;
        $this->comment = $comment;
        $this->account_id = $account_id;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.users.updated', [
            'old_user' => $this->old_user,
            'new_user' => $this->new_user,
            'plain_password' => $this->plain_password,
            'comment' => $this->comment,
            'account_id' => $this->account_id,
            'url' => URL::to('/login')
        ]);
    }
}
