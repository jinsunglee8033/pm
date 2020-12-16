@extends('sub-agent.layout.default')

@section('content')

    <script type="text/javascript">
        function show_bill_detail() {
            $('.bill-summary').hide();
            $('.bill-detail').show();
        }

        function hide_bill_detail() {
            $('.bill-summary').show();
            $('.bill-detail').hide();
        }
    </script>

    <div class="parallax" data-background="/img/parallax/innerpage.jpg" data-speed="0.5" data-size="50%">
        <div class="overlay white"></div>
        <div class="container">
            <div class="inner-head">
                <div class="row">
                    <div class="col-md-12 text-center">
                        <h4>Invoice Detail</h4>
                        <ol class="breadcrumb">
                            <li><a href="#">Home</a></li>
                            <li><a href="#">Reports</a></li>
                            <li><a href="/sub-agent/reports/invoices">Reports</a></li>
                            <li class="active">Invoice Detail</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="contain-wrapp padding-bot70">
        <div class="container">

            <div class="row">
                <div class="col-md-6">
                    <div class="well">
                        <div class="form-horizontal">
                            <div class="form-group">
                                <label class="control-label col-md-4">Invoice.No</label>
                                <div class="col-md-8">
                                    <span class="form-control">{{ $bill->id }}</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-4">Period.From</label>
                                <div class="col-md-8">
                                    <span class="form-control">{{ $bill->period_from }}</span>
                                </div>
                            </div>
                            <hr style="height:1px; background-color:#9e9e9e"/>
                            <div class="form-group">
                                <label class="control-label col-md-4">Invoice.Amount</label>
                                <div class="col-md-4">
                                    <span class="form-control">${{ number_format($bill->bill_amt, 2) }}</span>
                                </div>
                                <div class="col-md-4">
                                    <span class="control-label">Payable - Extra.Earning</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="well">
                        <div class="form-horizontal">
                            <div class="form-group">
                                <label class="control-label col-md-4">Invoice.Date</label>
                                <div class="col-md-8">
                                    <span class="form-control">{{ $bill->bill_date }}</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-4">Period.To</label>
                                <div class="col-md-8">
                                    <span class="form-control">{{ $bill->period_to }}</span>
                                </div>
                            </div>
                            <hr style="height:1px; background-color:#9e9e9e"/>
                            <div class="form-group">
                                <label class="control-label col-md-4">Paid.Total</label>
                                <div class="col-md-4">
                                    <span class="form-control">${{ number_format($bill->paid_total, 2) }}</span>
                                </div>
                                <div class="col-md-4 text-right">
                                    <button class="btn btn-primary btn-sm bill-summary" onclick="show_bill_detail()">+
                                    </button>
                                    <button class="btn btn-primary btn-sm bill-detail" style="display:none;"
                                            onclick="hide_bill_detail()">-
                                    </button>
                                    <a class="btn btn-default btn-sm" href="/sub-agent/reports/invoices">Back</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row bill-detail" style="display:none;">
                <div class="col-md-6">
                    <div class="well">
                        <div class="form-horizontal">
                            <div class="form-group">
                                <label class="control-label col-md-4">Starting Balance</label>
                                <div class="col-md-8">
                                    <span class="form-control">${{ number_format($bill->starting_balance, 2) }}</span>
                                </div>
                            </div>
                            <!--div class="form-group">
                                <label class="control-label col-md-4">Gross</label>
                                <div class="col-md-8">
                                    <span class="form-control">${{ number_format($bill->gross, 2) }}</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-4">Gross Margin</label>
                                <div class="col-md-8">
                                    <span class="form-control">${{ number_format($bill->net_margin, 2) }}</span>
                                </div>
                            </div-->
                            <div class="form-group">
                                <label class="control-label col-md-4">Net Payable</label>
                                <div class="col-md-8">
                                    <span class="form-control">${{ number_format($bill->net_revenue, 2) }}</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-4">Vendor.Fee</label>
                                <div class="col-md-8">
                                    <span class="form-control">${{ number_format($bill->fee + $bill->pm_fee, 2) }}</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-4">Consignment</label>
                                <div class="col-md-8">
                                    <span class="form-control">${{ number_format($bill->consignment, 2) }}</span>
                                </div>
                            </div>
                            @if ($bill->ach_bounce_amt != 0)
                                <div class="form-group">
                                    <label class="control-label col-md-4">ACH.Bounce</label>
                                    <div class="col-md-8">
                                        <span class="form-control">${{ number_format($bill->ach_bounce_amt, 2) }}</span>
                                    </div>
                                </div>
                            @endif
                            @if ($bill->ach_bounce_fee != 0)
                                <div class="form-group">
                                    <label class="control-label col-md-4">ACH.Bounce.Fee</label>
                                    <div class="col-md-8">
                                        <span class="form-control">${{ number_format($bill->ach_bounce_fee, 2) }}</span>
                                    </div>
                                </div>
                            @endif
                            @if ($bill->dist_paid_amt != 0)
                                <div class="form-group">
                                    <label class="control-label col-md-4">Paid.For.Children</label>
                                    <div class="col-md-8">
                                        <span class="form-control">${{ number_format($bill->dist_paid_amt, 2) }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="well">
                        <div class="form-horizontal">
                            <div class="form-group">
                                <label class="control-label col-md-4">Spiff</label>
                                <div class="col-md-8">
                                    <span class="form-control">${{ number_format($bill->spiff_credit - $bill->spiff_debit, 2) }}</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-4">Rebate</label>
                                <div class="col-md-8">
                                    <span class="form-control">${{ number_format($bill->rebate_credit - $bill->rebate_debit, 2) }}</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-4">Residual</label>
                                <div class="col-md-8">
                                    <span class="form-control">${{ number_format($bill->residual, 2) }}</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-4">Adjustment</label>
                                <div class="col-md-8">
                                    <span class="form-control">${{ number_format($bill->adjustment, 2) }}</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-4">Promotion</label>
                                <div class="col-md-8">
                                    <span class="form-control">${{ number_format($bill->promotion, 2) }}</span>
                                </div>
                            </div>
                            <hr style="height:1px; background-color:#9e9e9e"/>
                            <div class="form-group">
                                <label class="control-label col-md-4">Extra.Earning</label>
                                <div class="col-md-8">
                                    <span class="form-control">${{ number_format($bill->extra, 2) }}</span>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="col-md-6">

                    <div class="well">
                        <div class="form-horizontal">
                            <div class="form-group">
                                <label class="control-label col-md-4">Starting Deposit</label>
                                <div class="col-md-8">
                                    <span class="form-control">${{ number_format($bill->starting_deposit, 2) }}</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-4">New Deposit</label>
                                <div class="col-md-8">
                                    <span class="form-control">${{ number_format($bill->new_deposit, 2) }}</span>
                                </div>
                            </div>
                            <hr style="height:1px; background-color:#9e9e9e"/>
                            <div class="form-group">
                                <label class="control-label col-md-4">Deposit Total</label>
                                <div class="col-md-8">
                                    <span class="form-control">${{ number_format($bill->deposit_total, 2) }}</span>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="well">
                        <div class="form-horizontal">
                            <div class="form-group">
                                <label class="control-label col-md-4">Paid.By.Deposit</label>
                                <div class="col-md-8">
                                    <span class="form-control">${{ number_format($bill->deposit_paid_amt, 2) }}</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-4">Paid.By.ACH</label>
                                <div class="col-md-8">
                                    <span class="form-control">${{ number_format($bill->ach_paid_amt, 2) }}</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-4">Paid.By.Parent</label>
                                <div class="col-md-8">
                                    <span class="form-control">${{ number_format($bill->dist_paid_amt, 2) }}</span>
                                </div>
                            </div>
                            <hr style="height:1px; background-color:#9e9e9e"/>
                            <div class="form-group">
                                <label class="control-label col-md-4">Paid.Total</label>
                                <div class="col-md-8">
                                    <span class="form-control">${{ number_format($bill->paid_total, 2) }}</span>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="well">
                        <div class="form-horizontal">
                            <div class="form-group">
                                <label class="control-label col-md-4">Ending.Balance</label>
                                <div class="col-md-8">
                                    <span class="form-control">${{ number_format($bill->ending_balance, 2) }}</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-4">Ending.Deposit</label>
                                <div class="col-md-8">
                                    <span class="form-control">${{ number_format($bill->ending_deposit, 2) }}</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">


                    <table class="table table-bordered">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Carrier</th>
                            <th>Product</th>
                            <th>Action</th>
                            <th>Phone</th>
                            <th>Denom</th>
                            <th>RTR.M</th>
                            <th>Gross</th>
                            <th>Net</th>
                            <th>Vendor.Fee</th>
                            <th>Spiff.M1</th>
                            <th>Rebate.M1</th>
                            <th>Date</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if (count($data) > 0)
                            @foreach ($data as $o)
                                <tr style="{{ $o->type_name == 'Void' ? 'color:red' : '' }}">
                                    <td>{{ $o->id }}</td>
                                    <td>{{ $o->type_name }}</td>
                                    <td>{{ $o->carrier() }}</td>
                                    <td>{{ $o->product_name() }}</td>
                                    <td>{{ $o->action }}</td>
                                    <td>{{ $o->action == 'PIN' ? Helper::mask_pin($o->pin) : $o->phone }}</td>
                                    <td>{{ $o->denom }}</td>
                                    <td>{{ $o->rtr_month }}</td>
                                    @if ($o->type_name == 'Void')
                                        <td style="color: red">$
                                            @if (number_format($o->collection_amt, 2) !=0)
                                                -{{ number_format($o->collection_amt, 2) }}
                                            @else
                                                {{ number_format($o->collection_amt, 2) }}
                                            @endif
                                        </td>
                                        <td style="color: red">$
                                            @if (number_format($o->net_revenue, 2) !=0)
                                                -{{ number_format($o->net_revenue, 2) }}
                                            @else
                                                {{ number_format($o->net_revenue, 2) }}
                                            @endif
                                        </td>
                                        <td style="color: red">$
                                            @if (number_format($o->fee + $o->pm_fee, 2) !=0)
                                                -{{ number_format($o->fee + $o->pm_fee, 2) }}
                                            @else
                                                {{ number_format($o->fee + $o->pm_fee, 2) }}
                                            @endif
                                        </td>
                                        <td style="color: red">$
                                            @if(number_format($o->spiff_amt, 2) !=0)
                                                -{{ number_format($o->spiff_amt, 2) }}
                                            @else
                                                {{ number_format($o->spiff_amt, 2) }}
                                            @endif
                                        </td>
                                        <td style="color: red">$
                                            @if(number_format($o->rebate_amt, 2) !=0)
                                                -{{ number_format($o->rebate_amt, 2) }}
                                            @else
                                                {{ number_format($o->rebate_amt, 2) }}
                                            @endif
                                        </td>
                                    @else
                                        <td>${{ number_format($o->collection_amt, 2) }}</td>
                                        <td>${{ number_format($o->net_revenue, 2) }}</td>
                                        <td>${{ number_format($o->fee + $o->pm_fee, 2) }}</td>
                                        <td>${{ number_format($o->spiff_amt, 2) }}</td>
                                        <td>${{ number_format($o->rebate_amt, 2) }}</td>
                                    @endif
                                    <td>{{ $o->cdate }}</td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="30">No Record Found.</td>
                            </tr>
                        @endif
                        </tbody>
                        <tfoot>
                        <tr>
                            <th colspan="8" class="text-right">
                                Total {{ $data->total() }} Record(s).
                            </th>
                            <th>
                                ${{ number_format($collection_amt, 2) }}
                            </th>
                            <th>
                                ${{ number_format($net_revenue, 2) }}
                            </th>
                            <th>
                                ${{ number_format($fee, 2) }}
                            </th>
                            <th>
                                ${{ number_format($spiff, 2) }}
                            </th>
                            <th>
                                ${{ number_format($rebate, 2) }}
                            </th>
                            <th></th>
                        </tr>
                        </tfoot>
                    </table>
                    <div class="row">
                        <div class="col-md-12">
                            <span style="color:red; font-weight:bold;">Note: For Spiff.M2, Spiff.M3, Rebate.M2, Rebate.M3 and Residual detail, please use Spiff / Residual Report instead.</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-8 text-left">
                    {{ $data->appends(Request::except('page'))->links() }}
                </div>
                <div class="col-sm-4 text-right">
                    <form method="post" action="/sub-agent/reports/invoices/{{ $bill->id }}">
                        {!! csrf_field() !!}
                        <input type="hidden" name="export" value="Y"/>
                        <button class="btn btn-default btn-sm">Export Transactions</button>
                    </form>
                </div>
            </div>
        </div>
    </div>


@stop