<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticsData extends Model
{
    use HasFactory;

    protected $table = "analytics_data";

    public static function getAnalyticsData($uid, $campaign_id)
    {

        $analytics_data = AnalyticsData::join('user_analytics', 'user_analytics.id', 'analytics_data.analytics_user_id')
            ->where('user_analytics.user_id', $uid)
            ->where('user_analytics.campaign_id', $campaign_id)
            ->select('analytics_data.id', 'analytics_data.number_of_users', 'analytics_data.number_of_newusers', 'analytics_data.revenue', 'analytics_data.session_duration', 'analytics_data.bounce_rate')
            ->get();
        
        if(count($analytics_data)!=0)
        {            
            $previous_day_record = $analytics_data[0];
            $latest_record = $analytics_data[1];        
            
            $view_data['users'] = $latest_record->number_of_users;
            $view_data['new_users'] = $latest_record->number_of_newusers;
            $view_data['revenue'] = $latest_record->revenue;
            $view_data['bounce_rate'] = $latest_record->bounce_rate;
            $view_data['session_duration'] = $latest_record->session_duration;

            $percentage = AnalyticsData::percentageCalculation($previous_day_record, $latest_record);

            $view_data['user_percentage'] = $percentage['user_percentage'];
            $view_data['new_user_percentage'] = $percentage['new_user_percentage'];
            $view_data['revenue_percentage'] = $percentage['revenue_percentage'];
            $view_data['bouncerate_percentage'] = $percentage['bouncerate_percentage'];
            $view_data['session_duration_percentage'] = $percentage['session_duration_percentage'];
            return $view_data;
        }
        
    }

    public static function percentageCalculation($previous_day_record, $latest_record)
    {

        try {
            
            $previous_users = (int)$previous_day_record->number_of_users;
            $latest_users = (int)$latest_record->number_of_users;

            $previous_new_users = (int)$previous_day_record->number_of_newusers;
            $latest_new_users = (int)$latest_record->number_of_newusers;

            $previous_revenue = (int)$previous_day_record->revenue;
            $latest_revenue = (int)$latest_record->revenue;

            
            $previous_bouncerate = (int)$previous_day_record->bounce_rate;
            $latest_bouncerate = (int)$latest_record->bounce_rate;

            $previous_session_duration = (int)$previous_day_record->session_duration;
            $latest_session_duration = (int)$latest_record->session_duration;

            /*User Percentage*/
            $percentage['user_percentage'] =($latest_users!=0)?(1 - $previous_users / $latest_users) * 100:0;            
            $percentage['new_user_percentage'] =($latest_new_users!=0)?(1 - $previous_new_users / $latest_new_users) * 100:0;
            $percentage['revenue_percentage'] =($latest_revenue!=0)?(1 - $previous_revenue / $latest_revenue) * 100:0;
            $percentage['bouncerate_percentage'] =($latest_bouncerate!=0)?(1 - $previous_bouncerate / $latest_bouncerate) * 100:0;
            $percentage['session_duration_percentage'] =($latest_session_duration!=0)?(1 - $previous_session_duration / $latest_session_duration) * 100:0;
            return $percentage;

        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }
}
