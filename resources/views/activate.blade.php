<!DOCTYPE html>
<!--[if IE 8]>
<html lang="en" class="ie8 no-js"> <![endif]-->
<!--[if IE 9]>
<html lang="en" class="ie9 no-js"> <![endif]-->
<!--[if !IE]><!-->
<head lang="en">
    <!--<![endif]-->
    <!-- BEGIN HEAD -->
    <meta charset="utf-8"/>
    <title>ROC mall by SoftPayPlue</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport"/>
    <meta content="" name="author"/>
    <!-- BEGIN GLOBAL MANDATORY STYLES -->
    <link href="http://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700&subset=all" rel="stylesheet"
          type="text/css"/>
    <link href="/mall/assets/global/plugins/font-awesome/css/font-awesome.min.css" rel="stylesheet"
          type="text/css"/>
    <link href="/mall/assets/global/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css"/>
    <!-- END GLOBAL MANDATORY STYLES -->
    <!-- BEGIN THEME GLOBAL STYLES -->
    <link href="/mall/assets/global/css/components-rounded.min.css" rel="stylesheet" id="style_components"
          type="text/css"/>
    <link href="/mall/assets/global/css/plugins.min.css" rel="stylesheet" type="text/css"/>
    <!-- END THEME GLOBAL STYLES -->
    <!-- BEGIN THEME LAYOUT STYLES -->
    <link href="/mall/assets/layouts/layout3/css/layout.min.css" rel="stylesheet" type="text/css"/>
    <link href="/mall/assets/layouts/layout3/css/themes/default.min.css" rel="stylesheet" type="text/css"
          id="style_color"/>
    <link href="/mall/assets/layouts/layout3/css/custom.min.css" rel="stylesheet" type="text/css"/>
    <!-- END THEME LAYOUT STYLES -->
    <link rel="shortcut icon" href="favicon.ico"/>

    <link href="/css/style_shop.css?ver=5" rel="stylesheet">



    <style type="text/css">
        .required {
            font-weight: 600 !important;
            color: #4B77BE !important;
            font-size: 14px !important;
            font-family: "Open Sans", sans-serif !important;
        }

    </style>
    <script type="text/javascript">
        var onload_func = window.onload;

    </script>
</head>
<!-- END HEAD -->


<body>


    <div class="page-wrapper no-print">
        <div class="page-wrapper-row">
            <div class="page-wrapper-top">
                <!-- BEGIN HEADER -->
                <div class="page-header">
                    <!-- BEGIN HEADER TOP -->
                    <div class="page-header-top">
                        <div class="container">
                            <!-- BEGIN LOGO -->
                            <div class="page-logo">
                                <a href="/">
                                    <img src="/img/brand/logo-black3.png" alt="logo"
                                         class="logo-default">
                                </a>
                            </div>
                            <!-- END LOGO -->
                            <div class="top-menu">
                                <ul class="nav navbar-nav pull-right">

                                    <!-- BEGIN INBOX DROPDOWN -->
                                    <a href="/" class="btn red-sunglo btn-sm">Back to softpayplus.com</a>

                                    <!-- END INBOX DROPDOWN -->
                                </ul>
                            </div>
                        </div>
                    </div>
                    <!-- END HEADER TOP -->
                </div>
                <!-- END HEADER -->
            </div>
        </div>
    </div>

    <div id="blog" class="contain-wrapp full-recent-post  gray-container">
        <div class="container">
            <div class="row">
                <div class="col-md-8 col-md-offset-2" style="margin-bottom: 50px;">
                    <div class="img-wrapper">
                        <img src="img/logo-white.png" class="img-responsive" alt="" />
                    </div>
                </div>
            </div>
        </div>
        <!-- Start post 1 -->
        
        
            <!-- Start full column wrapp -->
        <div class="col-md-3"></div>
        <div class="col-md-6">
            <div class="row">

                <div class="col-sm-6 col-xs-12">
                    <div class="full-column-content-logo" style="margin-bottom: 20px;">
                        <div class="media-wrapper">
                            <a href="/att/activate"><img src="img/category-img-005.jpg" class="img-thumbnail img-responsive" alt="" /></a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xs-12">
                    <div class="full-column-content-logo" style="margin-bottom: 20px;">
                        <div class="media-wrapper">
                            <a href="/freeup/activate"><img src="img/freeup_main.jpg" class="img-thumbnail img-responsive" alt="" /></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3"></div>
    </div>
    <!-- END CONTENT --><!-- END THEME LAYOUT SCRIPTS -->

    <!-- START JAVASCRIPT -->

    <div style="display:none;" id="app"></div>

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
        var account_id = "";
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

    <script>
        $(document).ready(function () {
            $('#clickmewow').click(function () {
                $('#radio1003').attr('checked', 'checked');
            });
        })
    </script>
</body>

</html>