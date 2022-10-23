<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Session;

use Google_Client;
use Google_Service_Analytics;

use App\Models\Myuser;
use App\Models\UserAnalytics;
use App\Models\AnalyticsDeviceCategory;

/*Jobs*/
use App\Jobs\SaveAnalyticsData;

class MyController extends Controller
{
    public function __construct()
    {
        $this->websiteurl="https://cxthozotplabwj.legiit.com/";
        $this->uid=1;
        $this->campaign_id=2;  
    }

    public function view()
    {
        $user_analytics=UserAnalytics::where('user_id',$this->uid)
        ->where('campaign_id',$this->campaign_id)
        ->first();
        
        $test=2;
        return view('analytics',compact('user_analytics','test'));
    }

    public function getDeviceData()
    {       

        $analytics_data=AnalyticsDeviceCategory::join('user_analytics','user_analytics.id','analytics_device_category.analytics_user_id')
        ->where('user_analytics.user_id',$this->uid)
        ->where('user_analytics.campaign_id',$this->campaign_id)
        ->select('analytics_device_category.desktop','analytics_device_category.mobile','analytics_device_category.tablet')
        ->first();

        if(!empty($analytics_data))
        {
            $total_devices=$analytics_data['desktop']+$analytics_data['mobile']+$analytics_data['tablet'];

            $desktop_percentage=number_format(($analytics_data['desktop']/$total_devices)*100,2);
            $mobile_percentage=number_format(($analytics_data['mobile']/$total_devices)*100,2);
            $tablet_percentage=number_format(($analytics_data['tablet']/$total_devices)*100,2);

            $desktop_data['name']='Desktop';
            $desktop_data['y']=(double)$desktop_percentage;
            $desktop_data['color']="#2c5ccb";

            $mobile_data['name']='Mobile';
            $mobile_data['y']=(double)$mobile_percentage;
            $mobile_data['color']="#3979f1";

            $tablet_data['name']='Tablet';
            $tablet_data['y']=(double)$tablet_percentage;
            $tablet_data['color']="#7faaf7";

            $chart_data[]=$desktop_data;
            $chart_data[]=$mobile_data;
            $chart_data[]=$tablet_data;
            return $chart_data;
        }
        

        
    }

