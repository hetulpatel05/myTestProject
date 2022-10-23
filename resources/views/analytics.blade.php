<html>
<head>
    <title>Dashboard</title>
    <link href="{{ url('assets/bootstrap.min.css') }}" rel="stylesheet">

    <style>
        .analytics_device_category{
            opacity: 0.3;
        }
        #btn_analytics_refresh
        {
            float: right;
        }
        .connect_analytics
        {
            display: none;
        }
    </style>
</head>

<body>
    <div class="container"> 
        <div>
            <a href="{{ url('redirectgoogle') }}" class="{{ ($user_analytics=='' || $user_analytics==null)?'':'connect_analytics'}}">Connect to Google Analytics</a>
            <button id="btn_analytics_refresh" class="{{ ($user_analytics!='' || $user_analytics!=null)?'':'connect_analytics'}}">Refresh</button>            
        </div>
        <div class="row">
            <div class="col-md-4" id="analytics_device_category">
                <div id="user_device_category"></div>
            </div>
        </div>        
    </div>
</body>

<script src="{{ url('assets/jquery.min.js') }}"></script>
<script src="{{ url('assets/highcharts.js') }}"></script>

<script>
    var fetchdevicedetailsURL = "{{ route('users.device.details') }}";
    var refreshURL = "{{ route('analytics.refresh') }}";

    $(document).ready(function() {
       
        $(document).on('click', '#btn_analytics_refresh', function () { 
            
            $.ajax({
                type: "post",
                dataType: 'json',
                url: refreshURL,
                data:{                    
                    "_token": "{{csrf_token()}}",
                    "user_refresh":1
                },            
                cache: false,
                beforeSend: function() {                
                    console.log('Before Refresh');
                },
                success: function (data) {                                                         
                    console.log(data)                    
                    setDeviceCategory();
                },
                fail: function () {
                    
                    alert('something went wrong');
                }
            });
            
        });
        
        function setDeviceCategory()
        {
            $.ajax({
                type: "get",
                dataType: 'json',
                url: fetchdevicedetailsURL,            
                cache: false,
                beforeSend: function() {                
                    $("#analytics_device_category").addClass('analytics_device_category');
                },
                success: function (data) {                                                         
                    devicechart(data)                
                    $("#analytics_device_category").removeClass('analytics_device_category');
                },
                fail: function () {
                    $("#analytics_device_category").removeClass('analytics_device_category');
                    alert('something went wrong');
                }
            });
        }        
        setDeviceCategory();
    });

    function devicechart(devicedetails) {
        console.log(devicedetails);    
    const gridSize2 = 152;
    const gridInnerSize2 = gridSize2 - 35;    
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
                        load() {                   
                        }
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
                colorByPoint: true,
                // name:'',                
                data:devicedetails                
            }]
        });
    
}
</script>
</html>