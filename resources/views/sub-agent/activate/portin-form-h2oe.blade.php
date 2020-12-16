<div class="divider2"></div>

<div class="col-sm-12">
    <div class="col-sm-4" align="right">
        <div class="form-group">
            <label class="required">Port-In Number</label>
        </div>
    </div>

    <div class="col-sm-5">
        <div class="form-group">
            <input type="text" class="form-control" 
            id="number_to_port" name="number_to_port" value="" maxlength="11" placeholder="10 digits and digits only"/>
            <div id="error_msg_number_to_port"></div>
        </div>
    </div>
</div>

<div class="col-sm-12">
    <div class="col-sm-4" align="right">
        <div class="form-group">
            <label class="required">Old Service Provider</label>
        </div>
    </div>
    <div class="col-sm-5">
        <div class="form-group">
            <select class="form-control" id="old_service_provider" name="old_service_provider"
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
            <div id="error_msg_old_service_provider"></div>
        </div>
    </div><div class="col-sm-2"></div>
</div>

<div class="col-sm-12">
    <div class="col-sm-4" align="right">
        <div class="form-group">
            <label class="required">If Other Service provider is under contract</label>
        </div>
    </div>
    <div class="col-sm-5">
        <div class="form-group">
            <select class="form-control" id="cell_number_contract" name="cell_number_contract"
                    data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                <option value="">Please Select</option>
                <option value="YES">YES</option>
                <option value="NO">NO</option>
            </select>
            <div id="error_msg_cell_number_contract"></div>
        </div>
    </div><div class="col-sm-2"></div>
</div>

{{--<div class="col-sm-12" id="imei_box">--}}
{{--    <div class="col-sm-4" align="right">--}}
{{--        <div class="form-group">--}}
{{--            <label class="required">IMEI</label>--}}
{{--        </div>--}}
{{--    </div>--}}
{{--    <div class="col-sm-5" align="right">--}}
{{--        <div class="form-group">--}}
{{--            <input type="text" class="form-control"--}}
{{--                   id="imei"--}}
{{--                   name="imei"--}}
{{--                   value=""--}}
{{--                   maxlength="20"--}}
{{--                   placeholder=""--}}
{{--                   onchange="get_commission()"--}}
{{--            />--}}
{{--            <span style="font-size: 12px; margin-left: 10px">  Enter in IMEI for Maximize activation bonus.</span>--}}
{{--            <div id="error_msg_imei"></div>--}}
{{--        </div>--}}
{{--    </div>--}}
{{--    <div class="col-sm-3" align="right">--}}
{{--    </div>--}}
{{--</div>--}}

<div class="col-sm-12">
   <div class="col-sm-4" align="right">
    <div class="form-group">
        <label class="required">Account #</label>
    </div>
    </div>
   <div class="col-sm-5">
    <div class="form-group">
        <input type="text" class="form-control" id="account_no" name="account_no"/>
        <div id="error_msg_account_no"></div>
    </div>
    </div><div class="col-sm-2"></div>
</div>


<div class="col-sm-12">
   <div class="col-sm-4" align="right">
    <div class="form-group">
        <label class="required">Account PIN</label>
    </div>
    </div>
   <div class="col-sm-5" align="right">
    <div class="form-group">
        <input type="text" class="form-control" id="account_pin" name="account_pin"/>
        <div id="error_msg_account_pin"></div>
    </div>
    </div><div class="col-sm-2"></div>
</div>

<div class="col-sm-12">
    <div class="col-sm-4" align="right">
        <div class="form-group">
            <label class="required">First Name</label>
        </div>
    </div>

    <div class="col-sm-5">
        <div class="form-group">
            <input type="text" class="form-control" 
            id="first_name" name="first_name" value=""/>
            <div id="error_msg_first_name"></div>
        </div>
    </div>
</div>

<div class="col-sm-12">
    <div class="col-sm-4" align="right">
        <div class="form-group">
            <label class="required">Last Name</label>
        </div>
    </div>

    <div class="col-sm-5">
        <div class="form-group">
            <input type="text" class="form-control" 
            id="last_name" name="last_name" value=""/>
            <div id="error_msg_last_name"></div>
        </div>
    </div>
</div>

<div class="col-sm-12">
   <div class="col-sm-4" align="right">   
    <div class="form-group">
        <label class="required">Street Number</label>
    </div>
   </div>
   <div class="col-sm-5">   
    <div class="form-group">
        <input type="text" class="form-control" id="address1" name="address1" placeholder="House Number Only (Unnecessary filled will occur error)" />
        <div id="error_msg_address1"></div>
    </div>
   </div><div class="col-sm-2"></div>
</div>


<div class="col-sm-12">
   <div class="col-sm-4" align="right">  
    <div class="form-group">
        <label class="required">Street Name</label>
     </div>
   </div>
   <div class="col-sm-5">  
    <div class="form-group">
        <input type="text" class="form-control" id="address2" name="address2" placeholder="Name of Street Only" />
        <div id="error_msg_address2"></div>
     </div>
   </div><div class="col-sm-2"></div>
</div>
                                    
<div class="col-sm-12">
   <div class="col-sm-4" align="right">  
    <div class="form-group">
        <label class="required">City</label>
     </div>
   </div>
   <div class="col-sm-5">  
    <div class="form-group">
        <input type="text" class="form-control" id="{{ !empty($product_id) && $product_id == 'WGENA' ? 'account_' :
        '' }}city" name="city"/>
        <div id="error_msg_city"></div>
     </div>
   </div><div class="col-sm-2"></div>
</div>
                                    
<div class="col-sm-12">
   <div class="col-sm-4" align="right"> 
    <div class="form-group">
        <label class="required">State</label>
    </div>
   </div>
   <div class="col-sm-5"> 
    <div class="form-group">
        <select class="form-control" id="{{ !empty($product_id) && $product_id == 'WGENA' ? 'account_' : '' }}state"
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
                                    
<div class="col-sm-12">
   <div class="col-sm-4" align="right">
    <div class="form-group">
        <label class="required">Zip Code</label>
     </div>
   </div>
   <div class="col-sm-5">
    <div class="form-group">
        <input type="number" class="form-control" id="account_zip" name="account_zip"/>
        <div id="error_msg_account_zip"></div>
     </div>
   </div><div class="col-sm-2"></div>
</div>

<div class="col-sm-12">
   <div class="col-sm-4" align="right">  
    <div class="form-group">
        <label class="required">Call Back</label>
     </div>
   </div>
   <div class="col-sm-5">  
    <div class="form-group">
        <input type="text" class="form-control" id="call_back_phone" name="call_back_phone" maxlength="11" placeholder="11 digits and digits only"/>
        <div id="error_msg_call_back_phone"></div>
     </div>
   </div><div class="col-sm-2"></div>
</div>

<div class="col-sm-12">
    <div class="col-sm-4" align="right">
        <div class="form-group">
            <label class="required">Email</label>
        </div>
    </div>
    <div class="col-sm-5">
        <div class="form-group">
            <input type="text" class="form-control" id="email" name="email"/>
            <div id="error_msg_email"></div>
        </div>
    </div><div class="col-sm-2"></div>
</div>

<div class="col-md-12">
    <div class="col-sm-4" align="right">
        <div class="form-group">
            <label>Note</label>
        </div>
    </div>
    <div class="col-sm-5">
        <div class="form-group">
            <textarea class="form-control" id="note" name="note"></textarea>
        </div>
    </div><div class="col-sm-2"></div>
</div>

<div class="divider2"></div>












