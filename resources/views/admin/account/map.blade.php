@extends('admin.layout.default')

@section('content')

    <style type="text/css">
        /* Set the size of the div element that contains the map */
        #map {
            height: 400px;  /* The height is 400 pixels */
            width: 100%;  /* The width is the width of the web page */
        }

        .sbox {
            -webkit-appearance: menulist-button;
            height: 37px;
        }

        .id-box {
            font-family: Arial, Helvetica,
            sans-serif; line-height:1.3em;
            margin-top: 7px;
        }

        table.gridtable {
            font-family: verdana,arial,sans-serif;
            font-size:15px;
            color:#333333;
            border-width: 1px;
            border-color: #666666;
            border-collapse: collapse;
        }
        table.gridtable th {
            border-width: 1px;
            padding: 8px;
            border-style: solid;
            border-color: #666666;
            background-color: #dedede;
        }
        table.gridtable td {
            border-width: 1px;
            padding: 8px;
            border-style: solid;
            border-color: #666666;
            background-color: #ffffff;
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

        function initMap(list) {

            if (list){
                var locations = list;
                var a = locations[0][1];
                var b = locations[0][2];
                var center = {lat: +a, lng: +b};
                var map = new google.maps.Map(document.getElementById('map'), {
                    zoom: 15,
                    center: center
                });
            }

            var infowindow = new google.maps.InfoWindow();

            var marker, i;
            var num = 1;
            var str = '';
            for (i = 0; i < locations.length; i++) {

                str = num.toString();

                if(locations[i][3] == 'A') {
                    marker = new google.maps.Marker({
                        id: i,
                        position: new google.maps.LatLng(locations[i][1], locations[i][2]),
                        // label: str,
                        map: map,
                        url: locations[i][4]
                    });
                }else if(locations[i][3] == 'L') {
                    marker = new google.maps.Marker({
                        id: i,
                        position: new google.maps.LatLng(locations[i][1], locations[i][2]),
                        // label: str,
                        map: map,
                        url: locations[i][4],
                        icon: {
                            url: "http://maps.google.com/mapfiles/ms/icons/yellow-dot.png"
                        }
                    });
                }else{
                    marker = new google.maps.Marker({
                        id: i,
                        position: new google.maps.LatLng(locations[i][1], locations[i][2]),
                        // label: str,
                        map: map,
                        url: locations[i][4],
                        icon: {
                            url: "http://maps.google.com/mapfiles/ms/icons/purple-dot.png"
                        }
                    });
                }

                num++;

                marker.addListener('mouseover', (function(marker, i) {
                    return function() {
                        // infowindow.setContent(locations[i][4] + locations[i][0]);
                        infowindow.setContent(locations[i][0]);
                        infowindow.open(map, this);
                    }
                })(marker, i));

                google.maps.event.addListener(marker, 'click', (function(marker, i) {
                    return function() {
                        infowindow.setContent("<a href='"+locations[i][4]+"' target='_blank'>"+locations[i][0]+"</a>");
                        infowindow.open(map, marker);
                    }
                })(marker, i));

                if(marker)
                {
                    marker.addListener('click', function() {
                        map.setZoom(16);
                        map.setCenter(this.getPosition());
                    });
                }
            }
        }

        function search_zip() {

            myApp.showLoading();

            $.ajax({
                url: '/admin/account/map/find',
                data: {
                    _token: '{!! csrf_token() !!}',
                    ids: $('#ids').val(),
                    att_ids: $('#att_ids').val(),
                    att_tid: $('#att_tid').val(),
                    address: $('#address').val(),
                    zip: $('#zip').val(),
                    state: $('#state').val(),
                    city: $('#city').val(),
                    name: $('#name').val(),
                    status: $('#status').val(),
                    has_doc: $('#has_doc').val()
                },
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(res) {
                    myApp.hideLoading();
                    if ($.trim(res.msg) === '') {

                        myApp.showSuccess('Your request has been processed successfully!', function() {

                            var new_location = [[]];
                            var num = 1;
                            $.each(res.data, function(i, r) {
                                new_location[i] = [
                                    '('+num+')'+ ' ' + r.address1 + ' ' + r.city + ' ' + r.state + ' ' + r.zip + ' [' + r.name + ']',
                                    r.lat, r.lng, r.status,
                                    'https://www.google.com/maps/place/'+ r.address1 + ' ' + r.city + ' ' + r.state + ' ' + r.zip
                                ];
                                num++;
                            })
                            initMap(new_location);
                            $('#list').empty();
                            $('#list').append(
                                '<tr>'+
                                '<th></th>'+
                                '<th></th>'+
                                '<th>No.</th>'+
                                '<th>Account.ID</th>'+
                                '<th>Account.Name</th>'+
                                '<th>Address1</th>'+
                                '<th>Address2</th>'+
                                '<th>Status</th>'+
                                '<th>ATT TID</th>'+
                                '<th>ATT TID2</th>'+
                                '<th>ATT Doc</th>'+
                                '</tr>');
                            var num = 1;
                            $.each(res.data, function(k, v) {
                                var addr = v.address1 + ' ' + v.city + ' ' + v.state + ' ' + v.zip;
                                var addr2 = '';
                                if(v.address2 != null){
                                    addr2 = v.address2;
                                }
                                var att_tid = '';
                                if(v.att_tid != null){
                                    att_tid = v.att_tid;
                                }
                                var att_tid2 = '';
                                if(v.att_tid2 != null){
                                    att_tid2 = v.att_tid2;
                                }
                                var file_name = '';
                                if(v.file_name != null){
                                    file_name = 'Yes';
                                }
                                $('#list').append('<tr>' +
                                    '<td><a href="https://www.google.com/maps/place/' + addr + '" target="_blank">Google</a></td>' +
                                    '<td><a href="https://maps.apple.com/?address=' + addr + '" target="_blank">iMap</a></td>' +
                                    '<td>' + num + '</td>' +
                                    '<td>' + v.id + '</td>' +
                                    '<td>' + v.name + '</td>' +
                                    '<td>' + addr + '</td>' +
                                    '<td>' + addr2 + '</td>' +
                                    '<td>' + v.status + '</td>' +
                                    '<td>' + att_tid + '</td>' +
                                    '<td>' + att_tid2 + '</td>' +
                                    '<td>' + file_name + '</td>' +
                                '</tr>');
                                num++;
                            });

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
            $('#send-zip').submit();
        }

    </script>

    <!-- Start parallax -->
    <div class="parallax" data-background="/img/parallax/innerpage.jpg" data-speed="0.5" data-size="50%">
        <div class="overlay white"></div>
        <div class="container">
            <div class="inner-head">
                <div class="row">
                    <div class="col-md-12 text-center">
                        <h4>Store Map</h4>
                        <ol class="breadcrumb">
                            <li><a href="#">Home</a></li>
                            <li><a href="#">Setting</a></li>
                            <li class="active">Map</li>
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
                <form id="send-zip" method="post" class="center-block" action="/admin/account/map/find">
                    {!! csrf_field() !!}
                    <input type="hidden" name="excel" id="excel"/>
                    <div>
                        <input id="address" name="address" type="text" placeholder="Address">
                        <input id="city" name="city" type="text" placeholder="City">
                        <select class="sbox" name="state" id="state">
                            <option value="">State</option>
                            @foreach ($states as $o)
                                <option value="{{ $o['code'] }}" {{ $o['code'] == old('state') ? 'selected' : ''}}>{{ $o['name'] }}</option>
                            @endforeach
                        </select>
                        <button type="button" id="btn_search"  class="btn btn-primary" onclick="search_zip()">Search</button>
                        <button type="button" id="btn_search"  class="btn btn-primary" onclick="excel_export()">Export</button>
                    </div>
                    <div>
                        <input id="zip" name="zip" type="text" placeholder= "Zip">
                        <input id="name" name="name" type="text" placeholder= "B.Name">
                        <input id="att_tid" name="att_tid" type="text" placeholder= "ATT TID">
                        <select class="sbox" name="status" id="status">
                            <option value="">Status - All</option>
                            <option value="A">Active</option>
                            <option value="H">On-Hold</option>
                            <option value="P">Pre-Auth</option>
                            <option value="F">Failed Payment</option>
                            <option value="L">Lead</option>
                            <option value="C">Closed</option>
                        </select>
                        <select class="sbox" name="has_doc" id="has_doc">
                            <option value="">Has ATT Doc? - All</option>
                            <option value="Y">Yes</option>
                            <option value="N">No</option>
                        </select>
                    </div>
                    <div>
                        <textarea class="id-box" id="ids" name="ids" rows="3" placeholder="ACCOUNT IDs"></textarea>
                        <textarea class="id-box" id="att_ids" name="att_ids" rows="3" placeholder="ATT TIDs"></textarea>
                    </div>
                </form>
                    <hr>
                <div id="map"></div>
                    <hr>
                <div>
                    <table class="gridtable" id="list">
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- End contain wrapp -->

    <!-- Map Result -->
    <script async defer
            src="https://maps.googleapis.com/maps/api/js?key=&callback=initMap">
    </script>

@stop
