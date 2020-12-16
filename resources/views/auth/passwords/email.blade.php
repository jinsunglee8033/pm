@extends('layout.default')

@section('content')

    <div class="logreg">
        <div class="contain-wrapp gray-container">
            <div class="row">
                <div class="col-md-4 col-md-offset-4 col-sm-8 col-sm-offset-2">
                    <div class="col-md-12 marginbot30">
                        <div class="cart-total">
                            <p class="subtotal"><label>Forgot Password</label></p>
                            <div class="dividerin"></div>

                            @if (session('status'))
                                <div class="alert alert-success">
                                    {{ session('status') }}
                                </div>
                            @endif

                            <form method="POST" action="{{ route('password.email') }}">
                                {{ csrf_field() }}
                                <div class="form-group{{ $errors->has('user_id') ? ' has-error' : '' }}">
                                    <label>User ID : <span class="noempaty">*</span></label>
                                    <input type="text" class="form-control" name="user_id"
                                           placeholder="Enter user ID"/>
                                    @if ($errors->has('user_id'))
                                        <span class="help-block">
                                                        <strong>{{ $errors->first('user_id') }}</strong>
                                                    </span>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary btn-block">Send Password Reset Link</button>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
