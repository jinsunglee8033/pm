<!DOCTYPE HTML>
<!--[if lt IE 7]>
<html class="no-js ie6 oldie" lang="en"> <![endif]-->
<!--[if IE 7]>
<html class="no-js ie7 oldie" lang="en"> <![endif]-->
<!--[if IE 8]>
<html class="no-js ie8 oldie" lang="en"> <![endif]-->
<html class="no-js" lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="robots" content="index,follow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="/ico/favicon3.png">

    <title>SoftPayPlus, Inc</title>

    <!-- Bootstrap Core CSS -->
    <link href="/css/bootstrap_shop.min.css" rel="stylesheet">

    <!-- RS5.0 Main Stylesheet -->
    <link rel="stylesheet" type="text/css" href="/css/revolution/settings.css">

    <!-- RS5.0 Layers and Navigation Styles -->
    <link rel="stylesheet" type="text/css" href="/css/revolution/layers.css">
    <link rel="stylesheet" type="text/css" href="/css/revolution/navigation.css">

    <link href="/css/style_shop.css?ver=5" rel="stylesheet">

    <!-- Color -->
    <link id="skin" href="/skins/default.css" rel="stylesheet">

    <!-- Just for debugging purposes. Don't actually copy these 2 lines! -->
    <!--[if lt IE 9]>
    <script src="/js/ie8-responsive-file-warning.js"></script><![endif]-->
    <script src="/js/ie-emulation-modes-warning.js"></script>

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="/js/html5shiv.min.js"></script>
    <script src="/js/respond.min.js"></script>
    <![endif]-->

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>

    <link ref="stylesheet" href="/css/app.css"/>
    <link rel="stylesheet"
          href="/bower_components/eonasdan-bootstrap-datetimepicker/build/css/bootstrap-datetimepicker.min.css"/>
    <link rel="stylesheet" href="/css/yong.css">

</head>
<body>


@include('sub-agent.layout.nav')

<style type="text/css">
    .news-headline {
        -webkit-box-shadow: 0px 0px 4px 0px rgba(0, 0, 0, 0);
        -moz-box-shadow: 0px 0px 4px 0px rgba(0, 0, 0, 0);
        box-shadow: 0px 0px 4px 0px rgba(0, 0, 0, 0);
        width: 100%;
        background: #F8F8F8;
        margin-bottom: 2px;
        /*height: 105px;*/
    }
</style>

@if (!Request::is("sub-agent/activate/rok") && !in_array(Auth::user()->account_id, [105124]))
<div class="news-headline no-print">
    {!! Helper::get_headline() !!}
{{--    <marquee behavior="scroll" direction="left" onmouseover="this.stop();"--}}
{{--             onmouseout="this.start();">{!! Helper::get_headline() !!}</marquee>--}}
</div>
@endif

@yield('content')

@if (!in_array(Auth::user()->account_id, [105124]))
<div class="vrmenu">
<ul>
 <li><A href="#"><img width="70" height="70" src="/img/vrpeople.png"></A>
   <ul>
     <li><a target="_blank" href="/assets/att/Why_ATT_pg1.pdf">Why ATT I </a></li>
{{--     <li><a target="_blank" href="/assets/att/Why_ATT_pg2.pdf">Why ATT II </a></li>--}}
{{--     <li><a target="_blank" href="/assets/att/ATT_Promotion.pdf">Why ATT III </a></li>--}}
     <li><a target="_blank" href="/assets/att/Why_FreeUp.pdf">Why FreeUp </a></li>
    </ul>
 </li>
</ul>
</div>
@endif

<!-- Start footer -->
<footer class="no-print">
    <div class="subfooter">
        <p>2017 ~ {{ date('Y') }} &copy; Copyright <a href="#">SoftPayPlus.</a> All rights Reserved.</p>
    </div>
</footer>
<!-- End footer -->

<!-- Start to top -->
<a href="#" class="toTop">
    <i class="fa fa-chevron-up"></i>
</a>
<!-- End to top -->

