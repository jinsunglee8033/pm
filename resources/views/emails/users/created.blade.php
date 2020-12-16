@component('mail::message')
# Welcome {{ $user->name }}!

Please use below user ID for logging in www.softpayplus.com.

- ID : {{ $user->user_id }}<br/>
- Password : {{ $plain_password }}<br/>

@component('mail::button', ['url' => $url])
Login
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
