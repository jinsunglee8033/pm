<!DOCTYPE HTML>
<!--[if lt IE 7]> <html class="no-js ie6 oldie" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js ie7 oldie" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js ie8 oldie" lang="en"> <![endif]-->
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
    <link href="/css/bootstrap.min.css" rel="stylesheet">

    <!-- RS5.0 Main Stylesheet -->
    <link rel="stylesheet" type="text/css" href="/css/revolution/settings.css">

    <!-- RS5.0 Layers and Navigation Styles -->
    <link rel="stylesheet" type="text/css" href="/css/revolution/layers.css">
    <link rel="stylesheet" type="text/css" href="/css/revolution/navigation.css">

    <link href="/css/style.css" rel="stylesheet">

    <!-- Color -->
    <link id="skin" href="/skins/default.css" rel="stylesheet">

    <!-- Just for debugging purposes. Don't actually copy these 2 lines! -->
    <!--[if lt IE 9]><script src="/js/ie8-responsive-file-warning.js"></script><![endif]-->
    <script src="/js/ie-emulation-modes-warning.js"></script>

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="/js/html5shiv.min.js"></script>
    <script src="/js/respond.min.js"></script>
    <![endif]-->

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>

    <link ref="stylesheet" href="/css/app.css"/>
    <link rel="stylesheet" href="/bower_components/eonasdan-bootstrap-datetimepicker/build/css/bootstrap-datetimepicker.min.css" />

    <link rel="stylesheet" href="/css/jquery.treegrid.css">
    <link rel="stylesheet" href="/css/yong.css">
</head>
<body>



@include('admin.layout.nav')

<style type="text/css">
    .news-headline {
        width: 100%;
        background: #FEFEFE;
        margin-bottom: 2px;
        /*height: 90px;*/
        -webkit-box-shadow: 0px 0px 4px 0px rgba(0,0,0,0.3);
        -moz-box-shadow: 0px 0px 4px 0px rgba(0,0,0,0.3);
        box-shadow: 0px 0px 4px 0px rgba(0,0,0,0.3);
    }
</style>

<div class="news-headline">

    {!! Helper::get_headline() !!}

{{--    <marquee behavior="scroll" direction="left" onmouseover="this.stop();"--}}
{{--             onmouseout="this.start();">{!! Helper::get_headline() !!}</marquee>--}}
</div>

<div class="contain-wrapp padding-bot70">
    <div class="container" style="width:100% !important;">
        @yield('content')
    </div>
</div>


<!-- Start footer -->
<footer>

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

<!-- START JAVASCRIPT -->

<div style="display:none;" id="app"></div>

<script>
    window.Laravel = <?php echo json_encode([
        'csrfToken' => csrf_token(),
    ]); ?>;
    var account_id = "{{ Auth::user()->account_id }}";
    var env = "{{ getenv('APP_ENV') }}";
</script>

<script src="/js/app.js"></script>

<!-- Placed at the end of the document so the pages load faster -->
<script type="text/javascript" src="/js/jquery.min.js"></script>
<script src="/js/bootstrap.min.js"></script>
<script src="/js/jquery.easing-1.3.min.js"></script>

<!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
<script src="/js/ie10-viewport-bug-workaround.js"></script>

<script src="/bower_components/twitter-bootstrap-wizard/jquery.bootstrap.wizard.js"></script>
<script src="/bower_components/jquery-validation/dist/jquery.validate.js"></script>
<script src="/js/loading.js"></script>
<!--script src="https://use.fontawesome.com/0f855c73a0.js"></script-->

<script src="/bower_components/moment/moment.js"></script>
<script src="/bower_components/eonasdan-bootstrap-datetimepicker/src/js/bootstrap-datetimepicker.js"></script>
<script src="/js/bootstrap-notify.min.js"></script>
<script type="text/javascript">
    function realtime_notify(e) {
        window.$.notify({
            icon: 'glyphicon glyphicon-warning-sign',
            title: 'Transaction Status Update!',
            message: e.message
        },{
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
            '<p>{2}</p>'+
            '</div>'
        });
    }
</script>

<!-- Bootsnavs -->
<script src="/js/bootsnav.js"></script>

<!-- Custom form -->
<script src="/js/form/jcf.js"></script>
<!--script src="/js/form/jcf.scrollable.js"></script-->
<!--script src="/js/form/jcf.select.js"></script-->

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

<!-- Theme option-->
<script src="/js/template-option/demosetting.js"></script>

<script type="text/javascript" src="/js/jquery.treegrid.min.js"></script>

<script type="text/javascript" src="/js/ckeditor/ckeditor.js"></script>

<!-- PayPal -->
<script src="https://www.paypalobjects.com/api/checkout.js"></script>

</body>
</html>