<div style="display:none;" id="app"></div>

<!-- Start modal -->
<div class="modal fade" id="virtual_rep_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Virtual Representative</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="vr_category"/>

                <!-- Start Accordion -->
                <div class="panel-group" id="accordion1">
                    <div class="panel panel-default">
                        <div class="panel-heading" id="heading1">
                            <h6 class="panel-title">
                                <a class="collapsed" data-toggle="collapse" onclick="$('#vr_category').val('M')" data-parent="#accordion1" href="#panel1">
                                    I REQUIRE MORE MARKETING MATERIALS
                                </a>
                            </h6>
                        </div>

                        <div id="panel1" class="panel-collapse collapse">
                            <div class="panel-body">
                                <p><span class="highlight default2">POSTERS</span>
                                <div class="checkbox">
                                    <label><input type="checkbox" id="vr_poster_vz"> VERIZON WIRELESS &nbsp; &nbsp; </label>
                                    <label><input type="checkbox" id="vr_poster_h2o"> H2O WIRELESS &nbsp; &nbsp; </label>
                                    <label><input type="checkbox" id="vr_poster_lyca"> LYCA MOBILE </label>
                                    <label><input type="checkbox" id="vr_poster_patriot"> PATRIOT MOBILE </label>
                                </div>

                                </p>
                                <p><span class="highlight default2">BROCHURES</span>
                                <div class="checkbox">
                                    <label><input type="checkbox" id="vr_brochure_vz"> VERIZON WIRELESS &nbsp; &nbsp; </label>
                                    <label><input type="checkbox" id="vr_brochure_h2o"> H2O WIRELESS &nbsp; &nbsp; </label>
                                    <label><input type="checkbox" id="vr_brochure_lyca"> LYCA MOBILE </label>
                                    <label><input type="checkbox" id="vr_brochure_patriot"> PATRIOT MOBILE </label>
                                </div>
                                </p>
                                <p><span class="highlight default2">OTHER</span>
                                    <textarea class="form-control" rows="3" id="vr_material_other" placeholder="Please enter your query"
                                              style="margin-top: 20px"></textarea>


                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="panel panel-default">
                        <div class="panel-heading" id="heading2">
                            <h6 class="panel-title">
                                <a class="collapsed" data-toggle="collapse" onclick="$('#vr_category').val('E')" data-parent="#accordion1" href="#panel2">
                                    EQUIPMENT ORDERING
                                </a>
                            </h6>
                        </div>
                        <div id="panel2" class="panel-collapse collapse">
                            <div class="panel-body">
                                <p><span class="highlight default2">SIM CARDS</span>
                                <div class="checkbox">
                                    <label><input type="checkbox" id="vr_sim_vz"> VERIZON WIRELESS &nbsp; &nbsp; </label>
                                    <label><input type="checkbox" id="vr_sim_h2o"> H2O WIRELESS &nbsp; &nbsp; </label>
                                    <label><input type="checkbox" id="vr_sim_lyca"> LYCA MOBILE </label>
                                    <label><input type="checkbox" id="vr_sim_patriot"> PATRIOT MOBILE </label>
                                </div>

                                </p>
                                <p><span class="highlight default2">HANDSETS</span>
                                <div class="checkbox">
                                    <label><input type="checkbox" id="vr_handset_vz"> VERIZON WIRELESS &nbsp; &nbsp; </label>
                                    <label><input type="checkbox" id="vr_handset_h2o"> H2O WIRELESS &nbsp; &nbsp; </label>
                                    <label><input type="checkbox" id="vr_handset_lyca"> LYCA MOBILE </label>
                                    <label><input type="checkbox" id="vr_handset_patriot"> PATRIOT MOBILE </label>
                                </div>
                                </p>
                                <p><span class="highlight default2">OTHER</span>
                                    <textarea class="form-control" rows="3" id="vr_equipment_other" placeholder="Please enter your query"
                                              style="margin-top: 20px"></textarea>
                                    <strong>*Pricing Information for Call or E-mail</strong>

                                </p>
                            </div>
                        </div>
                    </div>


                    <div class="panel panel-default">
                        <div class="panel-heading" id="heading3">
                            <h6 class="panel-title">
                                <a class="collapsed" data-toggle="collapse" onclick="$('#vr_category').val('T')" data-parent="#accordion1" href="#panel3">
                                    TECHNICAL ISSUES
                                </a>
                            </h6>
                        </div>
                        <div id="panel3" class="panel-collapse collapse">
                            <div class="panel-body">
                                <p><strong>VERIZON WIRELESS</strong>
                                    <textarea class="form-control" rows="3" id="vr_tech_vz" placeholder="Please enter your query"></textarea>
                                </p>
                                <p><strong>H2O WIRELESS</strong>
                                    <textarea class="form-control" rows="3" id="vr_tech_h2o" placeholder="Please enter your query"></textarea>
                                </p>
                                <p><strong>LYCA MOBILE</strong>
                                    <textarea class="form-control" rows="3" id="vr_tech_lyca" placeholder="Please enter your query"></textarea>
                                </p>
                                <p><strong>PATRIOT MOBILE</strong>
                                    <textarea class="form-control" rows="3" id="vr_tech_patriot" placeholder="Please enter your query"></textarea>
                                </p>
                                <p><strong>PORTAL RELATED</strong>
                                    <textarea class="form-control" rows="3" id="vr_tech_portal" placeholder="Please enter your query"></textarea>
                                </p>
                                <p><strong>OTHERS</strong>
                                    <textarea class="form-control" rows="3" id="vr_tech_other" placeholder="Please enter your query"></textarea>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="panel panel-default">
                        <div class="panel-heading" id="heading4">
                            <h6 class="panel-title">
                                <a class="collapsed" data-toggle="collapse" onclick="$('#vr_category').val('C')" data-parent="#accordion1" href="#panel4">
                                    COMMENTS
                                </a>
                            </h6>
                        </div>
                        <div id="panel4" class="panel-collapse collapse">
                            <div class="panel-body">
                                <p>
                                    <textarea class="form-control" rows="3" id="vr_comments" placeholder="Please leave your opinion"></textarea>

                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Accordion -->


            </div>
            <div class="modal-footer">
                <button class="btn btn-primary btn-sm" type="submit" data-dismiss="modal">Close</button>
                <button class="btn btn-default btn-sm" type="button" onclick="save_vr()">Submit</button>
            </div>
        </div>
    </div>
