<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Session;

use Google_Client;
use Google_Service_Analytics;
use Illuminate\Support\Facades\DB;

use App\Models\AnalyticsData;
use App\Models\UserAnalytics;
use App\Models\AnalyticsDeviceCategory;
use App\Models\UserAnalyticsTraffic;

use Google\Analytics\Admin\V1alpha\AnalyticsAdminServiceClient;

/*Jobs*/
use App\Jobs\SaveAnalyticsData;

class MyController extends Controller
{
    public function __construct()
    {
        $this->websiteurl="https://cxthozotplabwj.legiit.com/";
        // $this->websiteurl="https://nikyfagajy.legiit.com";
        
        $this->uid=1;
        $this->campaign_id=2;  
    }

    public function getCurrencySymbol($locale, $currency)
    {
        // Create a NumberFormatter
        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);

        // Prevent any extra spaces, etc. in formatted currency
        $formatter->setPattern('¤');

        // Prevent significant digits (e.g. cents) in formatted currency
        $formatter->setAttribute(\NumberFormatter::MAX_SIGNIFICANT_DIGITS, 0);

        // Get the formatted price for '0'
        $formattedPrice = $formatter->formatCurrency(0, $currency);

        // Strip out the zero digit to get the currency symbol
        $zero = $formatter->getSymbol(\NumberFormatter::ZERO_DIGIT_SYMBOL);
        $currencySymbol = str_replace($zero, '', $formattedPrice);

        return $currencySymbol;
    }

    public function NewCurrency($locale,$currency)
    {       
        $fmt = new \NumberFormatter( $locale."@currency=$currency", \NumberFormatter::CURRENCY );        
        $symbol = $fmt->getSymbol(\NumberFormatter::CURRENCY_SYMBOL );
        header("Content-Type: text/html; charset=UTF-8;");
        return $symbol;
    }

    function get_currency_symbol($string)
    {
        $symbol = '';
        $length = mb_strlen($string, 'utf-8');
        for ($i = 0; $i < $length; $i++)
        {
            $char = mb_substr($string, $i, 1, 'utf-8');
            if (!ctype_digit($char) && !ctype_punct($char))
                $symbol .= $char;
        }
        return $symbol;
    }

    public function view()
    {
        // $symbol=$this->getCurrencySymbol('en-US','AUD');
        $symbol=$this->NewCurrency('en','AUD');        
        // $string = $format->formatCurrency(123456789, 'CNY');       

        dd($symbol);        
        
        $length = mb_strlen($symbol, 'utf-8');
        for ($i = 0; $i < $length; $i++)
        {
            $char = mb_substr($symbol, $i, 1, 'utf-8');
            dd(ctype_alpha($char));
            if (!ctype_digit($char) && !ctype_punct($char))
            {
                $final=$char;
            }                
        }
        // dd('ok0');
        dd($final);


        $user_analytics=UserAnalytics::where('user_id',$this->uid)
        ->where('campaign_id',$this->campaign_id)
        ->first();       
        
        return view('analytics',compact('user_analytics'));
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

                  $get_userdetails=UserAnalytics::select('id','profile_id','token')
                    ->where('user_id',$this->uid)
                    ->where('campaign_id',$this->campaign_id)
                    ->first();

                    $analytics_user_id=$get_userdetails['id'];
                    $profile_id=$get_userdetails['profile_id'];
                    $access_token=$get_userdetails['token'];
                    
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

   

    public function fetchAnalyticsData()
    {        
        /*Getting the General data with percentage*/
        $general_details=AnalyticsData::getAnalyticsData($this->uid,$this->campaign_id);
        $data['general_details']=$general_details;

        /*Get Device Details*/
        $device_details=AnalyticsDeviceCategory::getDeviceData($this->uid,$this->campaign_id);        
        $data['devicedata']=$device_details;

        
        /*User Traffic Details*/
        $traffic_details=UserAnalyticsTraffic::getUserTrafficData($this->uid,$this->campaign_id);
        $data['traffic_details']=$traffic_details;

        return response()->json(['success'=>true,'message'=>'Google Analytics Data','data'=>$data]);
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


        if(isset($request->user_refresh) && $request->user_refresh==1)
        {
            $userdetails=UserAnalytics::select('id','token','refresh_token','profile_id','campaign_id','user_id')
            ->where('user_id',$this->uid)
            ->where('campaign_id',$this->campaign_id)
            ->first();
            
            $access_token=$userdetails['token'];
            $refresh_token=$userdetails['refresh_token'];
            $user_analytics_id=$userdetails['id'];
            $profile_id=$userdetails['profile_id'];
            $campaign_id=$userdetails['campaign_id'];
            $user_id=$userdetails['user_id'];

            $client = new Google_Client();
            $client->setAccessType('offline');            
            $client->setClientId(env('GOOGLE_CLIENT_ID'));
            $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));   
            $client->addScope('https://www.googleapis.com/auth/analytics.readonly');            
            $client->fetchAccessTokenWithRefreshToken($refresh_token);

            $mytoken=$client->getAccessToken();
            $new_token=$mytoken['access_token'];
            // $new_token

            SaveAnalyticsData::dispatch('',$user_analytics_id,$user_id,$campaign_id,$profile_id,$new_token,$from_date,$to_date);

            return response()->json(['success'=>true,'message'=>'Analytics Data Refreshed']);  
        }
        else
        {
            $userdetails=UserAnalytics::select('id','token','refresh_token','profile_id','campaign_id','user_id')
            ->get(); 
            $myuser=$userdetails[0];
            
            // dd($myuser->refresh_token);

            $access_token=$myuser->token;
            $refresh_token=$myuser->refresh_token; 
            // $access_new_token="ya29.a0Aa4xrXMu47PNcQl7yMjtaSvQfdY1tnh3LRgQZZPBUIWoSJvf6m1Z0deloRf35_E7f60Ab1_1Vv5KKZlokgZRjB7VvRqiUu2rE8QrkVXQiLlEsLkcFDbAvaakuHsRPHuKNbqBDtp6R0guhtRvljYvOyhig";

            $client = new Google_Client();
            $client->setAccessType('offline');            
            $client->setClientId(env('GOOGLE_CLIENT_ID'));
            $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));   
            $client->addScope('https://www.googleapis.com/auth/analytics.readonly');            
            $client->fetchAccessTokenWithRefreshToken($refresh_token);

            dd($client);
            // dd($client->isAccessTokenExpired());

            if($client->isAccessTokenExpired())
            {
                
                $client->fetchAccessTokenWithRefreshToken($refresh_token);
                dd($client);
            }


            // foreach ($userdetails as $key => $user) {
            //     $user_analytics_id=$user['id'];
            //     $access_token=$user['token'];                
            //     $profile_id=$user['profile_id'];
            //     $campaign_id=$user['campaign_id'];
            //     $user_id=$user['user_id'];                
            //     SaveAnalyticsData::dispatch('',$user_analytics_id,$user_id,$campaign_id,$profile_id,$access_token,$from_date,$to_date);
            // }            
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
}
