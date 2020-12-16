
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
                network: "PINK",
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


<div class="col-sm-12">
    <div class="col-sm-4" align="right">
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
    <div class="col-sm-3" align="left">
        <a class="btn btn-info btn-xs">
            Eligible Check
        </a>
    </div>
</div>

<div class="col-sm-12">
    <div class="col-sm-4" align="right">
        <div class="form-group">
            <label class="required">Carrier</label>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="form-group">
            <select class="form-control" id="carrier" name="carrier"
                    data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                <option value="">Please Select</option>
                <option value="ATT">ATT</option>
                <option value="Sprint">Sprint</option>
                <option value="T-Mobile">T-Mobile</option>
                <option value="Verizon Wireless">Verizon Wireless</option>
                <option value="Airvoice Wireless">Airvoice Wireless</option>
                <option value="Boost Mobile">Boost Mobile</option>
                <option value="Claro PR">Claro PR</option>
                <option value="Consumer Cellular">Consumer Cellular</option>
                <option value="Credo">Credo</option>
                <option value="Cricket Wireless">Cricket Wireless</option>
                <option value="Envie">Envie</option>
                <option value="Google Voice">Google Voice</option>
                <option value="Hanacell">Hanacell</option>
                <option value="Helio Wireless">Helio Wireless</option>
                <option value="Horizon Mobile">Horizon Mobile</option>
                <option value="iWireless">iWireless</option>
                <option value="Jolt Mobile">Jolt Mobile</option>
                <option value="Lycamobile">Lycamobile</option>
                <option value="Metro PCS">Metro PCS</option>
                <option value="Mobi PCS">Mobi PCS</option>
                <option value="Net10">Net10</option>
                <option value="Open Mobile">Open Mobile</option>
                <option value="PAETEC/Windstream">PAETEC/Windstream</option>
                <option value="PagePlus">PagePlus</option>
                <option value="Pocket Mobile">Pocket Mobile</option>
                <option value="RedPocket">RedPocket</option>
                <option value="Revol Wireless">Revol Wireless</option>
                <option value="Safelink Wireless">Safelink Wireless</option>
                <option value="Simple Mobile">Simple Mobile</option>
                <option value="Straight Talk">Straight Talk</option>
                <option value="Total Call Wireless">Total Call Wireless</option>
                <option value="Tracfone">Tracfone</option>
                <option value="Tuyo Mobile">Tuyo Mobile</option>
                <option value="Ultra Mobile">Ultra Mobile</option>
                <option value="US Cellular">US Cellular</option>
                <option value="Virgin Mobile">Virgin Mobile</option>
                <option value="Vonage">Vonage</option>
                <option value="Walmart Family Mobile">Walmart Family Mobile</option>
                <option value="Xtreme Mobile">Xtreme Mobile</option>
                <option value="Others">Others</option>
            </select>
            <div id="error_msg_carrier"></div>
        </div>
    </div><div class="col-sm-2"></div>
</div>

<div class="col-sm-12">
    <div class="col-sm-4" align="right">
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


<div class="col-sm-12">
    <div class="col-sm-4" align="right">
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

{{--<div class="col-sm-12">--}}
{{--   <div class="col-sm-4" align="right">   --}}
{{--    <div class="form-group">--}}
{{--        <label class="required">Street 1</label>--}}
{{--    </div>--}}
{{--   </div>--}}
{{--   <div class="col-sm-4">--}}
{{--    <div class="form-group">--}}
{{--        <input type="text" class="form-control" id="street_number" name="street_number" placeholder="Street Address" />--}}
{{--        <div id="error_msg_street_number"></div>--}}
{{--    </div>--}}
{{--   </div><div class="col-sm-2"></div>--}}
{{--</div>--}}


{{--<div class="col-sm-12">--}}
{{--   <div class="col-sm-4" align="right">  --}}
{{--    <div class="form-group">--}}
{{--        <label class="required">Street 2</label>--}}
{{--     </div>--}}
{{--   </div>--}}
{{--   <div class="col-sm-4">--}}
{{--    <div class="form-group">--}}
{{--        <input type="text" class="form-control" id="street_name" name="street_name" placeholder="Apartment/Suite/Other" />--}}
{{--        <div id="error_msg_street_name"></div>--}}
{{--     </div>--}}
{{--   </div><div class="col-sm-2"></div>--}}
{{--</div>--}}
                                    
{{--<div class="col-sm-12">--}}
{{--   <div class="col-sm-4" align="right">  --}}
{{--    <div class="form-group">--}}
{{--        <label class="required">City</label>--}}
{{--     </div>--}}
{{--   </div>--}}
{{--   <div class="col-sm-4">--}}
{{--    <div class="form-group">--}}
{{--        <input type="text" class="form-control" id="city" name="city"/>--}}
{{--        <div id="error_msg_city"></div>--}}
{{--     </div>--}}
{{--   </div><div class="col-sm-2"></div>--}}
{{--</div>--}}
{{--                                    --}}
{{--<div class="col-sm-12">--}}
{{--   <div class="col-sm-4" align="right"> --}}
{{--    <div class="form-group">--}}
{{--        <label class="required">State</label>--}}
{{--    </div>--}}
{{--   </div>--}}
{{--   <div class="col-sm-4">--}}
{{--    <div class="form-group">--}}
{{--        <select class="form-control" id="state"--}}
{{--                name="state"--}}
{{--                data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>--}}
{{--            <option value="">Please Select</option>--}}
{{--            @if (isset($states))--}}
{{--                @foreach ($states as $o)--}}
{{--                    <option value="{{ $o->code }}">{{ $o->name }}</option>--}}
{{--                @endforeach--}}
{{--            @endif--}}
{{--        </select>--}}
{{--        <div id="error_msg_state"></div>--}}
{{--    </div>--}}
{{--   </div><div class="col-sm-2"></div>--}}
{{--</div>--}}

{{--<div class="col-sm-12">--}}
{{--    <div class="col-sm-4" align="right">--}}
{{--        <div class="form-group">--}}
{{--            <label class="required">Zip</label>--}}
{{--        </div>--}}
{{--    </div>--}}
{{--    <div class="col-sm-4">--}}
{{--        <div class="form-group">--}}
{{--            <input type="text" class="form-control" id="portin_zip" name="portin_zip"/>--}}
{{--            <div id="error_msg_portin_zip"></div>--}}
{{--        </div>--}}
{{--    </div><div class="col-sm-2"></div>--}}
{{--</div>--}}

<div class="col-sm-12">
   <div class="col-sm-4" align="right">  
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

{{--<div class="col-sm-12">--}}
{{--    <div class="col-sm-4" align="right">--}}
{{--        <div class="form-group">--}}
{{--            <label class="required">Email</label>--}}
{{--        </div>--}}
{{--    </div>--}}
{{--    <div class="col-sm-4">--}}
{{--        <div class="form-group">--}}
{{--            <input type="text" class="form-control" id="email" name="email"/>--}}
{{--            <div id="error_msg_email"></div>--}}
{{--        </div>--}}
{{--    </div><div class="col-sm-2"></div>--}}
{{--</div>--}}

<div class="divider2"></div>












