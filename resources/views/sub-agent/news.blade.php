@extends('sub-agent.layout.default')

@section('content')

        <!-- Start contain wrapp -->
        <div id="testimoni" class="contain-wrapp gray-container">
            <div class="container">

                <div class="row">
                    <div class="col-md-8 col-md-offset-2">
                        <div class="title-head centered" style="margin-bottom:20px;">
                            <h2>NEWS</h2>
                            <p>We are Master Agent of VERIZON PREPAID, H2O, LYCA and more! </p>
                        </div>
                    </div>
                </div>
                <div class="row" style="margin-bottom:20px; z-index: 1">
                    <div class="col-md-12">
                        <form method="post" onsubmit="this.form.submit()">
                            {!! csrf_field() !!}
                            <div class="form-group">
                                <div class="col-md-1" style="text-align:right;">
                                    <span style="color: black;font-size:18px;
                                    text-align:right;"><strong>Product:</strong></span>
                                </div>
                                <div class="col-md-4">
                                    <select name="product" class="form-control" onchange="this.form.submit()">
                                        <option value="">Show All</option>
                                        <option value="PM Market" {{ old('product', $product) == 'PM Market' ? 'selected' : '' }}>PM Market</option>
                                        <option value="AT&T" {{ old('product', $product) == 'AT&T' ? 'selected' : '' }}>AT&T</option>
                                        <option value="AT&T PR/VI" {{ old('product', $product) == 'AT&T PR/VI' ? 'selected' : ''}}>AT&T PR/VI</option>
                                        <option value="Emida" {{ old('product', $product) == 'Emida' ? 'selected' : '' }}>Emida</option>
                                        <option value="ePay" {{ old('product', $product) == 'ePay' ? 'selected' : '' }}>ePay</option>
                                        <option value="FreeUP" {{ old('product', $product) == 'FreeUP' ? 'selected' : '' }}>FreeUP</option>
                                        <option value="GEN Mobile" {{ old('product', $product) == 'GEN Mobile' ? 'selected' : ''}}>GEN Mobile</option>
                                        <option value="Boom Mobile" {{ old('product', $product) == 'Boom Mobile' ? 'selected' : ''}}>Boom Mobile</option>
                                        <option value="H2O" {{ old('product', $product) == 'H2O' ? 'selected' : '' }}>H2O</option>
                                        <option value="Liberty Mobile" {{ old('product', $product) == 'Liberty Mobile' ? 'selected' : '' }}>Liberty Mobile</option>
                                        <option value="Incomm (Qpay)" {{ old('product', $product) == 'Incomm (Qpay)' ? 'selected' : '' }}>Incomm (Qpay)</option>
                                        <option value="Lyca" {{ old('product', $product) == 'Lyca' ? 'selected' : '' }}>Lyca</option>
                                        <option value="MetroPcs" {{ old('product', $product) == 'MetroPcs' ? 'selected' : '' }}>MetroPcs</option>
                                        <option value="Net10" {{ old('product', $product) == 'Net10' ? 'selected' : '' }}>Net10</option>
                                        <option value="PagePlus" {{ old('product', $product) == 'PagePlus' ? 'selected' : '' }}>PagePlus</option>
                                        <option value="Patriot Mobile" {{ old('product', $product) == 'Patriot Mobile' ? 'selected' : '' }}>Patriot Mobile</option>
                                        <option value="ROK" {{ old('product', $product) == 'ROK' ? 'selected' : '' }}>ROK</option>
                                        <option value="Simple Mobile" {{ old('product', $product) == 'Simple Mobile' ? 'selected' : '' }}>Simple Mobile</option>
                                        <option value="Telcel America" {{ old('product', $product) == 'Telcel America' ? 'selected' : '' }}>Telcel America</option>
                                        <option value="Ultra Mobile" {{ old('product', $product) == 'Ultra Mobile' ? 'selected' : '' }}>Ultra Mobile</option>
                                        <option value="Verizon" {{ old('product', $product) == 'Verizon' ? 'selected' : '' }}>Verizon</option>
                                        <option value="VidaPay" {{ old('product', $product) == 'VidaPay' ? 'selected' : '' }}>VidaPay</option>
                                    </select>
                                </div>
                                <div class="col-md-1">
                                </div>
                                <div class="col-md-1" style="text-align:right;">
                                    <span style="color: black;font-size:18px;"><strong>Subject:</strong></span>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" name="subject" value="{{old('subject',
                                    $subject)}}"/>
                                </div>
                                <div class="col-md-1">
                                    <button type="submit" class="btn btn-primary btn-sm" id="btn_search">Search</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @if (isset($news))
                    @foreach ($news as $o)
                        <div class="row" id="{!! $o->id !!}" style="margin-top: -100px;">
                            <div class="col-md-12">
                                <div class="testimoni-wrapp">
                                    <div class="testimoni-contain">
                                        <blockquote>
                                            <p>
                                                <b>{!! $o->subject !!}</b><br/>
                                                {!! $o->body !!}
                                            </p>
                                        </blockquote>
                                    </div>

                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
        <!-- End contain wrapp -->
@stop