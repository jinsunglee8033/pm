@component('mail::message')
# Account ID : {{ $r->buyer_id }}
# Buyer Name : {{ $r->buyer_name }}
# Buyer Date : {{ $r->buyer_date }}
# Carrier : {{ $r->carrier }}

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse: collapse;">
    <tr>
        <td style="color: blue; font-size: 19px;">{{ $r->email_message }}</td>
    </tr>
</table>

Here is your Order
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse: collapse;">
    <tr>
        <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">SIM</td>
    </tr>

    <tr>
        <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">
            @foreach($r->sims as $sim)
                {{ $sim }} <br>
            @endforeach
        </td>
    </tr>
</table>

@component('mail::button', ['url' => $url])
    Visit Site
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
