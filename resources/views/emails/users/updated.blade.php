@component('mail::message')
# Hi, {{ $old_user->name }}!

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse: collapse;">
    <tr>
        <td style="color: blue; font-size: 19px;">{{ $comment }}</td>
    </tr>
</table>

Please note that your information has been updated as below

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse: collapse;">
    <tr>
        <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">Account ID</td>
        <td style="border:solid 1px silver; padding: 5px;">{{ $account_id }}</td>
    </tr>
    <tr>
        <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">User ID</td>
        <td style="border:solid 1px silver; padding: 5px;">{{ $old_user->user_id }}</td>
    </tr>
    @if (!empty($plain_password))
    <tr>
        <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">Password</td>
        <td style="border:solid 1px silver; padding: 5px;">{{ $plain_password }}</td>
    </tr>
    @endif
    @if ($old_user->name != $new_user->name)
    <tr>
        <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">Full Name</td>
        <td style="border:solid 1px silver; padding: 5px;">{{ $new_user->name}}</td>
    </tr>
    @endif
    @if ($old_user->email != $new_user->email)
    <tr>
        <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">Email</td>
        <td style="border:solid 1px silver; padding: 5px;">{{ $new_user->email }}</td>
    </tr>
    @endif
    @if ($old_user->role != $new_user->role)
    <tr>
        <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">Role</td>
        <td style="border:solid 1px silver; padding: 5px;">{{ $new_user->role_name }}</td>
    </tr>
    @endif
    @if ($old_user->status != $new_user->status)
    <tr>
        <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">Status</td>
        <td style="border:solid 1px silver; padding: 5px;">{{ $new_user->status_name }}</td>
    </tr>
    @endif
</table>

@component('mail::button', ['url' => $url])
Login
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
