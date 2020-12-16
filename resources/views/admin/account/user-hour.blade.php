@extends('admin.layout.default')

@section('content')

    <style type="text/css">
        table.redTable {
            border: 2px solid #0863a4;
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
            background: #acd9ea;
        }
        table.redTable thead {
            background: #0863a4;
        }
        table.redTable thead th {
            font-size: 19px;
            font-weight: bold;
            color: #FFFFFF;
            text-align: center;
            border-left: 2px solid #0863a4;
        }
        table.redTable thead th:first-child {
            border-left: none;
        }

        table.redTable tfoot {
            font-size: 13px;
            font-weight: bold;
            color: #FFFFFF;
            background: #0863a4;
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
            color: #0863a4;
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

        function update_hour(day, user) {

            if($('#'+day+'_stime').val().length < 1){
                alert("Please input Open Time");
                return;
            }

            if($('#'+day+'_etime').val().length < 1){
                alert("Please input Close Time");
                return;
            }

            myApp.showLoading();
            
            $.ajax({
                url: '/admin/account/user-hour/update',
                data: {
                    _token: '{!! csrf_token() !!}',
                    day: day.substr(day.length - 3),
                    user: user,
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
                            location.href = "/admin/account/user-hour";
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

        function remove_hour(day, user) {

            myApp.showLoading();

            $.ajax({
                url: '/admin/account/user-hour/remove',
                data: {
                    _token: '{!! csrf_token() !!}',
                    day: day.substr(day.length - 3),
                    user: user,
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
                            location.href = "/admin/account/user-hour";
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

        function remove_ip(id) {
            myApp.showLoading();
            $.ajax({
                url: '/admin/account/user-hour/remove-ip',
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
                            location.href = "/admin/account/user-hour";
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

            if($('#add_user').val() == ''){
                alert("please select User");
                return
            }

            if($('#add_ip').val().length < 1){
                alert("please input IP Address");
                return
            }

            if($('#add_comment').val().length < 1){
                alert("please input Comment");
                return
            }

            myApp.showLoading();
            $.ajax({
                url: '/admin/account/user-hour/add-ip',
                data: {
                    _token: '{!! csrf_token() !!}',
                    user_id: $('#add_user').val(),
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
                            location.href = "/admin/account/user-hour";
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
                        <h4>User Access Hours</h4>
                        <ol class="breadcrumb">
                            <li><a href="#">Home</a></li>
                            <li><a href="#">Setting</a></li>
                            <li class="active">User Hours</li>
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
                <h5><p style="text-align:center">Account ID : {{$account_id}}</p></h5>
                <h5><p style="text-align:center">If you don't set up access by users, they will be affected by store hours</p></h5>
                <div class="col-md-12 col-sm-12">
                <hr>
                    @foreach($users as $u)

                        <h4><p style="text-align:center"> User Hours : {{ $u->u_id }} [{{ $u->role == 'M' ? 'Manager' : 'Staff' }}]</p></h4>
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
                                               class="form-control stime" id="{{$u->u_id}}_mon_stime" name="{{$u->u_id}}_mon_stime" value="{{$u->mon_stime}}"/>
                                    </td>
                                    <td>
                                        <input type="text" disabled style="width:150px; display:inline-block"
                                               class="form-control stime" id="{{$u->u_id}}_mon_etime" name="{{$u->u_id}}_mon_etime" value="{{$u->mon_etime}}"/>
                                    </td>
                                    <td>
                                        <button type="button" id="btn_{{$u->u_id}}_mon_update" style="display:none" class="btn btn-primary btn-xs" onclick="update_hour('{{$u->u_id}}_mon', '{{$u->u_id}}')">Update</button>
                                        <button type="button" id="btn_{{$u->u_id}}_mon_edit" class="btn btn-primary btn-xs" onclick="edit_hour('{{$u->u_id}}_mon')">Edit</button>
                                        <button type="button" id="btn_{{$u->u_id}}_mon_remove"  class="btn btn-primary btn-xs" onclick="remove_hour('{{$u->u_id}}_mon', '{{$u->u_id}}')">Remove</button>
                                        <button type="button" id="btn_{{$u->u_id}}_mon_cancel" style="display:none" class="btn btn-primary btn-xs" onclick="cancel_hour('{{$u->u_id}}_mon')">Cancel</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Tuesday</td>
                                    <td>
                                        <input type="text" disabled style="width:150px; display:inline-block"
                                               class="form-control stime" id="{{$u->u_id}}_tue_stime" name="{{$u->u_id}}_tue_stime" value="{{$u->tue_stime}}"/>
                                    </td>
                                    <td>
                                        <input type="text" disabled style="width:150px; display:inline-block"
                                               class="form-control stime" id="{{$u->u_id}}_tue_etime" name="{{$u->u_id}}_tue_etime" value="{{$u->tue_etime}}"/>
                                    </td>
                                    <td>
                                        <button type="button" id="btn_{{$u->u_id}}_tue_update" style="display:none" class="btn btn-primary btn-xs" onclick="update_hour('{{$u->u_id}}_tue', '{{$u->u_id}}')">Update</button>
                                        <button type="button" id="btn_{{$u->u_id}}_tue_edit" class="btn btn-primary btn-xs" onclick="edit_hour('{{$u->u_id}}_tue')">Edit</button>
                                        <button type="button" id="btn_{{$u->u_id}}_tue_remove" class="btn btn-primary btn-xs" onclick="remove_hour('{{$u->u_id}}_tue', '{{$u->u_id}}')">Remove</button>
                                        <button type="button" id="btn_{{$u->u_id}}_tue_cancel" style="display:none" class="btn btn-primary btn-xs" onclick="cancel_hour('{{$u->u_id}}_tue')">Cancel</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Wednesday</td>
                                    <td>
                                        <input type="text" disabled style="width:150px; display:inline-block"
                                               class="form-control stime" id="{{$u->u_id}}_wed_stime" name="{{$u->u_id}}_wed_stime" value="{{$u->wed_stime}}"/>
                                    </td>
                                    <td>
                                        <input type="text" disabled style="width:150px; display:inline-block"
                                               class="form-control stime" id="{{$u->u_id}}_wed_etime" name="{{$u->u_id}}_wed_etime" value="{{$u->wed_etime}}"/>
                                    </td>
                                    <td>
                                        <button type="button" id="btn_{{$u->u_id}}_wed_update" style="display:none" class="btn btn-primary btn-xs" onclick="update_hour('{{$u->u_id}}_wed', '{{$u->u_id}}')">Update</button>
                                        <button type="button" id="btn_{{$u->u_id}}_wed_edit" class="btn btn-primary btn-xs" onclick="edit_hour('{{$u->u_id}}_wed')">Edit</button>
                                        <button type="button" id="btn_{{$u->u_id}}_wed_remove" class="btn btn-primary btn-xs" onclick="remove_hour('{{$u->u_id}}_wed', '{{$u->u_id}}')">Remove</button>
                                        <button type="button" id="btn_{{$u->u_id}}_wed_cancel" style="display:none" class="btn btn-primary btn-xs" onclick="cancel_hour('{{$u->u_id}}_wed')">Cancel</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Thursday</td>
                                    <td>
                                        <input type="text" disabled style="width:150px; display:inline-block"
                                               class="form-control stime" id="{{$u->u_id}}_thu_stime" name="{{$u->u_id}}_thu_stime" value="{{$u->thu_stime}}"/>
                                    </td>
                                    <td>
                                        <input type="text" disabled style="width:150px; display:inline-block"
                                               class="form-control stime" id="{{$u->u_id}}_thu_etime" name="{{$u->u_id}}_thu_etime" value="{{$u->thu_etime}}"/>
                                    </td>
                                    <td>
                                        <button type="button" id="btn_{{$u->u_id}}_thu_update" style="display:none" class="btn btn-primary btn-xs" onclick="update_hour('{{$u->u_id}}_thu', '{{$u->u_id}}')">Update</button>
                                        <button type="button" id="btn_{{$u->u_id}}_thu_edit" class="btn btn-primary btn-xs" onclick="edit_hour('{{$u->u_id}}_thu')">Edit</button>
                                        <button type="button" id="btn_{{$u->u_id}}_thu_remove" class="btn btn-primary btn-xs" onclick="remove_hour('{{$u->u_id}}_thu', '{{$u->u_id}}')">Remove</button>
                                        <button type="button" id="btn_{{$u->u_id}}_thu_cancel" style="display:none" class="btn btn-primary btn-xs" onclick="cancel_hour('{{$u->u_id}}_thu')">Cancel</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Friday</td>
                                    <td>
                                        <input type="text" disabled style="width:150px; display:inline-block"
                                               class="form-control stime" id="{{$u->u_id}}_fri_stime" name="{{$u->u_id}}_fri_stime" value="{{$u->fri_stime}}"/>
                                    </td>
                                    <td>
                                        <input type="text" disabled style="width:150px; display:inline-block"
                                               class="form-control stime" id="{{$u->u_id}}_fri_etime" name="{{$u->u_id}}_fri_etime" value="{{$u->fri_etime}}"/>
                                    </td>
                                    <td>
                                        <button type="button" id="btn_{{$u->u_id}}_fri_update" style="display:none" class="btn btn-primary btn-xs" onclick="update_hour('{{$u->u_id}}_fri', '{{$u->u_id}}')">Update</button>
                                        <button type="button" id="btn_{{$u->u_id}}_fri_edit" class="btn btn-primary btn-xs" onclick="edit_hour('{{$u->u_id}}_fri')">Edit</button>
                                        <button type="button" id="btn_{{$u->u_id}}_fri_remove" class="btn btn-primary btn-xs" onclick="remove_hour('{{$u->u_id}}_fri', '{{$u->u_id}}')">Remove</button>
                                        <button type="button" id="btn_{{$u->u_id}}_fri_cancel" style="display:none" class="btn btn-primary btn-xs" onclick="cancel_hour('{{$u->u_id}}_fri')">Cancel</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Saturday</td>
                                    <td>
                                        <input type="text" disabled style="width:150px; display:inline-block"
                                               class="form-control stime" id="{{$u->u_id}}_sat_stime" name="{{$u->u_id}}_sat_stime" value="{{$u->sat_stime}}"/>
                                    </td>
                                    <td>
                                        <input type="text" disabled style="width:150px; display:inline-block"
                                               class="form-control stime" id="{{$u->u_id}}_sat_etime" name="{{$u->u_id}}_sat_etime" value="{{$u->sat_etime}}"/>
                                    </td>
                                    <td>
                                        <button type="button" id="btn_{{$u->u_id}}_sat_update" style="display:none" class="btn btn-primary btn-xs" onclick="update_hour('{{$u->u_id}}_sat', '{{$u->u_id}}')">Update</button>
                                        <button type="button" id="btn_{{$u->u_id}}_sat_edit" class="btn btn-primary btn-xs" onclick="edit_hour('{{$u->u_id}}_sat')">Edit</button>
                                        <button type="button" id="btn_{{$u->u_id}}_sat_remove" class="btn btn-primary btn-xs" onclick="remove_hour('{{$u->u_id}}_sat', '{{$u->u_id}}')">Remove</button>
                                        <button type="button" id="btn_{{$u->u_id}}_sat_cancel" style="display:none" class="btn btn-primary btn-xs" onclick="cancel_hour('{{$u->u_id}}_sat')">Cancel</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Sunday</td>
                                    <td>
                                        <input type="text" disabled style="width:150px; display:inline-block"
                                               class="form-control stime" id="{{$u->u_id}}_sun_stime" name="{{$u->u_id}}_sun_stime" value="{{$u->sun_stime}}"/>
                                    </td>
                                    <td>
                                        <input type="text" disabled style="width:150px; display:inline-block"
                                               class="form-control stime" id="{{$u->u_id}}_sun_etime" name="{{$u->u_id}}_sun_etime" value="{{$u->sun_etime}}"/>
                                    </td>
                                    <td>
                                        <button type="button" id="btn_{{$u->u_id}}_sun_update" style="display:none" class="btn btn-primary btn-xs" onclick="update_hour('{{$u->u_id}}_sun', '{{$u->u_id}}')">Update</button>
                                        <button type="button" id="btn_{{$u->u_id}}_sun_edit" class="btn btn-primary btn-xs" onclick="edit_hour('{{$u->u_id}}_sun')">Edit</button>
                                        <button type="button" id="btn_{{$u->u_id}}_sun_remove" class="btn btn-primary btn-xs" onclick="remove_hour('{{$u->u_id}}_sun', '{{$u->u_id}}')">Remove</button>
                                        <button type="button" id="btn_{{$u->u_id}}_sun_cancel" style="display:none" class="btn btn-primary btn-xs" onclick="cancel_hour('{{$u->u_id}}_sun')">Cancel</button>
                                    </td>
                                </tr>
                            </tboby>
                        </table>

                        <hr>
                    @endforeach

                    <hr>

                    <h4><p style="text-align:center">Static IP Address</p></h4>

                    <table class="redTable" align="center">
                        <thead>
                        <tr>
                            <th>User ID</th>
                            <th>IP Address</th>
                            <th>Comment</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tboby>

                            @foreach($ips as $ip)
                                <tr>
                                    <td>
                                        {{ $ip->user_id }}
                                    </td>
                                    <td>
                                        <input type="text" disabled style="width:150px; display:inline-block"
                                               class="form-control" id="ip_{{$ip->id}}" name="ip_{{$ip->id}}" value="{{$ip->ip}}"/>
                                    </td>
                                    <td>
                                        <input type="text" disabled style="width:150px; display:inline-block"
                                               class="form-control" id="comment_{{$ip->id}}" name="comment_{{$ip->id}}" value="{{$ip->comment}}"/>
                                    </td>
                                    <td>
                                        <button type="button" id="btn_ip_remove_{{$ip->id}}"  class="btn btn-primary btn-xs" onclick="remove_ip('{{$ip->id}}')">Remove</button>
                                    </td>
                                </tr>
                            @endforeach

                            <tr>
                                <td>
                                    <select id="add_user" name="add_user" class="form-control">
                                        <option value="">All</option>
                                        @foreach ($users as $u)
                                            <option value="{{ $u->u_id }}">{{ $u->u_id }}</option>
                                        @endforeach
                                    </select>
                                </td>
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

                </div>

            </div>

        </div>
    </div>
    <!-- End contain wrapp -->
@stop
