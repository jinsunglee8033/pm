@extends('admin.layout.default')

@section('content')

    <script type="text/javascript">

        var current_mode = '';

        function close_modal(id, account_id) {
            $('#' + id).modal('hide');
            //$('#btn_search').click();
            myApp.hidePleaseWait(current_mode);

            //$('body').removeClass('modal-open');
            //$('.modal-backdrop').remove();

            $('#btn_search').click();

            /*if (account_id) {
             show_account_detail(account_id);
             } else {
             $('#btn_search').click();
             }*/
        }

        function show_detail(id) {
            var mode = typeof id === 'undefined' ? 'new' : 'edit';
            var title = mode == 'new' ? 'Add News' : 'News Detail';
            $('#title').text(title);
            current_mode = mode;

            if (mode == 'new') {
                $('.edit').hide();

                $('#n_id').val('');
                $('#n_sdate').val('');
                $('#n_edate').val('');
                $('#n_account_id').val('');
                $('#n_account_type').val('');
                $('#n_subject').val('');
                $('#n_body').val('');
                $('#n_status').val('');

                $('#div_detail').modal();
            } else {
                $('.edit').show();

                myApp.showLoading();

                $.ajax({
                    url: '/admin/settings/news/get-detail',
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
                            var o = res.data;
                            $('#n_id').val(o.id);
                            $('#n_sdate').val(o.sdate);
                            $('#n_edate').val(o.edate);
                            $('#n_account_id').val(o.account_id);
                            $('#n_account_type').val(o.account_type);
                            $('#n_subject').val(o.subject);
                            $('#n_body').val(o.body);
                            $('#n_status').val(o.status);
                            $('#n_created_by').val(o.created_by);
                            $('#n_cdate').val(o.cdate);
                            $('#n_modified_by').val(o.modified_by);
                            $('#n_mdate').val(o.mdate);

                            $('#div_detail').modal();
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
        }

        function show_detail_and_refresh(id) {
            $('#id').val(id);
            $('#frm_news').submit();
        }

        function save_detail() {
            var url = current_mode == 'new' ? '/admin/settings/phones/add' : '/admin/settings/phones/update';

            myApp.showLoading();

            $.ajax({
                url: url,
                data: {
                    _token: '{!! csrf_token() !!}',
                    id: $('#n_id').val(),
                    sdate: $('#n_sdate').val(),
                    edate: $('#n_edate').val(),
                    account_id: $('#n_account_id').val(),
                    account_type: $('#n_account_type').val(),
                    subject: $('#n_subject').val(),
                    body: $('#n_body').val(),
                    status: $('#n_status').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        $('#div_detail').modal('hide');
                        myApp.showSuccess('Your rquest has been processed successfully!', function() {
                            show_detail_and_refresh(res.id);
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


    <h4>News</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_news" class="form-horizontal" method="post" action="/admin/settings/phones" onsubmit="myApp.showLoading();">
            {{ csrf_field() }}
            <input type="hidden" id="id" name="id"/>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Name</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="name" value="{{ old('name') }}"/>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                </div>
                <div class="col-md-4 text-right">
                    <div class="form-group">
                        <div class="col-md-8 col-md-offset-4">
                            <button type="submit" class="btn btn-primary btn-sm" id="btn_search">Search</button>
                            <button type="button" class="btn btn-primary btn-sm" onclick="show_detail()">Add New</button>
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
            <th>Name</th>
            <th>Image</th>
            <th>Status</th>
            <th>Created.By</th>
            <th>Created.At</th>
        </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="10" class="text-center">No Record Found</td>
            </tr>
        </tbody>
    </table>

    <div class="text-right">
        {{ isset($phones) ? $phones->appends(Request::except('page'))->links() : '' }}
    </div>


    <div class="modal" id="div_detail" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">Phone Detail</h4>
                </div>
                <div class="modal-body">

                    <form id="frm_transaction" class="form-horizontal" method="post" style="padding:15px;" target="ifm_upload" enctype="multipart/form-data">
                        <div class="form-group">
                            <label class="col-sm-2 control-label">ID</label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control" id="n_id" name="id" readonly/>
                            </div>
                            <label class="col-sm-2 control-label required">Name</label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control" id="n_name" name="name"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Image List</label>
                            <div class="col-sm-10">
                                <div style="overflow:auto">
                                    <div class="row-fluid" style="white-space: nowrap;">
                                        <img src="http://placehold.it/150x150?text=No+Image+Available" style="max-height:150px;">
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label required">Upload New</label>
                            <div class="col-sm-10">
                                <input type="file" class="form-control" name="upload_file"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Link To</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="n_link" name="link" placeholder="https://"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label required">Status</label>
                            <div class="col-sm-4">
                                <select class="form-control" id="n_status" name="status">
                                    <option value="">Please Select</option>
                                    <option value="A">Active</option>
                                    <option value="H">On Hold</option>
                                    <option value="O">Out of Stock</option>
                                    <option value="C">Closed</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group edit">
                            <label class="col-sm-2 control-label">Created.By</label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control" id="n_created_by" readonly/>
                            </div>
                            <label class="col-sm-2 control-label">Created.At</label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control" id="n_cdate" readonly/>
                            </div>
                        </div>
                        <div class="form-group edit">
                            <label class="col-sm-2 control-label">Modified.By</label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control" id="n_modified_by" readonly/>
                            </div>
                            <label class="col-sm-2 control-label">Modified.At</label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control" id="n_mdate" readonly/>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="margin-right:15px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="save_detail()">Save</button>
                </div>
            </div>
        </div>
    </div>

    <div style="display:none">
        <iframe name="ifm_upload"></iframe>
    </div>
@stop
