@extends('admin.layout.default')

@section('content')

    <script type="text/javascript">
        var onload_events = window.onload;
        window.onload = function() {

            if (onload_events) {
                onload_events();
            }

            $( "#sdate_c" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });
            $( "#edate_c" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });
            $( "#sdate_m" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });
            $( "#edate_m" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });
            $( "#pdate" ).datetimepicker({
                format: 'YYYY-MM-DD'
            });

            $( "#n_sdate" ).datetimepicker({
                format: 'YYYY-MM-DD',
                widgetPositioning: {
                    horizontal: 'right'
                }
            });
            $( "#n_edate" ).datetimepicker({
                format: 'YYYY-MM-DD',
                widgetPositioning: {
                    horizontal: 'right'
                }
            });

            if( $("#acct_included").val()== ''){
                $("#check_included").prop('checked', true);
            }

            CKEDITOR.replace('n_subject', {
                height: '100px',
                extraPlugins: 'uploadimage,image2,lineheight',
                toolbarGroups: [
                    {"name":"basicstyles","groups":["basicstyles"]},
                    {"name":"links","groups":["links"]},
                    { name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align', 'bidi', 'paragraph' ] },
                    {"name":"document","groups":["mode"]},
                    {"name":"insert","groups":["insert"]},
                    { name: 'styles', groups: [ 'Styles', 'Format', 'lineheight' ] },
                    {"name":"colors","groups" : ['TextColor', 'BGColor']},
                    {"name":"about","groups":["about"]}
                ],
                line_height: '1em;1.1em;1.2em;1.3em;1.4em;1.5em;1.6em;1.7em;1.8em;1.9em;2.0em;2.5em;3.0em;'
            });
            CKEDITOR.replace('n_body', {
                height: '150px',
                extraPlugins: 'uploadimage,image2,lineheight',
                toolbarGroups: [
                    {"name":"basicstyles","groups":["basicstyles"]},
                    {"name":"links","groups":["links"]},
                    { name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align', 'bidi', 'paragraph' ] },
                    {"name":"document","groups":["mode"]},
                    {"name":"insert","groups":["insert"]},
                    { name: 'styles', groups: [ 'Styles', 'Format', 'lineheight' ] },
                    {"name":"colors","groups" : ['TextColor', 'BGColor']},
                    {"name":"about","groups":["about"]}
                ],
                line_height: '1em;1.1em;1.2em;1.3em;1.4em;1.5em;1.6em;1.7em;1.8em;1.9em;2.0em;2.5em;3.0em;'
            });

            var show_detail_yn = '{{ $show_detail }}';
            var id = '{{ $id }}';
            if (show_detail_yn == 'Y') {
                show_detail(id);
            }

            $(".tooltip").tooltip({
                html: true
            })

            $.fn.modal.Constructor.prototype.enforceFocus = function() {
                modal_this = this
                $(document).on('focusin.modal', function (e) {
                    if (modal_this.$element[0] !== e.target && !modal_this.$element.has(e.target).length
                        && !$(e.target.parentNode).hasClass('cke_dialog_ui_input_select')
                        && !$(e.target.parentNode).hasClass('cke_dialog_ui_input_text')) {
                        modal_this.$element.focus()
                    }
                })
            };
            
        };

        var current_mode = '';
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
                $('[name=n_account_type]').prop('checked', false)
                CKEDITOR.instances['n_subject'].setData('');
                CKEDITOR.instances['n_body'].setData('');
                $('#n_invi_subject').val();
                $('#n_url').val();
                $('#n_url_s').val();
                $('#n_status').val('');
                $('#n_sorting').val('');
                $('#n_scroll').val('');

                set_display();

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
                            $('#n_type').val(o.type);
                            $('#n_product').val(o.product);
                            $('#n_sdate').val(o.sdate);
                            $('#n_edate').val(o.edate);
                            $('#n_exclude_account_ids').val('');
                            $('#n_include_account_ids').val('');

                            // if(o.include_account_ids !== null ){
                            //     var value = o.include_account_ids.replace(/,/g, '\n');
                            //     $('#n_include_account_ids').val(value);
                            // }
                            // if(o.exclude_account_ids !== null){
                            //     var value = o.exclude_account_ids.replace(/,/g, '\n');
                            //     $('#n_exclude_account_ids').val(value);
                            // }

                            if(!o.account_ids_i == ''){
                                var vi = '';
                                $.each(o.account_ids_i, function(i, n){
                                    vi = vi + (n.account_id) + '\n';
                                });
                                $('#n_include_account_ids').val(vi);
                            }
                            if(!o.account_ids_e == ''){
                                var ve = '';
                                $.each(o.account_ids_e, function(i, n){
                                    ve = ve + (n.account_id) + '\n';
                                });
                                $('#n_exclude_account_ids').val(ve);
                            }

                            CKEDITOR.instances['n_subject'].setData(o.subject);
                            CKEDITOR.instances['n_body'].setData(o.body);
                            $('#n_invi_subject').val(o.invi_subject);
                            $('#n_url').val(res.url);
                            $('#n_url_s').val(res.url_s);
                            $('#n_status').val(o.status);
                            $('#n_sorting').val(o.sorting);
                            $('#n_scroll').val(o.scroll);
                            $('#n_created_by').val(o.created_by);
                            $('#n_cdate').val(o.cdate);
                            $('#n_modified_by').val(o.modified_by);
                            $('#n_mdate').val(o.mdate);

                            $('[name=n_account_type]').prop('checked', false)

                            $.each(o.account_types, function(i, n) {
                                $('[name=n_account_type][value=' + n.account_type + ']').prop('checked', true);
                            });

                            set_display();

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

        function copy_add() {

            var account_types = [];
            $('[name=n_account_type]:checked').each(function() {
                account_types.push($(this).val());
            });

            myApp.showConfirm('Are you sure to proceed?', function() {

                myApp.showLoading();

                $.ajax({
                    url: '/admin/settings/news/copy_add',
                    data: {
                        _token: '{!! csrf_token() !!}',
                        id: $('#n_id').val(),
                        'account_type[]': account_types,
                        include_account_ids: $('#n_include_account_ids').val(),
                        exclude_account_ids: $('#n_exclude_account_ids').val()
                    },
                    cache: false,
                    type: 'post',
                    dataType: 'json',
                    success: function (res) {
                        myApp.hideLoading();
                        if ($.trim(res.msg) === '') {
                            $('#div_detail').modal('hide');
                            myApp.showSuccess('Your request has been processed successfully!', function () {
                                $('#frm_news').submit();
                            });

                        } else {
                            myApp.showError(res.msg);
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        myApp.hideLoading();
                        myApp.showError(errorThrown);
                    }
                });
            });
        }

        function remove() {

            var account_types = [];
            $('[name=n_account_type]:checked').each(function() {
                account_types.push($(this).val());
            });

            myApp.showConfirm('Are you sure to proceed?', function() {

                myApp.showLoading();

                $.ajax({
                    url: '/admin/settings/news/remove',
                    data: {
                        _token: '{!! csrf_token() !!}',
                        id: $('#n_id').val()
                    },
                    cache: false,
                    type: 'post',
                    dataType: 'json',
                    success: function (res) {
                        myApp.hideLoading();
                        if ($.trim(res.msg) === '') {
                            $('#div_detail').modal('hide');
                            myApp.showSuccess('Your request has been processed successfully!', function () {
                                $('#frm_news').submit();
                            });

                        } else {
                            myApp.showError(res.msg);
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        myApp.hideLoading();
                        myApp.showError(errorThrown);
                    }
                });
            });
        }

        function copy_update() {

            var account_types = [];
            $('[name=n_account_type]:checked').each(function() {
                account_types.push($(this).val());
            });

            if(account_types.length == 0){
                alert('Please select Target Account Type!');
                return;
            }

            myApp.showConfirm('Are you sure to proceed?', function() {

                myApp.showLoading();

                $.ajax({
                    url: '/admin/settings/news/add',
                    data: {
                        _token: '{!! csrf_token() !!}',
                        id: $('#n_id').val(),
                        type: $('#n_type').val(),
                        product: $('#n_product').val(),
                        sdate: $('#n_sdate').val(),
                        edate: $('#n_edate').val(),
                        'account_type[]': account_types,
                        include_account_ids: $('#n_include_account_ids').val(),
                        exclude_account_ids: $('#n_exclude_account_ids').val(),
                        subject: CKEDITOR.instances['n_subject'].getData(),
                        body: CKEDITOR.instances['n_body'].getData(),
                        status: $('#n_status').val(),
                        sorting: $('#n_sorting').val(),
                        invi_subject: $('#n_invi_subject').val(),
                        url: $('#n_url').val(),
                        url_s: $('#url_s').val(),
                        scroll: $('#n_scroll').val()
                    },
                    cache: false,
                    type: 'post',
                    dataType: 'json',
                    success: function (res) {
                        myApp.hideLoading();
                        if ($.trim(res.msg) === '') {
                            $('#div_detail').modal('hide');
                            myApp.showSuccess('Your request has been processed successfully!', function () {
                                $('#frm_news').submit();
                            });

                        } else {
                            myApp.showError(res.msg);
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        myApp.hideLoading();
                        myApp.showError(errorThrown);
                    }
                });
            });
        }

        function save_detail() {
            var url = current_mode == 'new' ? '/admin/settings/news/add' : '/admin/settings/news/update';

            var account_types = [];
            $('[name=n_account_type]:checked').each(function() {
                 account_types.push($(this).val());
            });

            if(account_types.length == 0){
                alert('Please select Target Account Type!');
                return;
            }

            myApp.showConfirm('Are you sure to proceed?', function() {

                myApp.showLoading();

                $.ajax({
                    url: url,
                    data: {
                        _token: '{!! csrf_token() !!}',
                        id: $('#n_id').val(),
                        type: $('#n_type').val(),
                        product: $('#n_product').val(),
                        sdate: $('#n_sdate').val(),
                        edate: $('#n_edate').val(),
                        'account_type[]': account_types,
                        include_account_ids: $('#n_include_account_ids').val(),
                        exclude_account_ids: $('#n_exclude_account_ids').val(),
                        subject: CKEDITOR.instances['n_subject'].getData(),
                        body: CKEDITOR.instances['n_body'].getData(),
                        status: $('#n_status').val(),
                        sorting: $('#n_sorting').val(),
                        invi_subject: $('#n_invi_subject').val(),
                        url: $('#n_url').val(),
                        url_s: $('#url_s').val(),
                        scroll: $('#n_scroll').val()
                    },
                    cache: false,
                    type: 'post',
                    dataType: 'json',
                    success: function (res) {
                        myApp.hideLoading();
                        if ($.trim(res.msg) === '') {
                            $('#div_detail').modal('hide');
                            myApp.showSuccess('Your request has been processed successfully!', function () {
                                $('#frm_news').submit();
                            });

                        } else {
                            myApp.showError(res.msg);
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        myApp.hideLoading();
                        myApp.showError(errorThrown);
                    }
                });
            });
        }

        function set_display() {
            var type = $('#n_type').val();
            switch (type) {
                case 'N':
                    $('#div_subject').show();
                    $('#div_product').show();
                    return;
                case 'S':
                    $('#div_subject').hide();
                    $('#div_product').hide();
                    return;
                case 'H':
                    $('#div_subject').hide();
                    $('#div_product').hide();
                    return;
                case 'P':
                case 'R':
                    $('#div_subject').hide();
                    $('#div_product').show();
                    return;
                case 'O':
                    $('#div_subject').hide();
                    $('#div_product').show();
                    return;
                case 'I':
                    $('#div_subject').show();
                    $('#div_product').hide();
                    return;
                case 'A':
                    $('#div_subject').show();
                    $('#div_product').hide();
                    return;
                case 'T':
                    $('#div_subject').show();
                    $('#div_product').hide();
                    return;
                default:
                    $('#div_subject').hide();
                    $('#div_product').hide();
                    return;
            }
        }

        function void_news(news_id) {
            var url = '/admin/settings/news/void';

            myApp.showLoading();

            $.ajax({
                url: url,
                data: {
                    _token: '{!! csrf_token() !!}',
                    id: news_id
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {
                        myApp.showSuccess('Your request has been processed successfully!', function() {
                            $('#frm_news').submit();
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

        function excel_export() {
            $('#excel').val('Y');
            $('#frm_news').submit();
            myApp.hideLoading();
            $('#excel').val('');
        }

        function refresh_all() {
            window.location.href = '/admin/settings/news';
        }

    </script>

    <h4>News</h4>

    <div class="well filter" style="padding-bottom:5px;">
        <form id="frm_news" class="form-horizontal" method="post" action="/admin/settings/news" onsubmit="myApp.showLoading();">
            {{ csrf_field() }}
            <input type="hidden" name="page" value="{{ $page }}"/>
            <input type="hidden" name="excel" id="excel" value=""/>
            <input type="hidden" id="id" name="id"/>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">News Type</label>
                        <div class="col-md-8">
                            <select class="form-control" name="type">
                                <option value="">All</option>
                                <option value="N" {{ old('type', $type) == 'N' ? 'selected' : '' }}>News</option>
                                <option value="S" {{ old('type', $type) == 'S' ? 'selected' : '' }}>Static Headline</option>
                                <option value="D" {{ old('type', $type) == 'D' ? 'selected' : '' }}>Static Headline (2nd)</option>
                                <option value="H" {{ old('type', $type) == 'H' ? 'selected' : '' }}>Headline</option>
                                <option value="M" {{ old('type', $type) == 'M' ? 'selected' : '' }}>Marketplace</option>
                                <option value="P" {{ old('type', $type) == 'P' ? 'selected' : '' }}>Promotion</option>
                                <option value="R" {{ old('type', $type) == 'R' ? 'selected' : '' }}>Reminder</option>
                                <option value="F" {{ old('type', $type) == 'F' ? 'selected' : '' }}>Reminder (Refill Section)</option>
                                <option value="G" {{ old('type', $type) == 'G' ? 'selected' : '' }}>Reminder (PIN Section)</option>
                                <option value="O" {{ old('type', $type) == 'O' ? 'selected' : '' }}>Over Activation</option>
                                <option value="I" {{ old('type', $type) == 'I' ? 'selected' : '' }}>Digital e-Marketing</option>
                                <option value="A" {{ old('type', $type) == 'A' ? 'selected' : '' }}>Advertise</option>
                                <option value="T" {{ old('type', $type) == 'T' ? 'selected' : '' }}>Tasks</option>
                                <option value="W" {{ old('type', $type) == 'W' ? 'selected' : '' }}>Follow-Ups</option>
                                <option value="U" {{ old('type', $type) == 'U' ? 'selected' : '' }}>Documents</option>
                                <option value="C" {{ old('type', $type) == 'C' ? 'selected' : '' }}>Communications</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Product</label>
                        <div class="col-md-8">
                            <select class="form-control" name="product">
                                <option value="">All</option>
                                <option value="Root" {{ old('product', $product) == 'Root' ? 'selected' : '' }}>Root</option>
                                <option value="PM Market" {{ old('product', $product) == 'PM Market' ? 'selected' : '' }}>PM Market</option>
                                <option value="Air Voice" {{ old('product', $product) == 'Air Voice' ? 'selected' : '' }}>Air Voice</option>
                                <option value="AT&T" {{ old('product', $product) == 'AT&T' ? 'selected' : '' }}>AT&T</option>
                                <option value="AT&T PR/VI" {{ old('product', $product) == 'AT&T PR/VI' ? 'selected' : ''}}>AT&T PR/VI</option>
                                <option value="AT&T Data Only" {{ old('product', $product) == 'AT&T Data Only' ? 'selected' : ''}}>AT&T Data Only</option>
                                <option value="Emida" {{ old('product', $product) == 'Emida' ? 'selected' : '' }}>Emida</option>
                                <option value="ePay" {{ old('product', $product) == 'ePay' ? 'selected' : '' }}>ePay</option>
                                <option value="FreeUP" {{ old('product', $product) == 'FreeUP' ? 'selected' : '' }}>FreeUP</option>
                                <option value="GEN Mobile" {{ old('product', $product) == 'GEN Mobile' ? 'selected' : ''}}>GEN Mobile</option>
                                <option value="GEN Mobile TMO" {{ old('product', $product) == 'GEN Mobile TMO' ? 'selected' : ''}}>GEN Mobile (TMO)</option>
                                <option value="H2O" {{ old('product', $product) == 'H2O' ? 'selected' : '' }}>H2O</option>
                                <option value="Liberty Mobile" {{ old('product', $product) == 'Liberty Mobile' ? 'selected' : '' }}>Liberty Mobile</option>
                                <option value="Boom Blue" {{ old('product', $product) == 'Boom Blue' ? 'selected' : '' }}>Boom Blue</option>
                                <option value="Boom Red" {{ old('product', $product) == 'Boom Red' ? 'selected' : '' }}>Boom Red</option>
                                <option value="Boom Purple" {{ old('product', $product) == 'Boom Purple' ? 'selected' : '' }}>Boom Purple</option>
                                <option value="Incomm (Qpay)" {{ old('product', $product) == 'Incomm (Qpay)' ? 'selected' : '' }}>Incomm (Qpay)</option>
                                <option value="Lyca" {{ old('product', $product) == 'Lyca' ? 'selected' : '' }}>Lyca</option>
                                <option value="MetroPcs" {{ old('product', $product) == 'MetroPcs' ? 'selected' : '' }}>MetroPcs</option>
                                <option value="Net10" {{ old('product', $product) == 'Net10' ? 'selected' : '' }}>Net10</option>
                                <option value="PagePlus" {{ old('product', $product) == 'PagePlus' ? 'selected' : '' }}>PagePlus</option>
                                <option value="Patriot Mobile" {{ old('product', $product) == 'Patriot Mobile' ? 'selected' : '' }}>Patriot Mobile</option>
                                <option value="ROKiT" {{ old('product', $product) == 'ROKIT' ? 'selected' : '' }}>ROKiT</option>
                                <option value="Simple Mobile" {{ old('product', $product) == 'Simple Mobile' ? 'selected' : '' }}>Simple Mobile</option>
                                <option value="Telcel America" {{ old('product', $product) == 'Telcel America' ? 'selected' : '' }}>Telcel America</option>
                                <option value="Ultra Mobile" {{ old('product', $product) == 'Ultra Mobile' ? 'selected' : '' }}>Ultra Mobile</option>
                                <option value="XFINITY" {{ old('product', $product) == 'XFINITY' ? 'selected' : '' }}>XFINITY</option>
                                <option value="Verizon" {{ old('product', $product) == 'Verizon' ? 'selected' : '' }}>Verizon</option>
                                <option value="VidaPay" {{ old('product', $product) == 'VidaPay' ? 'selected' : '' }}>VidaPay</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Posting Date</label>
                        <div class="col-md-8">
                            <input type="text" style="width:100px; float:left;" class="form-control" id="pdate" name="pdate" value="{{ old('pdate', $pdate) }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Status</label>
                        <div class="col-md-8">
                            <select class="form-control" id="status" name="status">
                                <option value="">All</option>
                                <option value="A" {{ old('status', $status) == 'A' ? 'selected' : '' }}>Active</option>
                                <option value="E" {{ old('status', $status) == 'E' ? 'selected' : '' }}>Expired</option>
                                <option value="H" {{ old('status', $status) == 'H' ? 'selected' : '' }}>On Hold</option>
                                <option value="C" {{ old('status', $status) == 'C' ? 'selected' : '' }}>Closed</option>
                                <option value="V" {{ old('status', $status) == 'V' ? 'selected' : '' }}>Voided</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">News ID</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="news_id" value="{{ old('news_id', $news_id) }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Created.At</label>
                        <div class="col-md-8">
                            <input type="text" style="width:100px; float:left;" class="form-control" id="sdate_c" name="sdate_c" value="{{ old('sdate_c', $sdate_c) }}"/>
                            <span class="control-label" style="margin-left:5px; float:left;"> ~ </span>
                            <input type="text" style="width:100px; margin-left: 5px; float:left;" class="form-control" id="edate_c" name="edate_c" value="{{ old('edate_c', $edate_c) }}"/>
                        </div>
                    </div>
                </div>
{{--                <div class="col-md-4">--}}
{{--                    <div class="form-group">--}}
{{--                        <label class="col-md-4 control-label">Created.At</label>--}}
{{--                        <div class="col-md-8">--}}
{{--                            <input type="text" style="width:100px; float:left;" class="form-control" id="cdate" name="cdate" value="{{ old('cdate', $cdate) }}"/>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Subject</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="subject" value="{{ old('subject', $subject) }}"/>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Acct Included</label>
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="acct_included" id="acct_included" value="{{ old('acct_included', $acct_included) }}"/>
                        </div>
                        <div class="col-md-4">
                            <input type="checkbox" name="check_included" id="check_included" value="Y" {{ $check_included == 'Y' ? 'checked' : '' }} /> Include for All
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Modified.At</label>
                        <div class="col-md-8">
                            <input type="text" style="width:100px; float:left;" class="form-control" id="sdate_m" name="sdate_m" value="{{ old('sdate_m', $sdate_m) }}"/>
                            <span class="control-label" style="margin-left:5px; float:left;"> ~ </span>
                            <input type="text" style="width:100px; margin-left: 5px; float:left;" class="form-control" id="edate_m" name="edate_m" value="{{ old('edate_m', $edate_m) }}"/>
                        </div>
                    </div>
                </div>
{{--                <div class="col-md-4">--}}
{{--                    <div class="form-group">--}}
{{--                        <label class="col-md-4 control-label">Modified.At</label>--}}
{{--                        <div class="col-md-8">--}}
{{--                            <input type="text" style="width:100px; float:left;" class="form-control" id="cdate_2" name="cdate_2" value="{{ old('cdate_2', $cdate_2) }}"/>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Invisible Subject</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="invi_subject" value="{{ old('invi_subject', $invi_subject) }}"/>
                        </div>
                    </div>
                </div>


                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Acct Excluded</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="acct_excluded" id="acct_excluded" value="{{ old('acct_excluded', $acct_excluded) }}"/>
                        </div>
                        {{--                        <div class="col-md-4">--}}
                        {{--                            <input type="checkbox" name="check_excluded" id="check_excluded" value="Y" {{ $check_excluded == 'Y' ? 'checked' : '' }} /> Exclude for All--}}
                        {{--                        </div>--}}
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Target.Type</label>
                        <div class="col-md-8">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="account_type[]" value="L"{{ isset($account_type) && in_array('L', $account_type) ? 'checked' : '' }}> Root
                            </label>
                            @foreach ($account_types as $o)
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="account_type[]" value="{{ $o['code'] }}" {{ isset($account_type) && in_array($o['code'], $account_type) ? 'checked' : '' }}/> {{ $o['name'] }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>



                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Sorting By</label>
                        <div class="col-md-8">
                            <select class="form-control" name="order_by">
                                <option value="news.id desc" {{ $order_by == 'news.id desc' ? 'selected' : ''}}>News ID Descending</option>
                                <option value="news.cdate asc" {{ $order_by == 'news.cdate asc' ? 'selected' : ''}}>Created date Ascending</option>
                                <option value="news.cdate desc" {{ $order_by == 'news.cdate desc' ? 'selected' : ''}}>Created date Descending</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 col-md-offset-8 text-right">
                    <div class="form-group">
                        <div class="col-md-8 col-md-offset-4">
                            <button type="button" class="btn btn-info btn-sm" onclick="refresh_all()">Refresh All</button>
                            <button type="submit" class="btn btn-primary btn-sm" id="btn_search">Search</button>
                            <button type="button" class="btn btn-primary btn-sm" onclick="show_detail()">Add New</button>
                            <button type="button" class="btn btn-info btn-sm" onclick="excel_export()">Download</button>
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
            <th>Type</th>
            <th>Product</th>
            <th>From</th>
            <th>To</th>
            <th>Included.Account</th>
            <th>Excluded.Account</th>
            <th>Target.Type</th>
            <th>Status</th>
            <th>Created.By</th>
            <th>Created.At</th>
            <th>Modified.By</th>
            <th>Modified.At</th>
            @if (Auth::user()->account_type == 'L')
                <th>Void</th>
            @endif
            <th>Sorting</th>
            <th>Invisible Subject</th>
            <th>Subject</th>
        </tr>
        </thead>
        <tbody>
        @if (isset($news) && count($news) > 0)
            @foreach ($news as $o)
                <tr>
                    <td><a href="javascript:show_detail('{{ $o->id }}')">{{ $o->id }}</a></td>
                    <td>
                        {{ \App\Model\News::getTypeNameAttribute($o->type) }}
                    </td>
                    <td>{{ $o->product }}</td>
                    <td>{{ $o->sdate }}</td>
                    <td>{{ $o->edate }}</td>
                    <td>{{ \App\Lib\Helper::get_included_account_id($o->id) }}</td>
                    <td>{{ \App\Lib\Helper::get_excluded_account_id($o->id) }}</td>
                    <td>{{ \App\Lib\Helper::get_news_target_type($o->id) }}</td>
                    <td>
                        {!! \App\Model\News::getStatusNameAttribute($o->status) !!}
                    </td>
                    <td style="color:blue;">
                        <a href="#" class="tooltip" style="cursor:pointer;" title="{{ htmlentities(Helper::get_account_name_html($o->created_by)) }}">
                            {{ $o->created_by }}
                        </a>
                        {{ $o->created_by }}
                    </td>
                    <td>{{ $o->cdate }}</td>
                    <td style="color:blue;">
                        <a href="#" class="tooltip" style="cursor:pointer;" title="{{ htmlentities(Helper::get_account_name_html($o->created_by)) }}">
                            {{ $o->modified_by }}
                        </a>
                        {{ $o->modified_by }}
                    </td>
                    <td>{{ $o->mdate }}</td>
                    @if (Auth::user()->account_type == 'L')
                        <td>
                            @if ($o->status != 'V')
                                <button type="submit" class="btn btn-default btn-sm btn-td" onclick="void_news({{ $o->id }})">
                                    Void
                                </button>
                            @endif
                        </td>
                    @endif
                    <td>{{ $o->sorting }}</td>
                    <td>{{ $o->invi_subject }}</td>
                    <td><a href="javascript:show_detail('{{ $o->id }}')">
                            {!! ($o->type == 'N' || $o->type == 'A' || $o->type == 'T' || $o->type == 'I') ? $o->subject : $o->body !!}
                        </a>
                    </td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="20" class="text-center">No Record Found</td>
            </tr>
        @endif
        </tbody>
    </table>

    <div class="text-right">
        {{ $news->appends(Request::except('page'))->links() }}
    </div>

    <div class="modal" id="div_detail" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg" role="document" style="width:1200px !important;">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">x</span></button>
                    <h4 class="modal-title" id="title">News Detail</h4>
                </div>
                <div class="modal-body">

                    <form id="frm_transaction" class="form-horizontal filter" method="post" style="padding:15px;">
                        <div class="form-group">
                            <label class="col-sm-2 control-label">ID</label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control" id="n_id" readonly/>
                            </div>
                            <label class="col-sm-2 control-label required">Date</label>
                            <div class="col-sm-4">
                                <div class="form-inline">
                                    <input type="text" class="form-control" style="width:100px;" id="n_sdate"/>
                                    <span style="margin-left:10px; margin-right:10px;"> ~ </span>
                                    <input type="text" class="form-control" style="width:100px;" id="n_edate"/>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label required">Type</label>
                            <div class="col-sm-4">
                                <select class="form-control" id="n_type" onchange="set_display()">
                                    <option value="">Please Select</option>
                                    <option value="N">News</option>
                                    <option value="S">Static Headline</option>
                                    <option value="D">Static Headline (2nd)</option>
                                    <option value="H">Headline</option>
                                    <option value="M">Marketplace</option>
                                    <option value="P">Promotion</option>
                                    <option value="R">Reminder</option>
                                    <option value="F">Reminder (Refill Section)</option>
                                    <option value="G">Reminder (PIN Section)</option>
                                    <option value="O">Over Activation</option>
                                    <option value="I">Digital e-Marketing</option>
                                    <option value="A">Advertise</option>
                                    <option value="T">Tasks</option>
                                    <option value="W">Follow-Ups</option>
                                    <option value="U">Documents</option>
                                    <option value="C">Communications</option>
                                </select>
                            </div>
                            <div class="form-group" id="div_product">
                                <label class="col-sm-2 control-label required">Product</label>
                                <div class="col-sm-4">
                                    <select class="form-control" id="n_product">
                                        <option value="">Please Select</option>
                                        <option value="Root">Root</option>
                                        <option value="PM Market">PM Market</option>
                                        <option value="Air Voice">Air Voice</option>
                                        <option value="AT&T">AT&T</option>
                                        <option value="AT&T PR/VI">AT&T PR/VI</option>
                                        <option value="AT&T Data Only">AT&T Data Only</option>
                                        <option value="Emida">Emida</option>
                                        <option value="ePay">ePay</option>
                                        <option value="FreeUP">FreeUP</option>
                                        <option value="GEN Mobile">GEN Mobile</option>
                                        <option value="GEN Mobile TMO">GEN Mobile (TMO)</option>
                                        <option value="H2O">H2O</option>
                                        <option value="Liberty Mobile">Liberty Mobile</option>
                                        <option value="Boom Blue">Boom Blue</option>
                                        <option value="Boom Red">Boom Red</option>
                                        <option value="Boom Purple">Boom Purple</option>
                                        <option value="Incomm (Qpay)">Incomm (Qpay)</option>
                                        <option value="Lyca">Lyca</option>
                                        <option value="MetroPcs">MetroPcs</option>
                                        <option value="Net10">Net10</option>
                                        <option value="PagePlus">PagePlus</option>
                                        <option value="Patriot Mobile">Patriot Mobile</option>
                                        <option value="ROKiT">ROKiT</option>
                                        <option value="Simple Mobile">Simple Mobile</option>
                                        <option value="Telcel America">Telcel America</option>
                                        <option value="Ultra Mobile">Ultra Mobile</option>
                                        <option value="XFINITY">XFINITY</option>
                                        <option value="Verizon">Verizon</option>
                                        <option value="VidaPay">VidaPay</option>
                                    </select>
                                </div>
                            </div>
                            <label class="col-sm-2 control-label">Target.Account.Type</label>
                            <div class="col-sm-4">
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="n_account_type" value="L"> Root
                                </label>
                                @foreach ($account_types as $o)
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="n_account_type" value="{{ $o['code'] }}"/> {{ $o['name'] }}
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label">Include.Account.IDs</label>

                            <div class="col-sm-4 margin-bot10">
                                <textarea class="form-control" rows="10" id="n_include_account_ids" placeholder=""></textarea>
                            </div>

                            <label class="col-sm-2 control-label">Exclude.Account.IDs</label>

                           <div class="col-sm-4 margin-bot10">
                                <textarea class="form-control" rows="10" id="n_exclude_account_ids" placeholder=""></textarea>
                            </div>
                        </div>

                        <div class="form-group" id="div_subject">
                            <label class="col-sm-2 control-label required">Subject</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="n_subject"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label required">Body</label>
                            <div class="col-sm-10">
                                <textarea class="form-control" id="n_body" rows="5"></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label required">Invisible Subject</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="n_invi_subject"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label required">File URL (L/M/D)</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="n_url" readonly/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label required">File URL (S)</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="n_url_s" readonly/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label required">Status</label>
                            <div class="col-sm-4">
                                <select class="form-control" id="n_status">
                                    <option value="">Please Select</option>
                                    <option value="A">Active</option>
                                    <option value="E">Expired</option>
                                    <option value="H">On-Hold</option>
                                    <option value="C">Closed</option>
                                    <option value="V">Voided</option>
                                </select>
                            </div>
                            <label class="col-sm-2 control-label">Sorting</label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control" id="n_sorting"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label required">Scroll</label>
                            <div class="col-sm-4">
                                <select class="form-control" id="n_scroll">
                                    <option value="">Please Select</option>
                                    <option value="Y">Yes</option>
                                    <option value="N">No</option>
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
                    <button type="button" class="btn btn-default edit" onclick="copy_update()">Copy & UPDATE</button>
{{--                    <button type="button" class="btn btn-default" onclick="copy_add()">Copy & ADD</button>--}}
                    <button type="button" class="btn btn-default edit" onclick="remove()">Delete</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="save_detail()">Save</button>
                </div>
            </div>
        </div>
    </div>
@stop
