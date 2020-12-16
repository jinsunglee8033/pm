@extends('sub-agent.layout.default')

@section('content')

    <!-- Start parallax -->
    <div class="parallax" data-background="/img/parallax/innerpage.jpg" data-speed="0.5" data-size="50%">
        <div class="overlay white"></div>
        <div class="container">
            <div class="inner-head">
                <div class="row">
                    <div class="col-md-12 text-center">
                        <h4>Phones</h4>
                        <ol class="breadcrumb">
                            <li><a href="#">Home</a></li>
                            <li><a href="#">Log-in</a></li>
                            <li class="active">Phones</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End parallax -->

    <!-- Start contain wrapp -->
    <div class="contain-wrapp padding-bot60">
        <div class="container">
            <div class="row">
                <div class="col-md-3 col-sm-4 col-xs-12">
                    <aside>
                        <div class="widget">
                            <h5 class="widget-head">Categories</h5>
                            <ul class="cat">
                                <li><a href="#">Verizon</a></li>
                                <li><a href="#">H2O</a></li>
                                <li><a href="#">Lyca</a></li>
                            </ul>
                        </div>
                        <div class="widget">
                            <h5 class="widget-head">Filter by price</h5>
                            <div class="range-wrapp">
                                <input type="text" id="range" value="" name="range" />
                            </div>
                        </div>
                        <div class="widget">
                            <div id="filters">
                                <div class='filter-attributes form-inline'>
                                    <h5 class="widget-head">Shop by color</h5>
                                    <div class="form-group"><input type='checkbox' name='colour' id='black' value='black'> <span>Black</span></div>
                                    <div class="form-group"><input type='checkbox' name='colour' id='white' value='white' /> <span>White</span></div>
                                    <div class="form-group"><input type='checkbox' name='colour' id='gold' value='gold' /> <span>Gold</span></div>
                                    <div class="form-group"><input type='checkbox' name='colour' id='pink' value='pink'> <span>Pink</span></div>
                                    <div class="form-group"><input type='checkbox' name='colour' id='silver' value='silver'> <span>Silver</span></div>
                                    <div class="form-group"><input type='checkbox' name='colour' id='rose' value='rose'> <span>Rose</span></div>
                                </div>
                                <div class='filter-attributes form-inline'>
                                    <h5 class="widget-head">Shop by Brand</h5>
                                    <div class="form-group"><input type='checkbox' name='brand' id='iPhone' value='iPhone'> <span>iPhone</span></div>
                                    <div class="form-group"><input type='checkbox' name='brand' id='Samsung' value='Samsung'> <span>Samsung</span></div>
                                    <div class="form-group"><input type='checkbox' name='brand' id='LG' value='LG'> <span>LG</span></div>
                                    <div class="form-group"><input type='checkbox' name='brand' id='Motolora' value='Motolora'> <span>Motolora</span></div>
                                    <div class="form-group"><input type='checkbox' name='brand' id='HTC' value='HTC'> <span>HTC</span></div>
                                </div>
                                <div>
                                    <input type='button' id='none' class="btn btn-primary btn-sm" value='Clear filter' />
                                </div>
                            </div>
                        </div>


                    </aside>
                </div>
                <div class="col-md-9 col-sm-8 col-xs-12">
                    <div class="row">
                        <div class="col-md-4 col-sm-6 col-xs-12">
                            <label>Sort by :</label>
                            <select class="form-control" data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                                <option>Sort by latest item</option>
                                <option>Sort by popular item</option>
                                <option>Sort by low-height price</option>
                                <option>Sort by height-low price</option>
                            </select>
                        </div>
                        <div class="col-md-8 col-sm-6 col-xs-12 marginbot30">
                            <div class="grider">
                                <a href="#" class="active"><i class="fa fa-th-large"></i></a>
                                <a href="#"><i class="fa fa-list"></i></a>
                            </div>
                        </div>
                    </div>

                    <div class="clearfix"></div>

                    <div class="row">
                        <!-- Start product 1 -->
                        <div class="col-md-4 col-xs-6 product-wrapper" data-price='100' data-colour='red' data-size='s'  data-brand='ereble'>
                            <div class="product-image">
                                <div class="product-caption">
                                    <a href="#" class="add-cart"><i class="fa fa-shopping-basket"></i> Add to cart</a>
                                    <a href="shop-detail.html" class="view-detail"><i class="fa fa-link"></i> View detail</a>
                                </div>
                                <img src="/img/product/prod01.jpg" class="/img-responsive" alt="" />
                            </div>
                            <div class="product-containt">
                                <div class="price"><span>$100</span></div>
                                <div class="rating">
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star-half-o"></i>
                                    <i class="fa fa-star-o"></i>
                                    <i class="fa fa-star-o"></i>
                                </div>
                                <h6><a href="shop-detail.html">Aliquip nusquam</a></h6>
                            </div>
                        </div>
                        <!-- End product 1 -->

                        <!-- Start product 2 -->
                        <div class="col-md-4 col-xs-6 product-wrapper" data-price='90'  data-colour='yellow' data-size='l'  data-brand='unkl'>
                            <div class="product-image">
                                <span class="product-label">Sale</span>
                                <div class="product-caption">
                                    <a href="#" class="add-cart"><i class="fa fa-shopping-basket"></i> Add to cart</a>
                                    <a href="shop-detail.html" class="view-detail"><i class="fa fa-link"></i> View detail</a>
                                </div>
                                <img src="/img/product/prod02.jpg" class="/img-responsive" alt="" />
                            </div>
                            <div class="product-containt">
                                <div class="price"><del>$100</del> <span>$90</span></div>
                                <div class="rating">
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star-half-o"></i>
                                    <i class="fa fa-star-o"></i>
                                </div>
                                <h6><a href="shop-detail.html">Doming tractatos</a></h6>
                            </div>
                        </div>
                        <!-- End product 2 -->

                        <!-- Start product 3 -->
                        <div class="col-md-4 col-xs-6 product-wrapper" data-price='410' data-colour='blue' data-size='m'  data-brand='lumo'>
                            <div class="product-image">
                                <div class="product-caption">
                                    <a href="#" class="add-cart"><i class="fa fa-shopping-basket"></i> Add to cart</a>
                                    <a href="shop-detail.html" class="view-detail"><i class="fa fa-link"></i> View detail</a>
                                </div>
                                <img src="/img/product/prod03.jpg" class="/img-responsive" alt="" />
                            </div>
                            <div class="product-containt">
                                <div class="price"><span>$410</span></div>
                                <div class="rating">
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star-o"></i>
                                    <i class="fa fa-star-o"></i>
                                </div>
                                <h6><a href="shop-detail.html">Debet ubique</a></h6>
                            </div>
                        </div>
                        <!-- End product 3 -->

                        <!-- Start product 4 -->
                        <div class="col-md-4 col-xs-6 product-wrapper" data-price='150' data-colour='green' data-size='medium'  data-brand='babybones'>
                            <div class="product-image">
                                <div class="product-caption">
                                    <a href="#" class="add-cart"><i class="fa fa-shopping-basket"></i> Add to cart</a>
                                    <a href="shop-detail.html" class="view-detail"><i class="fa fa-link"></i> View detail</a>
                                </div>
                                <img src="/img/product/prod04.jpg" class="/img-responsive" alt="" />
                            </div>
                            <div class="product-containt">
                                <div class="price"><span>$150</span></div>
                                <div class="rating">
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star-o"></i>
                                    <i class="fa fa-star-o"></i>
                                    <i class="fa fa-star-o"></i>
                                </div>
                                <h6><a href="shop-detail.html">Mundi quando</a></h6>
                            </div>
                        </div>
                        <!-- End product 4 -->

                        <!-- Start product 5 -->
                        <div class="col-md-4 col-xs-6 product-wrapper" data-price='250' data-colour='white' data-size='s'  data-brand='wadezig'>
                            <div class="product-image">
                                <div class="product-caption">
                                    <a href="#" class="add-cart"><i class="fa fa-shopping-basket"></i> Add to cart</a>
                                    <a href="shop-detail.html" class="view-detail"><i class="fa fa-link"></i> View detail</a>
                                </div>
                                <img src="/img/product/prod05.jpg" class="/img-responsive" alt="" />
                            </div>
                            <div class="product-containt">
                                <div class="price"><span>$250</span></div>
                                <div class="rating">
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star-o"></i>
                                    <i class="fa fa-star-o"></i>
                                </div>
                                <h6><a href="shop-detail.html">Theophrastus</a></h6>
                            </div>
                        </div>
                        <!-- End product 5 -->

                        <!-- Start product 6 -->
                        <div class="col-md-4 col-xs-6 product-wrapper" data-price='400' data-colour='black' data-size='xl'  data-brand='nls'>
                            <div class="product-image">
                                <div class="product-caption">
                                    <a href="#" class="add-cart"><i class="fa fa-shopping-basket"></i> Add to cart</a>
                                    <a href="shop-detail.html" class="view-detail"><i class="fa fa-link"></i> View detail</a>
                                </div>
                                <img src="/img/product/prod06.jpg" class="/img-responsive" alt="" />
                            </div>
                            <div class="product-containt">
                                <div class="price"><span>$400</span></div>
                                <div class="rating">
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star-o"></i>
                                    <i class="fa fa-star-o"></i>
                                    <i class="fa fa-star-o"></i>
                                </div>
                                <h6><a href="shop-detail.html">Adhuc utamur</a></h6>
                            </div>
                        </div>
                        <!-- End product 6 -->

                        <!-- Start product 7 -->
                        <div class="col-md-4 col-xs-6 product-wrapper" data-price='320' data-colour='cream' data-size='xxl'  data-brand='rsch'>
                            <div class="product-image">
                                <div class="product-caption">
                                    <a href="#" class="add-cart"><i class="fa fa-shopping-basket"></i> Add to cart</a>
                                    <a href="shop-detail.html" class="view-detail"><i class="fa fa-link"></i> View detail</a>
                                </div>
                                <span class="product-label">Sold</span>
                                <img src="/img/product/prod07.jpg" class="/img-responsive" alt="" />
                            </div>
                            <div class="product-containt">
                                <div class="price"><span>$320</span></div>
                                <div class="rating">
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                </div>
                                <h6><a href="shop-detail.html">Imperdiet</a></h6>
                            </div>
                        </div>
                        <!-- End product 7 -->

                        <!-- Start product 8 -->
                        <div class="col-md-4 col-xs-6 product-wrapper" data-price='120' data-colour='purple' data-size='s'  data-brand='screamous'>
                            <div class="product-image">
                                <div class="product-caption">
                                    <a href="#" class="add-cart"><i class="fa fa-shopping-basket"></i> Add to cart</a>
                                    <a href="shop-detail.html" class="view-detail"><i class="fa fa-link"></i> View detail</a>
                                </div>
                                <img src="/img/product/prod08.jpg" class="/img-responsive" alt="" />
                            </div>
                            <div class="product-containt">
                                <div class="price"><span>$120</span></div>
                                <div class="rating">
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star-o"></i>
                                    <i class="fa fa-star-o"></i>
                                </div>
                                <h6><a href="shop-detail.html">Ridens facilisi</a></h6>
                            </div>
                        </div>
                        <!-- End product 8 -->

                        <!-- Start product 9 -->
                        <div class="col-md-4 col-xs-6 product-wrapper" data-price='200' data-colour='brown' data-size='m'  data-brand='kickdenim'>
                            <div class="product-image">
                                <div class="product-caption">
                                    <a href="#" class="add-cart"><i class="fa fa-shopping-basket"></i> Add to cart</a>
                                    <a href="shop-detail.html" class="view-detail"><i class="fa fa-link"></i> View detail</a>
                                </div>
                                <span class="product-label">Sold</span>
                                <img src="/img/product/prod09.jpg" class="/img-responsive" alt="" />
                            </div>
                            <div class="product-containt">
                                <div class="price"><span>$200</span></div>
                                <div class="rating">
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star-o"></i>
                                    <i class="fa fa-star-o"></i>
                                    <i class="fa fa-star-o"></i>
                                </div>
                                <h6><a href="shop-detail.html">Tritani docendi</a></h6>
                            </div>
                        </div>
                        <!-- End product 9 -->

                        <!-- Start product 10 -->
                        <div class="col-md-4 col-xs-6 product-wrapper" data-price='110' data-colour='white' data-size='m'  data-brand='rsch'>
                            <div class="product-image">
                                <div class="product-caption">
                                    <a href="#" class="add-cart"><i class="fa fa-shopping-basket"></i> Add to cart</a>
                                    <a href="shop-detail.html" class="view-detail"><i class="fa fa-link"></i> View detail</a>
                                </div>
                                <img src="/img/product/prod10.jpg" class="/img-responsive" alt="" />
                            </div>
                            <div class="product-containt">
                                <div class="price"><span>$110</span></div>
                                <div class="rating">
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star-half-o"></i>
                                    <i class="fa fa-star-o"></i>
                                </div>
                                <h6><a href="shop-detail.html">Imperdiet</a></h6>
                            </div>
                        </div>
                        <!-- End product 10 -->

                        <!-- Start product 11 -->
                        <div class="col-md-4 col-xs-6 product-wrapper" data-price='250' data-colour='black' data-size='xl'  data-brand='unkl'>
                            <div class="product-image">
                                <span class="product-label">Sale</span>
                                <div class="product-caption">
                                    <a href="#" class="add-cart"><i class="fa fa-shopping-basket"></i> Add to cart</a>
                                    <a href="shop-detail.html" class="view-detail"><i class="fa fa-link"></i> View detail</a>
                                </div>
                                <img src="/img/product/prod11.jpg" class="/img-responsive" alt="" />
                            </div>
                            <div class="product-containt">
                                <div class="price"><span>$250</span></div>
                                <div class="rating">
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star-o"></i>
                                </div>
                                <h6><a href="shop-detail.html">Ridens facilisi</a></h6>
                            </div>
                        </div>
                        <!-- End product 11 -->

                        <!-- Start product 12 -->
                        <div class="col-md-4 col-xs-6 product-wrapper" data-price='280' data-colour='blue' data-size='l'  data-brand='wadezig'>
                            <div class="product-image">
                                <div class="product-caption">
                                    <a href="#" class="add-cart"><i class="fa fa-shopping-basket"></i> Add to cart</a>
                                    <a href="shop-detail.html" class="view-detail"><i class="fa fa-link"></i> View detail</a>
                                </div>
                                <img src="/img/product/prod12.jpg" class="/img-responsive" alt="" />
                            </div>
                            <div class="product-containt">
                                <div class="price"><span>$280</span></div>
                                <div class="rating">
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star-o"></i>
                                    <i class="fa fa-star-o"></i>
                                    <i class="fa fa-star-o"></i>
                                    <i class="fa fa-star-o"></i>
                                </div>
                                <h6><a href="shop-detail.html">Tritani docendi</a></h6>
                            </div>
                        </div>
                        <!-- End product 12 -->
                    </div>
                    <div class="row">
                        <div class="col-md-6 col-sm-6">
                            <nav>
                                <ul class="pagination">
                                    <li class="disabled"><a href="#" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a></li>
                                    <li class="active"><a href="#">1</a></li>
                                    <li><a href="#">2</a></li>
                                    <li><a href="#">3</a></li>
                                    <li><a href="#">4</a></li>
                                    <li><a href="#">5</a></li>
                                    <li><a href="#" aria-label="Next"><span aria-hidden="true">&raquo;</span></a></li>
                                </ul>
                            </nav>
                        </div>
                        <div class="col-md-6 col-sm-6">
                            <div class="result">
                                <label>Showing :</label>
                                <span>1-12 of 102 results</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End contain wrapp -->
@stop