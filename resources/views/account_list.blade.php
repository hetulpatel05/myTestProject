<!DOCTYPE html>
<html>
<head>
    <title>Account List</title>
    <link href="{{ url('assets/bootstrap.min.css') }}" rel="stylesheet">
</head>
<style>
    .total_list
    {
        pointer-events: none;
        opacity: 0.5;
    }
    #my_loader{
        display: none !important;
    }
</style>

<body>
    @if(isset($error))
        <p>User does not have any Google Analytics account.</p>
    @else
        <h2>List of accounts</h2>
       
        <div id="mylist">           
            <table class="table" id="tbl_list">
                <tbody>
                    @forelse($profiles as $profile)
                        <tr data-name="{{ $profile->name }}" data-id="{{ $profile->id }}" style="cursor:pointer"
                            class="analytics_account">
                            <td>
                                <b>{{ $profile->name }}</b><p>{{ $profile->id }}</p>                                
                            </td>
                        </tr>
                    @empty
                        <p>No Properties found for this user.</p>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</body>

<script src="{{ url('assets/jquery.min.js') }}"></script>

<script>
    var saveAccountURL = "{{ route('save.analytics.account') }}";
    $(document).ready(function () {

        $(".analytics_account").on('click', function () {
            var account_name = $(this).attr("data-name");
            var account_id = $(this).attr("data-id");

            $.ajax({
                type: "POST",
                async: false,
                dataType: 'json',
                data: {
                    "account_name": account_name,
                    "account_id": account_id,
                    "_token": "{{ csrf_token() }}"
                },
                url: saveAccountURL,
                cache: false,
                beforeSend: function() {
                    console.log('launcg');
                  $("#tbl_list").addClass('total_list');                  
                },
                success: function (data) {
                    $("#tbl_list").removeClass('total_list');                    
                    if (data.success == true) {
                        // console.log(data)                        
                        $("#mylist").html(data.view);
                    } else {
                        alert(data.message);
                    }
                },
                fail: function () {
                    $("#tbl_list").removeClass('total_list');
                    alert_error('Something went wrong.');
                }
            });
        });

        /*Final View Click*/
        $(document).on('click', '.profile_account', function () {            
            var profile_id = $(this).attr("data-profileid");
            var profile_name = $(this).attr("data-profilename");
            $.ajax({
                type: "POST",
                async: false,
                dataType: 'json',
                data: {                    
                    "profile_id": profile_id,
                    "profile_name": profile_name,
                    "_token": "{{ csrf_token() }}"
                },
                url: saveAccountURL,
                cache: false,
                beforeSend: function() {
                  $("#tbl_list").addClass('total_list');
                  
                },
                success: function (data) {
                    $("#tbl_list").removeClass('total_list');
                    if (data.success == true) {                        
                        // location.reload();
                        window.location.href = '/dashboard';
                    } else {
                        alert_error(data.message);
                    }
                },
                fail: function () {
                    $("#tbl_list").removeClass('total_list');
                    alert_error('Something went wrong.');
                }
            });
        });
    });
</script>

</html>