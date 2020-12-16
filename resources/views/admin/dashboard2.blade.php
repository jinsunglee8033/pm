@extends('admin.layout.default')

@section('content')

<div class="row">

    @if (in_array("AT&T", $carriers))
        <div class="col-lg-6">
            <h4>Activations / Port-In (ATT)</h4>
            <div class="well">
                <canvas id="att" width="200" height="200"></canvas>
                <script>
                    var ctx = document.getElementById("att");
                    var myChart = new Chart(ctx, {
                        type: 'line',
                        data: {!! $att !!},
                        options: {
                            maintainAspectRatio : false,
                            scales: {
                                yAxes: [{
                                    ticks: {
                                        beginAtZero:true
                                    }
                                }]
                            }
                        }
                    });
                </script>
            </div>
        </div>
    @endif

    @if (in_array("Boom Mobile", $carriers))
        <div class="col-lg-6">
            <h4>Activations / Port-In (Boom Mobile)</h4>
            <div class="well">
                <canvas id="boom" width="200" height="200"></canvas>
                <script>
                    var ctx = document.getElementById("boom");
                    var myChart = new Chart(ctx, {
                        type: 'line',
                        data: {!! $boom !!},
                        options: {
                            maintainAspectRatio : false,
                            scales: {
                                yAxes: [{
                                    ticks: {
                                        beginAtZero:true
                                    }
                                }]
                            }
                        }
                    });
                </script>
            </div>
        </div>
    @endif

    @if (in_array("GEN Mobile", $carriers))
        <div class="col-lg-6">
            <h4>Activations / Port-In (Gen Mobile)</h4>
            <div class="well">
                <canvas id="gen" width="200" height="200"></canvas>
                <script>
                    var ctx = document.getElementById("gen");
                    var myChart = new Chart(ctx, {
                        type: 'line',
                        data: {!! $gen !!},
                        options: {
                            maintainAspectRatio : false,
                            scales: {
                                yAxes: [{
                                    ticks: {
                                        beginAtZero:true
                                    }
                                }]
                            }
                        }
                    });
                </script>
            </div>
        </div>
    @endif

    @if (in_array("Lyca", $carriers))
        <div class="col-lg-6">
            <h4>Activations / Port-In (Lyca)</h4>
            <div class="well">
                <canvas id="lyca" width="200" height="200"></canvas>
                <script>
                    var ctx = document.getElementById("lyca");
                    var myChart = new Chart(ctx, {
                        type: 'line',
                        data: {!! $lyca !!},
                        options: {
                            maintainAspectRatio : false,
                            scales: {
                                yAxes: [{
                                    ticks: {
                                        beginAtZero:true
                                    }
                                }]
                            }
                        }
                    });
                </script>
            </div>
        </div>
    @endif

    @if (in_array("H2O", $carriers))
        <div class="col-lg-6">
            <h4>Activations / Port-In (H2O)</h4>
            <div class="well">
                <canvas id="h2o" width="200" height="200"></canvas>
                <script>
                    var ctx = document.getElementById("h2o");
                    var myChart = new Chart(ctx, {
                        type: 'line',
                        data: {!! $h2o !!},
                        options: {
                            maintainAspectRatio : false,
                            scales: {
                                yAxes: [{
                                    ticks: {
                                        beginAtZero:true
                                    }
                                }]
                            }
                        }
                    });
                </script>
            </div>
        </div>
    @endif

    @if (in_array("FreeUP", $carriers))
        <div class="col-lg-6">
            <h4>Activations / Port-In (FreeUP)</h4>
            <div class="well">
                <canvas id="freeup" width="200" height="200"></canvas>
                <script>
                    var ctx = document.getElementById("freeup");
                    var myChart = new Chart(ctx, {
                        type: 'line',
                        data: {!! $freeup !!},
                        options: {
                            maintainAspectRatio : false,
                            scales: {
                                yAxes: [{
                                    ticks: {
                                        beginAtZero:true
                                    }
                                }]
                            }
                        }
                    });
                </script>
            </div>
        </div>
    @endif

    @if (in_array("Liberty Mobile", $carriers))
        <div class="col-lg-6">
            <h4>Activations / Port-In (Liberty Mobile)</h4>
            <div class="well">
                <canvas id="liberty" width="200" height="200"></canvas>
                <script>
                    var ctx = document.getElementById("liberty");
                    var myChart = new Chart(ctx, {
                        type: 'line',
                        data: {!! $liberty !!},
                        options: {
                            maintainAspectRatio : false,
                            scales: {
                                yAxes: [{
                                    ticks: {
                                        beginAtZero:true
                                    }
                                }]
                            }
                        }
                    });
                </script>
            </div>
        </div>
    @endif

    <div class="col-lg-6">
        <h4>RTR / PIN</h4>
        <div class="well">
            <canvas id="myChart1" width="200" height="200"></canvas>
            <script>
                var ctx = document.getElementById("myChart1");
                var myChart = new Chart(ctx, {
                    type: 'line',
                    data: {!! $rtr_pin !!},
                    options: {
                        maintainAspectRatio : false,
                        scales: {
                            yAxes: [{
                                ticks: {
                                    beginAtZero:true
                                }
                            }]
                        }
                    }
                });
            </script>
        </div>
    </div>

</div>
@stop
