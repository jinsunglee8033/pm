<script type="text/javascript">

    function validate_mdn() {

        if($('#port_in_mdn').val().length != 10){
            alert('Please 10 digit MDN');
            return;
        }

        myApp.showLoading();
        $.ajax({
            url: '/sub-agent/activate/boom/validate_mdn',
            data: {
                mdn: $('#port_in_mdn').val(),
                network: "RED",
                zip: " "
            },
            type: 'get',
            dataType: 'json',
            cache: false,
            success: function(res) {
                myApp.hideLoading();
                if (res.code === '0') {
                    alert("ELIGIBLE MDN");
                    $('#act_btn').prop('disabled', false);
                } else {
                    alert(res.msg);
                    $('#port_in_mdn').focus();
                    $('#account_no').prop('readonly', false);
                    $('#act_btn').prop('disabled', true);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                myApp.hideLoading();
                myApp.showError(errorThrown);
            }
        });
    }

</script>

    <div class="divider2"></div>

    <div class="col-sm-8">
        <div class="col-sm-7" align="right">
            <div class="form-group">
                <label class="required">Port-In MDN</label>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                <input type="text" class="form-control"
                       id="port_in_mdn" name="port_in_mdn" value="" maxlength="10"
                       placeholder="10 digits and digits only"
                       onchange="validate_mdn()"
                />
                <div id="error_msg_port_in_mdn"></div>
            </div>
        </div>
        <div class="col-sm-1" align="right">
            <a class="btn btn-info btn-xs">
                Eligible Check
            </a>
        </div>
    </div>

    <div class="col-sm-8">
        <div class="col-sm-7" align="right">
            <div class="form-group">
                <label class="required">First Name</label>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                <input type="text" class="form-control"
                id="first_name" name="first_name" value=""/>
                <div id="error_msg_first_name"></div>
            </div>
        </div>
    </div>

    <div class="col-sm-8">
        <div class="col-sm-7" align="right">
            <div class="form-group">
                <label class="required">Last Name</label>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                <input type="text" class="form-control"
                id="last_name" name="last_name" value=""/>
                <div id="error_msg_last_name"></div>
            </div>
        </div>
    </div>

    <div class="col-sm-8">
        <div class="col-sm-7" align="right">
            <div class="form-group">
                <label class="required">Carrier</label>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <select class="form-control" id="carrier" name="carrier"
                        data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                    <option value="">Please Select</option>
                    @foreach ($carriers as $o)
                        <option value="{{ $o->name }}">{{ $o->name }}</option>
                    @endforeach

                </select>
                <div id="error_msg_carrier"></div>
            </div>
        </div><div class="col-sm-2"></div>
    </div>

    <div class="col-sm-8">
        <div class="col-sm-7" align="right">
            <div class="form-group">
                <label class="required">Account #</label>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <input type="text" class="form-control" id="account_no" name="account_no"/>
                <div id="error_msg_account_no"></div>
            </div>
        </div><div class="col-sm-2"></div>
    </div>


    <div class="col-sm-8">
        <div class="col-sm-7" align="right">
            <div class="form-group">
                <label class="required">Password</label>
            </div>
        </div>
        <div class="col-sm-4" align="right">
            <div class="form-group">
                <input type="text" class="form-control" id="password" name="password"/>
                <div id="error_msg_password"></div>
            </div>
        </div><div class="col-sm-2"></div>
    </div>

    <div class="col-sm-8">
       <div class="col-sm-7" align="right">
        <div class="form-group">
            <label class="required">Street 1</label>
        </div>
       </div>
       <div class="col-sm-4">
        <div class="form-group">
            <input type="text" class="form-control" id="street_number" name="street_number" placeholder="Street Address" />
            <div id="error_msg_street_number"></div>
        </div>
       </div><div class="col-sm-2"></div>
    </div>


    <div class="col-sm-8">
       <div class="col-sm-7" align="right">
        <div class="form-group">
            <label class="required">Street 2</label>
         </div>
       </div>
       <div class="col-sm-4">
        <div class="form-group">
            <input type="text" class="form-control" id="street_name" name="street_name" placeholder="Apartment/Suite/Other" />
            <div id="error_msg_street_name"></div>
         </div>
       </div><div class="col-sm-2"></div>
    </div>

    <div class="col-sm-8">
       <div class="col-sm-7" align="right">
        <div class="form-group">
            <label class="required">City</label>
         </div>
       </div>
       <div class="col-sm-4">
        <div class="form-group">
            <input type="text" class="form-control" id="city" name="city"/>
            <div id="error_msg_city"></div>
         </div>
       </div><div class="col-sm-2"></div>
    </div>

    <div class="col-sm-8">
       <div class="col-sm-7" align="right">
        <div class="form-group">
            <label class="required">State</label>
        </div>
       </div>
       <div class="col-sm-4">
        <div class="form-group">
            <select class="form-control" id="state"
                    name="state"
                    data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                <option value="">Please Select</option>
                @if (isset($states))
                    @foreach ($states as $o)
                        <option value="{{ $o->code }}">{{ $o->name }}</option>
                    @endforeach
                @endif
            </select>
            <div id="error_msg_state"></div>
        </div>
       </div><div class="col-sm-2"></div>
    </div>

    <div class="col-sm-8">
        <div class="col-sm-7" align="right">
            <div class="form-group">
                <label class="required">Zip</label>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <input type="text" class="form-control" id="portin_zip" name="portin_zip"/>
                <div id="error_msg_portin_zip"></div>
            </div>
        </div><div class="col-sm-2"></div>
    </div>

    <div class="col-sm-8">
       <div class="col-sm-7" align="right">
        <div class="form-group">
            <label class="required">Call Back Number</label>
         </div>
       </div>
       <div class="col-sm-4">
        <div class="form-group">
            <input type="text" class="form-control" id="call_back_number" name="call_back_number" maxlength="10" placeholder="10 digits and digits only"/>
            <div id="error_msg_call_back_number"></div>
         </div>
       </div><div class="col-sm-2"></div>
    </div>

{{--    <div class="col-sm-8">--}}
{{--        <div class="col-sm-7" align="right">--}}
{{--            <div class="form-group">--}}
{{--                <label class="required">Email</label>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--        <div class="col-sm-4">--}}
{{--            <div class="form-group">--}}
{{--                <input type="text" class="form-control" id="email" name="email"/>--}}
{{--                <div id="error_msg_email"></div>--}}
{{--            </div>--}}
{{--        </div><div class="col-sm-2"></div>--}}
{{--    </div>--}}

    <div class="divider2"></div>












