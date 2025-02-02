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
    <link rel="icon" href="/ico/favicon.png">
 
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
    <!--[if lt IE 9]><script src="/js/ie8-responsive-file-warning.js"></script><![endif]-->
    <script src="/js/ie-emulation-modes-warning.js"></script>

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="/js/html5shiv.min.js"></script>
    <script src="/js/respond.min.js"></script>
    <![endif]-->

</head>
<body>

@include('layout.nav')

<!-- Start half contain wrapp -->
@yield('content')
<!-- End half contain wrapp -->

<div class="vrmenu">
<ul>
 <li><A href="#"><img width="70" height="70" src="/img/vrpeople.png"></A>
   <ul>
     <li><a href="/virtual-rep">Market Place</a></li>
     <li><a target="_blank" href="/assets/att/Why_ATT_pg1.pdf">Why ATT I </a></li>
{{--     <li><a target="_blank" href="/assets/att/Why_ATT_2.pdf">Why ATT II </a></li>--}}
{{--     <li><a target="_blank" href="/assets/att/Spiff_Promo.pdf">ATT Spiff </a></li>--}}
     <li><a target="_blank" href="/assets/att/Why_FreeUp.pdf">Why FreeUp </a></li>
     <li><a target="_blank" href="/assets/jingotv/JingoTV.mp4">Jingo TV. Video</a></li>
     <li><a target="_blank" href="/assets/Esign.mp4">Esign Video</a></li>
     <li><a target="_blank" href="/assets/att/ATT_Prepaid_Video_1080.mp4">ATT Prepaid Video</a></li>
    </ul>
 </li>
</ul>
</div>

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

<!-- START JAVASCRIPT -->
<!-- Placed at the end of the document so the pages load faster -->
<script type="text/javascript" src="/js/jquery.min.js"></script>
<script src="/js/bootstrap.min.js"></script>
<script src="/js/jquery.easing-1.3.min.js"></script>

<!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
<script src="/js/ie10-viewport-bug-workaround.js"></script>

<!-- Bootsnavs -->
<script src="/js/bootsnav.js"></script>

<!-- Custom form -->
<script src="/js/form/jcf.js"></script>
<script src="/js/form/jcf.scrollable.js"></script>
<script src="/js/form/jcf.select.js"></script>

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

<!-- Theme option-->
<script src="/js/template-option/demosetting.js"></script>
</body>
</html>
