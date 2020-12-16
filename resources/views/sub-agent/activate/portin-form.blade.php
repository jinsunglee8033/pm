<div class="divider2"></div>

@if (!empty($phone_type))
<div class="col-sm-12" style="margin-top: 16px;">
    <div class="col-sm-4" align="right">
        <div class="form-group">
            <label class="required">Equipment Type</label>
        </div>
    </div>

    <div class="col-sm-5">
        <div class="form-group">
            <select class="form-control" id="equipment_type" name="equipment_type">
                <option value="">Select Equipment Type</option>
                <option value="3G">3G</option>
                <option value="4GGSM">4G GSM</option>
                <option value="4GLTE">4G LTE</option>
            </select>
            <div id="error_msg_equipment_type"></div>
        </div>
    </div>
</div>
@endif

<div class="col-sm-12">
    <div class="col-sm-4" align="right">
        <div class="form-group">
            <label class="required">Port-In Number</label>
        </div>
    </div>

    <div class="col-sm-5">
        <div class="form-group">
            <input type="text" class="form-control" 
            id="number_to_port" name="number_to_port" value="" maxlength="10" placeholder="10 digits and digits only"/>
            <div id="error_msg_number_to_port"></div>
        </div>
    </div>
</div>
                                    
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
        <input type="text" class="form-control" id="call_back_phone" name="call_back_phone" maxlength="10" placeholder="10 digits and digits only"/>
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












