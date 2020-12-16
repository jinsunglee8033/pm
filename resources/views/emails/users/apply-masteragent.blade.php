@component('mail::message')
# Hi, {{ $r->first_name }} {{ $r->last_name }}!

Please note that your request has been sent as below

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse: collapse;">
    <tr>
        <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">Business Name</td>
        <td style="border:solid 1px silver; padding: 5px;">{{ $r->business_name }}</td>
    </tr>
    @if (!empty($r->biz_license))
    <tr>
        <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">Biz License # / Biz Certificate #</td>
        <td style="border:solid 1px silver; padding: 5px;">{{ $r->biz_license }}</td>
    </tr>
    @endif
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
        <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">Address</td>
        <td style="border:solid 1px silver; padding: 5px;">{{ $r->address }}</td>
    </tr>

    <tr>
        <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">City</td>
        <td style="border:solid 1px silver; padding: 5px;">{{ $r->city }}</td>
    </tr>
    <tr>
        <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">State</td>
        <td style="border:solid 1px silver; padding: 5px;">{{ $r->state }}</td>
    </tr>
    <tr>
        <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">Zip</td>
        <td style="border:solid 1px silver; padding: 5px;">{{ $r->zip }}</td>
    </tr>
    <tr>
        <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">Number of Your Distribution
            <br> Retail  Locations</td>
        <td style="border:solid 1px silver; padding: 5px;">{{ $r->retail_location_no }}</td>
    </tr>
    <tr>
        <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">Desire User Name</td>
        <td style="border:solid 1px silver; padding: 5px;">{{ $r->user_name }}</td>
    </tr>
    <tr>
        <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">Desire Password</td>
        <td style="border:solid 1px silver; padding: 5px;">{{ $r->password }}</td>
    </tr>

</table>

@component('mail::button', ['url' => $url])
Visit Site
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