    public function redirectToGoogle()
    {        
        Session::forget('google_access_token');
        $parameters = ["access_type" => "offline", "prompt" => "consent select_account"];

        return Socialite::driver('google')
            ->scopes('https://www.googleapis.com/auth/analytics.readonly')
            ->with($parameters)
            ->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
          
            $alldata=[];

            if (!Session::has('google_access_token'))
            {
                $google_user = Socialite::driver('google')->user();
                
                $access_token = $google_user->token;

                Session::put('google_access_token', $access_token);
                Session::put('google_id', $google_user->id);
                Session::put('google_refresh_token', $google_user->refreshToken);
            }
           
            $access_token=Session::get('google_access_token');

            $client = new Google_Client();
            $client->setAuthConfig(base_path('google/client_secret.json'));
            $client->addScope('https://www.googleapis.com/auth/analytics.readonly');
            $client->setAccessToken($access_token);

            $analytics = new Google_Service_Analytics($client);

                if(isset($request->account_id))
                {                
                  //Fetching the Properties based on Account
                  $properties = $analytics->management_webproperties
                  ->listManagementWebproperties($request->account_id);
    
                  $propertyitems = $properties->getItems();
                  foreach($propertyitems as $item)
                  {                    
                    //Website Url is Matched with the Campaign Url
                    if($this->websiteurl==$item->websiteUrl)
                    {
                        $property_id = $item->id;
                        $property_name = $item->name;
                        
                        Session::put('account_id',$request->account_id);
                        Session::put('account_name',$request->account_name);
                        Session::put('property_id', $property_id);
                        Session::put('property_name',$property_name);
                        //save Account & Property Details
                        // $this->saveAccount($alldata);
    
                    // Get the list of views (profiles) for the authorized user.
                    $profiles = $analytics->management_profiles
                    ->listManagementProfiles($request->account_id, $property_id);
    
                    $profileitems = $profiles->getItems();
                    $view = view('profile_list',compact('profileitems'))->render();
    
                    return response()->json(['success'=>true,'message'=>'Profile View','view'=>$view]);
                      
                    }                
                  }
    
                  //If Property is not exists on the selected Account
                  return response()->json(['success'=>false,'message'=>'Property is not available same with your campaign']);              
                }
                
                /*If Profile data is received*/
                if(isset($request->profile_id))
                {  

                    $google_id=Session::get('google_id');
                    $refresh_token=Session::get('google_refresh_token');
                    $account_id=Session::get('account_id');
                    $account_name=Session::get('account_name');
                    $property_id=Session::get('property_id');
                    $property_name=Session::get('property_name');                    
                   

                    $alldata['access_token']=$access_token;
                    $alldata['google_id']=$google_id;
                    $alldata['refresh_token']=$refresh_token;
                    $alldata['user_id']=$this->uid;
                    $alldata['campaign_id']=$this->campaign_id;

                    $alldata['account_id']=$account_id;
                    $alldata['account_name']=$account_name;
                    $alldata['property_id']=$property_id;
                    $alldata['property_name']=$property_name;                  
                  
                    $alldata['profile_id']=$request->profile_id;
                    $alldata['profile_name']=$request->profile_name;
    
                    //save all data
                    $this->saveAccount($alldata);
    
                   /* Date Ranges Starts */
                   $from_date=date('Y-m-d', time()-7*24*60*60); 
                   $to_date=date('Y-m-d', time()-24*60*60);  //Last Day 
                   /* Date Ranges Ends */
    
                  //Here  Dispatch the Job for storing the analytical data for results              
                //   dd($this->uid);
                  $get_userdetails=UserAnalytics::select('id','profile_id','token')
                    ->where('user_id',$this->uid)
                    ->where('campaign_id',$this->campaign_id)
                    ->first();

                    $analytics_user_id=$get_userdetails['id'];
                    $profile_id=$get_userdetails['profile_id'];
                    $access_token=$get_userdetails['token'];
                    // dd($get_userdetails);
                  SaveAnalyticsData::dispatch(1,$analytics_user_id,$this->uid,$this->campaign_id,$profile_id,$access_token,$from_date,$to_date);
                  
               /*================================================================*/   
                return response()->json(['success'=>true,'message'=>'Connecting the analytics Account']);
                }
           
                $profiles = $this->getAccountList($analytics);            
                return view('account_list',compact('profiles'));
            
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }

    public function saveAccount($analytics_data)
    {
        $saveuser=new UserAnalytics;                
        $saveuser->user_id=$analytics_data['user_id'];
        $saveuser->token=$analytics_data['access_token'];
        $saveuser->refresh_token=$analytics_data['refresh_token'];
        // $saveuser->google_id=$analytics_data['google_id'];
        $saveuser->campaign_id=$analytics_data['campaign_id'];
        $saveuser->account_id=$analytics_data['account_id'];
        $saveuser->account_name=$analytics_data['account_name'];
        $saveuser->property_id=$analytics_data['property_id'];
        $saveuser->property_name=$analytics_data['property_name'];
        $saveuser->profile_id=$analytics_data['profile_id'];
        $saveuser->profile_name=$analytics_data['profile_name'];      
        $saveuser->save();
        
        Session::forget('user_id');
        Session::forget('access_token');
        Session::forget('refresh_token');
        Session::forget('google_id');
        Session::forget('campaign_id');
        Session::forget('account_id');
        Session::forget('account_name');
        Session::forget('property_id');
        Session::forget('property_name');
        Session::forget('profile_id');
        Session::forget('profile_name');

    }

