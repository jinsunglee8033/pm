@extends('admin.layout.default_shop')

@section('content')

    <script type="text/javascript">
        window.onload = function() {
            $('#virtual_rep').find('textarea').val('');

            $('.panel-collapse.in').collapse('hide');
            $('#panel1').collapse('show');
            $('#vr_category').val('O');

            $("#sdate").datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $("#edate").datetimepicker({
                format: 'YYYY-MM-DD'
            });

        };

        var products = [];

        function change_qty() {

            var price = 0;
            products = [];

            $("input[name='product[]']").each(function(){

                var sku = $.trim($(this).val());
                var key = sku.replace(/\W+/g, '_');

                if ($('#count_' + key).val() > 0) {

                    var cnt = $.trim($('#count_' + key).val());
                    var prc = $.trim($('#denom_' + key).text());

                    products.push([sku,prc,cnt]);
                    price += prc * cnt;
                }
            });

            $('#vr_price').val(price);
        }

        function quick_search(search_name, search_column, month) {
            $('#qs_' + search_column).val(search_name);
            $('#qs_service_month').val(month);
            $('#frm_quick_search').submit();
        }

        function quick_search_promotion(promotion) {
            $('#qs_promotion').val(promotion);
            $('#frm_quick_search').submit();
        }

        function save_vr_request() {

            myApp.showLoading();

            if (products.length == 0 && !$('#vr_comments').val()) {
                myApp.showError('Please enter Order information or General Request!');
                myApp.hideLoading();
                return;
            }

            $.ajax({
                url: '/admin/virtual-rep/save',
                data: {
                    _token: '{!! csrf_token() !!}',
                    vr_category: $('#vr_category').val(),
                    vr_order: $('#vr_order').val(),
                    vr_price: $('#vr_price').val(),
                    pay_method: $('input[name="pay_method"]:checked').val(),
                    vr_comments: $('#vr_comments').val(),
                    products : products
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        myApp.showSuccess('Your request has been successfully submitted!');

                        location.href = "/admin/reports/vr-request";


                    } else {
                        myApp.showError(res.msg);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            });

        }

        function add_to_cart(id) {

            var qty = parseInt($('#qty_' + id).val());
            if (qty < 1) {
                myApp.showError('Please enter at least one');
                return;
            }
            var account_type = $('#account_type').val();
            $.ajax({
                url: '/admin/virtual-rep/add_to_cart',
                data: {
                    id: id,
                    qty: qty,
                    account_type: account_type
                },
                cache: false,
                type: 'get',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        // myApp.showSuccess('Your request has been successfully submitted!');
                        $('#modal_qty').text(qty);
                        $('#modal_cart').modal();
                    } else {
                        myApp.showError(res.msg);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }
            });
        }

    </script>

    <script src="/js/exif.js"></script>
    <style type="text/css">
        .rotate-90 {
          -moz-transform: rotate(90deg);
          -webkit-transform: rotate(90deg);
          -o-transform: rotate(90deg);
          transform: rotate(90deg);
        }

        .rotate-180 {
          -moz-transform: rotate(180deg);
          -webkit-transform: rotate(180deg);
          -o-transform: rotate(180deg);
          transform: rotate(180deg);
        }

        .rotate-270 {
          -moz-transform: rotate(270deg);
          -webkit-transform: rotate(270deg);
          -o-transform: rotate(270deg);
          transform: rotate(270deg);
        }

        .flip {
          -moz-transform: scaleX(-1);
          -webkit-transform: scaleX(-1);
          -o-transform: scaleX(-1);
          transform: scaleX(-1);
        }

        .flip-and-rotate-90 {
          -moz-transform: rotate(90deg) scaleX(-1);
          -webkit-transform: rotate(90deg) scaleX(-1);
          -o-transform: rotate(90deg) scaleX(-1);
          transform: rotate(90deg) scaleX(-1);
        }

        .flip-and-rotate-180 {
          -moz-transform: rotate(180deg) scaleX(-1);
          -webkit-transform: rotate(180deg) scaleX(-1);
          -o-transform: rotate(180deg) scaleX(-1);
          transform: rotate(180deg) scaleX(-1);
        }

        .flip-and-rotate-270 {
          -moz-transform: rotate(270deg) scaleX(-1);
          -webkit-transform: rotate(270deg) scaleX(-1);
          -o-transform: rotate(270deg) scaleX(-1);
          transform: rotate(270deg) scaleX(-1);
        }
    </style>

    <script type="text/javascript">
        function fixExifOrientation(img) {
            EXIF.getData(img, function() {
                switch(parseInt(EXIF.getTag(this, "Orientation"))) {
                    case 2:
                        img.classList.add('flip'); break;
                    case 3:
                        img.classList.add('rotate-180'); break;
                    case 4:
                        img.classList.add('flip-and-rotate-180'); break;
                    case 5:
                        img.classList.add('flip-and-rotate-270'); break;
                    case 6:
                        img.classList.add('rotate-90'); break;
                    case 7:
                        img.classList.add('flip-and-rotate-90'); break;
                    case 8:
                        img.classList.add('rotate-270'); break;
                }
            });
        }
    </script>

    <!-- Start parallax -->
    <div class="parallax" data-background="/img/parallax/innerpage.jpg" data-speed="0.5" data-size="50%">
        
        <div class="overlay white"></div>
        <div class="container">
            <div class="inner-head">
                <div class="row">
                    <div class="col-md-12 text-center">
                        <h4>Market Place</h4>
                        <b>
                        <ol class="breadcrumb">
                            <li class="active">Order</li>
                            <li><a href="/admin/virtual-rep/cart">View Cart / Proceed to Checkout</a></li>
                            <li><a href="/admin/reports/vr-request-for-master">Track My Order</a></li>
                            <li><a href="/admin/virtual-rep/general_request">General Request</a></li>
                        </ol>
                        </b>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End parallax -->

    <div class="news-headline no-print">
        {!! Helper::get_news_marketplace() !!}
    </div>
    
    <!-- Start contain wrapp -->
    <div class="contain-wrapp padding-bot50">