</div>
<!-- End modal -->

<div class="modal" tabindex="-1" role="dialog" id="loading-modal" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Please Wait...</h4>
            </div>
            <div class="modal-body">
                <div class="progress" style="margin-top:20px;">
                    <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
                        <span class="sr-only">Please wait.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal" tabindex="-1" role="dialog" id="error-modal">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="error-modal-title">Modal title</h4>
            </div>
            <div class="modal-body" id="error-modal-body">
            </div>
            <div class="modal-footer" id="error-modal-footer">
                <button type="button" id="error-modal-ok" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal" tabindex="-1" role="dialog" id="confirm-modal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="confirm-modal-title">Modal title</h4>
            </div>
            <div class="modal-body" id="confirm-modal-body">

            </div>
            <div class="modal-footer" id="confirm-modal-footer">
                <button type="button" id="confirm-modal-cancel" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" id="confirm-modal-ok" class="btn btn-primary" data-dismiss="modal">Ok</button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script>
    window.Laravel = <?php echo json_encode([
        'csrfToken' => csrf_token(),
    ]); ?>;
    var account_id = "{{ Auth::user()->account_id }}";

    var env = "{{ getenv('APP_ENV') }}";
</script>

<script src="/js/app.js"></script>

<!-- START JAVASCRIPT -->
<!-- Placed at the end of the document so the pages load faster -->
<script type="text/javascript" src="/js/jquery.min.js"></script>
<script src="/js/bootstrap.min.js"></script>
<script src="/js/jquery.easing-1.3.min.js"></script>