    public function refreshData(Request $request)
    {   

        $from_date=date('Y-m-d', time()-7*24*60*60);
        $to_date=date('Y-m-d', time()-24*60*60);


        if(isset($user_refresh) && $user_refresh==1)
        {
            $userdetails=UserAnalytics::select('id','token','profile_id','campaign_id','user_id')
            ->where('user_id',$this->uid)
            ->where('campaign_id',$this->campaign_id)
            ->first();

            $access_token=$userdetails['token'];
            $user_analytics_id=$userdetails['id'];
            $profile_id=$userdetails['profile_id'];
            $campaign_id=$userdetails['campaign_id'];
            $user_id=$userdetails['user_id'];
            SaveAnalyticsData::dispatch('',$user_analytics_id,$user_id,$campaign_id,$profile_id,$access_token,$from_date,$to_date);

            return response()->json(['success'=>true,'message'=>'Analytics Data Refreshed']);  
        }
        else
        {
            $userdetails=UserAnalytics::select('id','token','profile_id','campaign_id','user_id')
            ->all(); 

            foreach ($userdetails as $key => $user) {

                $user_analytics_id=$user['id'];
                $access_token=$user['token'];                
                $profile_id=$user['profile_id'];
                $campaign_id=$user['campaign_id'];
                $user_id=$user['user_id'];
                
                SaveAnalyticsData::dispatch('',$user_analytics_id,$user_id,$campaign_id,$profile_id,$access_token,$from_date,$to_date);
            }
            
            return response()->json(['success'=>true,'message'=>'CronJob Analytics Data Refreshed for all users']);  
        }        
    }

    public function getAccountList($analytics)
    {
        //Fetch the Accounts
        $accounts = $analytics->management_accounts->listManagementAccounts();
        
        if (count($accounts->getItems()) > 0) {
            $items = $accounts->getItems();
            return $items;
          } else {            
            throw new Exception('No accounts found for this user.');
          }
    }

    function getFirstProfileId($analytics)
    {
        // Get the user's first view (profile) ID.

        // Get the list of accounts for the authorized user.
        $accounts = $analytics->management_accounts->listManagementAccounts();

        if (count($accounts->getItems()) > 0) {
            $items = $accounts->getItems();
            $firstAccountId = $items[0]->getId();

            // Get the list of properties for the authorized user.
            $properties = $analytics->management_webproperties
                ->listManagementWebproperties($firstAccountId);

            if (count($properties->getItems()) > 0) {
                $items = $properties->getItems();
                $firstPropertyId = $items[0]->getId();

                // Get the list of views (profiles) for the authorized user.
                $profiles = $analytics->management_profiles
                    ->listManagementProfiles($firstAccountId, $firstPropertyId);

                if (count($profiles->getItems()) > 0) {
                    $items = $profiles->getItems();

                    // Return the first view (profile) ID.
                    return $items[0]->getId();
                } else {
                    throw new Exception('No views (profiles) found for this user.');
                }
            } else {
                throw new Exception('No properties found for this user.');
            }
        } else {
            throw new Exception('No accounts found for this user.');
        }
    }

    function getResults($analytics, $profileId)
    {
        // for the last seven days.
        return $analytics->data_ga->get(
            'ga:' . $profileId,
            '7daysAgo',
            'today',
            'ga:users'
        );
    }

    function printResults($results)
    {
        // Parses the response from the Core Reporting API and prints
        // the profile name and total sessions.
        if (count($results->getRows()) > 0) {

            // Get the profile name.
            $profileName = $results->getProfileInfo()->getProfileName();

            // Get the entry for the first entry in the first row.
            $rows = $results->getRows();
            $sessions = $rows[0][0];

            // Print the results.
            print "<p>First view (profile) found: $profileName</p>";
            print "<p>Total sessions: $sessions</p>";
        } else {
            print "<p>No results found.</p>";
        }
    }
}
