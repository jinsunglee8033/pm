@extends('layout.default')

@section('content')

    <script type="text/javascript">
        window.onload = function() {
            var field = document.querySelector('[name="user_id"]');
            field.addEventListener('keypress', function ( event ) {
                var key = event.keyCode;
                if (key === 32) {
                    event.preventDefault();
                }
            });

            field.addEventListener('mouseout', function () {
                myText = $("#user_id").val();
                var remove_space = myText.replace(/[^a-zA-Z]/g, "");
                $("#user_id").val(remove_space);
            });

            @if ($errors->has('pending'))
                $('#div_detail').modal();
            @endif

        };
    </script>

    <div class="modal" id="div_detail" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">Welcome to SoftPayPlus!</h4>
                </div>
                <div class="modal-body" style="padding: 20px 20px 25px;">
                    <b>Attention to New Agents,<br></b>
                    Your account was created successfully and currently on "Hold" until verification of your email address.<br>
                    On the registration date, we've sent you a registration verification email from <b>SoftPayPlus</b> <<a href="mailto:ops@softpayplus.com" target="_blank">ops@softpayplus.com</a>> <br>
                    From our email, you can initiate your account to be Active status by clicking the link.<br>
                    {{ $errors->first('pending') }}
                </div>
                <div class="modal-footer" style="text-align: left;">
                    If you missed our email, please send us an email at <a href="mailto:ops@softpayplus.com" target="_blank" style="color: black">ops@softpayplus.com</a><br>
                    Thank you.
                </div>
            </div>
        </div>
    </div>

    <!-- Start register -->
    <div class="logreg">
        <div class="contain-wrapp gray-container">
            <div class="row">
                <div class="col-md-4 col-md-offset-4 col-sm-8 col-sm-offset-2">
                    <div class="col-md-12 marginbot30">
                        <div class="cart-total">
                            <p class="subtotal"><label>Login</label></p>
                            <div class="divider2"></div>

                            <form method="POST" action="{{ route('login') }}">
                                {{ csrf_field() }}
                                <div class="form-group{{ $errors->has('user_id') ? ' has-error' : '' }}">
                                    <label>User ID : <span class="noempaty">*</span> <strong>(Case Sensitive)
                                        </strong></label>
                                    <input type="text" class="form-control" name="user_id"
                                           placeholder="Enter user ID. Case sensitive."/>
                                    @if ($errors->has('user_id'))
                                        <span class="help-block">
                                            <strong>{{ $errors->first('user_id') }}</strong>
                                        </span>
                                    @endif
                                </div>
                                <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
                                    <label>Password : <span class="noempaty">*</span><strong>(Case Sensitive)
                                        </strong></label>
                                    <input type="password" class="form-control" name="password"
                                           placeholder="Enter your password. Case sensitive."/>
                                    @if ($errors->has('password'))
                                        <span class="help-block">
                                                        <strong>{{ $errors->first('password') }}</strong>
                                                    </span>
                                    @endif
                                </div>
								<p></p>
                                <div class="form-group" style="margin-top: 10px;">
                                    <button type="submit" class="btn btn-primary btn-block">Login now</button>
                                </div>
                                <div class="form-group" style="margin-top: 10px;text-align: left;">
                                    <a class="btn btn-link" href="{{ route('password.request') }}" style="font-size:
                                        22px;padding-left: 0px;">
                                        <strong>Reset Your Password?</strong>
                                    </a>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

{{--    @if ($errors->has('user_id'))--}}

{{--    @endif--}}
    <!-- End register -->
@endsection
