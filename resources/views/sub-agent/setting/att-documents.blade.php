@extends('sub-agent.layout.default')

@section('content')
    <!-- Start parallax -->
    <div class="parallax" data-background="/img/parallax/innerpage.jpg" data-speed="0.5" data-size="50%">
        <div class="overlay white"></div>
        <div class="container">
            <div class="inner-head">
                <div class="row">
                    <div class="col-md-12 text-center">
                        <h4>AT&T Documents</h4>
                        <ol class="breadcrumb">
                            <li><a href="#">Home</a></li>
                            <li><a href="#">Setting</a></li>
                            <li class="active">Documents</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End parallax -->

    <!-- Start contain wrapp -->
    <div class="contain-wrapp padding-bot70">
        <div class="container">
            <div class="row">
                <div class="col-md-12 col-sm-12">
                    <div class="clearfix"></div>


                    <div class="tabbable tab-lg">
                        <ul class="nav nav-tabs">
                            <li>
                                <a href="/sub-agent/setting/my-password">My Password</a>
                            </li>
                            <li>
                                <a href="/sub-agent/setting/my-account">My Account</a>
                            </li>
                            <li>
                                <a href="/sub-agent/setting/users">Users</a>
                            </li>
                            <li>
                                <a href="/sub-agent/setting/documents">Documents</a>
                            </li>
                            <li class="active">
                                <a href="/sub-agent/setting/att-documents">ATT Documents</a>
                            </li>
                            <li>
                                <a href="/sub-agent/setting/h2o-documents">H2O Documents</a>
                            </li>
                        </ul>

                        <!-- Tab panes -->
                        <div class="tab-content">
                            <div class="form-horizontal">

                                @foreach ($files as $o)
                                <div class="form-group">
                                    <label class="col-sm-2 control-label">{{ $o->label }} :
                                        @if ( $o->key == 'FILE_ATT_AGREEMENT')
                                            @if ( $o->file == null)
                                                <br><a class="float:right" href="/sub-agent/esig/FILE_ATT_AGREEMENT" target="_blank">eSignature</a>
                                            @else
                                                @if($o->file->locked == 'Y')

                                                @else
                                                    <br><a class="float:right" href="/sub-agent/esig/FILE_ATT_AGREEMENT" target="_blank">eSignature</a>
                                                @endif
                                            @endif
{{--                                            <br><a class="float:right" href="/upload_template/FILE_ATT_AGREEMENT.pdf" target="_blank">form download</a>--}}
                                        @elseif ($o->key == 'FILE_ATT_BUSINESS_CERTIFICATION')
                                            <br><small class="float:right">
                                                (If your states do not issue Business Cert. use <strong><u>Certificate of Registration</u></strong>)
                                            </small>
                                        @endif
                                    </label>
                                    <div class="col-sm-6" style="margin-top:8px;">
                                        <form method="post" class="form-inline {{ $errors->has($o->key) ? ' has-error' : '' }}" action="/sub-agent/setting/att-documents" enctype="multipart/form-data">
                                            {!! csrf_field() !!}
                                            <div class="form-group">
                                                <input type="hidden" name="file_type" value="{{ $o->key }}"/>
                                                <input type="file" class="form-control" name="{{ $o->key }}" {{ isset($o->file) && $o->file->locked == 'Y' ? 'disabled' : '' }}/>
                                                <button type="submit" class="btn btn-primary btn-sm" {{ isset($o->file) && $o->file->locked == 'Y' ? 'disabled' : '' }}>Upload</button>
                                            </div>
                                            @if ($errors->has($o->key))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first($o->key) }}</strong>
                                                </span>
                                            @else
                                                @if ($o->key != 'FILE_ATT_AGREEMENT')
                                                    <span class="help-block">
                                                        <strong>Recommended Picture Image not PDF</strong>
                                                    </span>
                                                @endif
                                            @endif
                                        </form>
                                    </div>
                                    <label class="col-sm-4 control-label">
                                        @if (isset($o->file))
                                            <a href="{{ isset($o->file) ? "/file/att_view/" . $o->file->id : '#' }}" id="a_{{ $o->key }}" class="form-control text-left" style="display:{{ isset($o->file) ? '' : 'none' }}">
                                                Upload at {{ isset($o->file) ? $o->file->cdate : '' }}.
                                            </a>
                                            @if ($o->key == 'FILE_ATT_AGREEMENT')
                                                @if ($o->file->signed == 'N' || $o->file->data== null)
                                                    <p class="text-left" style="color: red">Confirm your email and complete eSignature.</p>
                                                @endif
                                            @endif
                                        @endif
                                    </label>
                                </div>

                                <hr />
                                @endforeach
                                <div class="form-group">
                                    @if (session()->has('success'))
                                        <div class="alert alert-success alert-dismissible" role="alert">
                                            <strong>Success!</strong> {{ session('success') }}
                                        </div>
                                    @endif
                                    @if ($errors->has('exception'))
                                        <div class="alert alert-danger" role="alert">
                                            <strong>Error!</strong> {{ $errors->first('exception') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
    <!-- End contain wrapp -->
@stop
