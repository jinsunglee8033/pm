@php

$query = \App\Model\Transaction::join('product', 'transaction.product_id', 'product.id')
  ->join("accounts", 'transaction.account_id', 'accounts.id')
  ->join("accounts as master", "accounts.master_id", "master.id")
  ->Leftjoin("accounts as dist", function($join) {
      $join->on('accounts.parent_id', 'dist.id')
        ->where('dist.type', 'D');
  });

$transactions = $query->where('transaction.account_id', \Auth::user()->account_id)
  ->whereRaw('product_id like \'WGEN%\'')
  ->whereIn('action', ['RTR'])
  ->orderByRaw('ifnull(transaction.mdate, transaction.cdate) desc')
  ->select(
    'transaction.id',
    \DB::raw("if(transaction.type = 'S', 'Sales', 'Void') as type"),
    'master.id as master_id',
    'master.name as master_name',
    'dist.id as dist_id',
    'dist.name as dist_name',
    'accounts.id as account_id',
    'accounts.type as account_type',
    'accounts.name as account_name',
    'product.carrier',
    'product.name as product_name',
    'transaction.denom',
    'transaction.rtr_month',
    'transaction.collection_amt',
    'transaction.fee',
    'transaction.pm_fee',
    'transaction.net_revenue',
    'transaction.action',
    'transaction.api',
    'transaction.phone',
    'transaction.pin',
    'accounts.loc_id',
    'accounts.outlet_id',
    'accounts.state as loc_state',
    \DB::raw('concat(accounts.address1, " ", ifnull(accounts.address2, ""), ", ", accounts.city, " ", accounts.state, " ", accounts.zip) as loc_address'),
    'transaction.status',
    \DB::raw('case when transaction.note like \'%[EX-%\' then \'Connection Refused\' else transaction.note end as note'),
    'transaction.created_by',
    'transaction.cdate',
    'transaction.mdate'
  )->limit(10)->get();
@endphp


<table class="parameter-product table-bordered table-hover table-condensed filter">
    <thead>
    <tr class="active">
        <td><strong>ID</strong></td>
        <td><strong>Type</strong></td>
        <td><strong>Status</strong></td>
        <td><strong>Note</strong></td>
        <td><strong>Product</strong></td>
        <td><strong>Denom($)</strong></td>
        <td><strong>RTR.M</strong></td>
        <td><strong>Total($)</strong></td>
        <td><strong>Vendor.Fee($)</strong></td>
        <td><strong>Action</strong></td>
        <td><strong>Phone</strong></td>
        <td><strong>User.ID</strong></td>
        <td><strong>Last.Updated</strong></td>
    </tr>
    </thead>
    <tbody>
    @if (isset($transactions) && count($transactions) > 0)
        @foreach ($transactions as $o)
            <tr>
                <td>
                    @if ($o->status == 'C')
                        <a target="_RECEIPT" href="/sub-agent/reports/receipt/{{ $o->id }}">{{ $o->id }}</a>
                    @else
                        {{ $o->id }}
                    @endif
                </td>
                <td style="{{ $o->type_name == 'Void' ? 'color:red;' : '' }}">{{ $o->type_name }}</td>
                @if ($o->status == 'R')
                    <td><a href="/sub-agent/reports/transaction/{{ $o->id }}">{!! $o->status_name() !!}</a></td>
                @else
                    <td>{!! $o->status_name() !!}</td>
                @endif
                <td style="max-width: 150px;">
                    @if (!empty($o->note))
                        {{ $o->note }}
                    @else
                    @endif
                </td>
                <td>{{ $o->product_name  }}</td>
                <td>${{ $o->denom }}</td>
                <td>{{ $o->rtr_month }}</td>
                <td style="{{ $o->type_name == 'Void' ? 'color:red;' : '' }}">${{ $o->collection_amt }}</td>
                <td style="{{ $o->type_name == 'Void' ? 'color:red;' : '' }}">${{ $o->fee + $o->pm_fee}}</td>
                <td>{{ $o->action }}</td>
                <td>{{ $o->phone }}</td>
                <td>{{ $o->created_by }}</td>
                <td>{{ $o->last_updated }}</td>
            </tr>
        @endforeach
    @else
        <tr>
            <td colspan="20" class="text-center">No Record Found</td>
        </tr>
    @endif
    </tbody>
</table>