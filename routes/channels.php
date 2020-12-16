<?php

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

use App\Lib\Helper;

Broadcast::channel('App.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel(getenv('APP_ENV') . '.transaction.{account_id}', function ($user, $account_id) {

    Helper::log('### inside channel test ###', [
        'user' => $user,
        'account_id' => $account_id,
        'channel' => getenv('APP_ENV') . '.transaction.' . $account_id
    ]);

    return (int) $user->account_id == $account_id;
});

Broadcast::channel(getenv('APP_ENV') . '.transaction.root', function ($user) {

    Helper::log('### inside channel test ###', [
        'user' => $user,
        'channel' => getenv('APP_ENV') . '.transaction.root'
    ]);

    return (int) $user->account_id == 100000;
});
