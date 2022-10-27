<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAnalyticsTraffic extends Model
{
    use HasFactory;

    protected $table = "user_analytics_traffics";

    public static function getUserTrafficData($uid, $campaign_id)
    {
        /*Check the Date Range*/
        $userdata = UserAnalyticsTraffic::join('user_analytics', 'user_analytics.id', 'user_analytics_traffics.analytics_user_id')
            ->where('user_analytics.user_id', $uid)
            ->where('user_analytics.campaign_id', $campaign_id)
            ->select('user_analytics_traffics.to_date', 'user_analytics_traffics.from_date')
            ->first();

        if (!empty($userdata)) {
            $from_old_date = $userdata->from_date;
            $to_old_date = date('Y-m-d', strtotime($from_old_date . ' + 6 days'));

            $from_new_date = date('Y-m-d', strtotime($to_old_date . ' + 1 days'));
            $to_new_date = $userdata->to_date;

            /*Last 7 Days Traffic*/
            $latest_traffic = UserAnalyticsTraffic::join('user_analytics', 'user_analytics.id', 'user_analytics_traffics.analytics_user_id')
                ->where('user_analytics.user_id', $uid)
                ->where('user_analytics.campaign_id', $campaign_id)
                ->whereBetween('user_analytics_traffics.traffic_date', [$from_new_date, $to_new_date])
                ->select('user_analytics_traffics.users', 'user_analytics_traffics.traffic_date')
                ->get();

            foreach ($latest_traffic as $traffic) {
                $latest_traffic_users[] = $traffic->users;
                $latest_date[] = $traffic->traffic_date;
            }

            /*Previous 7 Days Traffic*/
            $previous_traffic_users = UserAnalyticsTraffic::join('user_analytics', 'user_analytics.id', 'user_analytics_traffics.analytics_user_id')
                ->where('user_analytics.user_id', $uid)
                ->where('user_analytics.campaign_id', $campaign_id)
                ->whereBetween('user_analytics_traffics.traffic_date', [$from_old_date, $to_old_date])
                ->pluck('users');

            $response_data['latest_traffic'] = $latest_traffic_users;
            $response_data['previous_traffic'] = $previous_traffic_users;
            $response_data['latest_dates'] = $latest_date;

            return $response_data;
        }
    }
}
