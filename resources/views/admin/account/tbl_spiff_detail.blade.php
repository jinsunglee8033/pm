
<table class="table table-bordered">
    <thead>
    <tr>
        <th>Product</th>
        <th>Amount($)</th>
        <th>Spiff.1st</th>
        <th>Spiff.2nd</th>
        <th>Spiff.3rd</th>
    </tr>
    </thead>
    <tbody>
    @if (!empty($spiff_setups))
    @foreach ($spiff_setups as $o)
        <tr>
            <td>{{ $o->product }}</td>
            <td>${{ number_format($o->denom, 2) }}</td>
            <td>${{ number_format($o->spiff_1st, 2) }}</td>
            <td>${{ number_format($o->spiff_2nd, 2) }}</td>
            <td>${{ number_format($o->spiff_3rd, 2) }}</td>
        </tr>
    @endforeach
    @endif
    </tbody>
</table>