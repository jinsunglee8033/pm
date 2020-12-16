@extends('sub-agent.layout.default')

@section('content')

        <!-- Start contain wrapp -->
        <div id="testimoni" class="contain-wrapp gray-container">
            <div class="container">

                <div class="row">
                    <div class="col-md-8 col-md-offset-2">
                        <div class="title-head centered" style="margin-bottom:20px;">
                            <h2>Follow-Ups</h2>
{{--                            <p>We are Master Agent of VERIZON PREPAID, H2O, LYCA and more! </p>--}}
                        </div>
                    </div>
                </div>
                <div class="row" style="margin-bottom:20px;">
                    <div class="col-md-12">
                        <form method="post" onsubmit="this.form.submit()">
                            {!! csrf_field() !!}
                        </form>
                    </div>
                </div>
                @if (isset($news))
                    @foreach ($news as $o)
                        <div id="{{ $o->id }}"></div>
                        <br><br>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="testimoni-wrapp">
                                    <div class="testimoni-contain">
                                        <blockquote>
                                            <p>
                                                <b>{!! $o->subject !!} </b><br/>
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