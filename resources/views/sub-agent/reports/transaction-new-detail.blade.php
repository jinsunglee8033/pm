@extends('sub-agent.layout.default')

@section('content')

    <div class="parallax" data-background="/img/parallax/innerpage.jpg" data-speed="0.5" data-size="50%">
        <div class="overlay white"></div>
        <div class="container">
            <div class="inner-head">
                <div class="row">
                    <div class="col-md-12 text-center">
                        <h4>Transaction Detail</h4>
                        <ol class="breadcrumb">
                            <li><a href="#">Home</a></li>
                            <li><a href="#">Reports</a></li>
                            <li><a href="/sub-agent/reports/transaction-new">Reports</a></li>
                            <li class="active">Detail</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="contain-wrapp padding-bot70">
        <div class="container">
            <div class="panel panel-default">
                <div class="panel-body">
                    <form id="frm_transaction" class="form-horizontal filter" method="post" style="padding:15px;">
                        {!! csrf_field() !!}
                        <div class="row" style="border-bottom:solid 1px #dedede;">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Tx.ID</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="id" name="id"
                                               value="{{ $detail->id }}" readonly/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Carrier</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="carrier" name="carrier"
                                               value="{{ $detail->carrier() }}" readonly/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Action</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="action" name="action"
                                               value="{{ $detail->action }}" readonly/>
                                    </div>
                                </div>
                            </div>
                            @if($detail->status != 'R')
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Product</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="product" name="product"
                                           value="{{ $detail->product_name() }}" readonly/>
                                    </div>
                                </div>
                            </div>
                            @endif
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">SIM</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="sim" name="sim" {!! $sim_length > 0 ? 'maxlength="' . $sim_length . '"' : '' !!}
                                               value="{{ old('sim', $detail->sim) }}" {!! $sim_length > 0 ? 'placeholder="' . $sim_length .' digits max"' : '' !!}/>
                                    </div>
                                </div>
                            </div>
                            @if($detail->status != 'R')
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Amount($)</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="denom" name="denom"
                                               value="{{ $detail->denom }}" readonly/>
                                    </div>
                                </div>
                            </div>
                            @endif
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">ESN</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="esn" id="esn" {!! $esn_length > 0 ? 'maxlength="' . $esn_length . '"' : '' !!}
                                               value="{{ old('esn', $detail->esn) }}" {!! $esn_length > 0 ? 'placeholder="' . $esn_length .' digits max"' : '' !!}/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Pref. Area Code</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="npa" id="npa"
                                               value="{{ $detail->npa }}" readonly/>
                                    </div>
                                </div>
                            </div>


                            @if($detail->status == 'R' && isset($products))
                                @if ($detail->action != 'Port-In')
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label class="col-sm-2 text-right required">Product</label>
                                        <div class="col-sm-10">
                                        @foreach ($products as $o)
                                            <div class="col-sm-2" class="form-group">
                                                <label>{{ $o->name }}</label>

                                                <!-- Start radio -->
                                                @if (isset($o->denominations))
                                                    @foreach($o->denominations as $d)
                                                        <div class="radio">
                                                            <label>
                                                                <input type="radio" {{ in_array($o->carrier, ['H2O', 'ROK']) && ($d->product_id != $detail->product_id || $d->product_id == $detail->product_id && $d->denom != $detail->denom) ? 'disabled' : '' }} name="denom_id" value="{{ $d->id }}" {{ old('denom_id', $denom_id) == $d->id ? 'checked' : '' }}>
                                                                ${{ $d->denom }}
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        @endforeach
                                        </div>
                                    </div>
                                </div>
                                @else
                                <input type="hidden" name="denom_id" value="{{ $denom_id }}">
                                @endif
                            @endif

                        </div>



                        <div id="port-in-row" class="row"
                             style="border-bottom:solid 1px #dedede; margin-top: 5px; display:{{ $detail->action == 'Port-In' ? '' : 'none' }}">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Port-In Number</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="number_to_port"
                                               id="number_to_port" value="{{ $detail->phone }}"/>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label {{ $detail->carrier() == 'H2O' ? 'required' : '' }}">Port-In From</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="current_carrier"
                                               id="current_carrier" value="{{ $detail->current_carrier }}"/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Account #</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="account_no" id="account_no" value="{{ $detail->account_no }}"/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Account PIN</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="account_pin" id="account_pin" value="{{ $detail->account_pin }}"/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Zip Code</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="zip" id="zip" value="{{ $detail->zip }}"/>
                                    </div>
                                </div>
                            </div>
                            @if ($detail->carrier() == 'H2O')
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label required">Under Contract?</label>
                                    <div class="col-sm-8">
                                        <input type="radio" class="radio-inline" name="carrier_contract" value="Y" {{ $detail->carrier_contract == 'Y' ? 'checked' : '' }}/> Yes
                                        <input type="radio" class="radio-inline" name="carrier_contract" value="N" {{ $detail->carrier_contract == 'N' ? 'checked' : '' }} checked/> No
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                        <div class="row" style="margin-top: 15px; border-bottom:solid 1px #dedede;">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label" style="color:red;">Note</label>
                                    <div class="col-sm-8">
                                        <textarea id="note" name="note" class="form-control" rows="4"
                                                  style="margin-top: 5px;"
                                                  placeholder="Please enter note" readonly>{{ $detail->note }}</textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label" style="color:red;">Status</label>
                                    <div class="col-sm-8">
                                        <select class="form-control" id="status" name="status" disabled
                                                data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                            <option value="">Please Select</option>
                                            <option value="N">New</option>
                                            <option value="P">Processing</option>
                                            <option value="C">Completed</option>
                                            <option value="R" selected>Action.Required</option>
                                            <option value="F">Failed</option>
                                        </select>
                                        <textarea id="portable_reason" name="portable_reason" class="form-control" rows="3"
                                                  style="margin-top: 5px;"
                                                  placeholder="" readonly>{{ $detail->portable_reason }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row" style="margin-top: 15px; padding-bottom: 16px; border-bottom:solid 1px #dedede;">
                            <div class="col-sm-6">
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <div class="col-sm-8 col-sm-offset-4">
                                        <a href="/sub-agent/reports/transaction-new">
                                            <button type="button" class="btn btn-default btn-sm">
                                                Back To List
                                            </button>
                                        </a>
                                        @if ($detail->carrier() == 'ROK' && $detail->status == 'R')
                                            <button type="button" class="btn btn-danger btn-sm">Please contact ROK C/S</button>
                                        @else
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                Submit
                                            </button>
                                        @endif

                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            @if ($errors->has('exception'))
                                <div class="alert alert-danger alert-dismissible" role="alert">
                                    <strong>Error!</strong> {{ $errors->first('exception') }}
                                </div>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


@stop