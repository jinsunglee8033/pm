@extends('sub-agent.layout.default')

@section('content')

    <style type="text/css">
        table.redTable {
            border: 2px solid #A40808;
            background-color: #FFFFFF;
            width: 800px;
            text-align: center;
            border-collapse: collapse;
        }
        table.redTable td, table.redTable th {
            border: 1px solid #AAAAAA;
            padding: 3px 2px;
        }
        table.redTable tbody td {
            font-size: 15px;
        }
        table.redTable tr:nth-child(even) {
            background: #F5C8BF;
        }
        table.redTable thead {
            background: #A40808;
        }
        table.redTable thead th {
            font-size: 19px;
            font-weight: bold;
            color: #FFFFFF;
            text-align: center;
            border-left: 2px solid #A40808;
        }
        table.redTable thead th:first-child {
            border-left: none;
        }

        table.redTable tfoot {
            font-size: 13px;
            font-weight: bold;
            color: #FFFFFF;
            background: #A40808;
        }
        table.redTable tfoot td {
            font-size: 13px;
        }
        table.redTable tfoot .links {
            text-align: right;
        }
        table.redTable tfoot .links a{
            display: inline-block;
            background: #FFFFFF;
            color: #A40808;
            padding: 2px 8px;
            border-radius: 5px;
        }
    </style>

    <script type="text/javascript">
        window.onload = function () {
            $(".stime").datetimepicker({
                format: 'hh:mm a'
            });

            $("#etime").datetimepicker({
                format: 'HH:mm:00'
            });

        };

        function update_hour(day) {

            if($('#'+day+'_stime').val().length < 1){
                alert("Please input Open Time");
                return;
            }

            if($('#'+day+'_etime').val().length < 1){
                alert("Please input Close Time");
                return;
            }

            if($('#time_zone').val() =='s'){
                alert("Please select Time Zone first!");
                return;
            }

            myApp.showLoading();
            $.ajax({
                url: '/sub-agent/setting/store/update',
                data: {
                    _token: '{!! csrf_token() !!}',
                    day: day,
                    stime: $('#'+day+'_stime').val(),
                    etime: $('#'+day+'_etime').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();

                    if ($.trim(res.msg) === '') {

                        myApp.showSuccess('Your request has been processed successfully!', function() {
                            location.href = "/sub-agent/setting/store";
                        });

                    } else {
                        myApp.showError(res.msg);
                    }

                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }

            });
        }

        function remove_hour(day) {
            myApp.showLoading();
            $.ajax({
                url: '/sub-agent/setting/store/remove',
                data: {
                    _token: '{!! csrf_token() !!}',
                    day: day,
                    stime: $('#'+day+'_stime').val(),
                    etime: $('#'+day+'_etime').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();

                    if ($.trim(res.msg) === '') {

                        myApp.showSuccess('Your request has been processed successfully!', function() {
                            location.href = "/sub-agent/setting/store";
                        });

                    } else {
                        myApp.showError(res.msg);
                    }

                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }

            });
        }

        var old_stime = '';
        var old_etime = '';

        function edit_hour(day) {

            $('#'+day+'_stime').attr('disabled', false);
            $('#'+day+'_etime').attr('disabled', false);

            old_stime = $('#'+day+'_stime').val();
            old_etime = $('#'+day+'_etime').val();

            $('#btn_'+day+'_edit').hide();
            $('#btn_'+day+'_remove').hide();
            $('#btn_'+day+'_update').show();
            $('#btn_'+day+'_cancel').show();
        }
        function cancel_hour(day) {
            $('#'+day+'_stime').attr('disabled', true);
            $('#'+day+'_etime').attr('disabled', true);

            $('#'+day+'_stime').val(old_stime);
            $('#'+day+'_etime').val(old_etime);

            $('#btn_'+day+'_edit').show();
            $('#btn_'+day+'_remove').show();
            $('#btn_'+day+'_update').hide();
            $('#btn_'+day+'_cancel').hide();
        }

        var old_ip = '';
        var old_comment = '';

        function edit_ip(id) {

            $('#ip_'+id).attr('disabled', false);
            $('#comment_'+id).attr('disabled', false);

            old_ip = $('#ip_'+id).val();
            old_comment = $('#comment_'+id).val();

            $('#btn_ip_edit_'+id).hide();
            $('#btn_ip_remove_'+id).hide();
            $('#btn_ip_update_'+id).show();
            $('#btn_ip_cancel_'+id).show();
        }

        function cancel_ip(id) {
            $('#ip_'+id).attr('disabled', true);
            $('#comment_'+id).attr('disabled', true);

            $('#ip_'+id).val(old_ip);
            $('#comment_'+id).val(old_comment);

            $('#btn_ip_edit_'+id).show();
            $('#btn_ip_remove_'+id).show();
            $('#btn_ip_update_'+id).hide();
            $('#btn_ip_cancel_'+id).hide();
        }

        function remove_ip(id) {
            myApp.showLoading();
            $.ajax({
                url: '/sub-agent/setting/store/remove-ip',
                data: {
                    _token: '{!! csrf_token() !!}',
                    id: id
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();

                    if ($.trim(res.msg) === '') {

                        myApp.showSuccess('Your request has been processed successfully!', function() {
                            location.href = "/sub-agent/setting/store";
                        });

                    } else {
                        myApp.showError(res.msg);
                    }

                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }

            });
        }

        function add_ip() {

            if($('#add_ip').val().length < 1){
                alert("please input IP Address");
                return
            }

            if($('#add_comment').val().length < 1){
                alert("please input IP Address");
                return
            }

            myApp.showLoading();
            $.ajax({
                url: '/sub-agent/setting/store/add-ip',
                data: {
                    _token: '{!! csrf_token() !!}',
                    ip: $('#add_ip').val(),
                    comment: $('#add_comment').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();

                    if ($.trim(res.msg) === '') {

                        myApp.showSuccess('Your request has been processed successfully!', function() {
                            location.href = "/sub-agent/setting/store";
                        });

                    } else {
                        myApp.showError(res.msg);
                    }

                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }

            });
        }

        function update_ip(id) {

            if($('#ip_'+id).val().length < 1){
                alert("please input IP Address");
                return
            }

            if($('#comment_'+id).val().length < 1){
                alert("please input Comment");
                return
            }

            myApp.showLoading();
            $.ajax({
                url: '/sub-agent/setting/store/update-ip',
                data: {
                    _token: '{!! csrf_token() !!}',
                    id: id,
                    ip: $('#ip_'+id).val(),
                    comment: $('#comment_'+id).val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();

                    if ($.trim(res.msg) === '') {

                        myApp.showSuccess('Your request has been processed successfully!', function() {
                            location.href = "/sub-agent/setting/store";
                        });

                    } else {
                        myApp.showError(res.msg);
                    }

                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }

            });
        }

        var old_time_zone = '';

        function edit_tz() {

            $('#time_zone').attr('disabled', false);

            old_time_zone = $('#time_zone').val();

            $('#btn_tz_edit').hide();
            $('#btn_tz_update').show();
            $('#btn_tz_cancel').show();
        }

        function cancel_tz() {

            $('#time_zone').attr('disabled', true);

            $('#time_zone').val(old_time_zone);

            $('#btn_tz_edit').show();
            $('#btn_tz_update').hide();
            $('#btn_tz_cancel').hide();
        }

        function update_tz() {

            // if($('#comment_'+id).val().length < 1){
            //     alert("please input IP Address");
            //     return
            // }

            myApp.showLoading();
            $.ajax({
                url: '/sub-agent/setting/store/update-tz',
                data: {
                    _token: '{!! csrf_token() !!}',
                    time_zone: $('#time_zone').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();

                    if ($.trim(res.msg) === '') {

                        myApp.showSuccess('Your request has been processed successfully!', function() {
                            location.href = "/sub-agent/setting/store";
                        });

                    } else {
                        myApp.showError(res.msg);
                    }

                },
                error: function(jqXHR, textStatus, errorThrown) {
                    myApp.hideLoading();
                    myApp.showError(errorThrown);
                }

            });
        }

    </script>


    <!-- Start parallax -->
    <div class="parallax" data-background="/img/parallax/innerpage.jpg" data-speed="0.5" data-size="50%">
        <div class="overlay white"></div>
        <div class="container">
            <div class="inner-head">
                <div class="row">
                    <div class="col-md-12 text-center">
                        <h4>Store Hours</h4>
                        <ol class="breadcrumb">
                            <li><a href="#">Home</a></li>
                            <li><a href="#">Setting</a></li>
                            <li class="active">Store Hours</li>
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
                    <h5><p style="text-align:center">Account ID : {{$account}}</p></h5>
                    <h4><p style="text-align:center">Time Zone</p></h4>
                    <table class="redTable" align="center">
                        <thead>
                        <tr>
                            <th>Current</th>
                            <th>Time Zone</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tboby>
                            <tr>
                                @if($tz_name == null)
                                    <td style="color: red; font-size: large">Please Select Time Zone First!</td>
                                @else
                                    <td style="color: blue; font-size: large">{{ $tz_name }}</td>
                                @endif
                                <td>
                                    <select class="form-control" id="time_zone" name="time_zone" value="" disabled>
                                        <option value="s" {{ old('time_zone', $time_zone) == 's' ? 'selected' : '' }}>Select</option>
                                        <option value="0" {{ old('time_zone', $time_zone) == '0' ? 'selected' : '' }}>Eastern Time - 0</option>
                                        <option value="1" {{ old('time_zone', $time_zone) == '1' ? 'selected' : '' }}>Central Time - 1</option>
                                        <option value="2" {{ old('time_zone', $time_zone) == '2' ? 'selected' : '' }}>Mountain Time - 2</option>
                                        <option value="3" {{ old('time_zone', $time_zone) == '3' ? 'selected' : '' }}>Pacific Time - 3</option>
                                        <option value="4" {{ old('time_zone', $time_zone) == '4' ? 'selected' : '' }}>Alaska Time - 4</option>
                                        <option value="6" {{ old('time_zone', $time_zone) == '6' ? 'selected' : '' }}>Hawaii Time - 6</option>
                                    </select>
                                </td>
                                <td>
                                    <button type="button" id="btn_tz_update" style="display:none" class="btn btn-primary btn-xs" onclick="update_tz()">Update</button>
                                    <button type="button" id="btn_tz_edit" class="btn btn-primary btn-xs" onclick="edit_tz()">Edit</button>
                                    <button type="button" id="btn_tz_cancel" style="display:none" class="btn btn-primary btn-xs" onclick="cancel_tz()">Cancel</button>
                                </td>
                            </tr>
                        </tboby>
                    </table>

                    <hr>

                    <h4><p style="text-align:center">Store Hours</p></h4>
                    <table class="redTable" align="center">
                        <thead>
                        <tr>
                            <th>Day</th>
                            <th>Open</th>
                            <th>Close</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tboby>
                            <tr>
                                <td>Monday</td>
                                <td>
                                    <input type="text" disabled style="width:150px; display:inline-block"
                                           class="form-control stime" id="mon_stime" name="mon_stime" value="{{ empty($mon) ? '' : $mon->stime}}"/>
                                </td>
                                <td>
                                    <input type="text" disabled style="width:150px; display:inline-block"
                                           class="form-control stime" id="mon_etime" name="mon_etime" value="{{ empty($mon) ? '' : $mon->etime}}"/>
                                </td>
                                <td>
                                    <button type="button" id="btn_mon_update" style="display:none" class="btn btn-primary btn-xs" onclick="update_hour('mon')">Update</button>
                                    <button type="button" id="btn_mon_edit" class="btn btn-primary btn-xs" onclick="edit_hour('mon')">Edit</button>
                                    <button type="button" id="btn_mon_remove"  class="btn btn-primary btn-xs" onclick="remove_hour('mon')">Remove</button>
                                    <button type="button" id="btn_mon_cancel" style="display:none" class="btn btn-primary btn-xs" onclick="cancel_hour('mon')">Cancel</button>
                                </td>
                            </tr>
                            <tr>
                                <td>Tuesday</td>
                                <td>
                                    <input type="text" disabled style="width:150px; display:inline-block"
                                           class="form-control stime" id="tue_stime" name="tue_stime" value="{{ empty($tue) ? '' : $tue->stime}}"/>
                                </td>
                                <td>
                                    <input type="text" disabled style="width:150px; display:inline-block"
                                           class="form-control stime" id="tue_etime" name="tue_etime" value="{{ empty($tue) ? '' : $tue->etime}}"/>
                                </td>
                                <td>
                                    <button type="button" id="btn_tue_update" style="display:none" class="btn btn-primary btn-xs" onclick="update_hour('tue')">Update</button>
                                    <button type="button" id="btn_tue_edit" class="btn btn-primary btn-xs" onclick="edit_hour('tue')">Edit</button>
                                    <button type="button" id="btn_tue_remove" class="btn btn-primary btn-xs" onclick="remove_hour('tue')">Remove</button>
                                    <button type="button" id="btn_tue_cancel" style="display:none" class="btn btn-primary btn-xs" onclick="cancel_hour('tue')">Cancel</button>
                                </td>
                            </tr>
                            <tr>
                                <td>Wednesday</td>
                                <td>
                                    <input type="text" disabled style="width:150px; display:inline-block"
                                           class="form-control stime" id="wed_stime" name="wed_stime" value="{{ empty($wed) ? '' : $wed->stime}}"/>
                                </td>
                                <td>
                                    <input type="text" disabled style="width:150px; display:inline-block"
                                           class="form-control stime" id="wed_etime" name="wed_etime" value="{{ empty($wed) ? '' : $wed->etime}}"/>
                                </td>
                                <td>
                                    <button type="button" id="btn_wed_update" style="display:none" class="btn btn-primary btn-xs" onclick="update_hour('wed')">Update</button>
                                    <button type="button" id="btn_wed_edit" class="btn btn-primary btn-xs" onclick="edit_hour('wed')">Edit</button>
                                    <button type="button" id="btn_wed_remove" class="btn btn-primary btn-xs" onclick="remove_hour('wed')">Remove</button>
                                    <button type="button" id="btn_wed_cancel" style="display:none" class="btn btn-primary btn-xs" onclick="cancel_hour('wed')">Cancel</button>
                                </td>
                            </tr>
                            <tr>
                                <td>Thursday</td>
                                <td>
                                    <input type="text" disabled style="width:150px; display:inline-block"
                                           class="form-control stime" id="thu_stime" name="thu_stime" value="{{ empty($thu) ? '' : $thu->stime}}"/>
                                </td>
                                <td>
                                    <input type="text" disabled style="width:150px; display:inline-block"
                                           class="form-control stime" id="thu_etime" name="thu_etime" value="{{ empty($thu) ? '' : $thu->etime}}"/>
                                </td>
                                <td>
                                    <button type="button" id="btn_thu_update" style="display:none" class="btn btn-primary btn-xs" onclick="update_hour('thu')">Update</button>
                                    <button type="button" id="btn_thu_edit" class="btn btn-primary btn-xs" onclick="edit_hour('thu')">Edit</button>
                                    <button type="button" id="btn_thu_remove" class="btn btn-primary btn-xs" onclick="remove_hour('thu')">Remove</button>
                                    <button type="button" id="btn_thu_cancel" style="display:none" class="btn btn-primary btn-xs" onclick="cancel_hour('thu')">Cancel</button>
                                </td>
                            </tr>
                            <tr>
                                <td>Friday</td>
                                <td>
                                    <input type="text" disabled style="width:150px; display:inline-block"
                                           class="form-control stime" id="fri_stime" name="fri_stime" value="{{ empty($fri) ? '' : $fri->stime}}"/>
                                </td>
                                <td>
                                    <input type="text" disabled style="width:150px; display:inline-block"
                                           class="form-control stime" id="fri_etime" name="fri_etime" value="{{ empty($fri) ? '' : $fri->etime}}"/>
                                </td>
                                <td>
                                    <button type="button" id="btn_fri_update" style="display:none" class="btn btn-primary btn-xs" onclick="update_hour('fri')">Update</button>
                                    <button type="button" id="btn_fri_edit" class="btn btn-primary btn-xs" onclick="edit_hour('fri')">Edit</button>
                                    <button type="button" id="btn_fri_remove" class="btn btn-primary btn-xs" onclick="remove_hour('fri')">Remove</button>
                                    <button type="button" id="btn_fri_cancel" style="display:none" class="btn btn-primary btn-xs" onclick="cancel_hour('fri')">Cancel</button>
                                </td>
                            </tr>
                            <tr>
                                <td>Saturday</td>
                                <td>
                                    <input type="text" disabled style="width:150px; display:inline-block"
                                           class="form-control stime" id="sat_stime" name="sat_stime" value="{{ empty($sat) ? '' : $sat->stime}}"/>
                                </td>
                                <td>
                                    <input type="text" disabled style="width:150px; display:inline-block"
                                           class="form-control stime" id="sat_etime" name="sat_etime" value="{{ empty($sat) ? '' : $sat->etime}}"/>
                                </td>
                                <td>
                                    <button type="button" id="btn_sat_update" style="display:none" class="btn btn-primary btn-xs" onclick="update_hour('sat')">Update</button>
                                    <button type="button" id="btn_sat_edit" class="btn btn-primary btn-xs" onclick="edit_hour('sat')">Edit</button>
                                    <button type="button" id="btn_sat_remove" class="btn btn-primary btn-xs" onclick="remove_hour('sat')">Remove</button>
                                    <button type="button" id="btn_sat_cancel" style="display:none" class="btn btn-primary btn-xs" onclick="cancel_hour('sat')">Cancel</button>
                                </td>
                            </tr>
                            <tr>
                                <td>Sunday</td>
                                <td>
                                    <input type="text" disabled style="width:150px; display:inline-block"
                                           class="form-control stime" id="sun_stime" name="sun_stime" value="{{ empty($sun) ? '' : $sun->stime}}"/>
                                </td>
                                <td>
                                    <input type="text" disabled style="width:150px; display:inline-block"
                                           class="form-control stime" id="sun_etime" name="sun_etime" value="{{ empty($sun) ? '' : $sun->etime}}"/>
                                </td>
                                <td>
                                    <button type="button" id="btn_sun_update" style="display:none" class="btn btn-primary btn-xs" onclick="update_hour('sun')">Update</button>
                                    <button type="button" id="btn_sun_edit" class="btn btn-primary btn-xs" onclick="edit_hour('sun')">Edit</button>
                                    <button type="button" id="btn_sun_remove" class="btn btn-primary btn-xs" onclick="remove_hour('sun')">Remove</button>
                                    <button type="button" id="btn_sun_cancel" style="display:none" class="btn btn-primary btn-xs" onclick="cancel_hour('sun')">Cancel</button>
                                </td>
                            </tr>
                        </tboby>
                    </table>

                    <hr>

                    <h4><p style="text-align:center">Static IP Address</p></h4>

                    <table class="redTable" align="center">
                        <thead>
                        <tr>
                            <th>IP Address</th>
                            <th>Comment</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tboby>
                            @foreach($ips as $ip)
                                <tr>
                                    <td>
                                        <input type="text" disabled style="width:150px; display:inline-block"
                                               class="form-control" id="ip_{{$ip->id}}" name="ip_{{$ip->id}}" value="{{$ip->ip}}"/>
                                    </td>
                                    <td>
                                        <input type="text" disabled style="width:150px; display:inline-block"
                                               class="form-control" id="comment_{{$ip->id}}" name="comment_{{$ip->id}}" value="{{$ip->comment}}"/>
                                    </td>
                                    <td>
                                        <button type="button" id="btn_ip_update_{{$ip->id}}" style="display:none" class="btn btn-primary btn-xs" onclick="update_ip('{{$ip->id}}')">Update</button>
                                        <button type="button" id="btn_ip_edit_{{$ip->id}}" class="btn btn-primary btn-xs" onclick="edit_ip('{{$ip->id}}')">Edit</button>
                                        <button type="button" id="btn_ip_remove_{{$ip->id}}"  class="btn btn-primary btn-xs" onclick="remove_ip('{{$ip->id}}')">Remove</button>
                                        <button type="button" id="btn_ip_cancel_{{$ip->id}}" style="display:none" class="btn btn-primary btn-xs" onclick="cancel_ip('{{$ip->id}}')">Cancel</button>
                                    </td>
                                </tr>
                            @endforeach

                            <tr>
                                <td>
                                    <input type="text" style="width:150px; display:inline-block"
                                           class="form-control" id="add_ip" name="add_ip" value=""/>
                                </td>
                                <td>
                                    <input type="text" style="width:150px; display:inline-block"
                                           class="form-control" id="add_comment" name="add_comment" value=""/>
                                </td>
                                <td>
                                    <button type="button" id="btn_tue_edit" class="btn btn-primary btn-xs" onclick="add_ip()">ADD</button>
                                </td>
                            </tr>

                        </tboby>
                    </table>

                    <div class="divider2"></div>
                    <hr><br>
                    <h4><p style="text-align:center"><a href="/sub-agent/setting/user-hour">If you want to control access by users Please click here</a></p></h4>
                </div>

            </div>

        </div>
    </div>
    <!-- End contain wrapp -->
@stop
