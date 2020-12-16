<div class="divider2"></div>


<div class="col-sm-12">
    <div class="col-sm-7" align="right">
        <div class="form-group">
            <label class="required">Port-In Number</label>
        </div>
    </div>

    <div class="col-sm-5">
        <div class="form-group">
            <input type="text" class="form-control"
                   id="p_number_to_port" name="p_number_to_port" value="" maxlength="10" placeholder="10 digits and digits only"/>
            <div id="error_msg_p_number_to_port"></div>
        </div>
    </div>
</div>

<div class="col-sm-12">
    <div class="col-sm-7" align="right">
        <div class="form-group">
            <label class="required">Account #</label>
        </div>
    </div>
    <div class="col-sm-5">
        <div class="form-group">
            <input type="text" class="form-control" id="p_account_no" name="p_account_no"/>
            <div id="error_msg_p_account_no"></div>
        </div>
    </div><div class="col-sm-2"></div>
</div>


<div class="col-sm-12">
    <div class="col-sm-7" align="right">
        <div class="form-group">
            <label class="required">Account PIN</label>
        </div>
    </div>
    <div class="col-sm-5" align="right">
        <div class="form-group">
            <input type="text" class="form-control" id="p_account_pin" name="p_account_pin"/>
            <div id="error_msg_p_account_pin"></div>
        </div>
    </div><div class="col-sm-2"></div>
</div>

<div class="col-sm-12">
    <div class="col-sm-7" align="right">
        <div class="form-group">
            <label class="required">First Name</label>
        </div>
    </div>

    <div class="col-sm-5">
        <div class="form-group">
            <input type="text" class="form-control"
                   id="p_first_name" name="p_first_name" value=""/>
            <div id="error_msg_p_first_name"></div>
        </div>
    </div>
</div>

<div class="col-sm-12">
    <div class="col-sm-7" align="right">
        <div class="form-group">
            <label class="required">Last Name</label>
        </div>
    </div>

    <div class="col-sm-5">
        <div class="form-group">
            <input type="text" class="form-control"
                   id="p_last_name" name="p_last_name" value=""/>
            <div id="error_msg_p_last_name"></div>
        </div>
    </div>
</div>

<div class="col-sm-12">
    <div class="col-sm-7" align="right">
        <div class="form-group">
            <label class="required">Address</label>
        </div>
    </div>
    <div class="col-sm-5">
        <div class="form-group">
            <input type="text" class="form-control" id="p_account_address1" name="p_account_address1"
                   placeholder="House Number Only (Unnecessary filled will occur error)" />
            <div id="error_msg_p_account_address1"></div>
        </div>
    </div><div class="col-sm-2"></div>
</div>


<div class="col-sm-12">
    <div class="col-sm-7" align="right">
        <div class="form-group">
            <label class="required">City</label>
        </div>
    </div>
    <div class="col-sm-5">
        <div class="form-group">
            <input type="text" class="form-control" id="p_account_city" name="p_account_city"/>
            <div id="error_msg_p_account_city"></div>
        </div>
    </div><div class="col-sm-2"></div>
</div>

<div class="col-sm-12">
    <div class="col-sm-7" align="right">
        <div class="form-group">
            <label class="required">State</label>
        </div>
    </div>
    <div class="col-sm-5">
        <div class="form-group">
            <select class="form-control" id="p_account_state" name="p_account_state"
                    data-jcf='{"wrapNative": false, "wrapNativeOnMobile": false}'>
                <option value="">Please Select</option>
                @if (isset($states))
                    @foreach ($states as $o)
                        <option value="{{ $o->code }}">{{ $o->name }}</option>
                    @endforeach
                @endif
            </select>
            <div id="error_msg_p_account_state"></div>
        </div>
    </div><div class="col-sm-2"></div>
</div>

<div class="col-sm-12">
    <div class="col-sm-7" align="right">
        <div class="form-group">
            <label class="required">Zip Code</label>
        </div>
    </div>
    <div class="col-sm-5">
        <div class="form-group">
            <input type="number" class="form-control" id="p_account_zip" name="p_account_zip"/>
            <div id="error_msg_p_account_zip"></div>
        </div>
    </div><div class="col-sm-2"></div>
</div>


<div class="divider2"></div>












