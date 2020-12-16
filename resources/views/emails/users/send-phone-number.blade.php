@component('mail::message')
# Your Activation Request has been processed successfully.

Here is your new phone number.<br/>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse: collapse;">
    <tr>
        <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">Carrier</td>
        <td style="border:solid 1px silver; padding: 5px;">
            @if($r->product_id == 'WFRUPA')
                FreeUP Mobile
            @elseif ($r->product_id == 'WGENA')
                Gen Mobile
            @endif
        </td>
    </tr>
    <tr>
        <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">Phone</td>
        <td style="border:solid 1px silver; padding: 5px;">{{ $r->phone }}</td>
    </tr>
    <tr>
        @if ($r->product_id == 'WFRUPA')
            <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">IMEI</td>
        @else
            <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">ESN</td>
        @endif
        <td style="border:solid 1px silver; padding: 5px;">{{ $r->esn}}</td>
    </tr>
    <tr>
        <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">SIM</td>
        <td style="border:solid 1px silver; padding: 5px;">{{ $r->sim }}</td>
    </tr>
    <tr>
        <td style="background-color:#efefef; border:solid 1px silver; padding: 5px;">Total</td>
        <td style="border:solid 1px silver; padding: 5px;">${{ $r->collection_amt + $r->fee + $r->pm_fee }}</td>
    </tr>

</table>

@if ($r->product_id == 'WGENA')
<p style="color: red">
    <strong>Activation</strong>: Dial ##25327# [talk] for iPhones or
    ##72786# [talk] for Androids; and then dial ##873283# [talk]
    to update your coverage.
</p>
@endif

@component('mail::button', ['url' => $url])
Visit Site
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
