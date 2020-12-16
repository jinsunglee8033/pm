@extends('sub-agent.layout.default')

@section('content')
    <style type="text/css">

        .receipt .row {
            border: 1px solid #e5e5e5;
        }

        .receipt .col-sm-4 {
            border-right: 1px solid #e5e5e5;
        }

        .row + .row {
            border-top: 0;
        }

        .divider2 {
            margin: 5px 0px !important;
        }

        hr {
            margin-top: 5px !important;
            margin-bottom: 5px !important;
        }

    </style>

    <script type="text/javascript">
        function printDiv() {
            window.print();
        }

    </script>


    <div id="activate_invoice" class="modal fade " tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false"
         style="display:block;">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Activate / Port-In Success</h4>
                </div>
                <div class="modal-body receipt" id="activate_invoice_body">
                    <p>
                        Your request is being processed.<br/>
                        Please refer to "Reports -> Activation / Port-In" for more information.
                    </p>
                    <div class="row">
                        <div class="col-sm-4">Date / Time</div>
                        <div class="col-sm-8">{{ date('Y-M-d H:i:s') }}</div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4">Invoice no.</div>
                        <div class="col-sm-8">{{ $trans->id }}</div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4">Phone no.</div>
                        <div class="col-sm-8">{{ $trans->phone }}</div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4">SIM</div>
                        <div class="col-sm-8">{{ $trans->sim }}</div>
                    </div>
                    @if(!empty(session('esn')))
                        <div class="row">
                            <div class="col-sm-4">ESN</div>
                            <div class="col-sm-8">{{ $trans->esn }}</div>
                        </div>
                    @endif
                    <div class="row">
                        <div class="col-sm-4">Carrier</div>
                        <div class="col-sm-8">{{ $trans->product->carrier }}</div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4">Product</div>
                        <div class="col-sm-8">{{ $trans->product->name }}</div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4">Plan Price</div>
                        <div class="col-sm-8">${{ number_format($trans->denom, 2) }}</div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4">Refill Month</div>
                        <div class="col-sm-8">{{ $trans->rtr_month }}</div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4">Sub Total</div>
                        <div class="col-sm-8">${{ number_format($trans->denom * $trans->rtr_month, 2) }}</div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4">Vendor Fee</div>
                        <div class="col-sm-8">${{ number_format($trans->fee, 2) }}</div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4">Total</div>
                        <div class="col-sm-8">${{ number_format($trans->denom * $trans->rtr_month + $trans->fee, 2) }}</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="printDiv()">Print</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@stop