<!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
<script src="/js/ie10-viewport-bug-workaround.js"></script>

<script src="/js/loading.js"></script>
<!--script src="https://use.fontawesome.com/0f855c73a0.js"></script-->

<script src="/bower_components/moment/moment.js"></script>
<script src="/bower_components/eonasdan-bootstrap-datetimepicker/src/js/bootstrap-datetimepicker.js"></script>
<script src="/js/bootstrap-notify.min.js"></script>
<script type="text/javascript">

    function realtime_notify(e) {
        window.$.notify({
            //icon: 'glyphicon glyphicon-warning-sign',
            title: 'Transaction Status Update!',
            message: e.message
        }, {
            type: e.transaction.status == 'C' ? 'success' : 'warning',
            delay: 0,
            animate: {
                enter: 'animated fadeInRight',
                exit: 'animated fadeOutRight'
            },
            template: '<div class="alert alert-{0}" role="alert" data-out="bounceOut">' +
            '<span class="close-alert" data-dismiss="alert"><i class="fa fa-times-circle"></i></span>' +
            '<i class="fa fa-{0}"></i>' +
            '<h6 class="title">{1}</h6>' +
            '<p>{2}</p>' +
            '</div>'
        });
    }
</script>


<!-- Bootsnavs -->
<script src="/js/bootsnav.js"></script>

<!-- Custom form -->
<script src="/js/form/jcf.js"></script>
<!--script src="/js/form/jcf.scrollable.js"></script>
<script src="/js/form/jcf.select.js"></script-->

<!-- Custom checkbox and radio -->
<script src="/js/checkator/fm.checkator.jquery.js"></script>
<script src="/js/checkator/setting.js"></script>

<!-- REVOLUTION JS FILES -->
<script type="text/javascript" src="/js/revolution/jquery.themepunch.tools.min.js"></script>
<script type="text/javascript" src="/js/revolution/jquery.themepunch.revolution.min.js"></script>

<!-- SLIDER REVOLUTION 5.0 EXTENSIONS
(Load Extensions only on Local File Systems !
The following part can be removed on Server for On Demand Loading) -->
<script type="text/javascript" src="/js/revolution/revolution.extension.actions.min.js"></script>
<script type="text/javascript" src="/js/revolution/revolution.extension.carousel.min.js"></script>
<script type="text/javascript" src="/js/revolution/revolution.extension.kenburn.min.js"></script>
<script type="text/javascript" src="/js/revolution/revolution.extension.layeranimation.min.js"></script>
<script type="text/javascript" src="/js/revolution/revolution.extension.migration.min.js"></script>
<script type="text/javascript" src="/js/revolution/revolution.extension.navigation.min.js"></script>
<script type="text/javascript" src="/js/revolution/revolution.extension.parallax.min.js"></script>
<script type="text/javascript" src="/js/revolution/revolution.extension.slideanims.min.js"></script>
<script type="text/javascript" src="/js/revolution/revolution.extension.video.min.js"></script>

<!-- CUSTOM REVOLUTION JS FILES -->
<script type="text/javascript" src="/js/revolution/setting/clean-revolution-slider.js"></script>

<!-- masonry -->
<script src="/js/masonry/masonry.min.js"></script>
<script src="/js/masonry/masonry.filter.js"></script>
<script src="/js/masonry/setting.js"></script>

<!-- PrettyPhoto -->
<script src="/js/prettyPhoto/jquery.prettyPhoto.js"></script>
<script src="/js/prettyPhoto/setting.js"></script>

<!-- flexslider -->
<script src="/js/flexslider/jquery.flexslider-min.js"></script>
<script src="/js/flexslider/setting.js"></script>

<!-- Parallax -->
<script src="/js/parallax/jquery.parallax-1.1.3.js"></script>
<script src="/js/parallax/setting.js"></script>

<!-- owl carousel -->
<script src="/js/owlcarousel/owl.carousel.min.js"></script>
<script src="/js/owlcarousel/setting.js"></script>

