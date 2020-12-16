@extends('admin.layout.default')

@section('content')

    <style type="text/css">
        input[type=text]:disabled {
            background-color: #efefef;
        }

        select:disabled {
            background-color: #efefef;
        }
    </style>

    <script>

        function show_detail(id) {
            var mode = typeof id === 'undefined' ? 'new' : 'edit';

            if (mode === 'new') {
                $('#n_id').val('');
                $('#n_path').val('');
                $('.edit').hide();
            } else {
                $('.edit').show();
            }

            $('#div_detail').modal();
        }

        function load_detail(id) {
            myApp.showLoading();

            $.ajax({
                url: '/admin/settings/permission/path/load',
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

                        var o = res.detail;

                        $('#n_id').val(o.id);
                        $('#n_path').val(o.path);

                        show_detail(id);

                    } else {
                        myApp.showError(res.msg);
                    }
                }
            });
        }

        function search() {
            $('#frm_search').submit();
        }

        function save_detail() {
            var id = $('#n_id').val();
            var action = id === '' ? 'add' : 'update';

            myApp.showConfirm('Are you sure?', function() {
                myApp.showLoading();
                $.ajax({
                    url: '/admin/settings/permission/path/' + action,
                    data: {
                        _token: '{!! csrf_token() !!}',
                        id: id,
                        path: $('#n_path').val()
                    },
                    cache: false,
                    type: 'post',
                    dataType: 'json',
                    success: function(res) {
                        myApp.hideLoading();

                        if ($.trim(res.msg) === '') {

                            myApp.showSuccess('Your request has been processed successfully!', function() {
                                $('#div_detail').modal('hide');
                                search();
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

            });
        }

        function load_actions(path_id) {
            myApp.showLoading();

            $.ajax({
                url: '/admin/settings/permission/action/list',
                data: {
                    _token: '{!! csrf_token() !!}',
                    path_id: path_id
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {

                        $('#na_path').val(res.path.path);
                        $('#nad_path').val(res.path.path);
                        $('#na_path_id').val(res.path.id);

                        $('#tbody_action_list').empty();
                        $.each(res.actions, function(i, o) {
                            var html = '<tr>';

                            html += '<td>' + o.id + '</td>';
                            html += '<td>' + o.path + '</td>';
                            html += '<td><a href="#" onclick="load_action_detail(' + o.id + ')">' + o.action + '</a></td>';

                            html += '</tr>';
                            $('#tbody_action_list').append(html);
                        });

                        if (res.actions.length < 1) {
                            var html = '<tr>';

                            html += '<td colspan="20">No record found.</td>';

                            html += '</tr>';
                            $('#tbody_action_list').append(html);
                        }

                        $('#div_actions').modal();

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

        function load_action_detail(id) {
            myApp.showLoading();

            $.ajax({
                url: '/admin/settings/permission/action/load',
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
                        var o = res.detail;

                        $('#nad_id').val(o.id);
                        $('#nad_path').val(o.path);
                        $('#nad_action').val(o.action);

                        show_action_detail(id);

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

        function show_action_detail(id) {
            var mode = typeof id === 'undefined' ? 'new' : 'edit';
            if (mode === 'new') {
                $('.edit').hide();
                $('#nad_id').val('');
                $('#nad_action').val('');
            } else {
                $('.edit').show();
            }
            $('#div_action_detail').modal();
        }

        function save_action_detail() {
            var id = $('#nad_id').val();
            var action = id === '' ? 'add' : 'update';

            myApp.showConfirm('Are you sure to proceed?', function() {

                myApp.showLoading();

                $.ajax({
                    url: '/admin/settings/permission/action/' + action,
                    data: {
                        _token: '{!! csrf_token() !!}',
                        id: id,
                        path_id: $('#na_path_id').val(),
                        action: $('#nad_action').val()
                    },
                    cache: false,
                    type: 'post',
                    dataType: 'json',
                    success: function(res) {
                        myApp.hideLoading();
                        if ($.trim(res.msg) === '') {

                            myApp.showSuccess('Your request has been processed successfully!', function() {
                                $('#div_action_detail').modal('hide');

                                $('#nad_id').val('');
                                $('#nad_action').val('');

                                load_actions($('#na_path_id').val());
                            })

                        } else {
                            myApp.showError(res.msg);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        myApp.hideLoading();
                        myApp.showError(errorThrown);
                    }
                });
            });
        }

        function search_permission() {
            myApp.showLoading();

            $.ajax({
                url: '/admin/settings/permission/permission/list',
                data: {
                    _token: '{!! csrf_token() !!}',
                    site: $('#site').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {

                        /* Header */
                        $('#thead_role_permission').empty();
                        var header = '<tr>';
                        header += '<th>Path</th>';
                        header += '<th>Action</th>';
                        $.each(res.roles, function(i, o) {
                            header += '<th>' + o.name + '</th>';
                        });
                        header += '</tr>';

                        $('#thead_role_permission').append(header);

                        /* Body */
                        $('#tbody_role_permission').empty();

                        $.each(res.actions, function(i, a) {

                            var body = '<tr>';
                            body += '<td>' + a.path + '</td>';
                            body += '<td>' + a.action + '</td>';

                            $.each(res.roles, function(j, r) {
                                var has_permission = find_json_value(res.data, a.id, r.id);

                                body += '<td><input type="checkbox" onclick="update_permission(' + a.id + ', ' + r.id + ')" id="rp_' + a.id + '_' + r.id + '" ' + (has_permission === 'Y' ? 'checked' : '')  + '/></td>';
                            })

                            body += '</tr>';

                            $('#tbody_role_permission').append(body);
                        });

                        if (res.actions.length === 0) {
                            var body = '<tr><td colspan="20">No record found.</td></tr>';
                            $('#tbody_role_permission').append(body);
                        }

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

        function update_permission(action_id, role_id) {
            var key = "rp_" + action_id + '_' + role_id;
            var has_permission = $('#' + key).prop('checked') ? 'Y' : 'N';

            myApp.showLoading();
            $.ajax({
                url: '/admin/settings/permission/permission/update',
                data: {
                    _token: '{!! csrf_token() !!}',
                    action_id: action_id,
                    role_id: role_id,
                    has_permission: has_permission
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {

                        search_permission();

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

        function find_json_value(data, action_id, role_id) {
            var value = '';
            $.each (data, function(i, o) {
                if (o.action_id === action_id) {
                    value = o["role_" + role_id];
                    return false;
                }
            });

            return value;
        }

    </script>

    <h4>Permission Setup</h4>

    <div class="bs-example bs-example-tabs" data-example-id="togglable-tabs">
        <ul class="nav nav-tabs" id="myTabs" role="tablist">
            <li role="presentation" class="active"><a href="#path" id="path-tab" role="tab" data-toggle="tab"
                                                aria-controls="path" aria-expanded="false">Path</a></li>
            <li role="presentation"><a href="#profile" role="tab" id="profile-tab" data-toggle="tab"
                                                      aria-controls="profile" aria-expanded="true">Role Permission</a></li>

        </ul>
        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade active in" role="tabpanel" id="path" aria-labelledby="path-tab">

                <div class="well filter" style="padding-bottom:5px; margin-top:10px;">
                    <form id="frm_search" class="form-horizontal" method="post" onsubmit="myApp.showLoading();">
                        {{ csrf_field() }}
                        <input type="hidden" id="id" name="id"/>
                        <input type="hidden" name="excel" id="excel" value=""/>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="col-md-4 control-label">Path</label>
                                    <div class="col-md-8">
                                        <input type="text" class="form-control" name="path"/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 col-md-offset-4">
                                <div class="form-group">
                                    <div class="col-md-12 text-right">
                                        <button type="submit" class="btn btn-primary btn-sm" id="btn_search">Search</button>
                                        <button type="button" class="btn btn-info btn-sm" onclick="show_detail()">Add New</button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </form>
                </div>

                <table class="table table-bordered table-hover table-condensed filter">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Path</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @if (isset($data) && count($data) > 0)
                        @foreach ($data as $o)
                            <tr>
                                <td>{{ $o->id }}</td>
                                <td><a href="#" onclick="load_detail({{ $o->id }})">{{ $o->path }}</a></td>
                                <td>{{ $o->actions_qty }} <button type="button" class="btn btn-default btn-xs" onclick="load_actions({{ $o->id }})">View Actions</button></td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="20" class="text-center">No Record Found</td>
                        </tr>
                    @endif
                    </tbody>
                </table>

                <div class="text-left">
                    Total {{ $data->total() }} record(s).
                </div>
                <div class="text-right">
                    {{ $data->appends(Request::except('page'))->links() }}
                </div>

            </div>
            <div class="tab-pane fade" role="tabpanel" id="profile" aria-labelledby="profile-tab">

                <div class="well filter" style="padding-bottom:5px; margin-top:10px;">
                    <form id="frm_search" class="form-horizontal" method="post" onsubmit="myApp.showLoading();">
                        {{ csrf_field() }}
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="col-md-4 control-label">Site</label>
                                    <div class="col-md-8">
                                        <select id="site" class="form-control">
                                            <option value="admin/">Admin</option>
                                            <option value="sub-agent/">Sub-agent</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 col-md-offset-4">
                                <div class="form-group">
                                    <div class="col-md-12 text-right">
                                        <button type="button" class="btn btn-primary btn-sm" onclick="search_permission()">Search</button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </form>
                </div>

                <table class="table table-bordered table-hover table-condensed filter">
                    <thead id="thead_role_permission"></thead>
                    <tbody id="tbody_role_permission"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal" id="div_detail" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">Path Detail</h4>
                </div>
                <div class="modal-body">

                    <form id="frm_upload" action="/admin/settings/lyca-sims/upload" class="form-horizontal filter" method="post" style="padding:15px;" target="ifm_upload" enctype="multipart/form-data">
                        {{ csrf_field() }}
                        <div class="form-group edit">
                            <label class="col-sm-4 control-label required">ID</label>
                            <div class="col-sm-8">
                                <input type="text" id="n_id" class="form-control" readonly/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label required">Path</label>
                            <div class="col-sm-8">
                                <input type="text" id="n_path" class="form-control"/>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="margin-right:15px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="save_detail()">Submit</button>
                </div>
            </div>
        </div>
    </div>


    <div class="modal" id="div_actions" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">Action List</h4>
                </div>
                <div class="modal-body">

                    <div class="well filter" style="padding-bottom:5px; margin-top:10px;">
                        <form id="frm_upload" action="/admin/settings/lyca-sims/upload" class="form-horizontal filter" method="post" style="padding:15px;" target="ifm_upload" enctype="multipart/form-data">
                            {{ csrf_field() }}
                            <input type="hidden" id="na_path_id"/>
                            <div class="form-group edit">
                                <label class="col-sm-4 control-label">Path</label>
                                <div class="col-sm-8">
                                    <input type="text" id="na_path" class="form-control" readonly/>
                                </div>
                            </div>
                        </form>
                    </div>

                    <table class="table table-bordered table-hover table-condensed filter">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Path</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody id="tbody_action_list">

                        </tbody>
                    </table>
                </div>
                <div class="modal-footer" style="margin-right:15px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="show_action_detail()">Add New Action</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="div_action_detail" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">Action Detail</h4>
                </div>
                <div class="modal-body">

                    <form id="frm_upload" action="/admin/settings/lyca-sims/upload" class="form-horizontal filter" method="post" style="padding:15px;" target="ifm_upload" enctype="multipart/form-data">
                        {{ csrf_field() }}
                        <div class="form-group edit">
                            <label class="col-sm-4 control-label required">ID</label>
                            <div class="col-sm-8">
                                <input type="text" id="nad_id" class="form-control" readonly/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label required">Path</label>
                            <div class="col-sm-8">
                                <input type="text" id="nad_path" class="form-control" readonly/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label required">Action</label>
                            <div class="col-sm-8">
                                <input type="text" id="nad_action" class="form-control"/>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="margin-right:15px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="save_action_detail()">Submit</button>
                </div>
            </div>
        </div>
    </div>
@stop
