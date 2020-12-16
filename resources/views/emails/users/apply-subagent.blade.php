@component('mail::message')
# Hi, {{ $r->first_name }} {{ $r->last_name }}!

{{ $r->added_msg }}

Please note that your request has been sent as below.<br/>

@if ($r->account_type == 'C')
    Please submit necessary ACH documents for a credit account. A checklist will be emailed shortly.
@endif

<h2>
    <a href="http://softpayplus.com/apply_dealer_approval/{{ $account->id }}">Please click here to activate your account right now</a>
</h2>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse: collapse;">
    <tr>
        <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">Business Name</td>
        <td style="border:solid 1px silver; padding: 5px;">{{ $r->business_name }}</td>
    </tr>
    <tr>
        <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">Biz License # / Biz Certificate #</td>
        <td style="border:solid 1px silver; padding: 5px;">{{ $r->biz_license}}</td>
    </tr>
    <tr>
        <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">Contact Name</td>
        <td style="border:solid 1px silver; padding: 5px;">{{ $r->first_name }} {{ $r->last_name }}</td>
    </tr>
    <tr>
        <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">Phone</td>
        <td style="border:solid 1px silver; padding: 5px;">{{ $r->phone }}</td>
    </tr>
    <tr>
        <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">Email</td>
        <td style="border:solid 1px silver; padding: 5px;">{{ $r->email }}</td>
    </tr>
    <tr>
        <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">Business Address</td>
        <td style="border:solid 1px silver; padding: 5px;">{{ $r->address1 }} {{ $r->address2 }}, {{ $r->city }}, {{ $r->state }} {{ $r->zip }}</td>
    </tr>

    @if (!empty($r->store_type))
    <tr>
        <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">Store Type</td>
        <td style="border:solid 1px silver; padding: 5px;">{{ $r->store_type }}</td>
    </tr>
    @endif

    @if (!empty($r->sales_name))
        <tr>
            <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">Sales Person Name</td>
            <td style="border:solid 1px silver; padding: 5px;">{{ $r->sales_name }}</td>
        </tr>
        <tr>
            <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">Phone</td>
            <td style="border:solid 1px silver; padding: 5px;">{{ $r->sales_phone }}</td>
        </tr>
        <tr>
            <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">Email</td>
            <td style="border:solid 1px silver; padding: 5px;">{{ $r->sales_email }}</td>
        </tr>
    @endif
    @if (!empty($r->promo_code))
        <tr>
            <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">Promo Code</td>
            <td style="border:solid 1px silver; padding: 5px;">{{ $r->promo_code }}</td>
        </tr>
    @endif
    <tr>
        <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">Desire User Name</td>
        <td style="border:solid 1px silver; padding: 5px;">{{ $r->user_name }}</td>
    </tr>
    <tr>
        <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">Desire Password</td>
        <td style="border:solid 1px silver; padding: 5px;">
            @if(!empty($r->password))
                {{ $r->password }}
            @else
                Please check a previous email we sent you already.
            @endif
        </td>
    </tr>
    <tr>
        <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">Desired account type</td>
        <td style="border:solid 1px silver; padding: 5px;">{{ $r->account_type == 'C' ? 'Credit Account' : 'Prepaid Account' }}</td>
    </tr>
    <tr>
        <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">Softpayplus Account ID</td>
        <td style="border:solid 1px silver; padding: 5px;">{{ $account->id }}</td>
    </tr>

</table>

@component('mail::button', ['url' => $url])
Visit Site
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
