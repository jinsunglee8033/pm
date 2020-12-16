@extends('admin.layout.default')

@section('content')

    <script type="text/javascript">

        function save_vr_request() {

            if($('#vr_comments').val().length < 1){
                alert('please leave your opinion');
                return;
            }

            $.ajax({
                url: '/admin/virtual-rep/general_request/save',
                data: {
                    vr_comments: $('#vr_comments').val()
                },
                cache: false,
                type: 'get',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        alert('Your request has been successfully submitted!');
                        location.href = '/admin/reports/vr-request';
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
                            <li><a href="/admin/virtual-rep/shop">Order</a></li>
                            <li><a href="/admin/virtual-rep/cart">View Cart / Proceed to Checkout</a></li>
                            <li><a href="/admin/reports/vr-request-for-master">Track My Order</a></li>
                            <li class="active">General Request</li>
                        </ol>
                        </b>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End parallax -->

    <!-- Start contain wrapp -->
    <div class="contain-wrapp"> 
        <div class="container">
            <form>
				<span class="highlight default2">Inquiries, Technical Issues, Comments</span>
                <p>
                    <textarea class="form-control" rows="5" id="vr_comments" placeholder="Please leave your opinion"></textarea>
                </p>

                <div class="row">
                    <div class="col-md-4 col-md-offset-8 text-right">
                        <div class="form-group">
                            <div class="col-md-12">
                                <a type="button" class="btn btn-primary btn-sm" onclick="save_vr_request()">Submit</a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- End contain wrapp -->


@stop
