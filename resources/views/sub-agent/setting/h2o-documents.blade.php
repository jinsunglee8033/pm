@extends('sub-agent.layout.default')

@section('content')
    <!-- Start parallax -->
    <div class="parallax" data-background="/img/parallax/innerpage.jpg" data-speed="0.5" data-size="50%">
        <div class="overlay white"></div>
        <div class="container">
            <div class="inner-head">
                <div class="row">
                    <div class="col-md-12 text-center">
                        <h4>Documents</h4>
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
                            <li>
                                <a href="/sub-agent/setting/att-documents">ATT Documents</a>
                            </li>
                            <li class="active">
                                <a href="/sub-agent/setting/h2o-documents">H2O Documents</a>
                            </li>
                        </ul>

                        <!-- Tab panes -->
                        <div class="tab-content">
                            <div class="form-horizontal">

                                @foreach ($files as $o)
                                <div class="form-group">
                                    <label class="col-sm-2 control-label">{{ $o->label }} :
                                        @if ( $o->key == 'FILE_H2O_DEALER_FORM')
                                            @if ($o->file == null)
                                                <br><a class="float:right" href="/sub-agent/esig/FILE_H2O_DEALER_FORM" target="_blank">eSignature</a>
                                            @else
                                                @if ($o->file->locked == 'Y')

                                                @else
                                                    <br><a class="float:right" href="/sub-agent/esig/FILE_H2O_DEALER_FORM" target="_blank">eSignature</a>
                                                @endif
                                            @endif
{{--                                            <br><a class="float:right" href="/upload_template/h2o_dealer_form.pdf" target="_blank">form download</a>--}}
                                        @endif
                                        @if ( $o->key == 'FILE_H2O_ACH')
                                            @if ($o->file == null)
                                                <br><a class="float:right" href="/sub-agent/esig/FILE_H2O_ACH" target="_blank">eSignature</a>
                                            @else
                                                @if ($o->file->locked == 'Y')

                                                @else
                                                    <br><a class="float:right" href="/sub-agent/esig/FILE_H2O_ACH" target="_blank">eSignature</a>
                                                @endif
                                            @endif
{{--                                            <br><a class="float:right" href="/upload_template/h2o_ach.pdf" target="_blank">form download</a>--}}
                                        @endif
                                    </label>
                                    <div class="col-sm-6" style="margin-top:8px;">
                                        @if ($o->key != 'FILE_DEALER_AGREEMENT')
                                        <form method="post" class="form-inline {{ $errors->has($o->key) ? ' has-error' : '' }}" action="/sub-agent/setting/documents" enctype="multipart/form-data">
                                            {!! csrf_field() !!}
                                            <div class="form-group">
                                                <input type="hidden" name="file_type" value="{{ $o->key }}"/>
{{--                                                <input type="file" class="form-control" name="{{ $o->key }}" {{ isset($o->file) && $o->file->locked == 'Y' ? 'disabled' : '' }}/>--}}

{{--                                                <button type="submit" class="btn btn-primary btn-sm" {{ isset($o->file) && $o->file->locked == 'Y' ? 'disabled' : '' }}>Upload</button>--}}
                                            </div>
                                            @if ($errors->has($o->key))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first($o->key) }}</strong>
                                                </span>
                                            @endif
                                        </form>
                                        @else
                                            <label class="control-label">
                                            @if (!isset($o->file) || isset($o->fiile) && $o->file->locked == 'N')
                                                <a href="/esig" class="form-control" target="_blank">Click here to sign dealer agreement</a>
                                            @elseif (isset($o->file) && $o->file->signed == 'Y')
                                            @elseif (isset($o->file) && $o->file->signed == 'N')
                                                <a href="/esig" class="form-control" target="_blank">I've lost email. Start re-signing process</a>
                                            @endif
                                            </label>
                                        @endif

                                    </div>
                                    <label class="col-sm-4 control-label">
                                        @if ($o->key != 'FILE_DEALER_AGREEMENT')
                                            @if ($o->key == 'FILE_W_9' || $o->key == 'FILE_ACH_DOC' || $o->key == 'FILE_H2O_DEALER_FORM' || $o->key == 'FILE_H2O_ACH' || $o->key == 'FILE_PR_SALES_TAX' || $o->key == 'FILE_USUC')
                                                <a href="{{ isset($o->file) ? "/file/view/" . $o->file->id : '#' }}" id="a_{{ $o->key }}" class="form-control text-left" style="display:{{ isset($o->file) ? '' : 'none' }}">
                                                    Upload at {{ isset($o->file) ? $o->file->cdate : '' }}.
                                                </a>
                                                @if (isset($o->file) && $o->file->signed == 'N' && $o->file->data== null)
                                                    <p class="text-left" style="color: red">Confirm your email and complete eSignature.</p>
                                                @endif
                                            @else
                                                <a href="{{ isset($o->file) ? "/file/view/" . $o->file->id : '#' }}" id="a_{{ $o->key }}" class="form-control text-left" style="display:{{ isset($o->file) ? '' : 'none' }}">
                                                    Upload at {{ isset($o->file) ? $o->file->cdate : '' }}.
                                                </a>
                                            @endif
                                        @else
                                            @if (isset($o->file) && $o->file->signed == 'Y')
                                                <a href="{{ isset($o->file) ? "/file/view/" . $o->file->id : '#' }}" id="a_{{ $o->key }}" class="form-control text-left" style="display:{{ isset($o->file) ? '' : 'none' }}">
                                                    Upload at {{ isset($o->file) ? $o->file->cdate : '' }}.
                                                </a>
                                            @elseif (isset($o->file) && $o->file->signed == 'N')
                                                <a href="#" class="form-control text-left">Please confirm your email first.</a>
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
