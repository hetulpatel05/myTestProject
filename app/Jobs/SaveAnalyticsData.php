<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


use Google\Client as Google_Client;
use Google_Service_Analytics;

//Models
use App\Models\AnalyticsData;
use App\Models\UserAnalytics;
use App\Models\AnalyticsDeviceCategory;
use App\Models\UserAnalyticsTraffic;

class SaveAnalyticsData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $profile_id;    
    protected $access_token;
    protected $from_date;
    protected $to_date;
    protected $uid;
    protected $campaign_id;
    protected $user_analytics_id;
    protected $is_new;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($is_new='',$user_analytics_id,$uid,$campaign_id,$profile_id,$access_token,$from_date,$to_date)
    {
        $this->is_new = $is_new;
        $this->profile_id = $profile_id;
        $this->access_token = $access_token;
        $this->from_date = $from_date;
        $this->to_date = $to_date;
        $this->uid = $uid;
        $this->campaign_id = $campaign_id;
        $this->user_analytics_id = $user_analytics_id;
    }

    public function getResults($type,$analytics,$profileId,$from_date,$to_date)
    {
        /*For Getting the Device Category Data*/
        if($type=='device_data')
        {
            $options['dimensions']= 'ga:deviceCategory';
            $options['filters']= 'ga:deviceCategory==desktop,ga:deviceCategory==mobile,ga:deviceCategory==tablet';

            $device_categories=$analytics->data_ga->get(
                'ga:' . $profileId,           
                $from_date,            
                $to_date,
                'ga:users',
                $options
            );
            $analytics_data['device_category']=$device_categories;
        }        
        /*Device Category Ends*/

        /*Getting General Data Starts*/
        if($type=='general_data')
        {
            $metrics = 'ga:users,ga:newUsers,ga:transactionRevenue,ga:bounceRate,ga:sessionDuration';
            $general_data=$analytics->data_ga->get(
                'ga:' . $profileId,
                $from_date,
                $to_date,
                $metrics,          
            );
            $analytics_data['general_data']=$general_data;
        }
        /*Getting General Data Ends*/

         /*Getting User Traffic Data Starts */         
         if($type=='user_traffic')
         {
            $from_traffic_user_date=date('Y-m-d', time()-14*24*60*60); 
            $to_traffic_user_date=date('Y-m-d', time()-24*60*60);

            $dimensions = 'ga:date';  //For User Traffic

            $traffic_user=$analytics->data_ga->get(
                'ga:' . $profileId,           
                $from_traffic_user_date,            
                $to_traffic_user_date,
                'ga:users',            //Metrics            
                array('dimensions' => $dimensions)
            );

            $analytics_data['traffic_user']=$traffic_user;
         }
        /*Getting User Traffic Data Ends */

        return $analytics_data;
    }

    public function setAnalyticsData($results,$analytics_user_id)
    {
        $rows = $results['general_data']->getRows();
        $analyticsdata=[];      
        $analyticsdata['analytics_user_id'] = $analytics_user_id;
        $analyticsdata['users'] = $rows[0][0];
        $analyticsdata['new_users'] = $rows[0][1];
        $analyticsdata['revenue'] = $rows[0][2];
        $analyticsdata['bouncerate'] = $rows[0][3];
        $analyticsdata['session_duration'] = $rows[0][4];
        return $analyticsdata;
    }

    public function setDeviceData($results,$analytics_user_id)
    {
       
        $device_categories=$results['device_category']->getRows();

        $analytics_device_data=[];
        $analytics_device_data['analytics_user_id'] = $analytics_user_id;
        $analytics_device_data['desktop'] = isset($device_categories[0][1])?$device_categories[0][1]:0;
        $analytics_device_data['mobile'] = isset($device_categories[1][1])?$device_categories[1][1]:0;
        $analytics_device_data['tablet'] = isset($device_categories[2][1])?$device_categories[2][1]:0;
        
        return $analytics_device_data;
    }

    public function getCurrentWeek($date='')
    {
        if($date!=''){
            $set_date = $date;
        }else{
            $set_date = date('Y-m-d');
        }

        $date = new \DateTime($set_date);
        $week_number = $date->format("W");
        return $week_number;
    }

    public function saveAnalyticsData($analytics_data,$from_date,$to_date)
    {        
        $current_week=$this->getCurrentWeek($from_date);

        $savedata=new AnalyticsData;
        $savedata->analytics_user_id=$analytics_data['analytics_user_id'];
        $savedata->number_of_users=$analytics_data['users'];
        $savedata->number_of_newusers=$analytics_data['new_users'];
        $savedata->revenue=$analytics_data['revenue'];
        $savedata->bounce_rate=$analytics_data['bouncerate'];
        $savedata->session_duration=$analytics_data['session_duration'];
        $savedata->from_date=$from_date;
        $savedata->to_date=$to_date;
        $savedata->week_number=$current_week;
        $savedata->save();
    }

    public function saveAnalyticsDeviceData($devicedata,$from_date,$to_date)
    {
        $current_week=$this->getCurrentWeek($from_date);
        
        $savedata=new AnalyticsDeviceCategory;
        $savedata->analytics_user_id=$devicedata['analytics_user_id'];
        $savedata->desktop=$devicedata['desktop'];
        $savedata->mobile=$devicedata['mobile'];
        $savedata->tablet=$devicedata['tablet'];
        $savedata->from_date=$from_date;
        $savedata->to_date=$to_date;      
        $savedata->week_number=$current_week;
        $savedata->save();        
    }

    /*SEtup the General Analytics Data for refresh page*/
    public function setupGeneralAnalyticsData($analytics,$set_analytics_data,$user_analytics_id)
    {   
        $current_week=$this->getCurrentWeek($this->from_date);            
        $save_new=new AnalyticsData;
        $save_new->analytics_user_id=$set_analytics_data['analytics_user_id'];
        $save_new->number_of_users=$set_analytics_data['users'];
        $save_new->number_of_newusers=$set_analytics_data['new_users'];
        $save_new->revenue=$set_analytics_data['revenue'];
        $save_new->bounce_rate=$set_analytics_data['bouncerate'];
        $save_new->session_duration=$set_analytics_data['session_duration'];
        $save_new->from_date=$this->from_date;
        $save_new->to_date=$this->to_date;
        $save_new->week_number=$current_week;
        $save_new->save();

            //Here Find the Old records and Delete them
        $get_old_record=AnalyticsData::select('id')
            ->where('analytics_user_id',$set_analytics_data['analytics_user_id'])
            ->first();               
        $get_old_record->delete();

        if($get_old_record==true)
        {   
            $get_previous_record=AnalyticsData::select('id','from_date','to_date')
            ->where('analytics_user_id',$set_analytics_data['analytics_user_id'])
            ->first();             

            //Getting the previous 7 days records Starts          
                $from_old_date = date('Y-m-d', strtotime($this->from_date. ' - 7 days'));
                $to_old_date = date('Y-m-d', strtotime($this->to_date. ' - 7 days'));
                $old_weeek_number=$this->getCurrentWeek($from_old_date);                
            
            /*============================================================================*/
            $results = $this->getResults('general_data',$analytics, $this->profile_id,$from_old_date,$to_old_date);
            $set_analytics_previous_data=$this->setAnalyticsData($results,$this->user_analytics_id);                
            /*============================================================================*/

            AnalyticsData::where('id', $get_previous_record['id'])
            ->update(
                [
                'number_of_users' =>$set_analytics_previous_data['users'],
                'number_of_newusers' => $set_analytics_previous_data['new_users'],
                'revenue' =>$set_analytics_previous_data['revenue'],
                'bounce_rate' =>$set_analytics_previous_data['bouncerate'],
                'session_duration' => $set_analytics_previous_data['session_duration'],
                'from_date' =>$from_old_date,
                'to_date' =>$to_old_date,             
                'week_number' =>$old_weeek_number,             
                ]
            ); 
        }        
    }

    public function setupDeviceAnalyticsData($set_analytics_device_data,$user_analytics_id)
    { 
        $current_week=$this->getCurrentWeek($this->from_date);        
        $save_new=new AnalyticsDeviceCategory;
        $save_new->analytics_user_id=$set_analytics_device_data['analytics_user_id'];
        $save_new->desktop=$set_analytics_device_data['desktop'];
        $save_new->mobile=$set_analytics_device_data['mobile'];            
        $save_new->tablet=$set_analytics_device_data['tablet'];            
        $save_new->from_date=$this->from_date;
        $save_new->to_date=$this->to_date;
        $save_new->week_number=$current_week;
        $save_new->save();

        //Here Find the Old records and Delete them
        $get_old_record=AnalyticsDeviceCategory::select('id')
        ->where('analytics_user_id',$set_analytics_device_data['analytics_user_id'])
        ->first();
        $get_old_record->delete();
            
    }

    public function setandSaveUserTrafficData($results,$analytics_user_id,$from_date)    
    {
        $users_traffic=$results['traffic_user']->getRows();
        $current_week=$this->getCurrentWeek($from_date);

        $from_traffic_user_date=date('Y-m-d', time()-14*24*60*60); 
        $to_traffic_user_date=date('Y-m-d', time()-24*60*60);

        foreach ($users_traffic as $key => $traffic) {            
            
            $set_date = strtotime($traffic[0]);
            $traffic_date=date('Y-m-d',$set_date);
            $traffic_user=$traffic[1];

            $savedata=new UserAnalyticsTraffic;
            $savedata->analytics_user_id=$analytics_user_id;
            $savedata->traffic_date=$traffic_date;
            $savedata->users=$traffic_user;            
            $savedata->from_date=$from_traffic_user_date;
            $savedata->to_date=$to_traffic_user_date;      
            $savedata->week_number=$current_week;
            $savedata->save();
        }
    }

    public function setupSaveUserTrafficData($results,$analytics_user_id,$from_date)
    {
        $users_traffic=$results['traffic_user']->getRows();
        $current_week=$this->getCurrentWeek($from_date);

        $from_traffic_user_date=date('Y-m-d', time()-14*24*60*60); 
        $to_traffic_user_date=date('Y-m-d', time()-24*60*60);

        UserAnalyticsTraffic::where('analytics_user_id',$analytics_user_id)->delete();

        foreach ($users_traffic as $key => $traffic) {   
            
            $set_date = strtotime($traffic[0]);
            $traffic_date=date('Y-m-d',$set_date);
            $traffic_user=$traffic[1];

            $savedata=new UserAnalyticsTraffic;
            $savedata->analytics_user_id=$analytics_user_id;
            $savedata->traffic_date=$traffic_date;
            $savedata->users=$traffic_user;            
            $savedata->from_date=$from_traffic_user_date;
            $savedata->to_date=$to_traffic_user_date;      
            $savedata->week_number=$current_week;
            $savedata->save();
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        /*Authorization with Google Client*/
        $client = new Google_Client();
        $client->setAuthConfig(base_path('google/client_secret.json'));
        $client->setRedirectUri(url('/testcallback'));
        $client->addScope('https://www.googleapis.com/auth/analytics.readonly');
        $client->setAccessType("offline");
        $client->setAccessToken($this->access_token);

        $analytics = new Google_Service_Analytics($client);        

        /*New Accounts Starts*/
        if($this->is_new!='')
        { 
            /*First Setup the Previous 7 days records Starts*/
            $from_old_date = date('Y-m-d', strtotime($this->from_date. ' - 7 days'));
            $to_old_date = date('Y-m-d', strtotime($this->to_date. ' - 7 days'));

            $results=$this->getResults('general_data',$analytics,$this->profile_id,$from_old_date,$to_old_date);            
            $set_previous_analytics_data=$this->setAnalyticsData($results,$this->user_analytics_id);
            $this->saveAnalyticsData($set_previous_analytics_data,$from_old_date,$to_old_date);
            /*First Setup the Previous & days records Ends*/

            /*Setup the Latest 7 days Records Starts*/
            $results=$this->getResults('general_data',$analytics,$this->profile_id,$this->from_date,$this->to_date);
            $set_analytics_data=$this->setAnalyticsData($results,$this->user_analytics_id);
            $this->saveAnalyticsData($set_analytics_data,$this->from_date,$this->to_date);
            /*Setup the Latest 7 days Records Ends*/
          
            /*Storing the Device Data Starts*/
            $results=$this->getResults('device_data',$analytics,$this->profile_id,$this->from_date,$this->to_date);
            $set_analytics_device_data=$this->setDeviceData($results,$this->user_analytics_id);          
            $this->saveAnalyticsDeviceData($set_analytics_device_data,$this->from_date,$this->to_date);
            /*Storing the Device Data Ends*/

            /*User Traffic Data Starts*/
            $results=$this->getResults('user_traffic',$analytics,$this->profile_id,$this->from_date,$this->to_date);
            $this->setandSaveUserTrafficData($results,$this->user_analytics_id,$this->from_date);
            /*User Traffic Data ENds*/           
        }
        /*New Accounts Ends*/

        $from_traffic_user_date=date('Y-m-d', time()-14*24*60*60); 
        $to_traffic_user_date=date('Y-m-d', time()-24*60*60);
        
        $check_user_traffic=UserAnalyticsTraffic::select('id')
        ->where('analytics_user_id',$this->user_analytics_id)
        ->where('from_date',$from_traffic_user_date)
        ->where('to_date',$to_traffic_user_date)
        ->count();
        
        if(empty($check_user_traffic))
        {
            $results=$this->getResults('user_traffic',$analytics,$this->profile_id,$this->from_date,$this->to_date);
            $this->setupSaveUserTrafficData($results,$this->user_analytics_id,$this->from_date);
        }       
        
        $check_analytics_record=AnalyticsData::where('from_date',$this->from_date)
        ->where('to_date',$this->to_date)
        ->where('analytics_user_id',$this->user_analytics_id)
        ->first();

        if(empty($check_analytics_record))
        {
            $results=$this->getResults('general_data',$analytics,$this->profile_id,$this->from_date,$this->to_date);
            $set_analytics_data=$this->setAnalyticsData($results,$this->user_analytics_id);

            $this->setupGeneralAnalyticsData($analytics,$set_analytics_data,$this->user_analytics_id);
        }


        $check_device_record=AnalyticsDeviceCategory::where('from_date',$this->from_date)
                    ->where('to_date',$this->to_date)
                    ->where('analytics_user_id',$this->user_analytics_id)
                    ->first();
        
        if(empty($check_device_record))
        {
            $results=$this->getResults('device_data',$analytics,$this->profile_id,$this->from_date,$this->to_date);
            $set_analytics_device_data=$this->setDeviceData($results,$this->user_analytics_id);
            $this->setupDeviceAnalyticsData($set_analytics_device_data,$this->user_analytics_id);
        }
    }
}