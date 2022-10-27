<html>

<head>
    <title>Dashboard</title>
    <link href="{{ url('assets/bootstrap.min.css') }}" rel="stylesheet">

    <style>
        .analytics_device_category {
            opacity: 0.3;
        }

        #btn_analytics_refresh {
            float: right;
        }

        .connect_analytics {
            display: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <div>
            <a href="{{ url('redirectgoogle') }}"
                class="{{ $user_analytics == '' || $user_analytics == null ? '' : 'connect_analytics' }}">Connect to
                Google
                Analytics</a>
            <button id="btn_analytics_refresh"
                class="{{ $user_analytics != '' || $user_analytics != null ? '' : 'connect_analytics' }}">Refresh</button>
        </div>
        <div class="row">
            <div class="col-md-4" id="analytics_device_category">
                <div id="user_device_category"></div>
            </div>
            <div class="col-md-6">
                <div id="user_traffic_chart">
                </div>
            </div>
        </div>
    </div>
</body>

<script src="{{ url('assets/jquery.min.js') }}"></script>
<script src="{{ url('assets/highcharts.js') }}"></script>

<script>
    var fetchdevicedetailsURL = "{{ route('users.device.details') }}";
    var fetchusertrafficURL = "{{ route('users.traffic.details') }}";
    var AllAnalyticsDataURL = "{{ route('analytics.data') }}";
    var refreshURL = "{{ route('analytics.refresh') }}";
    var __token="{{ csrf_token() }}";
    $(document).ready(function() {

        $(document).on('click', '#btn_analytics_refresh', function() {
            $.ajax({
                type: "post",
                dataType: 'json',
                url: refreshURL,
                data: {
                    "_token": "{{ csrf_token() }}",
                    "user_refresh": 1
                },
                cache: false,
                beforeSend: function() {
                    console.log('Before Refresh');
                },
                success: function(data) {
                    console.log(data)
                    getAllAnalyticsData();
                },
                fail: function() {
                    alert('something went wrong');
                }
            });

        });


        // Get All Analytics Data
        function getAllAnalyticsData()
        {
            $.ajax({
                type: "post",
                dataType: 'json',
                url: AllAnalyticsDataURL,
                data: {
                    "_token": __token,                    
                },
                cache: false,
                beforeSend: function() {
                    $("#analytics_device_category").addClass('analytics_device_category');
                },
                success: function(response) {
                    // console.log(response.data)
                    if(response.data.devicedata!=null)
                    {
                        devicechart(response.data.devicedata)
                    }                    
                    if(response.data.traffic_details!=null)
                    {
                        setUserTrafficChart(response.data.traffic_details)
                    }

                    $("#analytics_device_category").removeClass('analytics_device_category');
                },
                fail: function() {
                    $("#analytics_device_category").removeClass('analytics_device_category');
                    alert('something went wrong');
                }
            });
        }
        getAllAnalyticsData()
    });

    function devicechart(devicedetails) {
        
        const gridSize2 = 152;
        const gridInnerSize2 = gridSize2 - 39;
        // Build the chart
        Highcharts.chart('user_device_category', {
            title: '',
            chart: {
                type: "pie",
                style: {
                    fontFamily: "Lato",
                },
                backgroundColor: 'transparent',
                width: 200,
                height: 250,
                events: {
                    load() {}
                }
            },
            credits: {
                enabled: false
            },
            plotOptions: {
                pie: {
                    allowPointSelect: true,
                    size: gridSize2,
                    innerSize: gridInnerSize2,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: false,
                    },
                    showInLegend: false
                },
                tooltip: {
                    enabled: false
                },
                series: {
                    cursor: "pointer",
                    states: {
                        hover: {
                            enabled: false
                        },
                        inactive: {
                            opacity: 1
                        }
                    }
                }
            },
            legend: {
                labelFormat: '{name} {y:.2f}%',
            },
            series: [{
                name:'',
                colorByPoint: true,
                data: devicedetails
            }]
        });
    }

    function setUserTrafficChart(trafficdata) {

        Highcharts.chart('user_traffic_chart', {
            chart: {
                type: 'line'
            },
            title: {
                text: ''
            },
            subtitle: {
                text: ''
            },
            credits: {
                enabled: false
            },
            tooltip: {
                enabled:true,
                // formatter: function() {
                //     return 'The value for <b>' + this.x + '</b> is <b>' + this.y + '</b>, in series ' + this
                //         .series.name;
                // }
            },
            yAxis: {
                crosshair: true,
                title: {
                    text: ''
                },
                opposite: true
            },
            xAxis: {
                crosshair: true,
                categories: trafficdata.latest_dates.map(date => {
                    return Highcharts.dateFormat('%d', new Date(date).getTime());
                }),
                // categories: [17, 18, 19, 20, 21, 22, 23],
                // tickInterval: 1,
                // labels: {
                //     enabled: true
                // },
                // type: 'date'
            },
            legend: {
                layout: 'vertical',
                align: 'left',
                verticalAlign: 'middle',
                symbolWidth: 50
            },
            plotOptions: {
                series: {
                    label: {
                        connectorAllowed: false
                    },
                },
                line: {
                    marker: {
                        enabled: false
                    }
                }
            },
            series: [{
                name: 'Last 7 Days',
                data: trafficdata.latest_traffic
            }, {
                name: 'previous 7 Days',
                dashStyle: 'shortdash',
                color: '#9faba2',
                data: trafficdata.previous_traffic
            }, ],
            responsive: {
                rules: [{
                    condition: {
                        maxWidth: 500,
                    },
                    chartOptions: {
                        legend: {
                            layout: 'horizontal',
                            align: 'left',
                            verticalAlign: 'bottom'
                        }
                    }
                }]
            }
        });
    }
</script>
</html>