<!-- Twitter -->
<script src="/js/twitter/tweetie.min.js"></script>
<script src="/js/twitter/ticker.js"></script>
<script src="/js/twitter/setting.js"></script>

<!-- Custom -->
<script src="/js/custom.js"></script>

<script src="/js/loading.js"></script>
<script src="/js/jquery.cookie.js"></script>
<script src="/js/jquery.rememberscroll.js"></script>

<!-- Theme option-->
<script src="/js/template-option/demosetting.js"></script>

<!-- PayPal -->
<script src="https://www.paypalobjects.com/api/checkout.js"></script>

<script type="text/javascript">
    $(document).ready(function() {
        $.scrollTrack();
    });

    function open_vr() {
        $('#virtual_rep_modal').find('checkbox').attr('checked', false);
        $('#virtual_rep_modal').find('input').val('');
        $('#virtual_rep_modal').find('textarea').val('');

        $('.panel-collapse.in').collapse('hide');
        $('#panel1').collapse('show');
        $('#vr_category').val('M');

        $('#virtual_rep_modal').modal('show');
    }

    function save_vr() {
        myApp.showLoading();
        $.ajax({
            url: '/sub-agent/virtual-rep/log-request',
            data: {
                _token: '{!! csrf_token() !!}',
                category: $('#vr_category').val(),

                poster_vz: $('#vr_poster_vz').is(':checked') ? 'Y' : 'N',
                poster_h2o: $('#vr_poster_h2o').is(':checked') ? 'Y' : 'N',
                poster_lyca: $('#vr_poster_lyca').is(':checked') ? 'Y' : 'N',
                poster_patriot: $('#vr_poster_patriot').is(':checked') ? 'Y' : 'N',
                brochure_vz: $('#vr_brochure_vz').is(':checked') ? 'Y' : 'N',
                brochure_h2o: $('#vr_brochure_h2o').is(':checked') ? 'Y' : 'N',
                brochure_lyca: $('#vr_brochure_lyca').is(':checked') ? 'Y' : 'N',
                brochure_patriot: $('#vr_brochure_patriot').is(':checked') ? 'Y' : 'N',
                material_other: $('#vr_material_other').val(),

                sim_vz: $('#vr_sim_vz').is(':checked') ? 'Y' : 'N',
                sim_h2o: $('#vr_sim_h2o').is(':checked') ? 'Y' : 'N',
                sim_lyca: $('#vr_sim_lyca').is(':checked') ? 'Y' : 'N',
                sim_patriot: $('#vr_sim_patriot').is(':checked') ? 'Y' : 'N',
                handset_vz: $('#vr_handset_vz').is(':checked') ? 'Y' : 'N',
                handset_h2o: $('#vr_handset_h2o').is(':checked') ? 'Y' : 'N',
                handset_lyca: $('#vr_handset_lyca').is(':checked') ? 'Y' : 'N',
                handset_patriot: $('#vr_handset_patriot').is(':checked') ? 'Y' : 'N',
                equipment_other: $('#vr_equipment_other').val(),

                tech_vz: $('#vr_tech_vz').val(),
                tech_h2o: $('#vr_tech_h2o').val(),
                tech_lyca: $('#vr_tech_lyca').val(),
                tech_patriot: $('#vr_tech_patriot').val(),
                tech_portal: $('#vr_tech_portal').val(),
                tech_other: $('#vr_tech_other').val(),

                comments: $('#vr_comments').val()
            },
            cache: false,
            type: 'post',
            dataType: 'json',
            success: function(res) {
                myApp.hideLoading();
                if ($.trim(res.msg) === '') {
                    $('#virtual_rep_modal').modal('hide');
                    myApp.showSuccess('Your request has been successfully submitted!');

                    $('#virtual_rep_modal').find('checkbox').attr('checked', false);
                    $('#virtual_rep_modal').find('input').val('');
                    $('#virtual_rep_modal').find('textarea').val('');

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
</body>
</html>