{{--        <div class="text-center">--}}
{{--            <a href="/admin/virtual-rep/cart" class="btn btn-default" style="margin-bottom: 10px; margin-top: -10px;">View Cart</a>--}}
{{--        </div>--}}
        <div class="floating">
            <a href="/admin/virtual-rep/cart" class="btn btn-default" style="margin-bottom: 10px; margin-top: -10px;">View Cart</a>
        </div>

        <div class="container-fluid" style="width: 100%;">
            <div class="row">
                <div class="col-md-2 col-sm-3 col-xs-12">
                <form id="frm_search" class="form-horizontal" method="post" action="/admin/virtual-rep/shop">
                    {{ csrf_field() }}
                    <aside>
                        <div class="widget">
{{--                            <h5 class="widget-head">Quick Search</h5>--}}
                            <input type="text" class="form-control" style="height: 32px;" name="quick_search" value="{{
                            $quick_search
                            }}" placeholder="Quick Search: Model, Type, ID"/>
                        </div>

                        <div class="margin-top10">
                            <button type="submit" class="btn btn-default btn-block">Search</button>
                        </div>

                        <div style="margin-top: 2px;">
                            <a href="/admin/virtual-rep/shop" class="btn btn-info btn-block">Refresh</a>
                        </div>

                        <div class="widget margin-top10">
                            <ul class="cat">
                                @php
                                    $sources = App\Model\VRQuickSearch::get_source();
                                @endphp
                                @foreach ($sources as $s)
                                    <li><a style="cursor:pointer;" onclick="quick_search('{{ $s->search_name }}', '{{ $s->search_column }}')">
                                    @if($s->search_name == $sub_category)
                                    <strong>{{ $s->display_name }}</strong>
                                    @else
                                    {{ $s->display_name }}
                                    @endif
                                    </a></li>

                                    @php
                                        $smons = null;
                                        if (!empty($s->service_month)) {
                                            $smons = explode('|', $s->service_month);
                                        }
                                    @endphp

                                    @if (!empty($s->service_month))
                                    @php
                                        $smons = explode('|', $s->service_month);
                                    @endphp
                                    @foreach ($smons as $sm)
                                        <li><a style="cursor:pointer;" onclick="quick_search('{{ $s->search_name }}', '{{ $s->search_column }}', '{{ $sm }}')"><span>- {{ $sm }} Month</span> </a></li>
                                    @endforeach
                                    @endif
                                @endforeach

                                @foreach ($promotions as $p)
                                <li><a style="cursor:pointer;" onclick="quick_search_promotion('{{ $p->promotion }}')">
                                    @if ($promotion == $p->promotion )
                                    <strong>{{ $p->promotion }}</strong>
                                    @else
                                    {{ $p->promotion }}
                                    @endif
                                </a></li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="widget">
                            <h5 class="widget-head2">Price</h5>
                            <input type="text" class="form-control" style="height: 32px; width:112px; float:left;" name="min" value="{{ $min }}" placeholder="$ Min"/>
                            <span class="control-label" style="margin-left:5px; float:left;"> ~ </span>
                            <input type="text" class="form-control" style="height: 32px; width:112px; float:left;" name="max" value="{{ $max }}" placeholder="$ Max"/>
                        </div>

                        <div class="widget">
                            <h5 class="widget-head2">Carrier</h5>
                            <select name="carrier" class="form-control">
                                <option value="">All</option>
                                @foreach ($carriers as $o)
                                    <option value="{{ $o->carrier }}" {{ $carrier == $o->carrier ? 'selected' : '' }}>{{ $o->carrier }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="widget">
                            <h5 class="widget-head2">Sub Carrier</h5>
                            <select name="sub_carrier" class="form-control">
                                <option value="">All</option>
                                @foreach ($sub_carriers as $o)
                                    <option value="{{ $o->sub_carrier }}" {{ $sub_carrier == $o->sub_carrier ? 'selected' : '' }}>{{ $o->sub_carrier }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="widget">
                            <h5 class="widget-head2">Category</h5>
                            <select name="category" class="form-control">
                                <option value="">All</option>
                                @foreach ($categories as $o)
                                    <option value="{{ $o->category }}" {{ $category == $o->category ? 'selected' : '' }}>{{ $o->category }}</option>
                                @endforeach
                            </select>
                        </div>                          
    
                        <div class="widget">
                            <h5 class="widget-head2">Sub Category</h5>
                            <select name="sub_category" id="sub_category" class="form-control">
                                <option value="">All</option>
                                @foreach ($sub_categories as $o)
                                    <option value="{{ $o->sub_category }}" {{ $sub_category == $o->sub_category ? 'selected' : '' }}>{{ $o->sub_category }}</option>
                                @endforeach
                            </select>
                        </div>
    
                        <div class="widget">
                            <h5 class="widget-head2">Month.Service</h5>
                            <select id="service_month" name="service_month" class="form-control">
                                <option value="">All</option>
                                <option value="1" {{ $service_month == 1 ? 'selected' : '' }}>1</option>
                                <option value="2" {{ $service_month == 2 ? 'selected' : '' }}>2</option>
                                <option value="3" {{ $service_month == 3 ? 'selected' : '' }}>3</option>
                            </select>
                        </div>                              
    
                        <div class="widget">
                            <h5 class="widget-head2">Plan</h5>
                            <select name="plan" class="form-control">
                                <option value="">All</option>
                                @foreach ($plans as $o)
                                    <option value="{{ $o->plan }}" {{ $plan == $o->plan ? 'selected' : '' }}>{{ $o->plan }}</option>
                                @endforeach
                            </select>
                        </div>                      
                            
                        <div class="widget">
                            <h5 class="widget-head2">Make</h5>
                            <select name="make" class="form-control">
                                <option value="">All</option>
                                @foreach ($makes as $o)
                                    <option value="{{ $o->make }}" {{ $make == $o->make ? 'selected' : '' }}>{{ $o->make }}</option>
                                @endforeach
                            </select>
                        </div>                  
                                                    
    
                        <div class="widget">
                            <h5 class="widget-head2">Product Name</h5>
                            <input type="text" class="form-control" name="model" value="{{ $model }}"/>
                        </div>                  
                                                    
    
                        <div class="widget">
                            <h5 class="widget-head2">Type</h5>
                            <select name="type" class="form-control">
                                <option value="">All</option>
                                @foreach ($types as $o)
                                    <option value="{{ $o->type }}" {{ $type == $o->type ? 'selected' : '' }}>{{ $o->type }}</option>
                                @endforeach
                            </select>
                        </div>
                                                    
    
                        <div class="widget">
                            <h5 class="widget-head2">Grade</h5>
                            <select name="grade" class="form-control">
                                <option value="">All</option>
                                @foreach ($grades as $o)
                                    <option value="{{ $o->grade }}" {{ $grade == $o->grade ? 'selected' : '' }}>{{ $o->grade }}</option>
                                @endforeach
                            </select>
                        </div> 

                        <div class="widget">
                            <h5 class="widget-head2">Promotion</h5>
                            <select name="promotion" class="form-control">
                                <option value="">All</option>
                                @foreach ($promotions as $p)
                                    <option value="{{ $p->promotion }}" {{ $promotion == $p->promotion ? 'selected' : '' }}>{{ $p->promotion }}</option>
                                @endforeach
                            </select>
                        </div>                  
                        
                        <div class="margin-top10">
                            <button type="submit" class="btn btn-default btn-block">Search</button>
                        </div>
                    </aside>
                </form>
                </div>
                
                <div class="col-md-10 col-sm-9 col-xs-12">

                    
                    <div class="clearfix"></div>
                    
                    <div class="row">
                        @foreach($vr_product as $o)
                        @php
                            $key = preg_replace("/\W+/", "_", $o->sku);
                        @endphp
                        <div class="col-xl-20 col-lg-20 col-md-3 col-xs-6 product-wrapper" data-brand="">
                            <div class="product-image" style="padding-top: 10px; padding-bottom: 5px;">
                                @if ($o->category == 'VIDEO')
                                    <a href="{{ $o->url }}" target="_blank"><img src="/img/no_image.jpg" class="img-responsive"></a>
                                @else
                                    <img src="{{ empty($o->url) ? '/img/no_image.jpg' : $o->url }}" class="img-responsive" onload="fixExifOrientation(this)" alt="">
                                @endif
                            </div>
                            
                            <div class="product-containt">
                                <h5>{{ $o->model }}</h5>
                                @if ($o->stock == 0)
                                    <out>Out of Stock</out>
                                @endif
                                @if ($o->stock == -1)
                                    <out style="background-color: blue;">Coming soon</out>
                                @endif
                                @if ($o->stock == -2)
                                    <out style="background-color: orange;">Back Ordered</out>
                                @endif
                                @php
                                    $file = \App\Model\Carrier::get_logo_img_link($o->carrier);
                                @endphp
                                @if ($file != 'blank')
                                    <carrier>
                                        <img id="img_select" src="{{$file}}" style="width: 72px; height: 45px;">
                                    </carrier>
                                @endif
                                @if ($o->is_dropship == 'Y')
                                    <drop>Dropship</drop>
                                @endif

                                <P> <SMALL>{{ empty($o->marketing_final) ? '-' : $o->marketing_final }}</SMALL></p>
                                <p style="white-space: nowrap;"> ID <span style="color:#ff4629;">{{ $o->id }}</span></p>
                                <p style="white-space: nowrap;"> Category <span style="color:#ff4629;">{{ $o->category }}</span></p>
                                <p style="white-space: nowrap;"> SubCategory <span style="color:#ff4629;">{{ $o->sub_category }}</span></p>
                                <p style="white-space: nowrap;"> Carrier <span style="color:#ff4629;">{{ $o->carrier }}</span></p>
                                <p style="white-space: nowrap;"> SubCarrier <span style="color:#ff4629;">{{ $o->sub_carrier }}</span></p>
                                <p style="white-space: nowrap;"> Month.Service <span style="color:#ff4629;">{{ $o->service_month }}</span></p>
                                <p style="white-space: nowrap;"> Plan <span style="color:#ff4629;">{{ $o->plan }}</span></p>
                                <p style="white-space: nowrap;"> Make <span style="color:#ff4629;">{{ $o->make }}</span></p>
                                <p style="white-space: nowrap;"> Type <span style="color:#ff4629;">{{ $o->type }}</span></p>
                                <p style="white-space: nowrap;"> Grade <span style="color:#ff4629;">{{ $o->grade }}</span></p>
                                <P> <SMALL>{{ empty($o->rebate_marketing_final) ? '-' : $o->rebate_marketing_final }}</SMALL></p>

                                @if ($account_type == 'D')
                                    <p style="white-space: nowrap;">Allocation:
                                        <span style="color:#ff4629;">
                                            @if ( $o->dis_max_final === 0 )
                                                0
                                            @elseif ( !empty($o->dis_max_final) )
                                                {{ $o->dis_max_final }}
                                            @endif
                                    </span>
                                    </p>
                                    <p>Remaining:
                                        <span style="color:#ff4629;">
                                            @if( $o->dis_max_final === 0 )
                                                0
                                            @elseif ( !empty($o->dis_max_final) )
                                                {{ $o->dis_max_final - $o->d_ordered_qty }}
                                            @endif
                                    </span>
                                    </p>

                                    <p >
                                        <SMALL><span style="color:#ff4629;">
                                        @if ($o->final_quick_note)
                                        {{ $o->final_quick_note }}
                                        @else
                                            -
                                        @endif
                                            </span>
                                        </SMALL>
                                    </p>

                                    <div class="row2">
                                        <div class="price">
                                            <span style="font-size: 14px">${{$o->dis_price_final}}</span>
                                            <input type="number" class="form-control2" placeholder="0"
                                                   id="qty_{{ $o->id }}" style="width: 60px;"
                                                   value="0" {{ $o->stock > 0 ? '' : 'disabled'}}>
                                            <button type="submit" class="btn btn-default btn-xs" {!! $o->stock > 0 ? 'onclick="add_to_cart(' . $o->id . ')"' : 'disabled' !!}>
                                                <i class="fa fa-shopping-cart"></i>
                                            </button>
                                        </div>
                                    </div>
                                @elseif($account_type == 'M')
                                    <p style="white-space: nowrap;">Allocation:
                                        <span style="color:#ff4629;">
                                            @if( $o->mas_max_final === 0 )
                                                0
                                            @elseif( !empty($o->mas_max_final) )
                                                {{ $o->mas_max_final }}
                                            @endif
                                    </span>
                                    </p>
                                    <p>Remaining:
                                        <span style="color:#ff4629;">
                                        @if ( $o->mas_max_final === 0)
                                            0
                                        @elseif ( !empty($o->mas_max_final))
                                           {{ $o->mas_max_final - $o->m_ordered_qty }}
                                        @endif
                                    </span>
                                    </p>

                                    <p style="white-space: nowrap;">
                                        @if ($o->final_quick_note)
                                            <small><span style="color:#ff4629;">
                                        {{ $o->final_quick_note }}
                                        </span>
                                        </small>
                                        @else
                                            -
                                        @endif
                                    </p>

                                    <div class="row2">
                                        <div class="price">
                                            <span style="font-size: 14px">${{$o->mas_price_final}}</span>
                                            <input type="number" class="form-control2" placeholder="0"
                                                   id="qty_{{ $o->id }}" style="width: 60px;"
                                                   value="0" {{ $o->stock > 0 ? '' : 'disabled'}}>
                                            <button type="submit" class="btn btn-default btn-xs" {!! $o->stock > 0 ? 'onclick="add_to_cart(' . $o->id . ')"' : 'disabled' !!}>
                                                <i class="fa fa-shopping-cart"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endif

                            </div>

                        </div>
                        @endforeach
                    </div>
                    
                    <hr>

                    <div class="row-fluid">
                        <div class="col-md-6 col-sm-6 col-xs-12">
                            <div class="result">
                                <label>Showing :</label>
                                <span>{{ $total_num }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div id="modal_cart" class="modal fade " tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false"
         style="display:none;">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" style="color:red">SHOPPING CART</h4>
                </div>
                <div class="modal-body">
                    <p>
                        <span id="modal_qty"></span> item(s) added to your cart.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Continue Shopping</button>
                    <a type="button" href="/admin/virtual-rep/cart" class="btn btn-default">Go to Cart</a>
                </div>
            </div>
        </div>
    </div>
    <!-- End contain wrapp -->


    <form id="frm_quick_search" class="form-horizontal" method="post" action="/admin/virtual-rep/shop">
        {{ csrf_field() }}
        <input type="hidden" id="qs_sub_category" name="sub_category">
        <input type="hidden" id="qs_service_month" name="service_month">
        <input type="hidden" id="qs_promotion" name="promotion">
        <input type="hidden" id="account_type" name="account_type" value="{{ $account_type }}">
    </form>


@stop
